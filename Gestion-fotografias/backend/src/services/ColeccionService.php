<?php
// QUÉ: Lógica de negocio para la gestión de colecciones.
// POR QUÉ: Centraliza las reglas de negocio (ej. verificación estricta del rol 'fotografo') antes de persistir datos.

declare(strict_types=1);

namespace App\services;

use App\middlewares\AuthMiddleware;
use App\Core\Database;
use App\Core\Response;
use App\dtos\CreateColeccionDto;
use App\helpers\MediaProcessor;
use App\models\Coleccion;
use App\repository\ColeccionRepository;
use App\repository\MultimediaRepository;
use App\repository\UserRepository;

class ColeccionService
{
    private UserRepository       $userRepository;
    private ColeccionRepository  $coleccionRepository;
    private MultimediaRepository $multimediaRepository;

    public function __construct()
    {
        $database = new Database();
        $pdo      = $database->getConnection();

        $this->userRepository       = new UserRepository($pdo);
        $this->coleccionRepository  = new ColeccionRepository($pdo);
        $this->multimediaRepository = new MultimediaRepository($pdo);
    }

    /**
     * Valida permisos y registra una nueva colección, sincronizando hashtags si es pública (HU2 / HU26).
     */
    public function create(CreateColeccionDto $dto, array $hashtags = []): array
    {
        // 1. Verificar existencia del usuario fotógrafo
        $usuario = $this->userRepository->findById($dto->fotografoId);

        if ($usuario === null) {
            Response::error('El fotógrafo especificado no existe.', 404);
        }

        // 2. CF-05: el usuario autenticado debe ser el fotógrafo dueño de la nueva colección.
        $autenticado = AuthMiddleware::user();
        if ($autenticado === null || (int) $autenticado['id'] !== $usuario->id) {
            Response::error('Solo puedes crear colecciones a tu propio nombre.', 403);
        }

        // 3. Verificar que el usuario tenga rol 'fotografo' (RF4 / HU2)
        if ($usuario->rol !== 'fotografo') {
            Response::error('Solo los usuarios con rol fotógrafo pueden crear colecciones.', 403);
        }

        // H-07: Verificar que el fotógrafo haya aceptado políticas y Ley 18.331 (RF26 / HU31)
        if (!$this->userRepository->politicasAceptadas($usuario->id)) {
            Response::error('Debes aceptar los Términos, Condiciones y Ley 18.331 antes de crear colecciones.', 403);
        }

        // 4. Crear la colección en la base de datos
        $coleccionId = $this->coleccionRepository->create($dto);

        // 5. Si es pública y se enviaron hashtags, guardarlos (HU26)
        if ($dto->tipoVisibilidad === 'publica' && !empty($hashtags)) {
            $this->coleccionRepository->sincronizarHashtags($coleccionId, $hashtags);
        }

        // 6. Obtener los datos completos de la colección creada
        $coleccion = $this->coleccionRepository->findById($coleccionId);

        if ($coleccion === null) {
            Response::error('La colección creada no está disponible.', 500);
        }

        return array_merge(
            $coleccion->toArray(),
            ['hashtags' => $this->coleccionRepository->obtenerHashtags($coleccionId)]
        );
    }

    /**
     * Lista las colecciones del fotógrafo autenticado (H09 / CC-33).
     * Habilita que el panel "Mis colecciones" se cargue desde el servidor,
     * de modo que las colecciones persistan entre navegadores/dispositivos.
     */
    public function listarMias(): array
    {
        $autenticado = AuthMiddleware::user();
        if ($autenticado === null) {
            Response::error('Debes iniciar sesión para ver tus colecciones.', 401);
        }

        $colecciones = $this->coleccionRepository->listarMiasConPortada((int) $autenticado['id']);

        return array_map(
            function (Coleccion $col): array {
                return array_merge(
                    $col->toArray(),
                    ['hashtags' => $this->coleccionRepository->obtenerHashtags($col->id)]
                );
            },
            $colecciones
        );
    }

    /**
     * Lista colecciones públicas con sus hashtags asociados (HU24 / HU27).
     */
    public function listarPublicas(?string $hashtag = null): array
    {
        $colecciones = $this->coleccionRepository->listarPublicas($hashtag);

        return array_map(
            function (Coleccion $col): array {
                return array_merge(
                    $col->toArray(),
                    ['hashtags' => $this->coleccionRepository->obtenerHashtags($col->id)]
                );
            },
            $colecciones
        );
    }

    /**
     * Obtiene el detalle de una colección verificando visibilidad y control de acceso (HU20 / HU24).
     */
    public function obtenerDetalle(int $id): array
    {
        $coleccion = $this->coleccionRepository->findById($id);

        if ($coleccion === null) {
            Response::error('La colección no existe.', 404);
        }

        // Si es privada, validar que el usuario tenga acceso (HU20 / RF6)
        if ($coleccion->tipoVisibilidad === 'privada') {
            $usuario = AuthMiddleware::user();

            if ($usuario === null) {
                Response::error('Debes iniciar sesión para acceder a esta colección privada.', 401);
            }

            $esDueno = $coleccion->fotografoId === (int) $usuario['id'];
            $tieneAcceso = $this->multimediaRepository->tieneAcceso((int) $usuario['id'], $id);

            if (!$esDueno && !$tieneAcceso) {
                Response::error('No tienes permisos para acceder a esta colección privada.', 403);
            }
        }

        return array_merge(
            $coleccion->toArray(),
            ['hashtags' => $this->coleccionRepository->obtenerHashtags($id)]
        );
    }

    /**
     * Valida un token de invitación privada y devuelve estado (HU3 / RF5).
     */
    public function validarInvitacion(string $token): array
    {
        $infoToken = $this->coleccionRepository->buscarToken($token);

        if ($infoToken === null || $infoToken->tipo !== 'acceso') {
            Response::error('El enlace de invitación es inválido o no existe.', 404);
        }

        $usuario = AuthMiddleware::user();
        $estaAutenticado = $usuario !== null;
        $tieneAcceso = $estaAutenticado
            ? ($infoToken->fotografoId === (int) $usuario['id'] ||
               $this->multimediaRepository->tieneAcceso((int) $usuario['id'], $infoToken->coleccionId))
            : false;

        return [
            'token'            => $token,
            'coleccion_id'     => $infoToken->coleccionId,
            'coleccion_titulo' => $infoToken->coleccionTitulo,
            'tipo_visibilidad' => $infoToken->tipoVisibilidad,
            'autenticado'      => $estaAutenticado,
            'tiene_acceso'     => $tieneAcceso,
            'requiere_auth'    => !$estaAutenticado,
        ];
    }

    /**
     * Canjea el token de invitación para el usuario autenticado otorgando acceso permanente (HU3 / RF5).
     */
    public function canjearInvitacion(string $token): array
    {
        $infoToken = $this->coleccionRepository->buscarToken($token);

        if ($infoToken === null || $infoToken->tipo !== 'acceso') {
            Response::error('El enlace de invitación es inválido o no existe.', 404);
        }

        $usuario = AuthMiddleware::user();
        if ($usuario === null) {
            Response::error('Debes iniciar sesión para desbloquear el acceso a esta colección.', 401);
        }

        $coleccionId = $infoToken->coleccionId;
        $usuarioId   = (int) $usuario['id'];

        $this->coleccionRepository->otorgarAcceso($usuarioId, $coleccionId);

        $coleccion = $this->coleccionRepository->findById($coleccionId);

        if ($coleccion === null) {
            Response::error('La colección no existe.', 404);
        }

        return [
            'coleccion' => array_merge(
                $coleccion->toArray(),
                ['hashtags' => $this->coleccionRepository->obtenerHashtags($coleccionId)]
            ),
            'acceso_concedido' => true,
        ];
    }

    /**
     * Lista hashtags populares para filtrado y autocompletado (HU27).
     */
    public function listarHashtags(): array
    {
        return $this->coleccionRepository->listarHashtags();
    }

    /**
     * Actualiza los hashtags de una colección existente (HU26).
     * Verifica que el usuario autenticado sea el fotógrafo dueño.
     */
    public function actualizarHashtags(int $id, array $hashtags = []): array
    {
        $coleccion = $this->coleccionRepository->findById($id);

        if ($coleccion === null) {
            Response::error('La colección no existe.', 404);
        }

        $usuario = AuthMiddleware::user();
        if ($usuario === null || $coleccion->fotografoId !== (int) $usuario['id']) {
            Response::error('Solo el fotógrafo dueño puede actualizar los hashtags.', 403);
        }

        $this->coleccionRepository->sincronizarHashtags($id, $hashtags);

        return array_merge(
            $coleccion->toArray(),
            ['hashtags' => $this->coleccionRepository->obtenerHashtags($id)]
        );
    }

    /**
     * Actualiza metadatos, visibilidad y hashtags de una colección existente (HU26).
     * Verifica que el usuario autenticado sea el fotógrafo dueño.
     */
    public function actualizar(int $id, array $campos, ?array $hashtags = null): array
    {
        $coleccion = $this->coleccionRepository->findById($id);

        if ($coleccion === null) {
            Response::error('La colección no existe.', 404);
        }

        $usuario = AuthMiddleware::user();
        if ($usuario === null || $coleccion->fotografoId !== (int) $usuario['id']) {
            Response::error('Solo el fotógrafo dueño puede modificar la colección.', 403);
        }

        $titulo = isset($campos['titulo']) && trim((string) $campos['titulo']) !== ''
            ? trim((string) $campos['titulo'])
            : ($coleccion->titulo ?? '');

        $descripcion = array_key_exists('descripcion', $campos)
            ? trim((string) $campos['descripcion'])
            : $coleccion->descripcion;

        $tipoVisibilidad = isset($campos['tipo_visibilidad']) && in_array($campos['tipo_visibilidad'], ['privada', 'publica'], true)
            ? (string) $campos['tipo_visibilidad']
            : $coleccion->tipoVisibilidad;

        // H-08: Si la colección pasa de pública a privada, eliminar de favoritos
        // para mantener la coherencia de que los favoritos solo apuntan a colecciones públicas (RF21).
        if ($coleccion->tipoVisibilidad === 'publica' && $tipoVisibilidad === 'privada') {
            $this->coleccionRepository->eliminarFavoritosPorColeccion($id);
        }

        $this->coleccionRepository->actualizar($id, $titulo, $descripcion, $tipoVisibilidad);

        if ($hashtags !== null) {
            $this->coleccionRepository->sincronizarHashtags($id, $hashtags);
        }

        $coleccionActualizada = $this->coleccionRepository->findById($id);

        if ($coleccionActualizada === null) {
            Response::error('La colección no existe.', 404);
        }

        return array_merge(
            $coleccionActualizada->toArray(),
            ['hashtags' => $this->coleccionRepository->obtenerHashtags($id)]
        );
    }

    /**
     * Elimina una colección y todos sus recursos asociados (multimedia, archivos, favoritos,
     * accesos, tokens QR y hashtags). Verifica que el usuario sea el fotógrafo dueño.
     */
    public function eliminar(int $id): void
    {
        $coleccion = $this->coleccionRepository->findById($id);

        if ($coleccion === null) {
            Response::error('La colección no existe.', 404);
        }

        $usuario = AuthMiddleware::user();
        if ($usuario === null || $coleccion->fotografoId !== (int) $usuario['id']) {
            Response::error('Solo el fotógrafo dueño puede eliminar la colección.', 403);
        }

        foreach ($this->coleccionRepository->obtenerRutasMultimediaDeColeccion($id) as $fila) {
            foreach (['ruta_original', 'vista_previa', 'poster'] as $clave) {
                if (!empty($fila[$clave])) {
                    $rutaAbsoluta = MediaProcessor::aRutaAbsoluta((string) $fila[$clave]);
                    if (file_exists($rutaAbsoluta)) {
                        @unlink($rutaAbsoluta);
                    }
                }
            }
            if (!empty($fila['id_multimedia'])) {
                MediaProcessor::eliminarStandard((int) $fila['id_multimedia']);
            }
        }

        $this->coleccionRepository->eliminarConDependencias($id);
    }
}
