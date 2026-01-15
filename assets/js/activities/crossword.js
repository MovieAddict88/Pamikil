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

  function key(r, c) {
    return `${r},${c}`;
  }

  function buildGrid(size, words) {
    const grid = Array.from({ length: size }, () => Array.from({ length: size }, () => null));

    for (const w of words) {
      const ans = String(w.answer || '').toUpperCase().replace(/[^A-Z]/g, '');
      const row = Number(w.row || 0);
      const col = Number(w.col || 0);
      const dir = w.dir === 'down' ? 'down' : 'across';

      for (let i = 0; i < ans.length; i++) {
        const r = dir === 'down' ? row + i : row;
        const c = dir === 'across' ? col + i : col;
        if (r < 0 || c < 0 || r >= size || c >= size) continue;
        grid[r][c] = ans[i];
      }
    }

    return grid;
  }

  function cellsForWord(word) {
    const ans = String(word.answer || '').toUpperCase().replace(/[^A-Z]/g, '');
    const row = Number(word.row || 0);
    const col = Number(word.col || 0);
    const dir = word.dir === 'down' ? 'down' : 'across';
    const cells = [];
    for (let i = 0; i < ans.length; i++) {
      const r = dir === 'down' ? row + i : row;
      const c = dir === 'across' ? col + i : col;
      cells.push({ r, c, letter: ans[i] });
    }
    return cells;
  }

  window.Pamikil.registerActivity('crossword', {
    async init(ctx) {
      const { root, data, meta, autosave, speak, api } = ctx;

      const size = Number(data.size || 9);
      const words = Array.isArray(data.words) ? data.words : [];
      if (!words.length) {
        root.innerHTML = '<div class="alert alert--error" role="alert">Crossword content is missing words.</div>';
        return;
      }

      const n = Math.max(5, Math.min(15, size));
      const grid = buildGrid(n, words);

      let state = { entries: {}, revealed: {}, selected: 0, hintsUsed: 0 };
      if (meta.isStudent && ctx.saved && typeof ctx.saved === 'object') {
        state = { ...state, ...ctx.saved };
        state.entries = state.entries && typeof state.entries === 'object' ? state.entries : {};
        state.revealed = state.revealed && typeof state.revealed === 'object' ? state.revealed : {};
        state.selected = Math.max(0, Math.min(words.length - 1, Number(state.selected || 0)));
        state.hintsUsed = Number(state.hintsUsed || 0);
      }

      const header = el('div', { class: 'activity__header' }, [
        el('div', { class: 'activity__progress' }, 'Fill the crossword'),
        el('button', { class: 'btn btn--small', type: 'button', onClick: () => speak('Fill in the crossword. Click a clue to highlight a word.') }, '🔊 Read')
      ]);

      const body = el('div', { class: 'activity__body' });
      const footer = el('div', { class: 'activity__footer' });

      root.innerHTML = '';
      root.appendChild(header);
      root.appendChild(body);
      root.appendChild(footer);

      async function save() {
        if (!meta.isStudent) return;
        await autosave.save(meta.id, state).catch(() => null);
      }

      function selectedWord() {
        return words[state.selected] || words[0];
      }

      function render() {
        body.innerHTML = '';
        footer.innerHTML = '';

        const wrap = el('div', { class: 'crossword-wrap' });
        const gridEl = el('div', { class: 'crossword-grid', style: `--n:${n};` });

        const highlight = new Set(cellsForWord(selectedWord()).map((c) => key(c.r, c.c)));

        for (let r = 0; r < n; r++) {
          for (let c = 0; c < n; c++) {
            const correct = grid[r][c];
            const k = key(r, c);

            if (!correct) {
              gridEl.appendChild(el('div', { class: 'cw-cell cw-cell--block', 'aria-hidden': 'true' }));
              continue;
            }

            const isRevealed = !!state.revealed[k];
            const value = isRevealed ? correct : String(state.entries[k] || '');

            const cell = el('div', { class: 'cw-cell' + (highlight.has(k) ? ' cw-cell--highlight' : '') });
            const input = el('input', {
              class: 'cw-input',
              type: 'text',
              maxlength: '1',
              inputmode: 'text',
              autocapitalize: 'characters',
              value,
              disabled: isRevealed,
              'aria-label': `Row ${r + 1} column ${c + 1}`,
              onFocus: () => {
                // choose a word that includes this cell
                const idx = words.findIndex((w) => cellsForWord(w).some((x) => x.r === r && x.c === c));
                if (idx >= 0) {
                  state.selected = idx;
                  render();
                }
              },
              onInput: async (e) => {
                const ch = String(e.target.value || '').toUpperCase().replace(/[^A-Z]/g, '').slice(0, 1);
                state.entries[k] = ch;
                await save();
              }
            });

            cell.appendChild(input);
            gridEl.appendChild(cell);
          }
        }

        const cluePanel = el('div', { class: 'cw-clues' });
        const across = words.filter((w) => (w.dir || 'across') !== 'down');
        const down = words.filter((w) => (w.dir || '') === 'down');

        cluePanel.appendChild(el('h3', {}, 'Clues'));
        cluePanel.appendChild(el('div', { class: 'muted' }, 'Click a clue, then fill letters in the grid.'));

        cluePanel.appendChild(el('h4', {}, 'Across'));
        cluePanel.appendChild(renderClueList(across));
        cluePanel.appendChild(el('h4', {}, 'Down'));
        cluePanel.appendChild(renderClueList(down));

        wrap.appendChild(gridEl);
        wrap.appendChild(cluePanel);

        body.appendChild(wrap);

        footer.appendChild(el('button', {
          class: 'btn',
          type: 'button',
          onClick: async () => {
            const w = selectedWord();
            const cells = cellsForWord(w);
            const next = cells.find((c) => {
              const k = key(c.r, c.c);
              return !state.revealed[k];
            });

            if (!next) {
              speak('No more hints for this word.');
              return;
            }

            const k = key(next.r, next.c);
            state.revealed[k] = true;
            state.hintsUsed += 1;
            speak('Here is a hint.');
            await save();
            render();
          }
        }, 'Hint'));

        footer.appendChild(el('button', {
          class: 'btn btn--primary',
          type: 'button',
          onClick: async () => {
            await finish();
          }
        }, 'Check & Finish'));

        footer.appendChild(el('button', {
          class: 'btn',
          type: 'button',
          onClick: async () => {
            state.entries = {};
            state.revealed = {};
            state.hintsUsed = 0;
            await save();
            render();
          }
        }, 'Reset'));
      }

      function renderClueList(list) {
        const ul = el('ul', { class: 'cw-clue-list' });
        list.forEach((w) => {
          const idx = words.indexOf(w);
          const li = el('li', {
            class: 'cw-clue' + (idx === state.selected ? ' cw-clue--active' : '')
          }, [
            el('button', {
              class: 'cw-clue-btn',
              type: 'button',
              onClick: () => {
                state.selected = idx;
                render();
              }
            }, String(w.clue || w.hint || w.answer || '')),
          ]);
          ul.appendChild(li);
        });
        return ul;
      }

      async function finish() {
        const totalCells = grid.flat().filter(Boolean).length;
        let correctCells = 0;

        for (let r = 0; r < n; r++) {
          for (let c = 0; c < n; c++) {
            const correct = grid[r][c];
            if (!correct) continue;
            const k = key(r, c);
            const v = state.revealed[k] ? correct : String(state.entries[k] || '').toUpperCase();
            if (v === correct) correctCells += 1;
          }
        }

        const raw = totalCells ? Math.round((correctCells / totalCells) * 100) : 100;
        const penalty = Math.min(40, Math.round(state.hintsUsed * 5));
        const score = Math.max(0, raw - penalty);

        body.innerHTML = '';
        footer.innerHTML = '';

        body.appendChild(el('h2', { class: 'activity__title' }, 'Crossword complete!'));
        body.appendChild(el('p', {}, `Correct letters: ${correctCells}/${totalCells}. Hints used: ${state.hintsUsed}. Score: ${score}%.`));

        if (meta.isStudent) {
          const res = await api.post('/api/activity_submit.php', {
            activity_id: meta.id,
            score_percent: score,
            details: { correctCells, totalCells, hintsUsed: state.hintsUsed }
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
