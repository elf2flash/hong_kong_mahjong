<?php

declare(strict_types=1);

require_once __DIR__ . '/Tile.php';
require_once __DIR__ . '/Deck.php';
require_once __DIR__ . '/Meld.php';
require_once __DIR__ . '/Player.php';
require_once __DIR__ . '/WinChecker.php';
require_once __DIR__ . '/Scorer.php';
require_once __DIR__ . '/AI.php';

class Game
{
    public const PHASE_DRAW = 'draw';
    public const PHASE_DISCARD = 'discard';
    public const PHASE_CLAIM = 'claim';
    public const PHASE_ENDED = 'ended';

    /** @var Player[] */
    public array $players = [];
    public Deck $deck;
    public int $currentPlayer = 0;
    public int $dealer = 0;
    public int $roundWind = 1; // East round
    public string $phase = self::PHASE_DRAW;
    public ?Tile $lastDiscard = null;
    public ?int $lastDiscardPlayer = null;
    /** Общая река сбросов в порядке появления (без сортировки) */
    public array $river = [];
    public array $claimQueue = []; // player ids who can claim, priority order
    public array $claimOptions = []; // playerId => options
    public ?array $pendingClaim = null;
    public array $log = [];
    public ?array $result = null;
    public int $wallDead = 14; // reserve (simplified)
    /** ID только что взятой плитки (показывается отдельно справа, пока не «вставлена») */
    public ?int $pendingDrawnId = null;

    private WinChecker $checker;
    private Scorer $scorer;
    private AI $ai;

    public function __construct()
    {
        $this->deck = new Deck();
        $this->checker = new WinChecker();
        $this->scorer = new Scorer();
        $this->ai = new AI();
    }

    public function newRound(bool $keepScores = true): void
    {
        $scores = [];
        if ($keepScores && $this->players) {
            foreach ($this->players as $p) {
                $scores[$p->id] = $p->score;
            }
        }

        // Позиции за столом: Восток / Юг / Запад / Север
        $names = [
            'Вы (East) 東',
            'Бот Юг (South) 南',
            'Бот Запад (West) 西',
            'Бот Север (North) 北',
        ];
        $this->players = [];
        for ($i = 0; $i < 4; $i++) {
            $wind = (($i - $this->dealer + 4) % 4) + 1;
            $p = new Player($i, $names[$i], $i === 0, $wind);
            $p->isDealer = ($i === $this->dealer);
            if (isset($scores[$i])) {
                $p->score = $scores[$i];
            }
            $this->players[] = $p;
        }

        $this->deck = new Deck();
        $this->deck->shuffle();
        $this->lastDiscard = null;
        $this->lastDiscardPlayer = null;
        $this->river = [];
        $this->claimQueue = [];
        $this->claimOptions = [];
        $this->pendingClaim = null;
        $this->result = null;
        $this->log = [];
        $this->phase = self::PHASE_DRAW;
        $this->pendingDrawnId = null;

        // Раздача: по 13, дилер +1
        for ($r = 0; $r < 13; $r++) {
            foreach ($this->players as $p) {
                $t = $this->deck->draw();
                if ($t) {
                    $p->addTile($t);
                }
            }
        }
        $extra = $this->deck->draw();
        if ($extra) {
            $this->players[$this->dealer]->addTile($extra);
            // Для игрока-дилера 14-я плитка — как «добор»
            if ($this->dealer === 0) {
                $this->pendingDrawnId = $extra->id;
            }
        }

        $this->currentPlayer = $this->dealer;
        $this->phase = self::PHASE_DISCARD; // дилер уже с 14 плитками
        $this->addLog('Новая раздача. Дилер: ' . $this->players[$this->dealer]->name);
    }

    public function addLog(string $msg): void
    {
        $this->log[] = $msg;
        if (count($this->log) > 40) {
            $this->log = array_slice($this->log, -40);
        }
    }

    public function drawForCurrent(): ?Tile
    {
        if ($this->phase !== self::PHASE_DRAW) {
            return null;
        }
        if ($this->deck->remaining() <= $this->wallDead) {
            $this->endDraw();
            return null;
        }
        $tile = $this->deck->draw();
        if (!$tile) {
            $this->endDraw();
            return null;
        }
        $p = $this->players[$this->currentPlayer];
        $p->addTile($tile);
        $this->addLog($p->name . ' берёт плитку');
        $this->phase = self::PHASE_DISCARD;
        $this->pendingDrawnId = $p->isHuman ? $tile->id : null;

        // Авто-победа для бота
        if (!$p->isHuman && $this->checker->canWin($p->hand, $p->melds)) {
            if ($this->ai->wantsWin($p, true, $p->seatWind, $this->roundWind)) {
                $this->declareWin($this->currentPlayer, true);
            }
        }

        return $tile;
    }

    /** Вставить добранную плитку в общую руку (только UI-состояние). */
    public function insertDrawn(): array
    {
        if ($this->currentPlayer !== 0 || $this->phase !== self::PHASE_DISCARD) {
            return ['ok' => false, 'error' => 'Сейчас нельзя'];
        }
        if ($this->pendingDrawnId === null) {
            return ['ok' => true];
        }
        $this->pendingDrawnId = null;
        return ['ok' => true];
    }

    public function discard(int $playerId, int $tileId): array
    {
        if ($this->phase !== self::PHASE_DISCARD || $playerId !== $this->currentPlayer) {
            return ['ok' => false, 'error' => 'Сейчас нельзя сбросить'];
        }
        $p = $this->players[$playerId];
        $tile = $p->removeTileById($tileId);
        if (!$tile) {
            return ['ok' => false, 'error' => 'Плитка не найдена'];
        }
        $p->discards[] = $tile;
        $this->river[] = [
            'tile' => $tile->jsonSerialize(),
            'playerId' => $playerId,
        ];
        $this->lastDiscard = $tile;
        $this->lastDiscardPlayer = $playerId;
        $this->pendingDrawnId = null;
        $this->addLog($p->name . ' сбрасывает ' . $tile->display());

        // Собрать клеймы (приоритет: win > kong/pung > chow)
        $this->buildClaimQueue($playerId, $tile);

        if ($this->claimQueue) {
            $this->phase = self::PHASE_CLAIM;
            return ['ok' => true, 'claims' => true];
        }

        $this->advanceTurn();
        return ['ok' => true, 'claims' => false];
    }

    private function buildClaimQueue(int $discarder, Tile $tile): void
    {
        $this->claimQueue = [];
        $this->claimOptions = [];

        $wins = [];
        $pungs = [];
        $chows = [];

        for ($i = 1; $i <= 3; $i++) {
            $pid = ($discarder + $i) % 4;
            $prev = ($discarder + 1) % 4 === $pid;
            $pl = $this->players[$pid];
            $opts = $this->checker->claimOptions($pl->hand, $pl->melds, $tile, $prev);

            // Для человека всегда показываем опции; для бота — фильтр
            if ($pl->isHuman) {
                if ($opts['win'] || $opts['kong'] || $opts['pung'] || $opts['chow']) {
                    $this->claimOptions[$pid] = $opts;
                    if ($opts['win']) {
                        $wins[] = $pid;
                    } elseif ($opts['kong'] || $opts['pung']) {
                        $pungs[] = $pid;
                    } elseif ($opts['chow']) {
                        $chows[] = $pid;
                    }
                }
            } else {
                $decision = $this->ai->chooseClaim($pl, $tile, $opts, $pl->seatWind, $this->roundWind);
                if ($decision['action'] === 'pass') {
                    continue;
                }
                $this->claimOptions[$pid] = $opts;
                $this->claimOptions[$pid]['aiAction'] = $decision;
                if ($decision['action'] === 'win') {
                    $wins[] = $pid;
                } elseif (in_array($decision['action'], ['kong', 'pung'], true)) {
                    $pungs[] = $pid;
                } elseif ($decision['action'] === 'chow') {
                    $chows[] = $pid;
                }
            }
        }

        // Приоритет очереди
        $this->claimQueue = array_values(array_unique([...$wins, ...$pungs, ...$chows]));
    }

    public function resolveClaims(): void
    {
        // Автоматически обработать ботов в очереди; человек ждёт API
        while ($this->phase === self::PHASE_CLAIM && $this->claimQueue) {
            $pid = $this->claimQueue[0];
            $pl = $this->players[$pid];
            if ($pl->isHuman) {
                return; // ждём ответа игрока
            }
            $opts = $this->claimOptions[$pid] ?? [];
            $decision = $opts['aiAction'] ?? ['action' => 'pass'];
            $this->applyClaim($pid, $decision['action'], $decision['chowOption'] ?? 0);
        }

        if ($this->phase === self::PHASE_CLAIM && !$this->claimQueue) {
            $this->advanceTurn();
        }
    }

    public function humanClaim(string $action, int $chowOption = 0): array
    {
        if ($this->phase !== self::PHASE_CLAIM) {
            return ['ok' => false, 'error' => 'Нет фазы клейма'];
        }
        if (!$this->claimQueue || $this->claimQueue[0] !== 0) {
            // человек не первый в очереди — но win имеет приоритет; упростим: человек всегда id 0
            if (!in_array(0, $this->claimQueue, true) && $action !== 'pass') {
                return ['ok' => false, 'error' => 'Вы не можете взять эту плитку'];
            }
            if ($action === 'pass') {
                $this->claimQueue = array_values(array_filter($this->claimQueue, fn($id) => $id !== 0));
                unset($this->claimOptions[0]);
                $this->resolveClaims();
                if ($this->phase === self::PHASE_CLAIM && !$this->claimQueue) {
                    $this->advanceTurn();
                }
                return ['ok' => true];
            }
            // Если win — приоритет
            if ($action === 'win' && in_array(0, $this->claimQueue, true)) {
                return $this->applyClaim(0, 'win');
            }
        }

        if ($action === 'pass') {
            array_shift($this->claimQueue);
            unset($this->claimOptions[0]);
            $this->resolveClaims();
            if ($this->phase === self::PHASE_CLAIM && !$this->claimQueue) {
                $this->advanceTurn();
            }
            return ['ok' => true];
        }

        return $this->applyClaim(0, $action, $chowOption);
    }

    public function applyClaim(int $playerId, string $action, int $chowOption = 0): array
    {
        if ($this->lastDiscard === null || $this->lastDiscardPlayer === null) {
            return ['ok' => false, 'error' => 'Нет сброса'];
        }

        $discard = $this->lastDiscard;
        $pl = $this->players[$playerId];
        $opts = $this->claimOptions[$playerId] ?? $this->checker->claimOptions(
            $pl->hand,
            $pl->melds,
            $discard,
            (($this->lastDiscardPlayer + 1) % 4) === $playerId
        );

        if ($action === 'pass') {
            $this->claimQueue = array_values(array_filter($this->claimQueue, fn($id) => $id !== $playerId));
            return ['ok' => true];
        }

        if ($action === 'win') {
            if (empty($opts['win'])) {
                return ['ok' => false, 'error' => 'Нельзя выиграть'];
            }
            $pl->addTile($discard);
            // убрать из дискардов предыдущего
            $this->takeLastDiscardFromRiver();
            $this->declareWin($playerId, false);
            return ['ok' => true, 'win' => true];
        }

        if ($action === 'pung' && !empty($opts['pung'])) {
            $taken = $pl->removeByKey($discard->key(), 2);
            $meldTiles = [...$taken, $discard];
            $pl->melds[] = new Meld(Meld::PUNG, $meldTiles, false, $this->lastDiscardPlayer);
            $this->takeLastDiscardFromRiver();
            $this->addLog($pl->name . ' объявляет Понг ' . $discard->display());
            $this->afterMeldClaim($playerId);
            return ['ok' => true];
        }

        if ($action === 'kong' && !empty($opts['kong'])) {
            $taken = $pl->removeByKey($discard->key(), 3);
            $meldTiles = [...$taken, $discard];
            $pl->melds[] = new Meld(Meld::KONG, $meldTiles, false, $this->lastDiscardPlayer);
            $this->takeLastDiscardFromRiver();
            $this->addLog($pl->name . ' объявляет Конг ' . $discard->display());
            // после конга — добор из стены и сброс
            $this->currentPlayer = $playerId;
            $this->phase = self::PHASE_DRAW;
            $this->clearClaims();
            $this->drawForCurrent();
            return ['ok' => true];
        }

        if ($action === 'chow' && !empty($opts['chow'])) {
            $option = $opts['chowOptions'][$chowOption] ?? $opts['chowOptions'][0] ?? null;
            if (!$option) {
                return ['ok' => false, 'error' => 'Нет варианта чоу'];
            }
            $meldTiles = [];
            foreach ($option as $key) {
                if ($key === $discard->key()) {
                    $meldTiles[] = $discard;
                } else {
                    $got = $pl->removeByKey($key, 1);
                    if (!$got) {
                        return ['ok' => false, 'error' => 'Не хватает плиток для чоу'];
                    }
                    $meldTiles[] = $got[0];
                }
            }
            usort($meldTiles, fn(Tile $a, Tile $b) => $a->value <=> $b->value);
            $pl->melds[] = new Meld(Meld::CHOW, $meldTiles, false, $this->lastDiscardPlayer);
            $this->takeLastDiscardFromRiver();
            $this->addLog($pl->name . ' объявляет Чоу');
            $this->afterMeldClaim($playerId);
            return ['ok' => true];
        }

        return ['ok' => false, 'error' => 'Недопустимое действие'];
    }

    /** Убрать последний сброс из реки и из дискардов игрока (при клейме). */
    private function takeLastDiscardFromRiver(): void
    {
        if ($this->lastDiscardPlayer !== null) {
            $prev = $this->players[$this->lastDiscardPlayer];
            if ($prev->discards) {
                array_pop($prev->discards);
            }
        }
        if ($this->river) {
            array_pop($this->river);
        }
    }

    private function afterMeldClaim(int $playerId): void
    {
        $this->clearClaims();
        $this->currentPlayer = $playerId;
        $this->phase = self::PHASE_DISCARD;
        $this->lastDiscard = null;
        $this->lastDiscardPlayer = null;
        $this->pendingDrawnId = null;
    }

    private function clearClaims(): void
    {
        $this->claimQueue = [];
        $this->claimOptions = [];
    }

    private function advanceTurn(): void
    {
        $this->clearClaims();
        $this->lastDiscard = null;
        $this->currentPlayer = ($this->currentPlayer + 1) % 4;
        $this->phase = self::PHASE_DRAW;
        $this->lastDiscardPlayer = null;
        // Ходы ботов крутит клиент (пауза + botStep).
        // Человеку добор делаем сразу — иначе UI застревает на «Добор».
        $p = $this->players[$this->currentPlayer] ?? null;
        if ($p && $p->isHuman) {
            $this->drawForCurrent();
        }
    }

    /** Нужен ли ещё ход бота (клиент покажет паузу и вызовет botStep). */
    public function isAwaitingBot(): bool
    {
        if ($this->phase === self::PHASE_ENDED) {
            return false;
        }
        if ($this->phase === self::PHASE_CLAIM) {
            if (!$this->claimQueue) {
                return false;
            }
            $pid = $this->claimQueue[0];
            return !($this->players[$pid]->isHuman ?? false);
        }
        if ($this->phase === self::PHASE_DRAW || $this->phase === self::PHASE_DISCARD) {
            $p = $this->players[$this->currentPlayer] ?? null;
            return $p !== null && !$p->isHuman;
        }
        return false;
    }

    /** Кого подсвечивать на паузе перед ходом бота. */
    public function botActorId(): ?int
    {
        if (!$this->isAwaitingBot()) {
            return null;
        }
        if ($this->phase === self::PHASE_CLAIM && $this->claimQueue) {
            return (int)$this->claimQueue[0];
        }
        return $this->currentPlayer;
    }

    /**
     * Один «тик» бота: полный ход (добор+сброс/победа) или разрешение клеймов ботов до человека.
     * Пауза между тиками — на клиенте.
     */
    public function processBotTurn(): void
    {
        if ($this->phase === self::PHASE_ENDED) {
            return;
        }

        if ($this->phase === self::PHASE_CLAIM) {
            $this->resolveClaims();
            return;
        }

        $p = $this->players[$this->currentPlayer] ?? null;
        if ($p === null) {
            return;
        }

        if ($p->isHuman) {
            if ($this->phase === self::PHASE_DRAW) {
                $this->drawForCurrent();
            }
            return;
        }

        if ($this->phase === self::PHASE_DRAW) {
            $this->drawForCurrent();
            if ($this->phase === self::PHASE_ENDED) {
                return;
            }
        }

        if ($this->phase === self::PHASE_DISCARD) {
            $p = $this->players[$this->currentPlayer];
            if ($this->checker->canWin($p->hand, $p->melds) && $this->ai->wantsWin($p, true, $p->seatWind, $this->roundWind)) {
                $this->declareWin($this->currentPlayer, true);
                return;
            }
            $tileId = $this->ai->chooseDiscard($p, $p->seatWind, $this->roundWind);
            $res = $this->discard($this->currentPlayer, $tileId);
            if (!empty($res['claims'])) {
                $this->resolveClaims();
            }
        }
    }

    /** Прокрутить всех ботов сразу (без пауз) — для совместимости. */
    public function processUntilHuman(): void
    {
        $guard = 0;
        while ($this->isAwaitingBot() && $guard++ < 50) {
            $this->processBotTurn();
        }
        $p = $this->players[$this->currentPlayer] ?? null;
        if ($p && $p->isHuman && $this->phase === self::PHASE_DRAW) {
            $this->drawForCurrent();
        }
    }

    public function declareWin(int $playerId, bool $selfDraw): void
    {
        $pl = $this->players[$playerId];
        $score = $this->scorer->score(
            $pl->hand,
            $pl->melds,
            $pl->seatWind,
            $this->roundWind,
            $selfDraw,
            $pl->isDealer
        );

        // Если мало фан — всё равно засчитываем с пометкой (для playability)
        $fan = max($score['fan'], 1);
        $base = 2 ** min($fan, 8);

        $payments = [];
        if ($selfDraw) {
            foreach ($this->players as $other) {
                if ($other->id === $playerId) {
                    continue;
                }
                $pay = $base;
                if ($pl->isDealer || $other->isDealer) {
                    $pay *= 2;
                }
                $other->score -= $pay;
                $pl->score += $pay;
                $payments[] = $other->name . ' платит ' . $pay;
            }
        } else {
            $loser = $this->players[$this->lastDiscardPlayer ?? (($playerId + 3) % 4)];
            $pay = $base * 2; // discarder pays for all (HK style often)
            if ($pl->isDealer || $loser->isDealer) {
                $pay *= 2;
            }
            $loser->score -= $pay;
            $pl->score += $pay;
            $payments[] = $loser->name . ' платит ' . $pay;
        }

        $this->result = [
            'winner' => $playerId,
            'winnerName' => $pl->name,
            'selfDraw' => $selfDraw,
            'fan' => $fan,
            'details' => $score['details'],
            'payments' => $payments,
            'minFanMet' => $score['valid'],
        ];
        $this->phase = self::PHASE_ENDED;
        $this->addLog($pl->name . ' побеждает! ' . $fan . ' фан' . ($selfDraw ? ' (自摸)' : ''));

        // Следующий дилер: если дилер выиграл — остаётся, иначе следующий
        if ($playerId !== $this->dealer) {
            $this->dealer = ($this->dealer + 1) % 4;
        }
    }

    private function endDraw(): void
    {
        $this->phase = self::PHASE_ENDED;
        $this->result = [
            'winner' => null,
            'winnerName' => null,
            'draw' => true,
            'message' => 'Ничья — стена закончилась',
        ];
        $this->addLog('Ничья (стена закончилась)');
        $this->dealer = ($this->dealer + 1) % 4;
    }

    public function humanWin(): array
    {
        $p = $this->players[0];
        if ($this->currentPlayer !== 0 || $this->phase !== self::PHASE_DISCARD) {
            return ['ok' => false, 'error' => 'Сейчас нельзя объявить победу'];
        }
        if (!$this->checker->canWin($p->hand, $p->melds)) {
            return ['ok' => false, 'error' => 'Рука не выигрышная'];
        }
        $this->declareWin(0, true);
        return ['ok' => true];
    }

    public function toClientState(): array
    {
        $humanOpts = null;
        if ($this->phase === self::PHASE_CLAIM && isset($this->claimOptions[0])) {
            $humanOpts = $this->claimOptions[0];
            unset($humanOpts['aiAction']);
        }

        $human = $this->players[0] ?? null;
        $canWin = $human
            && $this->phase === self::PHASE_DISCARD
            && $this->currentPlayer === 0
            && $this->checker->canWin($human->hand, $human->melds);

        $ended = $this->phase === self::PHASE_ENDED;

        return [
            'phase' => $this->phase,
            'currentPlayer' => $this->currentPlayer,
            'dealer' => $this->dealer,
            'roundWind' => $this->roundWind,
            'wallRemaining' => max(0, $this->deck->remaining() - $this->wallDead),
            // Остаток стены (включая мёртвую) — только после конца раздачи
            'wallTiles' => $ended ? $this->deck->toArray() : null,
            'lastDiscard' => $this->lastDiscard?->jsonSerialize(),
            'lastDiscardPlayer' => $this->lastDiscardPlayer,
            'river' => $this->river,
            'claimOptions' => $humanOpts,
            'claimQueue' => $this->claimQueue,
            'canWin' => $canWin,
            'pendingDrawnId' => ($this->currentPlayer === 0 && $this->phase === self::PHASE_DISCARD)
                ? $this->pendingDrawnId
                : null,
            'players' => array_map(
                fn(Player $p) => $p->toPublicArray($p->isHuman || $ended),
                $this->players
            ),
            'log' => $this->log,
            'result' => $this->result,
            'minFan' => Scorer::MIN_FAN,
            'awaitingBot' => $this->isAwaitingBot(),
            'botActorId' => $this->botActorId(),
        ];
    }

    public function toArray(): array
    {
        return [
            'players' => array_map(fn(Player $p) => $p->jsonSerialize(), $this->players),
            'deck' => $this->deck->toArray(),
            'currentPlayer' => $this->currentPlayer,
            'dealer' => $this->dealer,
            'roundWind' => $this->roundWind,
            'phase' => $this->phase,
            'lastDiscard' => $this->lastDiscard?->jsonSerialize(),
            'lastDiscardPlayer' => $this->lastDiscardPlayer,
            'river' => $this->river,
            'claimQueue' => $this->claimQueue,
            'claimOptions' => $this->claimOptions,
            'log' => $this->log,
            'result' => $this->result,
            'wallDead' => $this->wallDead,
            'pendingDrawnId' => $this->pendingDrawnId,
        ];
    }

    public static function fromArray(array $data): self
    {
        $g = new self();
        $g->players = array_map(fn($p) => Player::fromArray($p), $data['players']);
        $g->deck = Deck::fromArray($data['deck']);
        $g->currentPlayer = (int)$data['currentPlayer'];
        $g->dealer = (int)$data['dealer'];
        $g->roundWind = (int)$data['roundWind'];
        $g->phase = $data['phase'];
        $g->lastDiscard = $data['lastDiscard'] ? Tile::fromArray($data['lastDiscard']) : null;
        $g->lastDiscardPlayer = $data['lastDiscardPlayer'];
        $g->river = $data['river'] ?? [];
        $g->claimQueue = $data['claimQueue'] ?? [];
        $g->claimOptions = $data['claimOptions'] ?? [];
        $g->log = $data['log'] ?? [];
        $g->result = $data['result'] ?? null;
        $g->wallDead = (int)($data['wallDead'] ?? 14);
        $g->pendingDrawnId = isset($data['pendingDrawnId']) ? ($data['pendingDrawnId'] !== null ? (int)$data['pendingDrawnId'] : null) : null;
        return $g;
    }
}
