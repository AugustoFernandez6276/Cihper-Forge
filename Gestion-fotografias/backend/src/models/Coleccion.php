<?php
// QUÉ: Entidad de dominio 'colecciones'.
// POR QUÉ: Tipa la colección y sus campos derivados (fotógrafo, portada y
//          totales calculados por subconsultas) que antes viajaban en un array
//          asociativo. Los campos calculados son opcionales: solo las queries
//          de listado los seleccionan.

declare(strict_types=1);

namespace App\models;

readonly class Coleccion
{
    public function __construct(
        public int $id,
        public int $fotografoId,
        public ?string $titulo,
        public string $tipoVisibilidad,
        public ?string $descripcion,
        public string $creadoEn,
        // Derivados (JOIN / subconsultas) — opcionales según el query:
        public ?string $fotografoNombre = null,
        public ?string $portadaPreview = null,
        public ?int $portadaIdMultimedia = null,
        public ?string $portadaTipo = null,
        public ?int $totalArchivos = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            fotografoId: (int) $data['fotografo_id'],
            titulo: isset($data['titulo']) && $data['titulo'] !== null ? (string) $data['titulo'] : null,
            tipoVisibilidad: (string) $data['tipo_visibilidad'],
            descripcion: isset($data['descripcion']) && $data['descripcion'] !== null ? (string) $data['descripcion'] : null,
            creadoEn: (string) $data['creado_en'],
            fotografoNombre: isset($data['fotografo_nombre']) && $data['fotografo_nombre'] !== null ? (string) $data['fotografo_nombre'] : null,
            portadaPreview: isset($data['portada_preview']) && $data['portada_preview'] !== null ? (string) $data['portada_preview'] : null,
            portadaIdMultimedia: isset($data['portada_id_multimedia']) && $data['portada_id_multimedia'] !== null ? (int) $data['portada_id_multimedia'] : null,
            portadaTipo: isset($data['portada_tipo']) && $data['portada_tipo'] !== null ? (string) $data['portada_tipo'] : null,
            totalArchivos: isset($data['total_archivos']) && $data['total_archivos'] !== null ? (int) $data['total_archivos'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id'                   => $this->id,
            'fotografo_id'         => $this->fotografoId,
            'titulo'               => $this->titulo,
            'tipo_visibilidad'     => $this->tipoVisibilidad,
            'descripcion'          => $this->descripcion,
            'creado_en'            => $this->creadoEn,
            'fotografo_nombre'     => $this->fotografoNombre,
            'portada_preview'      => $this->portadaPreview,
            'portada_id_multimedia' => $this->portadaIdMultimedia,
            'portada_tipo'         => $this->portadaTipo,
            'total_archivos'       => $this->totalArchivos,
        ];
    }
}
