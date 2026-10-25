<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/src/Game.php';

header('Content-Type: application/json; charset=utf-8');

function getGame(): Game
{
    if (empty($_SESSION['game'])) {
        $game = new Game();
        $game->requireMinFan = (bool)($_SESSION['requireMinFan'] ?? false);
        $game->newRound(false);
        // Ботов крутит клиент (пауза + botStep)
        $_SESSION['game'] = $game->toArray();
    }
    return Game::fromArray($_SESSION['game']);
}

function saveGame(Game $game): void
{
    $_SESSION['game'] = $game->toArray();
    $_SESSION['requireMinFan'] = $game->requireMinFan;
}

function respond(Game $game, array $extra = []): void
{
    echo json_encode(
        array_merge(['ok' => true, 'state' => $game->toClientState()], $extra),
        JSON_UNESCAPED_UNICODE
    );
}

$input = json_decode(file_get_contents('php://input') ?: '{}', true) ?? [];
$action = $input['action'] ?? ($_GET['action'] ?? 'state');

try {
    $game = getGame();

    switch ($action) {
        case 'new':
            $keep = !empty($input['keepScores']);
            $mode = (bool)($_SESSION['requireMinFan'] ?? false);
            if ($keep && !empty($_SESSION['game'])) {
                $game = Game::fromArray($_SESSION['game']);
                $game->newRound(true);
            } else {
                $game = new Game();
                $game->requireMinFan = $mode;
                $game->newRound(false);
            }
            saveGame($game);
            respond($game);
            break;

        case 'setWinMode':
            $mode = (string)($input['mode'] ?? 'any');
            $game->requireMinFan = ($mode === 'min3');
            saveGame($game);
            respond($game, ['winMode' => $game->requireMinFan ? 'min3' : 'any']);
            break;

        case 'state':
            respond($game);
            break;

        case 'botStep':
            $claimAnnounce = null;
            if ($game->isAwaitingBot()) {
                $game->processBotTurn();
                // Явно отдаём надпись этого шага (state.announce тоже есть)
                $claimAnnounce = $game->announce;
                saveGame($game);
            }
            respond($game, [
                'claimAnnounce' => $claimAnnounce,
                // Дублируем на корне, чтобы клиент не зависел от state
                'announce' => $claimAnnounce,
            ]);
            break;

        case 'discard':
            $tileId = (int)($input['tileId'] ?? -1);
            $game->announce = null;
            $res = $game->discard(0, $tileId);
            // Клеймы ботов обрабатываются по одному через botStep (пауза + «Понг!» и т.п.)
            saveGame($game);
            echo json_encode(
                ['ok' => $res['ok'], 'error' => $res['error'] ?? null, 'state' => $game->toClientState()],
                JSON_UNESCAPED_UNICODE
            );
            break;

        case 'claim':
            $claimAction = $input['claim'] ?? 'pass';
            $chowOption = (int)($input['chowOption'] ?? 0);
            $res = $game->humanClaim($claimAction, $chowOption);
            saveGame($game);
            echo json_encode(
                ['ok' => $res['ok'], 'error' => $res['error'] ?? null, 'state' => $game->toClientState()],
                JSON_UNESCAPED_UNICODE
            );
            break;

        case 'win':
            $res = $game->humanWin();
            saveGame($game);
            echo json_encode(
                ['ok' => $res['ok'], 'error' => $res['error'] ?? null, 'state' => $game->toClientState()],
                JSON_UNESCAPED_UNICODE
            );
            break;

        case 'draw':
            if ($game->currentPlayer === 0 && $game->phase === Game::PHASE_DRAW) {
                $game->drawForCurrent();
            }
            saveGame($game);
            respond($game);
            break;

        case 'insertDrawn':
            $res = $game->insertDrawn();
            saveGame($game);
            echo json_encode(
                ['ok' => $res['ok'], 'error' => $res['error'] ?? null, 'state' => $game->toClientState()],
                JSON_UNESCAPED_UNICODE
            );
            break;

        default:
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Unknown action'], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
