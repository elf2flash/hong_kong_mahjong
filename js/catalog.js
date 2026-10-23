(() => {
  let id = 0;

  function makeTile(suit, value) {
    return {
      id: id++,
      suit,
      value,
      key: `${suit}-${value}`,
      latin:
        suit === 'wind'
          ? { 1: 'E', 2: 'S', 3: 'W', 4: 'N' }[value]
          : suit === 'dragon'
            ? { 1: 'C', 2: 'F', 3: 'P' }[value]
            : `${value}${suit[0]}`,
    };
  }

  function tileCard(tile, caption) {
    const card = document.createElement('div');
    card.className = 'tile-card';

    const btn = document.createElement('div');
    btn.className = 'tile';
    btn.appendChild(window.MahjongTiles.createFace(tile));

    const cap = document.createElement('span');
    cap.className = 'tile-caption';
    cap.textContent = caption;

    card.append(btn, cap);
    return card;
  }

  const names = {
    man: (v) => `${v}萬 · ${v}m`,
    pin: (v) => `${v}筒 · ${v}p`,
    sou: (v) => `${v}索 · ${v}s`,
    wind: (v) => ({ 1: '東 East · E', 2: '南 South · S', 3: '西 West · W', 4: '北 North · N' }[v]),
    dragon: (v) => ({ 1: '中 Red · C', 2: '發 Green · F', 3: '白 White · P' }[v]),
  };

  document.querySelectorAll('.tile-row').forEach((row) => {
    const suit = row.dataset.suit;
    const from = Number(row.dataset.from);
    const to = Number(row.dataset.to);
    for (let v = from; v <= to; v++) {
      const tile = makeTile(suit, v);
      row.appendChild(tileCard(tile, names[suit](v)));
    }
  });
})();
