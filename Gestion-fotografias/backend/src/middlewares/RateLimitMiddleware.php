<?php
// QUÉ: Middleware de limitación de tasa (rate limiting) aplicado SOLO a los endpoints sensibles.
// POR QUÉ: login, registro, verificación de correo y reenvío de código no tenían protección
//          contra fuerza bruta ni contra bombardeo de correos. Se evalúa ANTES del despacho
//          del Router (ver public/index.php), de modo que un 429 no toca la base de datos ni
//          los controladores.
// ALCANCE: Los límites se miden por IP (REMOTE_ADDR, igual que SistemaController — sin confiar
//          en X-Forwarded-For) y, para las reglas marcadas 'porEmail', por la combinación
//          IP + email del body JSON. Las rutas no listadas aquí NO tienen límite: el rate
//          limiting GLOBAL de todas las rutas está diseñado en
//          Gestion-fotografias/docs/implementaciones-futuras.md (sin implementar aún).

declare(strict_types=1);

namespace App\middlewares;

use App\Core\Response;
use App\helpers\RateLimiter;

class RateLimitMiddleware
{
    /**
     * Reglas por clave "MÉTODO /ruta" (coincidencia exacta con las rutas de routes.php).
     *
     * Cada regla es:
     *  - porEmail : true  → la clave del contador combina IP + email del body (protege una
     *                       cuenta concreta aunque el atacante cambie de IP).
     *               false → la clave es solo la IP (contiene abuso general al servicio).
     *  - limite   : intentos máximos por ventana.
     *  - ventana  : duración de la ventana fija, en segundos.
     *
     * Ajuste de cupos (razonamiento):
     *  - login 5 / 5 min por IP+email: tras 5 fallos un usuario legítimo espera ≤ 5 min;
     *    con un código de 6 dígitos equivaldría a siglos de fuerza bruta. El techo por IP
     *    (20 / 5 min) evita que un solo origen agote cupos de muchas cuentas a la vez.
     *  - verificar-email 6 / 10 min por IP+email: el código tiene 6 dígitos (10^6 combos)
     *    y expira en 1 h; a 6 intentos por ventana la fuerza bruta es inviable.
     *  - reenviar-codigo 3 / 15 min por IP+email y 10 / h por IP: evita bombardeo de correo
     *    (cada reenvío dispara un SMTP real hacia mailpit/proveedores).
     *  - register 10 / h por IP: registro masivo de cuentas de un mismo origen.
     */
    private const REGLAS = [
        'POST /auth/login' => [
            ['porEmail' => true, 'limite' => 5, 'ventana' => 60],
            ['porEmail' => false, 'limite' => 20, 'ventana' => 300],
        ],
        'POST /auth/register' => [
            ['porEmail' => false, 'limite' => 10, 'ventana' => 3600],
        ],
        'POST /auth/verificar-email' => [
            ['porEmail' => true, 'limite' => 6, 'ventana' => 600],
        ],
        'POST /auth/reenviar-codigo' => [
            ['porEmail' => true, 'limite' => 3, 'ventana' => 900],
            ['porEmail' => false, 'limite' => 10, 'ventana' => 3600],
        ],
    ];

    /**
     * Evalúa las reglas de la ruta actual. Si la petición excede el cupo responde con
     * HTTP 429 (JSON del proyecto + cabeceras Retry-After y X-RateLimit-*) y termina.
     * Si la ruta no está en REGLAS, retorna sin hacer nada (no aplica límite).
     */
    public static function handle(string $method, string $path): void
    {
        // Desactivable en caliente (p. ej. pruebas E2E o incidente operativo) con RATE_LIMIT_ENABLED=0.
        $env = getenv('RATE_LIMIT_ENABLED');
        if (is_string($env) && trim($env) === '0') {
            return;
        }

        $claveRuta = strtoupper($method) . ' ' . rtrim($path, '/');
        $reglas = self::REGLAS[$claveRuta] ?? null;
        if ($reglas === null) {
            return; // Ruta sin límite configurado.
        }

        $ip = self::ipCliente();
        $email = null; // Se lee una sola vez y solo si alguna regla lo necesita.
        $informativa = null; // Resultado de la regla más restrictiva (menor cupo) para las cabeceras.

        foreach ($reglas as $regla) {
            if ($regla['porEmail']) {
                $email ??= self::emailDelBody();
                if ($email === null) {
                    // Sin email en el body no se puede formar la clave de cuenta; se salta
                    // la regla por cuenta y deja que la regla por IP (si existe) actúe.
                    continue;
                }
            }

            $identificador = $regla['porEmail'] ? $ip . '|' . $email : $ip;
            $resultado = RateLimiter::registrar(
                $claveRuta . '|' . $identificador,
                (int) $regla['limite'],
                (int) $regla['ventana']
            );

            if (!$resultado['permitido']) {
                header('X-RateLimit-Limit: ' . $resultado['limite']);
                header('X-RateLimit-Remaining: ' . $resultado['restantes']);
                header('X-RateLimit-Reset: ' . (time() + $resultado['reintentarEn']));
                header('Retry-After: ' . $resultado['reintentarEn']);
                Response::error(
                    'Demasiadas solicitudes desde tu dispositivo. Intenta de nuevo en '
                        . $resultado['reintentarEn'] . ' segundos.',
                    429
                );
            }

            // Cabeceras informativas siempre: se conservan las de la regla con el cupo
            // más ajustado, que es la que el cliente debe respetar.
            if ($informativa === null || $resultado['limite'] < $informativa['limite']) {
                $informativa = $resultado;
            }
        }

        if ($informativa !== null) {
            header('X-RateLimit-Limit: ' . $informativa['limite']);
            header('X-RateLimit-Remaining: ' . $informativa['restantes']);
            header('X-RateLimit-Reset: ' . (time() + $informativa['ventana']));
        }
    }

    /**
     * IP real de la conexión TCP (REMOTE_ADDR), igual que SistemaController::restringirALocal():
     * solo se confía en el peer visto por Apache y NO en X-Forwarded-For (cabecera falsificable).
     * En Docker el host llega por el NAT del bridge con una IP privada, suficiente como identidad.
     */
    private static function ipCliente(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? 'desconocida');
    }

    /**
     * Lee el email del body JSON (solo para las reglas 'porEmail'). php://input es re-readable
     * en PHP 8 para requests con contenido application/json, de modo que el controlador posterior
     * (vía App\Core\Request) sigue pudiendo parsear el body sin problema. Cualquier fallo de
     * parseo se ignora: la regla por cuenta se omite y actúa la de IP.
     */
    private static function emailDelBody(): ?string
    {
        $raw = file_get_contents('php://input');
        $datos = json_decode(is_string($raw) ? $raw : '', true);

        if (!is_array($datos) || !isset($datos['email']) || !is_string($datos['email'])) {
            return null;
        }

        $email = strtolower(trim($datos['email']));
        return $email === '' ? null : $email;
    }
}
