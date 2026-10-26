<?php
$navCurrent = $navCurrent ?? 'game';
?>
<nav class="main-nav" aria-label="Главное меню">
  <a class="nav-brand" href="index.php">
    <span class="brand-zh">香港麻將</span>
    <span class="brand-en">Hong Kong Mahjong</span>
  </a>

  <ul class="nav-list">
    <?php if ($navCurrent === 'game'): ?>
    <li class="nav-item has-submenu is-active" data-nav="game">
      <button type="button" class="nav-trigger" aria-expanded="false" aria-haspopup="true">
        Игра
        <span class="nav-chevron" aria-hidden="true">▾</span>
      </button>
      <ul class="nav-submenu nav-submenu-wide" role="menu">
        <li role="none">
          <button type="button" class="nav-mode" role="menuitem" data-win-mode="any">
            <span class="nav-mode-check" aria-hidden="true">✓</span>
            Победа без ограничения по фанам
          </button>
        </li>
        <li role="none">
          <button type="button" class="nav-mode" role="menuitem" data-win-mode="min3">
            <span class="nav-mode-check" aria-hidden="true">✓</span>
            Победа минимум 3 фана
          </button>
        </li>
      </ul>
    </li>
    <?php else: ?>
    <li class="nav-item" data-nav="game">
      <a href="index.php">Игра</a>
    </li>
    <?php endif; ?>
    <li class="nav-item has-submenu <?= $navCurrent === 'help' ? 'is-active' : '' ?>">
      <button type="button" class="nav-trigger" aria-expanded="false" aria-haspopup="true">
        Help
        <span class="nav-chevron" aria-hidden="true">▾</span>
      </button>
      <ul class="nav-submenu" role="menu">
        <li role="none">
          <a role="menuitem" href="tiles.php">Все плитки</a>
        </li>
        <li role="none">
          <a role="menuitem" href="tiles.php#rules">Краткие правила</a>
        </li>
        <li role="none">
          <a role="menuitem" href="tiles.php#fans">Фаны (очки)</a>
        </li>
      </ul>
    </li>
    <li class="nav-item has-submenu <?= $navCurrent === 'settings' ? 'is-active' : '' ?>">
      <button type="button" class="nav-trigger" aria-expanded="false" aria-haspopup="true">
        Настройки
        <span class="nav-chevron" aria-hidden="true">▾</span>
      </button>
      <ul class="nav-submenu nav-submenu-wide" role="menu">
        <li role="none" class="nav-setting">
          <div class="tile-size-control" data-setting="tile-size">
            <span class="tile-size-label">Размер плитки</span>
            <div class="tile-size-row">
              <button type="button" class="btn-size" data-tile-size="dec" aria-label="Уменьшить" title="Уменьшить на 10%">−</button>
              <span class="tile-size-value" id="tileSizeValue">100%</span>
              <button type="button" class="btn-size" data-tile-size="inc" aria-label="Увеличить" title="Увеличить на 10%">+</button>
            </div>
            <p class="tile-size-hint">Шаг 10% · от −50% до +50%</p>
          </div>
        </li>
        <li role="none" class="nav-setting">
          <div class="tile-size-control" data-setting="bot-delay">
            <span class="tile-size-label">Время на ход бота</span>
            <div class="tile-size-row">
              <button type="button" class="btn-size" data-bot-delay="dec" aria-label="Уменьшить" title="Уменьшить на 0.1 с">−</button>
              <span class="bot-delay-value" id="botDelayValue">0.3 с</span>
              <button type="button" class="btn-size" data-bot-delay="inc" aria-label="Увеличить" title="Увеличить на 0.1 с">+</button>
            </div>
            <p class="tile-size-hint">Шаг 0.1 с · от 0 до 3 с</p>
          </div>
        </li>
      </ul>
    </li>
  </ul>
</nav>
