/**
 * Лица плиток: вариант A (текущий) и вариант B (классическая цветная таблица).
 */
window.MahjongTiles = (() => {
  const NUM = ['', '一', '二', '三', '四', '五', '六', '七', '八', '九'];
  const WINDS = { 1: '東', 2: '南', 3: '西', 4: '北' };
  const WIND_L = { 1: 'E', 2: 'S', 3: 'W', 4: 'N' };
  const DRAGONS = { 1: '中', 2: '發', 3: '白' };
  const DRAGON_L = { 1: 'C', 2: 'F', 3: 'P' };

  let currentStyle = 'a';

  function corner(label) {
    return `<text class="t-corner" x="46" y="14" text-anchor="end">${label}</text>`;
  }

  function labelFor(tile) {
    if (tile.suit === 'wind') return WIND_L[tile.value] || '';
    if (tile.suit === 'dragon') return DRAGON_L[tile.value] || '';
    return String(tile.value);
  }

  // ——— Variant A (current) ———

  function circleDotA(cx, cy, r = 5.2, center = 'blue') {
    const mid = center === 'red' ? '#c41e3a' : '#1a4a9c';
    const petal = center === 'red' ? '#c41e3a' : '#2a5aad';
    return `
      <circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="#1a4a9c" stroke-width="1.6"/>
      <circle cx="${cx}" cy="${cy}" r="${r * 0.55}" fill="none" stroke="${petal}" stroke-width="1.1"/>
      <circle cx="${cx}" cy="${cy}" r="${r * 0.22}" fill="${mid}"/>
    `;
  }

  function bambooStickA(x, y, h = 18) {
    const c = '#1a6b3a';
    const top = y;
    const bot = y + h;
    const mid = y + h * 0.5;
    const w = 3.1;
    return `
      <path d="M${x - w} ${top} L${x - w} ${bot} M${x + w} ${top} L${x + w} ${bot}"
            fill="none" stroke="${c}" stroke-width="1.85" stroke-linecap="round"/>
      <line x1="${x - w}" y1="${top}" x2="${x + w}" y2="${top}" stroke="${c}" stroke-width="1.85" stroke-linecap="round"/>
      <line x1="${x - w}" y1="${mid}" x2="${x + w}" y2="${mid}" stroke="${c}" stroke-width="1.7" stroke-linecap="round"/>
      <line x1="${x - w}" y1="${bot}" x2="${x + w}" y2="${bot}" stroke="${c}" stroke-width="1.85" stroke-linecap="round"/>
    `;
  }

  function pinFaceA(n) {
    const layouts = {
      1: [[26, 36, 9, 'red']],
      2: [[26, 22, 6.2, 'blue'], [26, 50, 6.2, 'green']],
      3: [[14, 20, 5.5, 'blue'], [26, 36, 5.5, 'red'], [38, 52, 5.5, 'blue']],
      4: [[16, 22, 5.5, 'blue'], [36, 22, 5.5, 'blue'], [16, 50, 5.5, 'blue'], [36, 50, 5.5, 'blue']],
      5: [[16, 20, 5.2, 'blue'], [36, 20, 5.2, 'blue'], [26, 36, 5.5, 'red'], [16, 52, 5.2, 'blue'], [36, 52, 5.2, 'blue']],
      6: [[16, 18, 5, 'blue'], [36, 18, 5, 'blue'], [16, 36, 5, 'red'], [36, 36, 5, 'red'], [16, 54, 5, 'blue'], [36, 54, 5, 'blue']],
      7: [[14, 16, 4.6, 'blue'], [26, 16, 4.6, 'blue'], [38, 16, 4.6, 'blue'], [20, 34, 4.8, 'red'], [32, 34, 4.8, 'red'], [20, 52, 4.6, 'blue'], [32, 52, 4.6, 'blue']],
      8: [[16, 15, 4.4, 'blue'], [36, 15, 4.4, 'blue'], [16, 29, 4.4, 'blue'], [36, 29, 4.4, 'blue'], [16, 43, 4.4, 'blue'], [36, 43, 4.4, 'blue'], [16, 57, 4.4, 'blue'], [36, 57, 4.4, 'blue']],
      9: [[15, 15, 4.2, 'blue'], [26, 15, 4.2, 'blue'], [37, 15, 4.2, 'blue'], [15, 36, 4.2, 'red'], [26, 36, 4.2, 'red'], [37, 36, 4.2, 'red'], [15, 57, 4.2, 'blue'], [26, 57, 4.2, 'blue'], [37, 57, 4.2, 'blue']],
    };
    return (layouts[n] || [])
      .map(([x, y, r, c]) => {
        if (c === 'green') {
          return `
            <circle cx="${x}" cy="${y}" r="${r}" fill="none" stroke="#1a6b3a" stroke-width="1.6"/>
            <circle cx="${x}" cy="${y}" r="${r * 0.55}" fill="none" stroke="#2a8a4a" stroke-width="1.1"/>
            <circle cx="${x}" cy="${y}" r="${r * 0.22}" fill="#1a6b3a"/>
          `;
        }
        return circleDotA(x, y, r, c);
      })
      .join('');
  }

  function souFaceA(n) {
    if (n === 1) {
      return `
        ${bambooStickA(26, 16, 40)}
        <circle cx="26" cy="14" r="3.2" fill="#c41e3a"/>
      `;
    }
    const layouts = {
      2: [[26, 14, 20], [26, 38, 20]],
      3: [[26, 12, 18], [16, 40, 18], [36, 40, 18]],
      4: [[16, 14, 18], [36, 14, 18], [16, 40, 18], [36, 40, 18]],
      5: [[16, 12, 16], [36, 12, 16], [26, 28, 16], [16, 44, 16], [36, 44, 16]],
      6: [[16, 12, 16], [36, 12, 16], [16, 30, 16], [36, 30, 16], [16, 48, 16], [36, 48, 16]],
      7: [[26, 10, 14], [14, 28, 14], [26, 28, 14], [38, 28, 14], [14, 48, 14], [26, 48, 14], [38, 48, 14]],
      8: [[14, 10, 14], [26, 10, 14], [38, 10, 14], [20, 28, 14], [32, 28, 14], [14, 46, 14], [26, 46, 14], [38, 46, 14]],
      9: [[14, 10, 14], [26, 10, 14], [38, 10, 14], [14, 30, 14], [26, 30, 14], [38, 30, 14], [14, 50, 14], [26, 50, 14], [38, 50, 14]],
    };
    return (layouts[n] || []).map(([x, y, h]) => bambooStickA(x, y, h)).join('');
  }

  function manFaceA(n) {
    return `
      <text class="t-man-num" x="26" y="34" text-anchor="middle">${NUM[n]}</text>
      <text class="t-man-wan" x="26" y="56" text-anchor="middle">萬</text>
    `;
  }

  function windFaceA(n) {
    return `<text class="t-honor" x="26" y="44" text-anchor="middle">${WINDS[n]}</text>`;
  }

  function dragonFaceA(n) {
    if (n === 1) return `<text class="t-dragon-red" x="26" y="44" text-anchor="middle">中</text>`;
    if (n === 2) return `<text class="t-dragon-green" x="26" y="44" text-anchor="middle">發</text>`;
    return `
      <rect x="12" y="18" width="28" height="38" rx="2" fill="none" stroke="#1a4a9c" stroke-width="2.2"/>
      <rect x="16" y="22" width="20" height="30" rx="1" fill="none" stroke="#1a4a9c" stroke-width="1.2"/>
    `;
  }

  // ——— Variant B (photo set) ———

  function pinDotB(cx, cy, r, color) {
    const pal = {
      blue: { o: '#1e4fbf', m: '#3a78e8', i: '#163a96' },
      green: { o: '#1f8a3c', m: '#3cb85a', i: '#146b2c' },
      red: { o: '#c41e3a', m: '#e0455c', i: '#9a1528' },
    }[color] || { o: '#1e4fbf', m: '#3a78e8', i: '#163a96' };
    return `
      <circle cx="${cx}" cy="${cy}" r="${r}" fill="${pal.o}"/>
      <circle cx="${cx}" cy="${cy}" r="${r * 0.72}" fill="none" stroke="#fff" stroke-width="1.1" opacity="0.85"/>
      <circle cx="${cx}" cy="${cy}" r="${r * 0.48}" fill="${pal.m}"/>
      <circle cx="${cx}" cy="${cy}" r="${r * 0.22}" fill="${pal.i}"/>
    `;
  }

  function pinFaceB(n) {
    if (n === 1) {
      return `
        <circle cx="26" cy="36" r="14.5" fill="#1f8a3c"/>
        <circle cx="26" cy="36" r="11.5" fill="#3cb85a"/>
        <circle cx="26" cy="36" r="8.2" fill="#1e4fbf"/>
        <circle cx="26" cy="36" r="5.4" fill="#3a78e8"/>
        <circle cx="26" cy="36" r="2.6" fill="#163a96"/>
        <circle cx="26" cy="36" r="13.2" fill="none" stroke="#fff" stroke-width="1.2" opacity="0.55"/>
      `;
    }
    const layouts = {
      2: [[26, 22, 7.2, 'green'], [26, 50, 7.2, 'blue']],
      3: [[14, 20, 6.2, 'blue'], [26, 36, 6.2, 'red'], [38, 52, 6.2, 'blue']],
      4: [[16, 22, 6.2, 'blue'], [36, 22, 6.2, 'blue'], [16, 50, 6.2, 'blue'], [36, 50, 6.2, 'blue']],
      5: [[16, 20, 5.8, 'blue'], [36, 20, 5.8, 'blue'], [26, 36, 6.2, 'red'], [16, 52, 5.8, 'blue'], [36, 52, 5.8, 'blue']],
      6: [[16, 18, 5.5, 'green'], [36, 18, 5.5, 'green'], [16, 36, 5.5, 'red'], [36, 36, 5.5, 'red'], [16, 54, 5.5, 'blue'], [36, 54, 5.5, 'blue']],
      7: [[14, 16, 5, 'green'], [26, 16, 5, 'green'], [38, 16, 5, 'green'], [16, 36, 5.2, 'red'], [36, 36, 5.2, 'red'], [16, 54, 5.2, 'red'], [36, 54, 5.2, 'blue']],
      8: [[16, 15, 4.8, 'blue'], [36, 15, 4.8, 'blue'], [16, 29, 4.8, 'green'], [36, 29, 4.8, 'green'], [16, 43, 4.8, 'red'], [36, 43, 4.8, 'red'], [16, 57, 4.8, 'blue'], [36, 57, 4.8, 'blue']],
      9: [[15, 15, 4.6, 'green'], [26, 15, 4.6, 'green'], [37, 15, 4.6, 'green'], [15, 36, 4.6, 'red'], [26, 36, 4.6, 'red'], [37, 36, 4.6, 'red'], [15, 57, 4.6, 'blue'], [26, 57, 4.6, 'blue'], [37, 57, 4.6, 'blue']],
    };
    return (layouts[n] || []).map(([x, y, r, c]) => pinDotB(x, y, r, c)).join('');
  }

  function bambooStickB(x, y, h = 18, green = true) {
    const c = green ? '#1f8a3c' : '#c41e3a';
    const light = green ? '#4ec96a' : '#e86a7a';
    const w = 3.4;
    const top = y;
    const bot = y + h;
    const mid = y + h * 0.5;
    return `
      <rect x="${x - w}" y="${top}" width="${w * 2}" height="${h}" rx="1.2" fill="${c}"/>
      <rect x="${x - w + 0.7}" y="${top + 1}" width="1.4" height="${h - 2}" rx="0.6" fill="${light}" opacity="0.55"/>
      <line x1="${x - w}" y1="${top}" x2="${x + w}" y2="${top}" stroke="#0f5a28" stroke-width="1.2" opacity="0.45"/>
      <line x1="${x - w}" y1="${mid}" x2="${x + w}" y2="${mid}" stroke="#fff" stroke-width="1.1" opacity="0.35"/>
      <line x1="${x - w}" y1="${bot}" x2="${x + w}" y2="${bot}" stroke="#0f5a28" stroke-width="1.2" opacity="0.45"/>
    `;
  }

  function birdB() {
    // Simplified sparrow for 1-bamboo
    return `
      <ellipse cx="26" cy="40" rx="9" ry="7" fill="#2f6fb0"/>
      <ellipse cx="30" cy="38" rx="6.5" ry="5.2" fill="#3f86c9"/>
      <circle cx="34.5" cy="30" r="5.2" fill="#c9a46a"/>
      <circle cx="36.2" cy="28.8" r="1.1" fill="#1a1a1a"/>
      <path d="M38.8 30.5 L43.5 31.5 L38.8 33" fill="#e07a2a"/>
      <path d="M18 38 Q12 28 20 26 Q24 32 22 38 Z" fill="#2a8a4a"/>
      <path d="M18 42 Q14 50 22 52 Q24 46 22 42 Z" fill="#1f6b3a"/>
      <ellipse cx="24" cy="48" rx="3.2" ry="1.6" fill="#c41e3a"/>
      <path d="M16 52 Q26 58 36 52" fill="none" stroke="#1f8a3c" stroke-width="2" stroke-linecap="round"/>
    `;
  }

  function souFaceB(n) {
    if (n === 1) return birdB();
    const layouts = {
      2: { sticks: [[26, 14, 20, true], [26, 38, 20, true]] },
      3: { sticks: [[26, 12, 18, true], [16, 40, 18, true], [36, 40, 18, true]] },
      4: { sticks: [[16, 14, 18, true], [36, 14, 18, true], [16, 40, 18, true], [36, 40, 18, true]] },
      5: {
        sticks: [
          [16, 12, 16, true],
          [36, 12, 16, true],
          [26, 28, 16, false],
          [16, 44, 16, true],
          [36, 44, 16, true],
        ],
      },
      6: {
        sticks: [
          [16, 12, 16, true],
          [36, 12, 16, true],
          [16, 30, 16, true],
          [36, 30, 16, true],
          [16, 48, 16, true],
          [36, 48, 16, true],
        ],
      },
      7: {
        sticks: [
          [26, 10, 14, false],
          [14, 28, 14, true],
          [26, 28, 14, true],
          [38, 28, 14, true],
          [14, 48, 14, true],
          [26, 48, 14, true],
          [38, 48, 14, true],
        ],
      },
      8: {
        sticks: [
          [14, 10, 14, true],
          [26, 10, 14, true],
          [38, 10, 14, true],
          [20, 28, 14, true],
          [32, 28, 14, true],
          [14, 46, 14, true],
          [26, 46, 14, true],
          [38, 46, 14, true],
        ],
      },
      9: {
        sticks: [
          [14, 10, 14, true],
          [26, 10, 14, true],
          [38, 10, 14, true],
          [14, 30, 14, false],
          [26, 30, 14, false],
          [38, 30, 14, false],
          [14, 50, 14, true],
          [26, 50, 14, true],
          [38, 50, 14, true],
        ],
      },
    };
    const entry = layouts[n];
    if (!entry) return '';
    return entry.sticks.map(([x, y, h, green]) => bambooStickB(x, y, h, green)).join('');
  }

  function manFaceB(n) {
    return `
      <text class="t-man-num t-man-num-b" x="26" y="32" text-anchor="middle">${NUM[n]}</text>
      <text class="t-man-wan" x="26" y="56" text-anchor="middle">萬</text>
    `;
  }

  function windFaceB(n) {
    return `<text class="t-honor t-honor-b" x="26" y="44" text-anchor="middle">${WINDS[n]}</text>`;
  }

  function dragonFaceB(n) {
    if (n === 1) return `<text class="t-dragon-red" x="26" y="44" text-anchor="middle">中</text>`;
    if (n === 2) return `<text class="t-dragon-green" x="26" y="44" text-anchor="middle">發</text>`;
    return `
      <rect x="11" y="16" width="30" height="40" rx="2.5" fill="none" stroke="#1e4fbf" stroke-width="2.6"/>
      <rect x="15" y="20" width="22" height="32" rx="1.5" fill="none" stroke="#3a78e8" stroke-width="1.2"/>
    `;
  }

  function bodyFor(tile) {
    const n = tile.value;
    if (currentStyle === 'b') {
      if (tile.suit === 'man') return manFaceB(n);
      if (tile.suit === 'pin') return pinFaceB(n);
      if (tile.suit === 'sou') return souFaceB(n);
      if (tile.suit === 'wind') return windFaceB(n);
      if (tile.suit === 'dragon') return dragonFaceB(n);
    }
    if (tile.suit === 'man') return manFaceA(n);
    if (tile.suit === 'pin') return pinFaceA(n);
    if (tile.suit === 'sou') return souFaceA(n);
    if (tile.suit === 'wind') return windFaceA(n);
    if (tile.suit === 'dragon') return dragonFaceA(n);
    return '';
  }

  function faceSvg(tile) {
    const showCorner = currentStyle === 'a';
    return `
      <svg class="tile-face-svg" viewBox="0 0 52 68" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        ${showCorner ? corner(labelFor(tile)) : ''}
        ${bodyFor(tile)}
      </svg>
    `;
  }

  function createFace(tile) {
    const wrap = document.createElement('div');
    wrap.className = 'tile-face';
    wrap.innerHTML = faceSvg(tile);
    return wrap;
  }

  function setStyle(style) {
    currentStyle = style === 'b' ? 'b' : 'a';
    // Не использовать data-tile-style на <html> — совпадает с кнопками настроек
    // и перехватывает все клики через closest('[data-tile-style]').
    document.documentElement.dataset.mahjongTileStyle = currentStyle;
    return currentStyle;
  }

  function getStyle() {
    return currentStyle;
  }

  // default until settings apply
  document.documentElement.dataset.mahjongTileStyle = currentStyle;

  return { createFace, labelFor, faceSvg, setStyle, getStyle };
})();
