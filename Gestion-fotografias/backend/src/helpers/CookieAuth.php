<?php
// QUÉ: Helper para transportar el JWT en una cookie de sesión (JWT + Cookies).
// POR QUÉ: Una cookie HttpOnly es inaccesible a JavaScript, por lo que un XSS
//          no puede robar el token (a diferencia de localStorage). SameSite
//          evita que el navegador la envíe en peticiones entre sitios (CSRF).

declare(strict_types=1);

namespace App\helpers;

use App\Core\Config;

class CookieAuth
{
    // Nombre por defecto de la cookie que almacena el JWT.
    public const NOMBRE_POR_DEFECTO = 'cipher_forge_token';

    /**
     * Nombre de la cookie (configurable vía COOKIE_NAME).
     */
    public static function nombre(): string
    {
        $env = getenv('COOKIE_NAME');
        return is_string($env) && trim($env) !== '' ? trim($env) : self::NOMBRE_POR_DEFECTO;
    }

    /**
     * ¿Marcar la cookie como Secure (solo HTTPS)?
     * POR QUÉ: en desarrollo (HTTP/localhost) debe ser false; en producción
     *          (HTTPS) se activa con COOKIE_SECURE=true.
     */
    public static function segura(): bool
    {
        $env = getenv('COOKIE_SECURE');
        if ($env === false || trim($env) === '') {
            return false;
        }

        return filter_var(trim($env), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Política SameSite de la cookie (Strict | Lax | None).
     * POR QUÉ: Strict es la más protectora contra CSRF. None exige Secure,
     *          por lo que sin HTTPS se degrada a Lax para que el navegador
     *          no rechace la cookie.
     */
    public static function mismoSitio(): string
    {
        $env = getenv('COOKIE_SAMESITE');
        $valor = is_string($env) && trim($env) !== '' ? trim($env) : 'Strict';

        if (!in_array($valor, ['Strict', 'Lax', 'None'], true)) {
            return 'Strict';
        }

        if ($valor === 'None' && !self::segura()) {
            return 'Lax';
        }

        return $valor;
    }

    /**
     * Crea la cookie con el JWT.
     * La cookie vive exactamente lo que dura el token (tokenHoras()) y es
     * HttpOnly: PHP la envía automáticamente en cada petición al mismo sitio,
     * pero JavaScript jamás puede leerla (document.cookie no la expone).
     */
    public static function crear(string $token): void
    {
        setcookie(
            self::nombre(),
            $token,
            [
                'expires'  => time() + (Config::tokenHoras() * 3600),
                'path'     => '/',
                'domain'   => '',
                'secure'   => self::segura(),
                'httponly' => true,
                'samesite' => self::mismoSitio(),
            ]
        );
    }

    /**
     * Lee el JWT almacenado en la cookie (vacío si no existe).
     */
    public static function leer(): string
    {
        $valor = $_COOKIE[self::nombre()] ?? '';
        return is_string($valor) && trim($valor) !== '' ? trim($valor) : '';
    }

    /**
     * Elimina la cookie (cierre de sesión).
     * POR QUÉ: se vuelve a enviar con el mismo path/dominio y fecha de
     *          expiración pasada para que el navegador la descarte.
     */
    public static function borrar(): void
    {
        setcookie(
            self::nombre(),
            '',
            [
                'expires'  => time() - 3600,
                'path'     => '/',
                'domain'   => '',
                'secure'   => self::segura(),
                'httponly' => true,
                'samesite' => self::mismoSitio(),
            ]
        );

        unset($_COOKIE[self::nombre()]);
    }
}
