(() => {
  const STORAGE_KEY = 'hk-mahjong-tile-scale';
  const STEP = 0.1;
  const MIN = 0.5;
  const MAX = 1.5;

  function clamp(n) {
    return Math.min(MAX, Math.max(MIN, Math.round(n * 10) / 10));
  }

  function loadScale() {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (raw === null) return 1;
    const n = Number(raw);
    return Number.isFinite(n) ? clamp(n) : 1;
  }

  function formatLabel(scale) {
    const pct = Math.round(scale * 100);
    if (pct === 100) return '100% (по умолчанию)';
    const delta = pct - 100;
    return `${pct}% (${delta > 0 ? '+' : ''}${delta}%)`;
  }

  let scale = loadScale();

  function applyScale(next) {
    scale = clamp(next);
    document.documentElement.style.setProperty('--tile-scale', String(scale));
    try {
      localStorage.setItem(STORAGE_KEY, String(scale));
    } catch (_) {
      /* ignore quota / private mode */
    }

    document.querySelectorAll('.tile-size-value').forEach((el) => {
      el.textContent = formatLabel(scale);
    });
    document.querySelectorAll('[data-tile-size="-1"]').forEach((btn) => {
      btn.disabled = scale <= MIN + 1e-9;
    });
    document.querySelectorAll('[data-tile-size="+1"]').forEach((btn) => {
      btn.disabled = scale >= MAX - 1e-9;
    });
  }

  function onSizeClick(e) {
    const btn = e.target.closest('[data-tile-size]');
    if (!btn || btn.disabled) return;
    e.preventDefault();
    e.stopPropagation();
    const dir = btn.getAttribute('data-tile-size') === '+1' ? 1 : -1;
    applyScale(scale + dir * STEP);
  }

  applyScale(scale);

  document.querySelectorAll('.btn-size[data-tile-size]').forEach((btn) => {
    btn.addEventListener('click', onSizeClick);
  });
})();
