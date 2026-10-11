<?php
// QUÉ: Entidad de dominio del fotógrafo (tabla 'fotografos' + perfil de 'usuarios').
// POR QUÉ: Agrupa en un objeto tipado los datos públicos del fotógrafo que
//          antes viajaban como array asociativo. 'politicasAceptadas' es opcional
//          porque el query de perfil público no lo selecciona.

declare(strict_types=1);

namespace App\models;

readonly class Fotografo
{
    public function __construct(
        public int $id,
        public string $nombreCompleto,
        public string $email,
        public ?string $telefono,
        public ?bool $politicasAceptadas = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            nombreCompleto: (string) $data['nombre_completo'],
            email: (string) $data['email'],
            telefono: isset($data['telefono']) && $data['telefono'] !== null ? (string) $data['telefono'] : null,
            politicasAceptadas: isset($data['politicas_aceptadas']) ? (bool) $data['politicas_aceptadas'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id'                  => $this->id,
            'nombre_completo'     => $this->nombreCompleto,
            'email'               => $this->email,
            'telefono'            => $this->telefono,
            'politicas_aceptadas' => $this->politicasAceptadas,
        ];
    }
}
