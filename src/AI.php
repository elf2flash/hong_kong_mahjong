<?php

declare(strict_types=1);

require_once __DIR__ . '/Tile.php';
require_once __DIR__ . '/Player.php';
require_once __DIR__ . '/WinChecker.php';
require_once __DIR__ . '/Scorer.php';

/**
 * Простой ИИ: оценивает «опасность» сброса и приоритет клеймов.
 */
class AI
{
    private WinChecker $checker;
    private Scorer $scorer;

    public function __construct()
    {
        $this->checker = new WinChecker();
        $this->scorer = new Scorer();
    }

    /**
     * Выбрать плитку для сброса (возвращает id).
     */
    public function chooseDiscard(Player $player, int $seatWind, int $roundWind): int
    {
        $hand = $player->hand;
        if (count($hand) === 0) {
            throw new RuntimeException('Empty hand');
        }

        // Если уже можно выиграть после добора — не сюда (win обрабатывается отдельно)
        $bestId = $hand[0]->id;
        $bestScore = PHP_INT_MAX;

        foreach ($hand as $tile) {
            $remaining = array_values(array_filter($hand, fn(Tile $t) => $t->id !== $tile->id));
            $score = $this->handDanger($remaining, $player->melds) + $this->tileKeepValue($tile, $seatWind, $roundWind);
            // Чем меньше score, тем охотнее сбрасываем
            if ($score < $bestScore) {
                $bestScore = $score;
                $bestId = $tile->id;
            }
        }

        return $bestId;
    }

    /**
     * Решение по клейму: win > kong > pung > chow > pass
     *
     * @return array{action: string, chowOption?: int}
     */
    public function chooseClaim(Player $player, Tile $discard, array $options, int $seatWind, int $roundWind): array
    {
        if (!empty($options['win'])) {
            $score = $this->scorer->score(
                [...$player->hand, $discard],
                $player->melds,
                $seatWind,
                $roundWind,
                false,
                $player->isDealer
            );
            if ($score['valid'] || $score['fan'] >= 1) {
                // AI takes win if at least 1 fan (lenient) or valid
                if ($score['fan'] >= Scorer::MIN_FAN - 1) {
                    return ['action' => 'win'];
                }
            }
            if ($score['fan'] >= Scorer::MIN_FAN) {
                return ['action' => 'win'];
            }
        }

        if (!empty($options['kong'])) {
            return ['action' => 'kong'];
        }

        if (!empty($options['pung'])) {
            // pung honors / terminals preferentially
            if ($discard->isHonor() || $discard->value === 1 || $discard->value === 9) {
                return ['action' => 'pung'];
            }
            // random-ish: pung if we have isolated tiles
            if (mt_rand(0, 100) < 55) {
                return ['action' => 'pung'];
            }
        }

        if (!empty($options['chow']) && !empty($options['chowOptions'])) {
            if (mt_rand(0, 100) < 40) {
                return ['action' => 'chow', 'chowOption' => 0];
            }
        }

        return ['action' => 'pass'];
    }

    public function wantsWin(Player $player, bool $selfDraw, int $seatWind, int $roundWind): bool
    {
        $score = $this->scorer->score(
            $player->hand,
            $player->melds,
            $seatWind,
            $roundWind,
            $selfDraw,
            $player->isDealer
        );
        return $score['fan'] >= Scorer::MIN_FAN || ($selfDraw && $score['fan'] >= 2);
    }

    /** @param Tile[] $hand @param Meld[] $melds */
    private function handDanger(array $hand, array $melds): float
    {
        // эвристика: меньше уникальных «осиротевших» = лучше рука
        $counts = [];
        foreach ($hand as $t) {
            $counts[$t->key()] = ($counts[$t->key()] ?? 0) + 1;
        }
        $score = 0.0;
        foreach ($counts as $key => $c) {
            [$suit, $val] = explode('-', $key);
            $val = (int)$val;
            if ($c >= 2) {
                $score -= 3 * $c; // pairs/pungs good to keep
            } else {
                $score += 2; // singleton
                if (in_array($suit, ['man', 'pin', 'sou'], true)) {
                    $neighbors = 0;
                    if (($counts[$suit . '-' . ($val - 1)] ?? 0) > 0) {
                        $neighbors++;
                    }
                    if (($counts[$suit . '-' . ($val + 1)] ?? 0) > 0) {
                        $neighbors++;
                    }
                    if (($counts[$suit . '-' . ($val - 2)] ?? 0) > 0) {
                        $neighbors += 0.5;
                    }
                    if (($counts[$suit . '-' . ($val + 2)] ?? 0) > 0) {
                        $neighbors += 0.5;
                    }
                    $score -= $neighbors * 2;
                }
            }
        }
        return $score;
    }

    private function tileKeepValue(Tile $tile, int $seatWind, int $roundWind): float
    {
        // Высокое значение = хотим оставить = добавляем к danger при оценке сброса
        // (в chooseDiscard: меньший score = сбрасываем)
        // tileKeepValue: чем ВЫШЕ, тем меньше хотим сбрасывать → вычитаем из score? 
        // Сейчас: score = handDanger(remaining) + tileKeepValue(discarded)
        // Если сбрасываем ценную — tileKeepValue высокий — score высокий — не выбираем. OK.

        $v = 0.0;
        if ($tile->suit === 'dragon') {
            $v += 4;
        }
        if ($tile->suit === 'wind') {
            if ($tile->value === $seatWind || $tile->value === $roundWind) {
                $v += 4;
            } else {
                $v -= 1; // чужой ветер — сбрасываем охотнее
            }
        }
        if ($tile->isSuit() && ($tile->value === 1 || $tile->value === 9)) {
            $v += 1;
        }
        if ($tile->isSuit() && $tile->value >= 4 && $tile->value <= 6) {
            $v += 0.5; // средние удобнее для chow
        }
        return $v;
    }
}
