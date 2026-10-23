<?php

declare(strict_types=1);

/**
 * Плитка Hong Kong Mahjong.
 * Suits: man (萬), pin (筒), sou (索), wind, dragon.
 * Flowers/seasons omitted for clearer gameplay (136-tile set).
 */
class Tile implements JsonSerializable
{
    public const SUITS = ['man', 'pin', 'sou', 'wind', 'dragon'];

    public const WINDS = [1 => 'E', 2 => 'S', 3 => 'W', 4 => 'N'];
    public const DRAGONS = [1 => 'R', 2 => 'G', 3 => 'W']; // red, green, white

    public function __construct(
        public readonly string $suit,
        public readonly int $value,
        public readonly int $id,
    ) {
    }

    public function key(): string
    {
        return $this->suit . '-' . $this->value;
    }

    public function isSuit(): bool
    {
        return in_array($this->suit, ['man', 'pin', 'sou'], true);
    }

    public function isHonor(): bool
    {
        return in_array($this->suit, ['wind', 'dragon'], true);
    }

    public function display(): string
    {
        return match ($this->suit) {
            'man' => $this->value . '萬',
            'pin' => $this->value . '筒',
            'sou' => $this->value . '索',
            'wind' => match ($this->value) {
                1 => '東', 2 => '南', 3 => '西', 4 => '北',
                default => '?',
            },
            'dragon' => match ($this->value) {
                1 => '中', 2 => '發', 3 => '白',
                default => '?',
            },
            default => '?',
        };
    }

    public function latin(): string
    {
        return match ($this->suit) {
            'man' => $this->value . 'm',
            'pin' => $this->value . 'p',
            'sou' => $this->value . 's',
            'wind' => self::WINDS[$this->value] ?? '?',
            'dragon' => self::DRAGONS[$this->value] ?? '?',
            default => '?',
        };
    }

    public function equals(Tile $other): bool
    {
        return $this->suit === $other->suit && $this->value === $other->value;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'suit' => $this->suit,
            'value' => $this->value,
            'key' => $this->key(),
            'display' => $this->display(),
            'latin' => $this->latin(),
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self($data['suit'], (int)$data['value'], (int)$data['id']);
    }
}
