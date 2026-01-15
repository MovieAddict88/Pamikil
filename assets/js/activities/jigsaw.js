(function () {
  'use strict';

  function el(tag, attrs = {}, children = []) {
    const node = document.createElement(tag);
    Object.entries(attrs).forEach(([k, v]) => {
      if (k === 'class') node.className = v;
      else if (k.startsWith('on') && typeof v === 'function') node.addEventListener(k.slice(2), v);
      else if (v === false || v === null || v === undefined) return;
      else node.setAttribute(k, String(v));
    });
    (Array.isArray(children) ? children : [children]).forEach((c) => {
      if (c === null || c === undefined) return;
      if (typeof c === 'string') node.appendChild(document.createTextNode(c));
      else node.appendChild(c);
    });
    return node;
  }

  function shuffle(a) {
    const arr = [...a];
    for (let i = arr.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [arr[i], arr[j]] = [arr[j], arr[i]];
    }
    return arr;
  }

  function diffConfig(data, key) {
    const d = data.difficulties || {};
    const cfg = d[key] || null;
    if (cfg && cfg.rows && cfg.cols) return { rows: Number(cfg.rows), cols: Number(cfg.cols) };

    if (key === 'easy') return { rows: 2, cols: 3 }; // 6
    if (key === 'medium') return { rows: 3, cols: 4 }; // 12
    return { rows: 4, cols: 6 }; // 24
  }

  window.Pamikil.registerActivity('jigsaw', {
    async init(ctx) {
      const { root, data, meta, autosave, speak, api } = ctx;

      const image = String(data.image || '/assets/img/puzzle-scene.svg');

      let state = { difficulty: 'easy', placed: {}, startedAt: Date.now(), finished: false };
      if (meta.isStudent && ctx.saved && typeof ctx.saved === 'object') {
        state = { ...state, ...ctx.saved };
        state.placed = state.placed && typeof state.placed === 'object' ? state.placed : {};
        state.difficulty = ['easy', 'medium', 'hard'].includes(state.difficulty) ? state.difficulty : 'easy';
        state.startedAt = Number(state.startedAt || Date.now());
      }

      const header = el('div', { class: 'activity__header' }, [
        el('div', { class: 'activity__progress' }, 'Build the picture'),
        el('button', { class: 'btn btn--small', type: 'button', onClick: () => speak('Put the puzzle pieces in the right place!') }, '🔊 Read')
      ]);
      const body = el('div', { class: 'activity__body' });
      const footer = el('div', { class: 'activity__footer' });

      root.innerHTML = '';
      root.appendChild(header);
      root.appendChild(body);
      root.appendChild(footer);

      let selectedPieceId = null;

      async function save() {
        if (!meta.isStudent) return;
        await autosave.save(meta.id, state).catch(() => null);
      }

      function pieceStyle(rows, cols, r, c) {
        const px = cols === 1 ? 0 : (c / (cols - 1)) * 100;
        const py = rows === 1 ? 0 : (r / (rows - 1)) * 100;
        return {
          backgroundImage: `url(${image})`,
          backgroundSize: `${cols * 100}% ${rows * 100}%`,
          backgroundPosition: `${px}% ${py}%`,
        };
      }

      function render() {
        body.innerHTML = '';
        footer.innerHTML = '';

        const diff = diffConfig(data, state.difficulty);
        const { rows, cols } = diff;
        const total = rows * cols;

        const controls = el('div', { class: 'puzzle-controls' }, [
          el('label', { class: 'field' }, [
            el('span', { class: 'field__label' }, 'Difficulty'),
            el('select', {
              class: 'field__input',
              onChange: async (e) => {
                state.difficulty = e.target.value;
                state.placed = {};
                state.startedAt = Date.now();
                selectedPieceId = null;
                await save();
                render();
              }
            }, [
              el('option', { value: 'easy', selected: state.difficulty === 'easy' }, 'Easy (6 pieces)'),
              el('option', { value: 'medium', selected: state.difficulty === 'medium' }, 'Medium (12 pieces)'),
              el('option', { value: 'hard', selected: state.difficulty === 'hard' }, 'Hard (24 pieces)'),
            ])
          ]),
        ]);

        body.appendChild(controls);

        const wrap = el('div', { class: 'puzzle-wrap' });
        const board = el('div', {
          class: 'puzzle-board',
          style: `--rows:${rows};--cols:${cols};`
        });
        const tray = el('div', { class: 'puzzle-tray', 'aria-label': 'Puzzle pieces' });

        const pieces = [];
        for (let r = 0; r < rows; r++) {
          for (let c = 0; c < cols; c++) {
            pieces.push({ id: `${r}-${c}`, r, c });
          }
        }

        const shuffled = shuffle(pieces);

        pieces.forEach((p) => {
          const slot = el('div', {
            class: 'puzzle-slot',
            'data-piece-id': p.id,
            tabindex: '0',
            'aria-label': 'Puzzle slot',
            onDragover: (e) => e.preventDefault(),
            onDrop: async (e) => {
              e.preventDefault();
              const pid = e.dataTransfer.getData('text/plain');
              await place(pid, p.id);
            },
            onClick: async () => {
              if (!selectedPieceId) return;
              await place(selectedPieceId, p.id);
            }
          });

          if (state.placed[p.id]) {
            slot.appendChild(renderPiece(p, rows, cols, true));
            slot.classList.add('puzzle-slot--filled');
          }

          board.appendChild(slot);
        });

        shuffled.forEach((p) => {
          if (state.placed[p.id]) return;
          tray.appendChild(renderPiece(p, rows, cols, false));
        });

        wrap.appendChild(board);
        wrap.appendChild(tray);
        body.appendChild(wrap);

        const placedCount = Object.keys(state.placed).length;
        body.appendChild(el('p', { class: 'muted' }, `Placed: ${placedCount}/${total}. Tip: drag on desktop, tap-to-select then tap a slot on mobile.`));

        footer.appendChild(el('button', {
          class: 'btn',
          type: 'button',
          onClick: async () => {
            state.placed = {};
            state.startedAt = Date.now();
            selectedPieceId = null;
            await save();
            render();
          }
        }, 'Restart'));

        if (placedCount === total) {
          footer.appendChild(el('button', {
            class: 'btn btn--primary',
            type: 'button',
            onClick: async () => {
              await finish(total);
            }
          }, 'Finish'));
        }
      }

      function renderPiece(p, rows, cols, fixed) {
        const piece = el('button', {
          class: 'puzzle-piece' + (selectedPieceId === p.id ? ' puzzle-piece--selected' : ''),
          type: 'button',
          draggable: fixed ? 'false' : 'true',
          'data-piece-id': p.id,
          'aria-label': `Puzzle piece ${p.id}`,
          onClick: (e) => {
            if (fixed) return;
            selectedPieceId = selectedPieceId === p.id ? null : p.id;
            e.preventDefault();
            render();
          },
          onDragstart: (e) => {
            if (fixed) return;
            e.dataTransfer.setData('text/plain', p.id);
          }
        });

        const styles = pieceStyle(rows, cols, p.r, p.c);
        Object.assign(piece.style, styles);
        return piece;
      }

      async function place(fromId, toId) {
        if (!fromId || !toId) return;
        if (state.placed[toId]) return;

        if (fromId === toId) {
          state.placed[toId] = true;
          selectedPieceId = null;
          speak('Nice!');
          await save();
          render();
        } else {
          speak('Try a different spot.');
        }
      }

      async function finish(total) {
        body.innerHTML = '';
        footer.innerHTML = '';

        const seconds = Math.max(1, Math.round((Date.now() - Number(state.startedAt || Date.now())) / 1000));
        body.appendChild(el('h2', { class: 'activity__title' }, 'Puzzle complete!'));
        body.appendChild(el('p', {}, `Time: ${seconds} seconds`));

        const score = 100;

        if (meta.isStudent) {
          const res = await api.post('/api/activity_submit.php', {
            activity_id: meta.id,
            score_percent: score,
            details: { difficulty: state.difficulty, seconds }
          });
          await autosave.clear(meta.id).catch(() => null);

          if (res && res.ok) {
            body.appendChild(el('div', { class: 'alert alert--success', role: 'status' }, `Saved! You earned ${res.coins_earned} coins.`));
          } else {
            body.appendChild(el('div', { class: 'alert alert--error', role: 'alert' }, 'Could not save your progress.'));
          }
        }

        footer.appendChild(el('a', { class: 'btn btn--primary', href: window.Pamikil.path('/activities') }, 'Choose another activity'));
      }

      render();
      save().catch(() => null);
    }
  });
})();
