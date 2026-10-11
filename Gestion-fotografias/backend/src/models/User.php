<?php
// QUÉ: Entidad de dominio 'usuarios' (tabla padre de 'clientes' y 'fotografos').
// POR QUÉ: Tipa los datos del usuario en lugar de devolver arrays asociativos
//          crudos desde el repositorio. El hash de la contraseña se mantiene
//          interno (para password_verify) y se EXCLUYE de toArray() para no
//          filtrarlo jamás por la API.

declare(strict_types=1);

namespace App\models;

readonly class User
{
    public function __construct(
        public int $id,
        public string $nombreCompleto,
        public string $email,
        public ?string $telefono,
        public bool $emailVerificado,
        public string $passwordHash,
        public string $rol,
    ) {}

    /** Construye la entidad desde una fila de PDO (FETCH_ASSOC). */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            nombreCompleto: (string) $data['nombre_completo'],
            email: (string) $data['email'],
            telefono: isset($data['telefono']) && $data['telefono'] !== null ? (string) $data['telefono'] : null,
            emailVerificado: (bool) $data['email_verificado'],
            passwordHash: (string) $data['password_hash'],
            rol: (string) $data['rol'],
        );
    }

    /** Representación para respuestas JSON (sin password_hash). */
    public function toArray(): array
    {
        return [
            'id'               => $this->id,
            'nombre_completo'  => $this->nombreCompleto,
            'email'            => $this->email,
            'telefono'         => $this->telefono,
            'email_verificado' => $this->emailVerificado,
            'rol'              => $this->rol,
        ];
    }
}
