<?php

declare(strict_types=1);

require_once __DIR__ . '/Tile.php';

class Meld implements JsonSerializable
{
    public const CHOW = 'chow';
    public const PUNG = 'pung';
    public const KONG = 'kong';

    /**
     * @param Tile[] $tiles
     */
    public function __construct(
        public readonly string $type,
        public readonly array $tiles,
        public readonly bool $concealed = false,
        public readonly ?int $fromPlayer = null,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type,
            'tiles' => array_map(fn(Tile $t) => $t->jsonSerialize(), $this->tiles),
            'concealed' => $this->concealed,
            'fromPlayer' => $this->fromPlayer,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['type'],
            array_map(fn($t) => Tile::fromArray($t), $data['tiles']),
            (bool)($data['concealed'] ?? false),
            $data['fromPlayer'] ?? null,
        );
    }
}
