<?php
$navCurrent = $navCurrent ?? 'game';
?>
<nav class="main-nav" aria-label="Главное меню">
  <a class="nav-brand" href="index.php">
    <span class="brand-zh">香港麻將</span>
    <span class="brand-en">Hong Kong Mahjong</span>
  </a>

  <ul class="nav-list">
    <li class="nav-item <?= $navCurrent === 'game' ? 'is-active' : '' ?>">
      <a href="index.php">Игра</a>
    </li>
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
              <button type="button" class="btn-size" data-tile-size="-1" aria-label="Уменьшить" title="Уменьшить на 10%">−</button>
              <span class="tile-size-value" id="tileSizeValue">100%</span>
              <button type="button" class="btn-size" data-tile-size="+1" aria-label="Увеличить" title="Увеличить на 10%">+</button>
            </div>
            <p class="tile-size-hint">Шаг 10% · от −50% до +50%</p>
          </div>
        </li>
      </ul>
    </li>
  </ul>
</nav>
