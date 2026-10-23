/**
 * Классические лица плиток (стиль Western / HK table):
 * символ по центру + латинская метка в правом верхнем углу.
 */
window.MahjongTiles = (() => {
  const NUM = ['', '一', '二', '三', '四', '五', '六', '七', '八', '九'];
  const WINDS = { 1: '東', 2: '南', 3: '西', 4: '北' };
  const WIND_L = { 1: 'E', 2: 'S', 3: 'W', 4: 'N' };
  const DRAGONS = { 1: '中', 2: '發', 3: '白' };
  const DRAGON_L = { 1: 'C', 2: 'F', 3: 'P' };

  function corner(label) {
    return `<text class="t-corner" x="46" y="14" text-anchor="end">${label}</text>`;
  }

  function circleDot(cx, cy, r = 5.2, center = 'blue') {
    const outer = center === 'red' ? '#1a4a9c' : '#1a4a9c';
    const mid = center === 'red' ? '#c41e3a' : '#1a4a9c';
    const petal = center === 'red' ? '#c41e3a' : '#2a5aad';
    return `
      <circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="${outer}" stroke-width="1.6"/>
      <circle cx="${cx}" cy="${cy}" r="${r * 0.55}" fill="none" stroke="${petal}" stroke-width="1.1"/>
      <circle cx="${cx}" cy="${cy}" r="${r * 0.22}" fill="${mid}"/>
    `;
  }

  /** Классическая «катушка» бамбука: две стойки + перекладины */
  function bambooStick(x, y, h = 18, green = true) {
    const c = green ? '#1a6b3a' : '#c41e3a';
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

  function pinFace(n) {
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
    const dots = (layouts[n] || []).map(([x, y, r, c]) => {
      if (c === 'green') {
        return `
          <circle cx="${x}" cy="${y}" r="${r}" fill="none" stroke="#1a6b3a" stroke-width="1.6"/>
          <circle cx="${x}" cy="${y}" r="${r * 0.55}" fill="none" stroke="#2a8a4a" stroke-width="1.1"/>
          <circle cx="${x}" cy="${y}" r="${r * 0.22}" fill="#1a6b3a"/>
        `;
      }
      return circleDot(x, y, r, c);
    }).join('');
    return dots;
  }

  function souFace(n) {
    // Traditional bamboo layouts
    if (n === 1) {
      // Bird / single peacock bamboo often shown as ornate stick — simplified green stick with red tip
      return `
        ${bambooStick(26, 16, 40)}
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
    return (layouts[n] || []).map(([x, y, h], i) => {
      // 1-bamboo style: first stick of 2 sometimes red-top — keep all green for clarity
      return bambooStick(x, y, h, true);
    }).join('');
  }

  function manFace(n) {
    return `
      <text class="t-man-num" x="26" y="34" text-anchor="middle">${NUM[n]}</text>
      <text class="t-man-wan" x="26" y="56" text-anchor="middle">萬</text>
    `;
  }

  function windFace(n) {
    return `<text class="t-honor" x="26" y="44" text-anchor="middle">${WINDS[n]}</text>`;
  }

  function dragonFace(n) {
    if (n === 1) {
      return `<text class="t-dragon-red" x="26" y="44" text-anchor="middle">中</text>`;
    }
    if (n === 2) {
      return `<text class="t-dragon-green" x="26" y="44" text-anchor="middle">發</text>`;
    }
    // White dragon — framed blank
    return `
      <rect x="12" y="18" width="28" height="38" rx="2" fill="none" stroke="#1a4a9c" stroke-width="2.2"/>
      <rect x="16" y="22" width="20" height="30" rx="1" fill="none" stroke="#1a4a9c" stroke-width="1.2"/>
    `;
  }

  function labelFor(tile) {
    if (tile.suit === 'wind') return WIND_L[tile.value] || '';
    if (tile.suit === 'dragon') return DRAGON_L[tile.value] || '';
    return String(tile.value);
  }

  function faceSvg(tile) {
    let body = '';
    if (tile.suit === 'man') body = manFace(tile.value);
    else if (tile.suit === 'pin') body = pinFace(tile.value);
    else if (tile.suit === 'sou') body = souFace(tile.value);
    else if (tile.suit === 'wind') body = windFace(tile.value);
    else if (tile.suit === 'dragon') body = dragonFace(tile.value);

    return `
      <svg class="tile-face-svg" viewBox="0 0 52 68" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        ${corner(labelFor(tile))}
        ${body}
      </svg>
    `;
  }

  function createFace(tile) {
    const wrap = document.createElement('div');
    wrap.className = 'tile-face';
    wrap.innerHTML = faceSvg(tile);
    return wrap;
  }

  return { createFace, labelFor, faceSvg };
})();
