<?php
// QUÉ: Limitador de tasa de peticiones (rate limiter) de ventana fija, sin dependencias externas.
// POR QUÉ: Los endpoints de autenticación (login, registro, verificación y reenvío de código)
//          son el vector natural de fuerza bruta y de abuso de correo. El proyecto no usa
//          Composer ni APCu, así que el contador se persiste en un archivo por clave
//          protegido con flock(): la escritura es atómica frente a peticiones concurrentes
//          de Apache/mod_php y el estado sobrevive a reinicios del proceso PHP. Los archivos
//          viven en sys_get_temp_dir() (no se versionan ni contaminan uploads/) y se purgan
//          solos tras 24 h sin actividad.
// NOTA: Existe un "fail open" deliberado: si el almacenamiento no está disponible, se permite
//       el intento en lugar de tumbar el endpoint (la disponibilidad pesa más que el rechazo
//       puntual) y el fallo queda registrado en el log de errores de PHP.

declare(strict_types=1);

namespace App\helpers;

final class RateLimiter
{
    /** @var string|null Directorio de contadores (se crea bajo demanda). */
    private static ?string $dir = null;

    /** Marca (mtime) de la última purga automática de contadores obsoletos. */
    private const MARCA_LIMPIEZA = '.ultima_limpieza';

    /** Un contador sin peticiones durante más de esta cantidad de segundos se borra. */
    private const TTL_CONTADOR = 86400; // 24 h

    /** Cada esta cantidad de segundos se intenta purgar el directorio (perezosamente). */
    private const INTERVALO_LIMPIEZA = 21600; // 6 h

    /**
     * Registra un intento para la clave indicada y determina si cabe en el cupo.
     *
     * @param string $clave          Identificador único de la regla (p. ej. "login|ip|1.2.3.4|email@x").
     * @param int    $limite         Intentos máximos permitidos por ventana.
     * @param int    $ventanaSegundos Duración de la ventana fija, en segundos.
     *
     * @return array{
     *     permitido: bool,
     *     restantes: int,
     *     reintentarEn: int,
     *     limite: int,
     *     ventana: int
     * } 'restantes' es el cupo que queda en la ventana actual y 'reintentarEn' los segundos
     *   hasta que la ventana se reinicia (0 cuando el intento está permitido).
     */
    public static function registrar(string $clave, int $limite, int $ventanaSegundos): array
    {
        $resultado = [
            'permitido'    => true,
            'restantes'    => $limite,
            'reintentarEn' => 0,
            'limite'       => $limite,
            'ventana'      => $ventanaSegundos,
        ];

        $archivo = self::archivo($clave);
        if ($archivo === null) {
            return $resultado; // Fail open: sin almacenamiento no se bloquea.
        }

        $mano = @fopen($archivo, 'c+');
        if ($mano === false) {
            return $resultado; // Fail open: no se pudo abrir/crear el contador.
        }

        try {
            if (!flock($mano, LOCK_EX)) {
                return $resultado; // Fail open: sin lock no hay garantía atómica.
            }

            $ahora = time();

            // Leer el estado actual de la ventana (si existe y es válido).
            $crudo = stream_get_contents($mano);
            $datos = json_decode(is_string($crudo) ? $crudo : '', true);

            $inicioVentana = $ahora;
            $conteo = 0;

            if (
                is_array($datos)
                && isset($datos['inicio'], $datos['conteo'])
                && is_numeric($datos['inicio'])
                && is_numeric($datos['conteo'])
            ) {
                $inicioVentana = (int) $datos['inicio'];
                $conteo = (int) $datos['conteo'];
            }

            // Ventana fija vencida → el contador arranca de cero.
            if ($ahora - $inicioVentana >= $ventanaSegundos) {
                $inicioVentana = $ahora;
                $conteo = 0;
            }

            if ($conteo < $limite) {
                // Dentro del cupo: se consume un intento.
                $conteo++;
                rewind($mano);
                ftruncate($mano, 0);
                fwrite($mano, json_encode(['inicio' => $inicioVentana, 'conteo' => $conteo]));
                fflush($mano);

                $resultado['restantes'] = max(0, $limite - $conteo);
            } else {
                // Cupo agotado: NO se incrementa el contador, para que un ataque sostenido
                // no extienda la ventana indefinidamente (el bloqueo dura exactamente lo que
                // resta de ventana y luego se libera solo).
                $resultado['permitido'] = false;
                $resultado['restantes'] = 0;
                $resultado['reintentarEn'] = max(1, ($inicioVentana + $ventanaSegundos) - $ahora);
            }

            flock($mano, LOCK_UN);
        } finally {
            fclose($mano);
        }

        return $resultado;
    }

    /**
     * Elimina todos los contadores. Uso exclusivo de pruebas automatizadas / mantenimiento,
     * para dejar el entorno limpo después de ejercitar los límites.
     */
    public static function reiniciarTodo(): void
    {
        $dir = self::dir();
        if ($dir === null) {
            return;
        }

        foreach (glob($dir . '/*.json') ?: [] as $archivo) {
            @unlink($archivo);
        }
        @unlink($dir . '/' . self::MARCA_LIMPIEZA);
    }

    /** Ruta del archivo de contador para la clave, o null si no hay almacenamiento. */
    private static function archivo(string $clave): ?string
    {
        $dir = self::dir();
        if ($dir === null) {
            return null;
        }

        // sha1 mantiene el nombre de archivo libre de caracteres problemáticos y de
        // longitud ilimitada; el prefijo 'rl_' evita colisiones con otros usos de /tmp.
        return $dir . '/rl_' . sha1($clave) . '.json';
    }

    /** Directorio de contadores, creado perezosamente (null si no es posible). */
    private static function dir(): ?string
    {
        if (self::$dir !== null) {
            return self::$dir;
        }

        $dir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'cipher_forge_rate_limit';

        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            error_log('[RateLimiter] No se pudo crear el directorio de contadores: ' . $dir);
            return null;
        }

        self::$dir = $dir;
        self::limpiarObsoletos($dir);

        return self::$dir;
    }

    /**
     * Purga perezosa de contadores huérfanos: como máximo una vez cada INTERVALO_LIMPIEZA
     * se borran los contadores con más de TTL_CONTADOR segundos sin tocarse. Así el
     * directorio no crece sin límite aunque nadie llame a reiniciarTodo().
     */
    private static function limpiarObsoletos(string $dir): void
    {
        $marca = $dir . '/' . self::MARCA_LIMPIEZA;
        $ahora = time();

        if (is_file($marca) && ($ahora - (int) @filemtime($marca)) < self::INTERVALO_LIMPIEZA) {
            return;
        }

        @touch($marca);

        $borde = $ahora - self::TTL_CONTADOR;
        foreach (glob($dir . '/*.json') ?: [] as $archivo) {
            $mtime = @filemtime($archivo);
            if ($mtime !== false && $mtime < $borde) {
                @unlink($archivo);
            }
        }
    }
}
