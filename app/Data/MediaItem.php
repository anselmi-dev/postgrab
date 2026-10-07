<?php

namespace App\Data;

final class MediaItem
{
    /**
     * @param  list<MediaVariant>  $variants
     */
    public function __construct(
        public string $id,
        public int $index,
        public string $type,
        public ?string $thumbnailUrl,
        public array $variants,
    ) {}

    public function bestVariant(): ?MediaVariant
    {
        if ($this->variants === []) {
            return null;
        }

        $ranked = $this->variants;
        usort($ranked, function (MediaVariant $a, MediaVariant $b): int {
            return ($b->bitrate ?? (($b->width ?? 0) * ($b->height ?? 0)))
                <=> ($a->bitrate ?? (($a->width ?? 0) * ($a->height ?? 0)));
        });

        return $ranked[0];
    }

    public function variant(string $key): ?MediaVariant
    {
        foreach ($this->variants as $variant) {
            if ($variant->key === $key) {
                return $variant;
            }
        }

        return $this->bestVariant();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'index' => $this->index,
            'type' => $this->type,
            'thumbnail_url' => $this->thumbnailUrl,
            'variants' => array_map(fn (MediaVariant $variant) => $variant->toArray(), $this->variants),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) $data['id'],
            index: (int) $data['index'],
            type: (string) $data['type'],
            thumbnailUrl: $data['thumbnail_url'] ?? null,
            variants: array_map(
                fn (array $variant) => MediaVariant::fromArray($variant),
                $data['variants'] ?? [],
            ),
        );
    }
}
