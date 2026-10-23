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
        // Ботов крутит клиент (пауза + botStep)
        $_SESSION['game'] = $game->toArray();
    }
    return Game::fromArray($_SESSION['game']);
}

function saveGame(Game $game): void
{
    $_SESSION['game'] = $game->toArray();
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
            $game = new Game();
            $keep = !empty($input['keepScores']);
            if ($keep && !empty($_SESSION['game'])) {
                $game = Game::fromArray($_SESSION['game']);
                $game->newRound(true);
            } else {
                $game->newRound(false);
            }
            saveGame($game);
            respond($game);
            break;

        case 'state':
            respond($game);
            break;

        case 'botStep':
            if ($game->isAwaitingBot()) {
                $game->processBotTurn();
                saveGame($game);
            }
            respond($game);
            break;

        case 'discard':
            $tileId = (int)($input['tileId'] ?? -1);
            $res = $game->discard(0, $tileId);
            if ($res['ok']) {
                if (!empty($res['claims'])) {
                    $game->resolveClaims();
                }
            }
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
