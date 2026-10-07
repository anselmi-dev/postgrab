<?php

namespace App\Data;

final class MediaVariant
{
    public function __construct(
        public string $key,
        public string $url,
        public string $label,
        public ?int $width,
        public ?int $height,
        public ?int $bitrate,
        public ?int $sizeBytes,
        public string $contentType,
        public string $extension,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'url' => $this->url,
            'label' => $this->label,
            'width' => $this->width,
            'height' => $this->height,
            'bitrate' => $this->bitrate,
            'size_bytes' => $this->sizeBytes,
            'content_type' => $this->contentType,
            'extension' => $this->extension,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            key: (string) $data['key'],
            url: (string) $data['url'],
            label: (string) $data['label'],
            width: isset($data['width']) ? (int) $data['width'] : null,
            height: isset($data['height']) ? (int) $data['height'] : null,
            bitrate: isset($data['bitrate']) ? (int) $data['bitrate'] : null,
            sizeBytes: isset($data['size_bytes']) ? (int) $data['size_bytes'] : null,
            contentType: (string) ($data['content_type'] ?? 'application/octet-stream'),
            extension: (string) ($data['extension'] ?? 'bin'),
        );
    }
}
