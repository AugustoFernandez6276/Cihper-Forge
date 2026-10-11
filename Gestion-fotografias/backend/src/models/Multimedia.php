<?php
// QUÉ: Entidad de dominio 'multimedia'.
// POR QUÉ: Tipa el archivo multimedia y sus campos derivados de la colección
//          (visibilidad y fotógrafo, obtenidos por JOIN en findById) que antes
//          viajaban en un array asociativo. Los campos no seleccionados por un
//          query concreto (ej. ruta_original en la galería) quedan como null.

declare(strict_types=1);

namespace App\models;

readonly class Multimedia
{
    public function __construct(
        public int $idMultimedia,
        public int $coleccionId,
        public ?string $titulo,
        public ?string $descripcion,
        public ?string $rutaOriginal,
        public string $vistaPrevia,
        public ?string $poster,
        public int $tamanio,
        public string $tipo,
        public bool $esInvitado,
        public bool $aprobado,
        public string $creadoEn,
        // Derivados (JOIN con colecciones) — opcionales según el query:
        public ?string $tipoVisibilidad = null,
        public ?int $fotografoId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            idMultimedia: (int) $data['id_multimedia'],
            coleccionId: (int) $data['coleccion_id'],
            titulo: isset($data['titulo']) && $data['titulo'] !== null ? (string) $data['titulo'] : null,
            descripcion: isset($data['descripcion']) && $data['descripcion'] !== null ? (string) $data['descripcion'] : null,
            rutaOriginal: isset($data['ruta_original']) && $data['ruta_original'] !== null ? (string) $data['ruta_original'] : null,
            vistaPrevia: (string) $data['vista_previa'],
            poster: isset($data['poster']) && $data['poster'] !== null ? (string) $data['poster'] : null,
            tamanio: (int) ($data['tamanio'] ?? 0),
            tipo: (string) ($data['tipo'] ?? ''),
            esInvitado: (bool) ($data['es_invitado'] ?? false),
            aprobado: (bool) ($data['aprobado'] ?? true),
            creadoEn: (string) ($data['creado_en'] ?? ''),
            tipoVisibilidad: isset($data['tipo_visibilidad']) && $data['tipo_visibilidad'] !== null ? (string) $data['tipo_visibilidad'] : null,
            fotografoId: isset($data['fotografo_id']) && $data['fotografo_id'] !== null ? (int) $data['fotografo_id'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id_multimedia'    => $this->idMultimedia,
            'coleccion_id'     => $this->coleccionId,
            'titulo'           => $this->titulo,
            'descripcion'      => $this->descripcion,
            'ruta_original'    => $this->rutaOriginal,
            'vista_previa'     => $this->vistaPrevia,
            'poster'           => $this->poster,
            'tamanio'          => $this->tamanio,
            'tipo'             => $this->tipo,
            'es_invitado'      => $this->esInvitado,
            'aprobado'         => $this->aprobado,
            'creado_en'        => $this->creadoEn,
            'tipo_visibilidad' => $this->tipoVisibilidad,
            'fotografo_id'     => $this->fotografoId,
        ];
    }
}
