<?php

declare(strict_types=1);

require_once __DIR__ . '/Tile.php';
require_once __DIR__ . '/Meld.php';

class Player implements JsonSerializable
{
    /** @var Tile[] */
    public array $hand = [];

    /** @var Meld[] */
    public array $melds = [];

    /** @var Tile[] */
    public array $discards = [];

    public int $score = 0;
    public bool $isHuman;
    public bool $isDealer = false;
    public int $seatWind; // 1=E, 2=S, 3=W, 4=N

    public function __construct(
        public readonly int $id,
        public readonly string $name,
        bool $isHuman = false,
        int $seatWind = 1,
    ) {
        $this->isHuman = $isHuman;
        $this->seatWind = $seatWind;
    }

    public function addTile(Tile $tile): void
    {
        $this->hand[] = $tile;
        $this->sortHand();
    }

    /** @param Tile[] $tiles */
    public function addTiles(array $tiles): void
    {
        foreach ($tiles as $t) {
            $this->hand[] = $t;
        }
        $this->sortHand();
    }

    public function removeTileById(int $id): ?Tile
    {
        foreach ($this->hand as $i => $t) {
            if ($t->id === $id) {
                $tile = $t;
                array_splice($this->hand, $i, 1);
                return $tile;
            }
        }
        return null;
    }

    /** Remove n tiles matching key */
    public function removeByKey(string $key, int $n = 1): array
    {
        $removed = [];
        for ($i = count($this->hand) - 1; $i >= 0 && count($removed) < $n; $i--) {
            if ($this->hand[$i]->key() === $key) {
                $removed[] = $this->hand[$i];
                array_splice($this->hand, $i, 1);
            }
        }
        return array_reverse($removed);
    }

    public function sortHand(): void
    {
        usort($this->hand, function (Tile $a, Tile $b) {
            $order = ['man' => 0, 'pin' => 1, 'sou' => 2, 'wind' => 3, 'dragon' => 4];
            $oa = $order[$a->suit] ?? 9;
            $ob = $order[$b->suit] ?? 9;
            if ($oa !== $ob) {
                return $oa <=> $ob;
            }
            return $a->value <=> $b->value;
        });
    }

    public function handCount(): int
    {
        return count($this->hand);
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'isHuman' => $this->isHuman,
            'isDealer' => $this->isDealer,
            'seatWind' => $this->seatWind,
            'score' => $this->score,
            'hand' => array_map(fn(Tile $t) => $t->jsonSerialize(), $this->hand),
            'handCount' => count($this->hand),
            'melds' => array_map(fn(Meld $m) => $m->jsonSerialize(), $this->melds),
            'discards' => array_map(fn(Tile $t) => $t->jsonSerialize(), $this->discards),
        ];
    }

    public function toPublicArray(bool $showHand): array
    {
        $data = $this->jsonSerialize();
        if (!$showHand) {
            $data['hand'] = [];
        }
        return $data;
    }

    public static function fromArray(array $data): self
    {
        $p = new self($data['id'], $data['name'], (bool)$data['isHuman'], (int)$data['seatWind']);
        $p->isDealer = (bool)$data['isDealer'];
        $p->score = (int)$data['score'];
        $p->hand = array_map(fn($t) => Tile::fromArray($t), $data['hand']);
        $p->melds = array_map(fn($m) => Meld::fromArray($m), $data['melds']);
        $p->discards = array_map(fn($t) => Tile::fromArray($t), $data['discards']);
        return $p;
    }
}
