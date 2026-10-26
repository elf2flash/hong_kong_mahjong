(() => {
  let uid = 0;
  const t = (suit, value) => ({
    id: uid++,
    suit,
    value,
    key: `${suit}-${value}`,
  });

  function tileNode(tile) {
    const el = document.createElement('div');
    el.className = 'tile tip-tile';
    el.appendChild(window.MahjongTiles.createFace(tile));
    return el;
  }

  function group(tiles, label) {
    const wrap = document.createElement('div');
    wrap.className = 'tip-meld';
    if (label) {
      const cap = document.createElement('span');
      cap.className = 'tip-meld-label';
      cap.textContent = label;
      wrap.appendChild(cap);
    }
    const row = document.createElement('div');
    row.className = 'tip-meld-tiles';
    tiles.forEach((tile) => row.appendChild(tileNode(tile)));
    wrap.appendChild(row);
    return wrap;
  }

  function example(title, melds) {
    const box = document.createElement('div');
    box.className = 'tip-example';
    const h = document.createElement('h4');
    h.textContent = title;
    box.appendChild(h);
    const row = document.createElement('div');
    row.className = 'tip-example-row';
    melds.forEach((m) => row.appendChild(m));
    box.appendChild(row);
    return box;
  }

  function textBlock(html) {
    const p = document.createElement('div');
    p.className = 'tip-text';
    p.innerHTML = html;
    return p;
  }

  const CONTENT = {
    win: {
      title: '4 комбинации + пара',
      build() {
        const frag = document.createDocumentFragment();
        frag.appendChild(
          textBlock(
            'Выигрышная рука = <strong>4 комбинации</strong> (чоу / понг / конг) и <strong>одна пара</strong> одинаковых плиток. Всего 14 плиток.'
          )
        );
        frag.appendChild(
          example('Пример 1 — смешанная рука', [
            group([t('man', 1), t('man', 2), t('man', 3)], 'чоу'),
            group([t('pin', 5), t('pin', 6), t('pin', 7)], 'чоу'),
            group([t('sou', 2), t('sou', 2), t('sou', 2)], 'понг'),
            group([t('dragon', 1), t('dragon', 1), t('dragon', 1)], 'понг'),
            group([t('wind', 1), t('wind', 1)], 'пара'),
          ])
        );
        frag.appendChild(
          example('Пример 2 — все понги', [
            group([t('man', 9), t('man', 9), t('man', 9)], 'понг'),
            group([t('pin', 1), t('pin', 1), t('pin', 1)], 'понг'),
            group([t('sou', 5), t('sou', 5), t('sou', 5)], 'понг'),
            group([t('wind', 2), t('wind', 2), t('wind', 2)], 'понг'),
            group([t('dragon', 3), t('dragon', 3)], 'пара'),
          ])
        );
        frag.appendChild(
          example('Пример 3 — все чоу + пара', [
            group([t('man', 2), t('man', 3), t('man', 4)], 'чоу'),
            group([t('man', 6), t('man', 7), t('man', 8)], 'чоу'),
            group([t('pin', 1), t('pin', 2), t('pin', 3)], 'чоу'),
            group([t('sou', 5), t('sou', 6), t('sou', 7)], 'чоу'),
            group([t('pin', 8), t('pin', 8)], 'пара'),
          ])
        );
        return frag;
      },
    },
    chow: {
      title: 'Чоу (吃)',
      build() {
        const frag = document.createDocumentFragment();
        frag.appendChild(
          textBlock(
            'Чоу — <strong>три подряд идущие</strong> плитки одной масти (萬 / 筒 / 索). Ветры и драконы в чоу не входят. Берётся только со сброса <em>предыдущего</em> игрока.'
          )
        );
        frag.appendChild(
          example('Пример 1', [group([t('man', 1), t('man', 2), t('man', 3)], '1–2–3萬')])
        );
        frag.appendChild(
          example('Пример 2', [group([t('pin', 4), t('pin', 5), t('pin', 6)], '4–5–6筒')])
        );
        frag.appendChild(
          example('Пример 3', [group([t('sou', 7), t('sou', 8), t('sou', 9)], '7–8–9索')])
        );
        return frag;
      },
    },
    pung: {
      title: 'Понг (碰)',
      build() {
        const frag = document.createDocumentFragment();
        frag.appendChild(
          textBlock(
            'Понг — <strong>три одинаковые</strong> плитки. Можно объявить со сброса любого игрока.'
          )
        );
        frag.appendChild(
          example('Пример 1', [group([t('pin', 5), t('pin', 5), t('pin', 5)], 'три 5筒')])
        );
        frag.appendChild(
          example('Пример 2', [group([t('wind', 1), t('wind', 1), t('wind', 1)], 'три 東')])
        );
        frag.appendChild(
          example('Пример 3', [group([t('dragon', 1), t('dragon', 1), t('dragon', 1)], 'три 中')])
        );
        return frag;
      },
    },
    kong: {
      title: 'Конг (槓)',
      build() {
        const frag = document.createDocumentFragment();
        frag.appendChild(
          textBlock(
            'Конг — <strong>четыре одинаковые</strong> плитки. После конга обычно берут дополнительную плитку со стены.'
          )
        );
        frag.appendChild(
          example('Пример 1', [group([t('sou', 2), t('sou', 2), t('sou', 2), t('sou', 2)], 'четыре 2索')])
        );
        frag.appendChild(
          example('Пример 2', [group([t('wind', 2), t('wind', 2), t('wind', 2), t('wind', 2)], 'четыре 南')])
        );
        frag.appendChild(
          example('Пример 3', [
            group([t('dragon', 3), t('dragon', 3), t('dragon', 3), t('dragon', 3)], 'четыре 白'),
          ])
        );
        return frag;
      },
    },
    fan: {
      title: 'Что такое фан (番)',
      build() {
        const frag = document.createDocumentFragment();
        frag.appendChild(
          textBlock(
            '<strong>Фан</strong> — единица ценности руки. Чем больше особых комбинаций и свойств, тем больше фан и тем выше выплата. В этой версии для победы нужно минимум <strong>3 фана</strong>.'
          )
        );
        frag.appendChild(
          textBlock(
            '<strong>Примеры начисления:</strong>' +
              '<ul class="tip-fan-list">' +
              '<li><strong>+1</strong> — 自摸 (победа со своего добора)</li>' +
              '<li><strong>+1</strong> — понг/конг дракона (中 / 發 / 白)</li>' +
              '<li><strong>+1</strong> — понг своего ветра или ветра круга</li>' +
              '<li><strong>+1</strong> — 平胡 (все чоу + «дешёвая» пара)</li>' +
              '<li><strong>+3</strong> — 對對胡 (все понги/конги)</li>' +
              '<li><strong>+3</strong> — 混一色 (одна масть + гоноры)</li>' +
              '<li><strong>+7</strong> — 清一色 (одна масть без гоноров)</li>' +
              '</ul>'
          )
        );
        frag.appendChild(
          example('Пример руки на ~3+ фана', [
            group([t('dragon', 1), t('dragon', 1), t('dragon', 1)], 'понг 中 +1'),
            group([t('man', 1), t('man', 2), t('man', 3)], 'чоу'),
            group([t('man', 5), t('man', 6), t('man', 7)], 'чоу'),
            group([t('man', 2), t('man', 2), t('man', 2)], 'понг'),
            group([t('man', 9), t('man', 9)], 'пара'),
          ])
        );
        frag.appendChild(
          textBlock(
            'В примере выше: понг дракона (+1) и рука почти в одной масти 萬 с драконом — это путь к <em>混一色</em> (+3) и уже хватает минимума.'
          )
        );
        frag.appendChild(
          textBlock(
            'Полный список комбинаций: <a href="tiles.php#fans">Help → Фаны (очки)</a>.'
          )
        );
        return frag;
      },
    },
  };

  const pop = document.getElementById('tipPopover');
  const titleEl = document.getElementById('tipTitle');
  const bodyEl = document.getElementById('tipBody');
  if (!pop || !titleEl || !bodyEl) return;

  let activeBtn = null;

  function close() {
    pop.classList.add('hidden');
    pop.hidden = true;
    if (activeBtn) {
      activeBtn.setAttribute('aria-expanded', 'false');
      activeBtn = null;
    }
  }

  function open(tipKey, btn) {
    const data = CONTENT[tipKey];
    if (!data) return;

    titleEl.textContent = data.title;
    bodyEl.replaceChildren(data.build());
    pop.classList.remove('hidden');
    pop.hidden = false;

    if (activeBtn) activeBtn.setAttribute('aria-expanded', 'false');
    activeBtn = btn;
    btn.setAttribute('aria-expanded', 'true');

    // Позиция рядом с кнопкой
    const rect = btn.getBoundingClientRect();
    const inner = pop.querySelector('.tip-popover-inner');
    const margin = 12;
    let top = rect.bottom + 8 + window.scrollY;
    let left = rect.left + window.scrollX;

    pop.style.top = `${top}px`;
    pop.style.left = `${left}px`;

    requestAnimationFrame(() => {
      const box = inner.getBoundingClientRect();
      let l = left;
      let t = top;
      if (box.right > window.innerWidth - margin) {
        l = Math.max(margin, window.innerWidth - box.width - margin + window.scrollX);
      }
      if (box.bottom > window.innerHeight - margin) {
        t = Math.max(margin + window.scrollY, rect.top + window.scrollY - box.height - 8);
      }
      pop.style.left = `${l}px`;
      pop.style.top = `${t}px`;
    });
  }

  document.querySelectorAll('.tip-link[data-tip]').forEach((btn) => {
    btn.setAttribute('aria-expanded', 'false');
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      const key = btn.getAttribute('data-tip');
      if (activeBtn === btn && !pop.hidden) {
        close();
        return;
      }
      open(key, btn);
    });
  });

  pop.querySelector('.tip-close')?.addEventListener('click', (e) => {
    e.stopPropagation();
    close();
  });

  pop.addEventListener('click', (e) => e.stopPropagation());

  document.addEventListener('click', () => close());
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') close();
  });
})();
