<?php
$navCurrent = 'help';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Все плитки — Hong Kong Mahjong</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@400;700&family=DM+Sans:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__ . '/css/style.css') ?>">
</head>
<body class="page-catalog">
  <header class="topbar topbar-nav">
    <?php require __DIR__ . '/includes/nav.php'; ?>
  </header>

  <main class="catalog">
    <div class="catalog-intro">
      <h1>Все плитки</h1>
      <p>В Hong Kong Mahjong используется колода из <strong>136</strong> плиток (по 4 экземпляра каждого вида). Ниже — полный набор уникальных лиц.</p>
    </div>

    <section class="tile-section" id="man">
      <h2>萬 Characters <span class="count">9 × 4</span></h2>
      <p class="section-desc">Числа от 1 до 9 масти «вань» (персонажи).</p>
      <div class="tile-row" data-suit="man" data-from="1" data-to="9"></div>
    </section>

    <section class="tile-section" id="pin">
      <h2>筒 Dots / Circles <span class="count">9 × 4</span></h2>
      <p class="section-desc">Круги (точки) от 1 до 9.</p>
      <div class="tile-row" data-suit="pin" data-from="1" data-to="9"></div>
    </section>

    <section class="tile-section" id="sou">
      <h2>索 Bamboo <span class="count">9 × 4</span></h2>
      <p class="section-desc">Бамбук от 1 до 9.</p>
      <div class="tile-row" data-suit="sou" data-from="1" data-to="9"></div>
    </section>

    <section class="tile-section" id="winds">
      <h2>風 Winds <span class="count">4 × 4</span></h2>
      <p class="section-desc">Восточный, южный, западный и северный ветры (E / S / W / N).</p>
      <div class="tile-row" data-suit="wind" data-from="1" data-to="4"></div>
    </section>

    <section class="tile-section" id="dragons">
      <h2>龍 Dragons <span class="count">3 × 4</span></h2>
      <p class="section-desc">Красный 中, зелёный 發, белый 白 (C / F / P).</p>
      <div class="tile-row" data-suit="dragon" data-from="1" data-to="3"></div>
    </section>

    <section class="tile-section rules-block" id="rules">
      <h2>Краткие правила</h2>
      <ul class="rules-list">
        <li>Цель — собрать <button type="button" class="tip-link" data-tip="win">4 комбинации + пару</button> (14 плиток).</li>
        <li>Комбинации: <button type="button" class="tip-link" data-tip="chow">чоу</button> (последовательность 3), <button type="button" class="tip-link" data-tip="pung">понг</button> (три одинаковых), <button type="button" class="tip-link" data-tip="kong">конг</button> (четыре).</li>
        <li>Минимум для победы в этой версии: <button type="button" class="tip-link" data-tip="fan">3 фана</button>.</li>
        <li>Можно брать чужой сброс: чоу — только у предыдущего игрока; понг/конг/победа — у любого.</li>
      </ul>
      <p class="back-link"><a href="index.php">← Вернуться к игре</a></p>
    </section>
  </main>

  <div id="tipPopover" class="tip-popover hidden" role="dialog" aria-modal="false" hidden>
    <div class="tip-popover-inner">
      <header class="tip-popover-head">
        <h3 id="tipTitle"></h3>
        <button type="button" class="tip-close" aria-label="Закрыть">×</button>
      </header>
      <div id="tipBody" class="tip-popover-body"></div>
    </div>
  </div>

  <script src="js/tiles.js?v=<?= filemtime(__DIR__ . '/js/tiles.js') ?>"></script>
  <script src="js/nav.js?v=<?= filemtime(__DIR__ . '/js/nav.js') ?>"></script>
  <script src="js/settings.js?v=<?= filemtime(__DIR__ . '/js/settings.js') ?>"></script>
  <script src="js/catalog.js?v=<?= filemtime(__DIR__ . '/js/catalog.js') ?>"></script>
  <script src="js/tips.js?v=<?= filemtime(__DIR__ . '/js/tips.js') ?>"></script>
</body>
</html>
