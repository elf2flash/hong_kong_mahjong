<?php

declare(strict_types=1);

require_once __DIR__ . '/Tile.php';
require_once __DIR__ . '/Meld.php';
require_once __DIR__ . '/WinChecker.php';

/**
 * Подсчёт фан по правилам Hong Kong Mahjong (упрощённый набор распространённых комбинаций).
 * Минимум для победы: 3 фана (стандарт HK).
 */
class Scorer
{
    public const MIN_FAN = 3;

    private WinChecker $checker;

    public function __construct()
    {
        $this->checker = new WinChecker();
    }

    /**
     * @param Tile[] $concealed
     * @param Meld[] $openMelds
     * @return array{fan: int, details: string[], valid: bool}
     */
    public function score(
        array $concealed,
        array $openMelds,
        int $seatWind,
        int $roundWind,
        bool $selfDraw,
        bool $isDealer,
        bool $grantNoFlowerBonus = true
    ): array {
        $wins = $this->checker->allWins($concealed, $openMelds);
        if (!$wins) {
            return ['fan' => 0, 'details' => [], 'valid' => false];
        }

        $best = ['fan' => 0, 'details' => [], 'valid' => false];

        foreach ($wins as $win) {
            $r = $this->scoreDecomposition(
                $win,
                $openMelds,
                $seatWind,
                $roundWind,
                $selfDraw,
                $isDealer,
                $concealed,
                $grantNoFlowerBonus
            );
            if ($r['fan'] > $best['fan']) {
                $best = $r;
            }
        }

        $best['valid'] = $best['fan'] >= self::MIN_FAN;
        return $best;
    }

    private function scoreDecomposition(
        array $win,
        array $openMelds,
        int $seatWind,
        int $roundWind,
        bool $selfDraw,
        bool $isDealer,
        array $concealed,
        bool $grantNoFlowerBonus = true
    ): array {
        $fan = 0;
        $details = [];

        $allMelds = [];
        foreach ($openMelds as $m) {
            $allMelds[] = [
                'type' => $m->type === Meld::KONG ? 'kong' : ($m->type === Meld::CHOW ? 'chow' : 'pung'),
                'tiles' => array_map(fn(Tile $t) => $t->key(), $m->tiles),
                'concealed' => $m->concealed,
                'open' => true,
            ];
        }
        foreach ($win['melds'] as $m) {
            $allMelds[] = [
                'type' => $m['type'],
                'tiles' => $m['tiles'],
                'concealed' => true,
                'open' => false,
            ];
        }

        $pair = $win['pair'];
        $allConcealed = count($openMelds) === 0;

        // 自摸 Self-draw
        if ($selfDraw) {
            $fan += 1;
            $details[] = '自摸 Self-draw (+1)';
        }

        // 無花無槓 — не используем цветы; бонус за полностью закрытую руку без конгов
        $hasKong = false;
        foreach ($allMelds as $m) {
            if ($m['type'] === 'kong') {
                $hasKong = true;
            }
        }

        // 門前清 All concealed (when winning on discard) / 自摸 already counted
        if ($allConcealed && !$selfDraw) {
            $fan += 1;
            $details[] = '門前清 Concealed hand (+1)';
        }

        // Dragons & winds
        foreach ($allMelds as $m) {
            if ($m['type'] === 'chow') {
                continue;
            }
            $key = $m['tiles'][0];
            [$suit, $val] = explode('-', $key);
            $val = (int)$val;

            if ($suit === 'dragon') {
                $fan += 1;
                $name = match ($val) {
                    1 => '紅中', 2 => '青發', 3 => '白板', default => 'Dragon',
                };
                $details[] = "{$name} Dragon pung (+1)";
            }
            if ($suit === 'wind') {
                if ($val === $seatWind) {
                    $fan += 1;
                    $details[] = '門風 Seat wind (+1)';
                }
                if ($val === $roundWind) {
                    $fan += 1;
                    $details[] = '圈風 Prevailing wind (+1)';
                }
            }
        }

        // Pair of dragons
        [$pSuit, $pVal] = explode('-', $pair);
        $pVal = (int)$pVal;
        if ($pSuit === 'dragon') {
            $fan += 1;
            $details[] = '雀頭 Eyes of dragon (+1)';
        }

        // 平胡 All chows (no pungs/kongs) + non-value pair
        $allChows = true;
        foreach ($allMelds as $m) {
            if ($m['type'] !== 'chow') {
                $allChows = false;
                break;
            }
        }
        $valuePair = ($pSuit === 'dragon')
            || ($pSuit === 'wind' && ($pVal === $seatWind || $pVal === $roundWind));
        if ($allChows && !$valuePair) {
            $fan += 1;
            $details[] = '平胡 All chows (+1)';
        }

        // 對對胡 All pungs
        $allPungs = true;
        foreach ($allMelds as $m) {
            if ($m['type'] === 'chow') {
                $allPungs = false;
                break;
            }
        }
        if ($allPungs) {
            $fan += 3;
            $details[] = '對對胡 All pungs (+3)';
        }

        // 混一色 Half flush
        // 清一色 Full flush
        $suitsUsed = [];
        $hasHonor = false;
        $collect = function (string $key) use (&$suitsUsed, &$hasHonor) {
            [$s] = explode('-', $key);
            if (in_array($s, ['man', 'pin', 'sou'], true)) {
                $suitsUsed[$s] = true;
            } else {
                $hasHonor = true;
            }
        };
        foreach ($allMelds as $m) {
            foreach ($m['tiles'] as $tk) {
                $collect($tk);
            }
        }
        $collect($pair);

        $suitCount = count($suitsUsed);
        if ($suitCount === 1 && $hasHonor) {
            $fan += 3;
            $details[] = '混一色 Half flush (+3)';
        } elseif ($suitCount === 1 && !$hasHonor) {
            $fan += 7;
            $details[] = '清一色 Full flush (+7)';
        }

        // 小三元 / 大三元 Little/Big three dragons
        $dragonPungs = 0;
        $dragonPair = ($pSuit === 'dragon');
        foreach ($allMelds as $m) {
            if ($m['type'] === 'chow') {
                continue;
            }
            [$s] = explode('-', $m['tiles'][0]);
            if ($s === 'dragon') {
                $dragonPungs++;
            }
        }
        if ($dragonPungs === 3) {
            $fan += 8;
            $details[] = '大三元 Big three dragons (+8)';
        } elseif ($dragonPungs === 2 && $dragonPair) {
            $fan += 5;
            $details[] = '小三元 Little three dragons (+5)';
        }

        // 小四喜 / 大四喜
        $windPungs = 0;
        $windPair = ($pSuit === 'wind');
        foreach ($allMelds as $m) {
            if ($m['type'] === 'chow') {
                continue;
            }
            [$s] = explode('-', $m['tiles'][0]);
            if ($s === 'wind') {
                $windPungs++;
            }
        }
        if ($windPungs === 4) {
            $fan += 13;
            $details[] = '大四喜 Big four winds (+13)';
        } elseif ($windPungs === 3 && $windPair) {
            $fan += 6;
            $details[] = '小四喜 Little four winds (+6)';
        }

        // 字一色 All honors
        if ($suitCount === 0 && $hasHonor) {
            $fan += 13;
            $details[] = '字一色 All honors (+13)';
        }

        // 清老頭 All terminals (1/9 only, no honors) — rare
        // 混老頭 Mixed terminals
        $onlyTerminalHonor = true;
        $hasTerminal = false;
        $checkTH = function (string $key) use (&$onlyTerminalHonor, &$hasTerminal) {
            [$s, $v] = explode('-', $key);
            $v = (int)$v;
            if (in_array($s, ['man', 'pin', 'sou'], true)) {
                if ($v !== 1 && $v !== 9) {
                    $onlyTerminalHonor = false;
                } else {
                    $hasTerminal = true;
                }
            }
        };
        foreach ($allMelds as $m) {
            foreach (array_unique($m['tiles']) as $tk) {
                $checkTH($tk);
            }
        }
        $checkTH($pair);
        if ($onlyTerminalHonor && $hasTerminal && $hasHonor && !$allChows) {
            $fan += 5;
            $details[] = '混老頭 Mixed terminals (+5)';
        }

        // Dealer bonus not as fan in HK — paid double instead; skip fan

        // No flowers / no kong chicken hand helper when low fan
        if (!$hasKong && $fan < self::MIN_FAN && $selfDraw) {
            // already have self-draw
        }

        // 無花 — бонус «без цветов» только в режиме без строгого минимума фанов
        if ($grantNoFlowerBonus && $fan < self::MIN_FAN) {
            $fan += 1;
            $details[] = '無花 No flowers (+1)';
        }

        return ['fan' => $fan, 'details' => $details, 'valid' => $fan >= self::MIN_FAN];
    }

    /**
     * Базовая выплата: 2^fan единиц (упрощённо), дилер x2.
     */
    public function payment(int $fan, bool $winnerIsDealer, bool $selfDraw, int $loserSeat = -1): array
    {
        $base = 2 ** min($fan, 10); // cap
        // returns map seat => delta (winner gets positive from each)
        return [
            'base' => $base,
            'fan' => $fan,
            'selfDraw' => $selfDraw,
            'dealer' => $winnerIsDealer,
        ];
    }
}
