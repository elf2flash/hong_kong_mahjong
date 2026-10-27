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
    public function chooseClaim(
        Player $player,
        Tile $discard,
        array $options,
        int $seatWind,
        int $roundWind,
        bool $requireMinFan = false
    ): array {
        if (!empty($options['win'])) {
            $score = $this->scorer->score(
                [...$player->hand, $discard],
                $player->melds,
                $seatWind,
                $roundWind,
                false,
                $player->isDealer,
                !$requireMinFan
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
            // В режиме min-3 фана понги берём гораздо чаще (путь к 對對胡 / фанам)
            $pungChance = $requireMinFan ? 88 : 55;
            if (mt_rand(0, 100) < $pungChance) {
                return ['action' => 'pung'];
            }
        }

        if (!empty($options['chow']) && !empty($options['chowOptions'])) {
            if ($requireMinFan) {
                // Обычный чоу почти не берём; только если ведёт к 混一色 (одна масть + гоноры)
                $best = $this->bestHalfFlushChowOption($player, $discard, $options['chowOptions']);
                if ($best !== null) {
                    return ['action' => 'chow', 'chowOption' => $best];
                }
            } elseif (mt_rand(0, 100) < 40) {
                return ['action' => 'chow', 'chowOption' => 0];
            }
        }

        return ['action' => 'pass'];
    }

    /**
     * Чоу для пути к 混一色: не открываем вторую числовую масть в мельдах,
     * и в руке эта масть уже преобладает (чужие можно потом сбросить).
     *
     * @param list<list<string>> $chowOptions
     */
    private function bestHalfFlushChowOption(Player $player, Tile $discard, array $chowOptions): ?int
    {
        if (!$discard->isSuit()) {
            return null;
        }

        $openSuits = $this->openSuitedSuits($player);
        // Уже две разные масти в открытых мельдах — 混一色 не светит
        if (count($openSuits) >= 2) {
            return null;
        }
        // Есть открытая масть — чоу только в ней
        if (count($openSuits) === 1 && $openSuits[0] !== $discard->suit) {
            return null;
        }

        $mainSuit = $discard->suit;
        $bestIdx = null;
        $bestScore = PHP_INT_MIN;

        foreach ($chowOptions as $idx => $option) {
            if (!is_array($option) || $option === []) {
                continue;
            }
            // Все ключи варианта должны быть той же масти, что и сброс
            $ok = true;
            foreach ($option as $key) {
                [$s] = explode('-', (string)$key);
                if ($s !== $mainSuit) {
                    $ok = false;
                    break;
                }
            }
            if (!$ok) {
                continue;
            }

            $same = 0;
            $foreign = 0;
            $needFromHand = [];
            foreach ($option as $key) {
                if ($key === $discard->key()) {
                    continue;
                }
                $needFromHand[$key] = ($needFromHand[$key] ?? 0) + 1;
            }
            $removed = $needFromHand;
            foreach ($player->hand as $t) {
                $k = $t->key();
                if (($removed[$k] ?? 0) > 0) {
                    $removed[$k]--;
                    continue;
                }
                if (!$t->isSuit()) {
                    continue;
                }
                if ($t->suit === $mainSuit) {
                    $same++;
                } else {
                    $foreign++;
                }
            }

            // Масть чоу должна явно преобладать в оставшейся руке
            if ($same < $foreign || $same < 2) {
                continue;
            }

            $score = $same * 3 - $foreign * 2;
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestIdx = (int)$idx;
            }
        }

        return $bestIdx;
    }

    /** Числовые масти уже открытых мельдов. */
    private function openSuitedSuits(Player $player): array
    {
        $suits = [];
        foreach ($player->melds as $m) {
            foreach ($m->tiles as $t) {
                if ($t->isSuit()) {
                    $suits[$t->suit] = true;
                    break;
                }
            }
        }
        return array_keys($suits);
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
