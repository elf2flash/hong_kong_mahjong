(() => {
  const windNames = { 1: '東 East', 2: '南 South', 3: '西 West', 4: '北 North' };
  const phaseNames = {
    draw: 'Добор',
    discard: 'Ваш ход — перетащите плитку на «Сбросы»',
    claim: 'Можно взять сброс',
    ended: 'Раздача окончена',
  };

  let state = null;
  let selectedId = null;
  let busy = false;
  let botsRunning = false;
  /** @type {number | null} */
  let thinkingSeat = null;
  let botLoopGen = 0;

  function sleep(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
  }

  function botDelayMs() {
    return window.MahjongSettings?.getBotDelayMs?.() ?? 300;
  }

  function needsBot() {
    return !!(state && state.awaitingBot && state.phase !== 'ended');
  }

  function isInputLocked() {
    return busy || botsRunning;
  }

  async function runBotLoop() {
    const gen = ++botLoopGen;
    botsRunning = true;
    try {
      while (needsBot() && gen === botLoopGen) {
        thinkingSeat = state.botActorId != null ? state.botActorId : state.currentPlayer;
        render();
        const ms = botDelayMs();
        if (ms > 0) {
          await sleep(ms);
        }
        if (gen !== botLoopGen) return;
        try {
          const data = await api('botStep');
          if (data.state) state = data.state;
        } catch (e) {
          console.error(e);
          break;
        }
        thinkingSeat = null;
        render();
      }
    } finally {
      if (gen === botLoopGen) {
        thinkingSeat = null;
        botsRunning = false;
      }
    }
  }

  async function applyState(data) {
    if (data && data.state) state = data.state;
    thinkingSeat = null;
    render();
    if (needsBot()) {
      await runBotLoop();
    }
  }

  /** @type {{ tileId: number, source: HTMLElement, ghost: HTMLElement | null, startX: number, startY: number, moved: boolean } | null} */
  let drag = null;

  function isDiscardTurn() {
    return !!state && state.phase === 'discard' && state.currentPlayer === 0;
  }

  function canDiscardNow() {
    return !isInputLocked() && isDiscardTurn();
  }

  function getDiscardZone() {
    return document.querySelector('.discard-field-center');
  }

  function endDrag(dropped) {
    const zone = getDiscardZone();
    if (zone) zone.classList.remove('discard-drop-hover');
    document.body.classList.remove('dragging-tile');
    if (drag?.ghost) drag.ghost.remove();
    document.querySelectorAll('.player-hand .tile.tile-dragging').forEach((t) => {
      t.classList.remove('tile-dragging');
    });
    const tileId = drag?.tileId;
    const moved = drag?.moved;
    drag = null;
    if (dropped && moved && tileId != null && canDiscardNow()) {
      doDiscard(tileId);
    }
  }

  function onPointerMove(e) {
    if (!drag) return;
    const dx = e.clientX - drag.startX;
    const dy = e.clientY - drag.startY;
    if (!drag.moved && (Math.abs(dx) > 6 || Math.abs(dy) > 6)) {
      drag.moved = true;
      document.body.classList.add('dragging-tile');
      if (drag.source) drag.source.classList.add('tile-dragging');
      if (!drag.ghost && drag.source) {
        const ghost = drag.source.cloneNode(true);
        ghost.classList.add('tile-drag-ghost');
        ghost.style.width = `${drag.source.offsetWidth}px`;
        ghost.style.height = `${drag.source.offsetHeight}px`;
        document.body.appendChild(ghost);
        drag.ghost = ghost;
      }
    }
    if (!drag.moved || !drag.ghost) return;
    drag.ghost.style.transform = `translate(${e.clientX - drag.ghost.offsetWidth / 2}px, ${e.clientY - drag.ghost.offsetHeight / 2}px)`;
    const zone = getDiscardZone();
    if (zone) {
      const r = zone.getBoundingClientRect();
      const over =
        e.clientX >= r.left && e.clientX <= r.right && e.clientY >= r.top && e.clientY <= r.bottom;
      zone.classList.toggle('discard-drop-hover', over);
    }
  }

  function onPointerUp(e) {
    if (!drag) return;
    window.removeEventListener('pointermove', onPointerMove);
    window.removeEventListener('pointerup', onPointerUp);
    window.removeEventListener('pointercancel', onPointerUp);

    let dropped = false;
    if (drag.moved) {
      const zone = getDiscardZone();
      if (zone) {
        const r = zone.getBoundingClientRect();
        dropped =
          e.clientX >= r.left && e.clientX <= r.right && e.clientY >= r.top && e.clientY <= r.bottom;
      }
    } else {
      // короткий клик — только выделение
      selectedId = drag.tileId;
      document.querySelectorAll('.player-hand .tile.selected').forEach((t) => t.classList.remove('selected'));
      const src = document.querySelector(`.player-hand .tile[data-id="${drag.tileId}"]`);
      if (src) src.classList.add('selected');
    }
    endDrag(dropped);
  }

  function makeDiscardable(el, tileId) {
    // SVG внутри не должен перехватывать pointer
    el.querySelectorAll('svg, svg *').forEach((n) => {
      n.style.pointerEvents = 'none';
    });

    // Вешаем обработчики по фазе хода, без учёта busy —
    // иначе после insertDrawn/claim render() при busy=true оставляет плитки «мёртвыми».
    if (!isDiscardTurn()) {
      el.classList.remove('tile-draggable');
      el.style.touchAction = '';
      return;
    }

    el.classList.add('tile-draggable');
    el.style.touchAction = 'none';
    el.title = (el.title ? el.title + ' · ' : '') + 'Перетащите на «Сбросы»';

    el.addEventListener('pointerdown', (e) => {
      if (!canDiscardNow() || e.button !== 0) return;
      if (drag) return;
      e.preventDefault();
      selectedId = tileId;

      drag = {
        tileId,
        source: el,
        ghost: null,
        startX: e.clientX,
        startY: e.clientY,
        moved: false,
      };

      window.addEventListener('pointermove', onPointerMove);
      window.addEventListener('pointerup', onPointerUp);
      window.addEventListener('pointercancel', onPointerUp);
    });
  }

  async function api(action, payload = {}) {
    const res = await fetch('api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action, ...payload }),
    });
    return res.json();
  }

  function tileEl(tile, { back = false, small = false } = {}) {
    if (back) {
      const d = document.createElement('div');
      d.className = 'tile-back';
      return d;
    }
    const el = document.createElement('div');
    el.className = 'tile' + (small ? ' tile-sm' : '');
    el.dataset.id = tile.id;
    el.title = tile.latin || '';
    el.setAttribute('role', 'img');
    el.appendChild(window.MahjongTiles.createFace(tile));
    return el;
  }

  function clear(node) {
    while (node.firstChild) node.removeChild(node.firstChild);
  }

  function renderRiver() {
    const el = document.getElementById('tableDiscards');
    if (!el) return;
    clear(el);
    const river = state.river || [];
    const lastId = state.lastDiscard ? state.lastDiscard.id : null;
    river.forEach((entry) => {
      const tile = entry.tile || entry;
      const node = tileEl(tile);
      if (lastId != null && tile.id === lastId) {
        node.classList.add('tile-last-discard');
      }
      el.appendChild(node);
    });
  }

  function renderWall() {
    const field = document.getElementById('wallField');
    const el = document.getElementById('wallTiles');
    const label = document.getElementById('wallFieldLabel');
    if (!field || !el) return;
    clear(el);
    field.classList.remove('hidden');

    const ended = state.phase === 'ended';
    const openTiles = ended && Array.isArray(state.wallTiles) ? state.wallTiles : null;

    if (label) {
      label.textContent = ended ? 'Остатки стены' : 'Стена';
    }

    if (openTiles) {
      openTiles.forEach((t) => el.appendChild(tileEl(t)));
      return;
    }

    // Во время партии — только рубашки (лица не раскрываем)
    const n = Math.max(0, Number(state.wallCount ?? state.wallRemaining) || 0);
    for (let i = 0; i < n; i++) {
      el.appendChild(tileEl(null, { back: true }));
    }
  }

  function renderSeat(seatEl, player, isCurrent) {
    const winnerId = state.result && state.result.winner != null ? state.result.winner : null;
    const isWinner = winnerId === player.id;
    seatEl.classList.toggle('active', isCurrent);
    seatEl.classList.toggle('is-winner', isWinner);
    seatEl.querySelector('.seat-name').textContent =
      player.name + (player.isDealer ? ' 莊' : '') + (isWinner ? ' 和' : '');
    seatEl.querySelector('.seat-score').textContent = player.score + ' pts';

    const meldsEl = seatEl.querySelector('.melds');
    clear(meldsEl);
    (player.melds || []).forEach((meld) => {
      const g = document.createElement('div');
      g.className = 'meld-group';
      meld.tiles.forEach((t) => g.appendChild(tileEl(t, { small: true })));
      meldsEl.appendChild(g);
    });

    const isThinking = thinkingSeat === player.id;
    const isHumanTurn =
      player.isHuman &&
      thinkingSeat == null &&
      state.phase !== 'ended' &&
      (
        ((state.phase === 'draw' || state.phase === 'discard') && state.currentPlayer === player.id) ||
        (state.phase === 'claim' && !!state.claimOptions)
      );
    const turnGlow = isThinking || isHumanTurn;

    // Ширина поля комбинаций: минимум 3 плитки, дальше по содержимому
    const meldField = seatEl.querySelector('.meld-field');
    if (meldField) {
      const melds = player.melds || [];
      let tileCount = 0;
      melds.forEach((m) => {
        tileCount += (m.tiles || []).length;
      });
      const gaps = Math.max(0, melds.length - 1);
      const cols = Math.max(3, tileCount + gaps);
      meldField.style.setProperty('--meld-cols', String(cols));
      meldField.classList.toggle('has-melds', melds.length > 0);
      meldField.classList.toggle('winner-field', isWinner);
      meldField.classList.toggle('turn-glow', turnGlow);
    }

    const handField = seatEl.querySelector('.hand-field');
    const handEl = seatEl.querySelector('.hand');
    clear(handEl);
    if (handField) {
      handField.classList.toggle('winner-field', isWinner);
      handField.classList.toggle('turn-glow', turnGlow);
    }

    let handCols = 0;
    const revealHand = player.hand && player.hand.length > 0;
    const interactive = player.isHuman && state.phase !== 'ended';

    if (revealHand) {
      const drawnId = interactive ? state.pendingDrawnId : null;
      const drawn = drawnId != null ? player.hand.find((t) => t.id === drawnId) : null;
      const main = drawn ? player.hand.filter((t) => t.id !== drawnId) : player.hand;

      main.forEach((t) => {
        const el = tileEl(t);
        if (interactive) {
          if (selectedId === t.id) el.classList.add('selected');
          makeDiscardable(el, t.id);
        }
        handEl.appendChild(el);
      });

      handCols = main.length;

      if (drawn && interactive) {
        const slot = document.createElement('div');
        slot.className = 'drawn-slot';

        const arrow = document.createElement('button');
        arrow.type = 'button';
        arrow.className = 'drawn-arrow';
        arrow.title = 'Добавить плитку в руку';
        arrow.setAttribute('aria-label', 'Добавить плитку в руку');
        arrow.addEventListener('click', (e) => {
          e.stopPropagation();
          insertDrawn();
        });

        const drawnEl = tileEl(drawn);
        drawnEl.classList.add('tile-drawn');
        if (selectedId === drawn.id) drawnEl.classList.add('selected');
        makeDiscardable(drawnEl, drawn.id);

        slot.append(arrow, drawnEl);
        handEl.appendChild(slot);
        handCols += 2;
      }

      handEl.classList.toggle('can-discard', interactive && state.phase === 'discard' && state.currentPlayer === 0);
      handEl.classList.toggle('has-drawn', !!(drawn && interactive));
    } else {
      const n = player.handCount || 0;
      handCols = n;
      for (let i = 0; i < n; i++) {
        handEl.appendChild(tileEl(null, { back: true }));
      }
    }

    if (handField) {
      const isSide = seatEl.classList.contains('seat-left') || seatEl.classList.contains('seat-right');
      if (isSide) {
        // Север / Юг: фиксированная сетка 4×4 обычных плиток
        handField.style.setProperty('--hand-cols', '4');
        handField.style.setProperty('--hand-rows', '4');
        handEl.classList.toggle('back-hand', !revealHand);
      } else {
        handField.style.setProperty('--hand-cols', String(Math.max(1, handCols)));
      }
    }
  }

  function render() {
    if (!state) return;

    document.getElementById('roundWind').textContent = 'Круг: ' + windNames[state.roundWind];
    document.getElementById('wallCount').textContent = 'Стена: ' + state.wallRemaining;
    document.getElementById('phaseLabel').textContent =
      thinkingSeat != null
        ? 'Бот думает…'
        : state.phase === 'discard' && state.currentPlayer !== 0
          ? 'Ход соперника…'
          : phaseNames[state.phase] || state.phase;

    // seats: 0 bottom, 1 right, 2 top, 3 left
    document.querySelectorAll('.seat').forEach((seatEl) => {
      const idx = Number(seatEl.dataset.seat);
      const player = state.players[idx];
      if (player) renderSeat(seatEl, player, state.currentPlayer === idx);
    });

    renderRiver();
    renderWall();

    const claimPanel = document.getElementById('claimPanel');
    clear(claimPanel);
    if (state.phase === 'claim' && state.claimOptions) {
      claimPanel.classList.remove('hidden');
      const opts = state.claimOptions;
      const addBtn = (label, claim, extra = {}) => {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'btn btn-claim';
        b.textContent = label;
        b.addEventListener('click', () => doClaim(claim, extra.chowOption));
        claimPanel.appendChild(b);
      };
      if (opts.win) addBtn('胡 Махджонг', 'win');
      if (opts.kong) addBtn('槓 Конг', 'kong');
      if (opts.pung) addBtn('碰 Понг', 'pung');
      if (opts.chow && opts.chowOptions) {
        opts.chowOptions.forEach((_, i) => {
          addBtn(opts.chowOptions.length > 1 ? `吃 Чоу #${i + 1}` : '吃 Чоу', 'chow', {
            chowOption: i,
          });
        });
      }
      const pass = document.createElement('button');
      pass.type = 'button';
      pass.className = 'btn btn-pass';
      pass.textContent = 'Пас';
      pass.addEventListener('click', () => doClaim('pass'));
      claimPanel.appendChild(pass);
    } else {
      claimPanel.classList.add('hidden');
    }

    const winBtn = document.getElementById('btnWin');
    if (state.canWin) {
      winBtn.classList.remove('hidden');
    } else {
      winBtn.classList.add('hidden');
    }

    const resultPanel = document.getElementById('resultPanel');
    if (state.phase === 'ended' && state.result) {
      resultPanel.classList.remove('hidden');
      clear(resultPanel);
      const r = state.result;
      const h = document.createElement('h3');
      if (r.draw) {
        h.textContent = '流局 Ничья';
        resultPanel.appendChild(h);
        const p = document.createElement('p');
        p.textContent = r.message || '';
        resultPanel.appendChild(p);
      } else {
        h.textContent = r.winnerName + ' — 和了!';
        resultPanel.appendChild(h);
        const fan = document.createElement('p');
        fan.textContent = `${r.fan} фан` + (r.selfDraw ? ' · 自摸' : '');
        resultPanel.appendChild(fan);
        if (r.details && r.details.length) {
          const ul = document.createElement('ul');
          r.details.forEach((d) => {
            const li = document.createElement('li');
            li.textContent = d;
            ul.appendChild(li);
          });
          resultPanel.appendChild(ul);
        }
        if (r.payments) {
          r.payments.forEach((p) => {
            const line = document.createElement('p');
            line.style.fontSize = '0.8rem';
            line.textContent = p;
            resultPanel.appendChild(line);
          });
        }
      }
      const again = document.createElement('button');
      again.type = 'button';
      again.className = 'btn';
      again.textContent = 'Следующая раздача';
      again.addEventListener('click', () => newGame(true));
      resultPanel.appendChild(again);
    } else {
      resultPanel.classList.add('hidden');
    }

    const log = document.getElementById('log');
    clear(log);
    (state.log || []).slice().reverse().forEach((line) => {
      const li = document.createElement('li');
      li.textContent = line;
      log.appendChild(li);
    });
  }

  async function doDiscard(tileId) {
    if (isInputLocked()) return;
    busy = true;
    try {
      const data = await api('discard', { tileId });
      if (!data.ok) {
        alert(data.error || 'Ошибка сброса');
      }
      selectedId = null;
      await applyState(data);
    } finally {
      busy = false;
    }
  }

  async function insertDrawn() {
    if (isInputLocked() || !state?.pendingDrawnId) return;
    busy = true;
    try {
      const data = await api('insertDrawn');
      if (!data.ok) {
        alert(data.error || 'Ошибка');
      }
      await applyState(data);
    } finally {
      busy = false;
    }
  }

  async function doClaim(claim, chowOption = 0) {
    if (isInputLocked()) return;
    busy = true;
    try {
      const data = await api('claim', { claim, chowOption });
      if (!data.ok) alert(data.error || 'Ошибка');
      await applyState(data);
    } finally {
      busy = false;
    }
  }

  async function doWin() {
    if (isInputLocked()) return;
    busy = true;
    try {
      const data = await api('win');
      if (!data.ok) alert(data.error || 'Нельзя выиграть сейчас');
      await applyState(data);
    } finally {
      busy = false;
    }
  }

  async function newGame(keepScores = false) {
    botLoopGen += 1;
    thinkingSeat = null;
    botsRunning = false;
    busy = true;
    try {
      const data = await api('new', { keepScores });
      selectedId = null;
      await applyState(data);
    } finally {
      busy = false;
    }
  }

  async function load() {
    const data = await api('state');
    await applyState(data);
  }

  document.getElementById('btnNew').addEventListener('click', () => newGame(false));
  document.getElementById('btnWin').addEventListener('click', doWin);

  load().catch((e) => {
    console.error(e);
    document.getElementById('phaseLabel').textContent = 'Ошибка загрузки. Запустите PHP-сервер.';
  });
})();
