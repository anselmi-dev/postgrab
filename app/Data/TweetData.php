<?php

namespace App\Data;

use Carbon\CarbonImmutable;

final class TweetData
{
    /**
     * @param  list<MediaItem>  $media
     */
    public function __construct(
        public string $id,
        public string $url,
        public string $authorName,
        public string $authorHandle,
        public ?string $avatarUrl,
        public ?CarbonImmutable $createdAt,
        public string $text,
        public array $media,
        public string $driver,
    ) {}

    public function mediaById(string $id): ?MediaItem
    {
        foreach ($this->media as $item) {
            if ($item->id === $id) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'author_name' => $this->authorName,
            'author_handle' => $this->authorHandle,
            'avatar_url' => $this->avatarUrl,
            'created_at' => $this->createdAt?->toIso8601String(),
            'text' => $this->text,
            'media' => array_map(fn (MediaItem $item) => $item->toArray(), $this->media),
            'driver' => $this->driver,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) $data['id'],
            url: (string) $data['url'],
            authorName: (string) $data['author_name'],
            authorHandle: (string) $data['author_handle'],
            avatarUrl: $data['avatar_url'] ?? null,
            createdAt: filled($data['created_at'] ?? null) ? CarbonImmutable::parse($data['created_at']) : null,
            text: (string) $data['text'],
            media: array_map(fn (array $item) => MediaItem::fromArray($item), $data['media'] ?? []),
            driver: (string) ($data['driver'] ?? 'cache'),
        );
    }
}
