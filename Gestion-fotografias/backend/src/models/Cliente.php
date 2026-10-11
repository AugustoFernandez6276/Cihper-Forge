<?php
// QUÉ: Entidad de dominio del cliente (tabla 'clientes' + perfil de 'usuarios').
// POR QUÉ: Completa la jerarquía de roles tipados. Hoy no existe un query de
//          lectura específico del cliente (se resuelve desde 'usuarios'), por lo
//          que la entidad queda disponible para cuando se añada ese acceso.

declare(strict_types=1);

namespace App\models;

readonly class Cliente
{
    public function __construct(
        public int $id,
        public string $nombreCompleto,
        public string $email,
        public ?string $telefono = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            nombreCompleto: (string) $data['nombre_completo'],
            email: (string) $data['email'],
            telefono: isset($data['telefono']) && $data['telefono'] !== null ? (string) $data['telefono'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id'              => $this->id,
            'nombre_completo' => $this->nombreCompleto,
            'email'           => $this->email,
            'telefono'        => $this->telefono,
        ];
    }
}
