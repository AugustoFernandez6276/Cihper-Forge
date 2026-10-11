<?php
// QUÉ: Entidad de dominio 'qr_tokens' (tokens de colaboración y de acceso).
// POR QUÉ: Tipa el token QR y, cuando el query lo hace por JOIN, los datos de
//          su colección vinculada (título, visibilidad, fotógrafo) que antes
//          viajaban en un array asociativo. Los campos de colección son opcionales.

declare(strict_types=1);

namespace App\models;

readonly class TokenQr
{
    public function __construct(
        public int $idToken,
        public string $token,
        public int $coleccionId,
        public string $tipo,
        public ?string $expiracion = null,
        // Derivados (JOIN con colecciones) — opcionales según el query:
        public ?string $coleccionTitulo = null,
        public ?string $tipoVisibilidad = null,
        public ?int $fotografoId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            idToken: (int) $data['id_token'],
            token: (string) $data['token'],
            coleccionId: (int) $data['coleccion_id'],
            tipo: (string) $data['tipo'],
            expiracion: isset($data['expiracion']) && $data['expiracion'] !== null ? (string) $data['expiracion'] : null,
            coleccionTitulo: isset($data['coleccion_titulo']) && $data['coleccion_titulo'] !== null ? (string) $data['coleccion_titulo'] : null,
            tipoVisibilidad: isset($data['tipo_visibilidad']) && $data['tipo_visibilidad'] !== null ? (string) $data['tipo_visibilidad'] : null,
            fotografoId: isset($data['fotografo_id']) && $data['fotografo_id'] !== null ? (int) $data['fotografo_id'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id_token'         => $this->idToken,
            'token'            => $this->token,
            'coleccion_id'     => $this->coleccionId,
            'tipo'             => $this->tipo,
            'expiracion'       => $this->expiracion,
            'coleccion_titulo' => $this->coleccionTitulo,
            'tipo_visibilidad' => $this->tipoVisibilidad,
            'fotografo_id'     => $this->fotografoId,
        ];
    }
}
