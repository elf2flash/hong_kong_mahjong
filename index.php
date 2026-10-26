<?php
session_start();
$navCurrent = 'game';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Hong Kong Mahjong</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@400;700&family=DM+Sans:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__ . '/css/style.css') ?>">
</head>
<body>
  <div class="app log-collapsed">
    <header class="topbar topbar-nav">
      <?php require __DIR__ . '/includes/nav.php'; ?>
      <div class="meta">
        <span id="roundWind" class="pill"></span>
        <span id="wallCount" class="pill"></span>
        <button type="button" id="btnNew" class="btn">Новая игра</button>
      </div>
    </header>

    <main class="table">
      <div class="seat seat-top" data-seat="2">
        <div class="seat-info"><span class="seat-name"></span><span class="seat-score"></span><span class="seat-announce" aria-live="polite"></span></div>
        <div class="seat-row">
          <div class="meld-field">
            <span class="hand-field-label">Комбинации</span>
            <div class="melds"></div>
          </div>
          <div class="hand-field">
            <span class="hand-field-label">Рука</span>
            <div class="hand back-hand"></div>
          </div>
        </div>
      </div>

      <div class="mid-row">
        <div class="seat seat-left" data-seat="3">
          <div class="seat-info"><span class="seat-name"></span><span class="seat-score"></span><span class="seat-announce" aria-live="polite"></span></div>
          <div class="meld-field">
            <span class="hand-field-label">Комбинации</span>
            <div class="melds"></div>
          </div>
          <div class="hand-field">
            <span class="hand-field-label">Рука</span>
            <div class="hand back-hand"></div>
          </div>
        </div>

        <div class="center">
          <div class="center-main">
            <div class="discard-field discard-field-center">
              <span class="hand-field-label">Сбросы</span>
              <div class="discards" id="tableDiscards"></div>
            </div>
            <div id="resultPanel" class="result-panel hidden"></div>
          </div>
          <div id="phaseLabel" class="phase"></div>
          <div id="claimPanel" class="claim-panel hidden"></div>
        </div>

        <div class="seat seat-right" data-seat="1">
          <div class="seat-info"><span class="seat-name"></span><span class="seat-score"></span><span class="seat-announce" aria-live="polite"></span></div>
          <div class="meld-field">
            <span class="hand-field-label">Комбинации</span>
            <div class="melds"></div>
          </div>
          <div class="hand-field">
            <span class="hand-field-label">Рука</span>
            <div class="hand back-hand"></div>
          </div>
        </div>
      </div>

      <div class="seat seat-bottom" data-seat="0">
        <div class="seat-info">
          <span class="seat-name"></span>
          <span class="seat-score"></span>
          <span class="seat-announce" aria-live="polite"></span>
          <button type="button" id="btnWin" class="btn btn-win hidden">自摸 Выиграть</button>
        </div>
        <div class="seat-row">
          <div class="meld-field">
            <span class="hand-field-label">Комбинации</span>
            <div class="melds"></div>
          </div>
          <div class="hand-field">
            <span class="hand-field-label">Рука</span>
            <div class="hand player-hand" id="playerHand"></div>
          </div>
        </div>
        <div id="wallField" class="wall-field">
          <span class="hand-field-label" id="wallFieldLabel">Стена (0)</span>
          <div class="wall-tiles" id="wallTiles"></div>
        </div>
      </div>
    </main>

    <aside class="log-panel is-collapsed" id="logPanel">
      <button type="button" class="log-toggle" id="btnLogToggle" aria-expanded="false" aria-controls="logBody">
        <span class="log-toggle-label">Журнал</span>
        <span class="log-chevron" aria-hidden="true">▾</span>
      </button>
      <div class="log-body" id="logBody" hidden>
        <ul id="log"></ul>
        <p class="hint">Без ограничения по фанам · Чоу / Понг / Конг / Махджонг</p>
      </div>
    </aside>
  </div>
  <script src="js/tiles.js?v=<?= filemtime(__DIR__ . '/js/tiles.js') ?>"></script>
  <script src="js/nav.js?v=<?= filemtime(__DIR__ . '/js/nav.js') ?>"></script>
  <script src="js/settings.js?v=<?= filemtime(__DIR__ . '/js/settings.js') ?>"></script>
  <script src="js/game.js?v=<?= filemtime(__DIR__ . '/js/game.js') ?>"></script>
</body>
</html>
