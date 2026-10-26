<?php

declare(strict_types=1);

require_once __DIR__ . '/Tile.php';
require_once __DIR__ . '/Meld.php';

/**
 * Проверка выигрышной руки Hong Kong Mahjong: 4 комбинации + пара.
 */
class WinChecker
{
    /**
     * @param Tile[] $concealed
     * @param Meld[] $melds
     */
    public function canWin(array $concealed, array $melds): bool
    {
        $needMelds = 4 - count($melds);
        $needTiles = $needMelds * 3 + 2;

        if (count($concealed) !== $needTiles) {
            return false;
        }

        $results = [];
        $this->enumerate($this->countMap($concealed), $needMelds, [], null, $results, true);
        return count($results) > 0;
    }

    /**
     * @param Tile[] $concealed
     * @param Meld[] $melds
     * @return array<int, array{pair: string, melds: array}>
     */
    public function allWins(array $concealed, array $melds): array
    {
        $needMelds = 4 - count($melds);
        $needTiles = $needMelds * 3 + 2;
        if (count($concealed) !== $needTiles) {
            return [];
        }

        $results = [];
        $this->enumerate($this->countMap($concealed), $needMelds, [], null, $results, false);
        return $results;
    }

    /** @param Tile[] $tiles */
    private function countMap(array $tiles): array
    {
        $map = [];
        foreach ($tiles as $t) {
            $k = $t->key();
            $map[$k] = ($map[$k] ?? 0) + 1;
        }
        ksort($map);
        return $map;
    }

    private function enumerate(
        array $counts,
        int $needMelds,
        array $formed,
        ?string $pair,
        array &$results,
        bool $stopEarly
    ): void {
        $counts = array_filter($counts, fn($c) => $c > 0);
        ksort($counts);

        // Сначала пара: иначе при 4 открытых мельдах (needMelds=0) победа на пару не находится
        if ($pair === null) {
            foreach ($counts as $key => $cnt) {
                if ($cnt >= 2) {
                    $next = $counts;
                    $next[$key] -= 2;
                    $this->enumerate($next, $needMelds, $formed, $key, $results, $stopEarly);
                    if ($stopEarly && $results) {
                        return;
                    }
                }
            }
            return;
        }

        if ($needMelds === 0) {
            if (array_sum($counts) === 0) {
                $results[] = ['pair' => $pair, 'melds' => $formed];
            }
            return;
        }

        // Берём наименьший ключ — стандартный рекурсивный разбор
        $key = array_key_first($counts);
        if ($key === null) {
            return;
        }
        $cnt = $counts[$key];

        // pung
        if ($cnt >= 3) {
            $next = $counts;
            $next[$key] -= 3;
            $meld = ['type' => 'pung', 'key' => $key, 'tiles' => [$key, $key, $key]];
            $this->enumerate($next, $needMelds - 1, [...$formed, $meld], $pair, $results, $stopEarly);
            if ($stopEarly && $results) {
                return;
            }
        }

        // chow
        [$suit, $val] = explode('-', $key);
        $val = (int)$val;
        if (in_array($suit, ['man', 'pin', 'sou'], true) && $val <= 7) {
            $k2 = $suit . '-' . ($val + 1);
            $k3 = $suit . '-' . ($val + 2);
            if (($counts[$k2] ?? 0) >= 1 && ($counts[$k3] ?? 0) >= 1) {
                $next = $counts;
                $next[$key]--;
                $next[$k2]--;
                $next[$k3]--;
                $meld = ['type' => 'chow', 'key' => $key, 'tiles' => [$key, $k2, $k3]];
                $this->enumerate($next, $needMelds - 1, [...$formed, $meld], $pair, $results, $stopEarly);
            }
        }
    }

    /**
     * @param Tile[] $hand
     * @param Meld[] $melds
     * @return array{chow: bool, pung: bool, kong: bool, win: bool, chowOptions: array}
     */
    public function claimOptions(array $hand, array $melds, Tile $discard, bool $isPreviousPlayer): array
    {
        $opts = [
            'chow' => false,
            'pung' => false,
            'kong' => false,
            'win' => false,
            'chowOptions' => [],
        ];

        $counts = $this->countMap($hand);
        $key = $discard->key();
        $same = $counts[$key] ?? 0;

        if ($same >= 2) {
            $opts['pung'] = true;
        }
        if ($same >= 3) {
            $opts['kong'] = true;
        }

        if ($isPreviousPlayer && $discard->isSuit()) {
            $suit = $discard->suit;
            $v = $discard->value;
            $patterns = [
                [$v - 2, $v - 1, $v],
                [$v - 1, $v, $v + 1],
                [$v, $v + 1, $v + 2],
            ];
            foreach ($patterns as $p) {
                if ($p[0] < 1 || $p[2] > 9) {
                    continue;
                }
                $need = [];
                $ok = true;
                foreach ($p as $n) {
                    if ($n === $v) {
                        continue;
                    }
                    $k = $suit . '-' . $n;
                    $need[$k] = ($need[$k] ?? 0) + 1;
                }
                foreach ($need as $k => $req) {
                    if (($counts[$k] ?? 0) < $req) {
                        $ok = false;
                        break;
                    }
                }
                if ($ok) {
                    $opts['chow'] = true;
                    $opts['chowOptions'][] = array_map(fn($n) => $suit . '-' . $n, $p);
                }
            }
        }

        $opts['win'] = $this->canWin([...$hand, $discard], $melds);

        return $opts;
    }
}
