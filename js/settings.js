(() => {
  const SCALE_KEY = 'hk-mahjong-tile-scale';
  const BOT_DELAY_KEY = 'hk-mahjong-bot-delay';

  const SCALE_STEP = 0.1;
  const SCALE_MIN = 0.5;
  const SCALE_MAX = 1.5;

  const DELAY_STEP = 0.1;
  const DELAY_MIN = 0;
  const DELAY_MAX = 3;
  const DELAY_DEFAULT = 0.3;

  function clampScale(n) {
    return Math.min(SCALE_MAX, Math.max(SCALE_MIN, Math.round(n * 10) / 10));
  }

  function clampDelay(n) {
    return Math.min(DELAY_MAX, Math.max(DELAY_MIN, Math.round(n * 10) / 10));
  }

  function loadScale() {
    const raw = localStorage.getItem(SCALE_KEY);
    if (raw === null) return 1;
    const n = Number(raw);
    return Number.isFinite(n) ? clampScale(n) : 1;
  }

  function loadDelay() {
    const raw = localStorage.getItem(BOT_DELAY_KEY);
    if (raw === null) return DELAY_DEFAULT;
    const n = Number(raw);
    return Number.isFinite(n) ? clampDelay(n) : DELAY_DEFAULT;
  }

  function formatScale(scale) {
    const pct = Math.round(scale * 100);
    if (pct === 100) return '100% (по умолчанию)';
    const delta = pct - 100;
    return `${pct}% (${delta > 0 ? '+' : ''}${delta}%)`;
  }

  function formatDelay(sec) {
    const s = sec.toFixed(1);
    if (Math.abs(sec - DELAY_DEFAULT) < 1e-9) return `${s} с (по умолчанию)`;
    return `${s} с`;
  }

  let scale = loadScale();
  let botDelay = loadDelay();

  function persist(key, value) {
    try {
      localStorage.setItem(key, String(value));
    } catch (_) {
      /* ignore quota / private mode */
    }
  }

  function applyScale(next) {
    scale = clampScale(next);
    document.documentElement.style.setProperty('--tile-scale', String(scale));
    persist(SCALE_KEY, scale);

    const label = document.getElementById('tileSizeValue');
    if (label) label.textContent = formatScale(scale);

    document.querySelectorAll('[data-tile-size]').forEach((btn) => {
      const dir = btn.getAttribute('data-tile-size');
      if (dir === 'dec' || dir === '-1') {
        btn.disabled = scale <= SCALE_MIN + 1e-9;
      } else if (dir === 'inc' || dir === '+1') {
        btn.disabled = scale >= SCALE_MAX - 1e-9;
      }
    });
  }

  function applyDelay(next) {
    botDelay = clampDelay(next);
    persist(BOT_DELAY_KEY, botDelay);

    const label = document.getElementById('botDelayValue');
    if (label) label.textContent = formatDelay(botDelay);

    document.querySelectorAll('[data-bot-delay]').forEach((btn) => {
      const dir = btn.getAttribute('data-bot-delay');
      if (dir === 'dec' || dir === '-1') {
        btn.disabled = botDelay <= DELAY_MIN + 1e-9;
      } else if (dir === 'inc' || dir === '+1') {
        btn.disabled = botDelay >= DELAY_MAX - 1e-9;
      }
    });
  }

  function onSettingsClick(e) {
    const tileBtn = e.target.closest('[data-tile-size]');
    if (tileBtn && !tileBtn.disabled) {
      e.preventDefault();
      e.stopPropagation();
      const dir = tileBtn.getAttribute('data-tile-size');
      const delta = dir === 'inc' || dir === '+1' ? 1 : -1;
      applyScale(scale + delta * SCALE_STEP);
      return;
    }

    const delayBtn = e.target.closest('[data-bot-delay]');
    if (delayBtn && !delayBtn.disabled) {
      e.preventDefault();
      e.stopPropagation();
      const dir = delayBtn.getAttribute('data-bot-delay');
      const delta = dir === 'inc' || dir === '+1' ? 1 : -1;
      applyDelay(botDelay + delta * DELAY_STEP);
    }
  }

  applyScale(scale);
  applyDelay(botDelay);

  // Делегирование: работает даже если кнопки в выпадающем меню
  document.addEventListener('click', onSettingsClick, true);

  window.MahjongSettings = {
    getBotDelaySec: () => botDelay,
    getBotDelayMs: () => Math.round(botDelay * 1000),
  };
})();
