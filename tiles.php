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
        <li>Минимум для победы в этой версии: <button type="button" class="tip-link" data-tip="fan">3 фана</button> (см. <a href="#fans">полный список</a>).</li>
        <li>Можно брать чужой сброс: чоу — только у предыдущего игрока; понг/конг/победа — у любого.</li>
      </ul>
    </section>

    <section class="tile-section rules-block" id="fans">
      <h2>Фаны — за что начисляются</h2>
      <p class="section-desc">
        <strong>Фан (番)</strong> — единица ценности руки. Ниже перечислены все варианты, которые считает эта версия игры.
        Фаны суммируются. В меню «Игра» можно выбрать победу без ограничения или с минимумом 3 фана.
      </p>

      <h3 class="fan-group-title">+1 фан</h3>
      <ul class="fan-list">
        <li>
          <span class="fan-name">自摸 Self-draw</span>
          <span class="fan-desc">Победа со своего добора (не с чужого сброса).</span>
        </li>
        <li>
          <span class="fan-name">門前清 Concealed hand</span>
          <span class="fan-desc">Закрытая рука (нет открытых мельдов) при победе с чужого сброса.</span>
        </li>
        <li>
          <span class="fan-name">龍 Dragon pung</span>
          <span class="fan-desc">Понг или конг дракона: 紅中 / 青發 / 白板 — по +1 за каждый.</span>
        </li>
        <li>
          <span class="fan-name">門風 Seat wind</span>
          <span class="fan-desc">Понг или конг своего ветра места (門風).</span>
        </li>
        <li>
          <span class="fan-name">圈風 Prevailing wind</span>
          <span class="fan-desc">Понг или конг ветра круга (圈風). Если ветер одновременно свой и круга — оба бонуса.</span>
        </li>
        <li>
          <span class="fan-name">雀頭 Eyes of dragon</span>
          <span class="fan-desc">Пара из драконов.</span>
        </li>
        <li>
          <span class="fan-name">平胡 All chows</span>
          <span class="fan-desc">Все четыре комбинации — чоу, и пара не из ценных плиток (не дракон и не ценный ветер).</span>
        </li>
        <li>
          <span class="fan-name">無花 No flowers</span>
          <span class="fan-desc">Бонус «без цветов», если рука ещё не набрала минимум (в этой колоде цветов нет).</span>
        </li>
      </ul>

      <h3 class="fan-group-title">+3 фана</h3>
      <ul class="fan-list">
        <li>
          <span class="fan-name">對對胡 All pungs</span>
          <span class="fan-desc">Все комбинации — понги или конги (без чоу).</span>
        </li>
        <li>
          <span class="fan-name">混一色 Half flush</span>
          <span class="fan-desc">Одна числовая масть (萬 / 筒 / 索) плюс гоноры (ветры и/или драконы).</span>
        </li>
      </ul>

      <h3 class="fan-group-title">+5 фанов</h3>
      <ul class="fan-list">
        <li>
          <span class="fan-name">小三元 Little three dragons</span>
          <span class="fan-desc">Два понга/конга драконов и пара из третьего дракона.</span>
        </li>
        <li>
          <span class="fan-name">混老頭 Mixed terminals</span>
          <span class="fan-desc">Только крайние числа (1 и 9) и гоноры; без чоу.</span>
        </li>
      </ul>

      <h3 class="fan-group-title">+6 фанов</h3>
      <ul class="fan-list">
        <li>
          <span class="fan-name">小四喜 Little four winds</span>
          <span class="fan-desc">Три понга/конга ветров и пара из четвёртого ветра.</span>
        </li>
      </ul>

      <h3 class="fan-group-title">+7 фанов</h3>
      <ul class="fan-list">
        <li>
          <span class="fan-name">清一色 Full flush</span>
          <span class="fan-desc">Вся рука в одной числовой масти, без ветров и драконов.</span>
        </li>
      </ul>

      <h3 class="fan-group-title">+8 фанов</h3>
      <ul class="fan-list">
        <li>
          <span class="fan-name">大三元 Big three dragons</span>
          <span class="fan-desc">Понги или конги всех трёх драконов.</span>
        </li>
      </ul>

      <h3 class="fan-group-title">+13 фанов</h3>
      <ul class="fan-list">
        <li>
          <span class="fan-name">大四喜 Big four winds</span>
          <span class="fan-desc">Понги или конги всех четырёх ветров.</span>
        </li>
        <li>
          <span class="fan-name">字一色 All honors</span>
          <span class="fan-desc">Вся рука только из ветров и драконов.</span>
        </li>
      </ul>

      <p class="fan-note">
        Дилер не даёт отдельный фан: при победе дилера выплата удваивается отдельно.
        Список соответствует подсчёту в игре (упрощённый набор Hong Kong Mahjong).
      </p>
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
