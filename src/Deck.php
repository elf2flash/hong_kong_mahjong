<?php

declare(strict_types=1);

require_once __DIR__ . '/Tile.php';

class Deck
{
    /** @var Tile[] */
    private array $tiles = [];

    public function __construct()
    {
        $this->reset();
    }

    public function reset(): void
    {
        $this->tiles = [];
        $id = 0;

        foreach (['man', 'pin', 'sou'] as $suit) {
            for ($v = 1; $v <= 9; $v++) {
                for ($c = 0; $c < 4; $c++) {
                    $this->tiles[] = new Tile($suit, $v, $id++);
                }
            }
        }

        for ($v = 1; $v <= 4; $v++) {
            for ($c = 0; $c < 4; $c++) {
                $this->tiles[] = new Tile('wind', $v, $id++);
            }
        }

        for ($v = 1; $v <= 3; $v++) {
            for ($c = 0; $c < 4; $c++) {
                $this->tiles[] = new Tile('dragon', $v, $id++);
            }
        }
    }

    public function shuffle(): void
    {
        shuffle($this->tiles);
    }

    public function draw(): ?Tile
    {
        return array_shift($this->tiles) ?: null;
    }

    public function remaining(): int
    {
        return count($this->tiles);
    }

    /** @return Tile[] */
    public function drawMany(int $n): array
    {
        $out = [];
        for ($i = 0; $i < $n; $i++) {
            $t = $this->draw();
            if ($t === null) {
                break;
            }
            $out[] = $t;
        }
        return $out;
    }

    public function toArray(): array
    {
        return array_map(fn(Tile $t) => $t->jsonSerialize(), $this->tiles);
    }

    public static function fromArray(array $data): self
    {
        $deck = new self();
        $deck->tiles = array_map(fn($d) => Tile::fromArray($d), $data);
        return $deck;
    }
}
