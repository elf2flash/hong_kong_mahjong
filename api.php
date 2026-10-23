<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/src/Game.php';

header('Content-Type: application/json; charset=utf-8');

function getGame(): Game
{
    if (empty($_SESSION['game'])) {
        $game = new Game();
        $game->newRound(false);
        $game->processUntilHuman();
        $_SESSION['game'] = $game->toArray();
    }
    return Game::fromArray($_SESSION['game']);
}

function saveGame(Game $game): void
{
    $_SESSION['game'] = $game->toArray();
}

$input = json_decode(file_get_contents('php://input') ?: '{}', true) ?? [];
$action = $input['action'] ?? ($_GET['action'] ?? 'state');

try {
    $game = getGame();

    switch ($action) {
        case 'new':
            $game = new Game();
            if (!empty($_SESSION['game'])) {
                $old = Game::fromArray($_SESSION['game']);
                foreach ($old->players as $i => $p) {
                    // keep nothing on full reset unless scores
                }
            }
            $keep = !empty($input['keepScores']);
            if ($keep && !empty($_SESSION['game'])) {
                $game = Game::fromArray($_SESSION['game']);
                $game->newRound(true);
            } else {
                $game->newRound(false);
            }
            $game->processUntilHuman();
            saveGame($game);
            echo json_encode(['ok' => true, 'state' => $game->toClientState()], JSON_UNESCAPED_UNICODE);
            break;

        case 'state':
            echo json_encode(['ok' => true, 'state' => $game->toClientState()], JSON_UNESCAPED_UNICODE);
            break;

        case 'discard':
            $tileId = (int)($input['tileId'] ?? -1);
            $res = $game->discard(0, $tileId);
            if ($res['ok']) {
                if (!empty($res['claims'])) {
                    $game->resolveClaims();
                }
                if ($game->phase !== Game::PHASE_ENDED && $game->phase !== Game::PHASE_CLAIM) {
                    $game->processUntilHuman();
                } elseif ($game->phase === Game::PHASE_CLAIM) {
                    $game->resolveClaims();
                    if ($game->phase !== Game::PHASE_CLAIM && $game->phase !== Game::PHASE_ENDED) {
                        $game->processUntilHuman();
                    }
                }
            }
            saveGame($game);
            echo json_encode(['ok' => $res['ok'], 'error' => $res['error'] ?? null, 'state' => $game->toClientState()], JSON_UNESCAPED_UNICODE);
            break;

        case 'claim':
            $claimAction = $input['claim'] ?? 'pass';
            $chowOption = (int)($input['chowOption'] ?? 0);
            $res = $game->humanClaim($claimAction, $chowOption);
            // После своего чоу/понга ход остаётся у игрока (фаза discard) — ботов не крутим.
            // processUntilHuman только если ход ушёл дальше или нужно ждать другой клейм.
            if (
                $res['ok']
                && $game->phase !== Game::PHASE_ENDED
                && !(
                    $game->phase === Game::PHASE_DISCARD
                    && $game->currentPlayer === 0
                )
            ) {
                $game->processUntilHuman();
            }
            saveGame($game);
            echo json_encode(['ok' => $res['ok'], 'error' => $res['error'] ?? null, 'state' => $game->toClientState()], JSON_UNESCAPED_UNICODE);
            break;

        case 'win':
            $res = $game->humanWin();
            saveGame($game);
            echo json_encode(['ok' => $res['ok'], 'error' => $res['error'] ?? null, 'state' => $game->toClientState()], JSON_UNESCAPED_UNICODE);
            break;

        case 'draw':
            if ($game->currentPlayer === 0 && $game->phase === Game::PHASE_DRAW) {
                $game->drawForCurrent();
            }
            saveGame($game);
            echo json_encode(['ok' => true, 'state' => $game->toClientState()], JSON_UNESCAPED_UNICODE);
            break;

        case 'insertDrawn':
            $res = $game->insertDrawn();
            saveGame($game);
            echo json_encode(['ok' => $res['ok'], 'error' => $res['error'] ?? null, 'state' => $game->toClientState()], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Unknown action'], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
