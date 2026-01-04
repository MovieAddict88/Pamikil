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

  function shuffle(arr) {
    const a = [...arr];
    for (let i = a.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [a[i], a[j]] = [a[j], a[i]];
    }
    return a;
  }

  window.Pamikil.registerActivity('dragdrop', {
    async init(ctx) {
      const { root, data, meta, autosave, speak, api } = ctx;

      const pairs = Array.isArray(data.pairs) ? data.pairs : [];
      if (!pairs.length) {
        root.innerHTML = '<div class="alert alert--error" role="alert">Drag & drop content is missing pairs.</div>';
        return;
      }

      let state = { map: {}, checked: false };
      if (meta.isStudent && ctx.saved && typeof ctx.saved === 'object') {
        state = { ...state, ...ctx.saved };
        state.map = state.map && typeof state.map === 'object' ? state.map : {};
      }

      const leftItems = shuffle(pairs.map((p, i) => ({ i, text: String(p.left || '') })));
      const rightItems = shuffle(pairs.map((p, i) => ({ i, text: String(p.right || '') })));

      const header = el('div', { class: 'activity__header' }, [
        el('div', { class: 'activity__progress' }, 'Match the items'),
        el('button', { class: 'btn btn--small', type: 'button', onClick: () => speak(String(data.prompt || 'Match the items')) }, '🔊 Read')
      ]);

      const body = el('div', { class: 'activity__body' });
      const footer = el('div', { class: 'activity__footer' });

      root.innerHTML = '';
      root.appendChild(header);
      root.appendChild(body);
      root.appendChild(footer);

      let selectedLeft = null;

      async function save() {
        if (!meta.isStudent) return;
        await autosave.save(meta.id, state).catch(() => null);
      }

      function render() {
        body.innerHTML = '';
        footer.innerHTML = '';

        const prompt = el('p', { class: 'muted' }, String(data.prompt || 'Drag (or tap) a left item to the matching right box.'));
        body.appendChild(prompt);

        const grid = el('div', { class: 'match-grid' });
        const leftCol = el('div', { class: 'match-col' });
        const rightCol = el('div', { class: 'match-col' });

        leftItems.forEach((item) => {
          const key = String(item.i);
          const chosen = state.map[key];

          const chip = el('button', {
            class: 'chip' + (selectedLeft === item.i ? ' chip--selected' : ''),
            type: 'button',
            draggable: 'true',
            'data-left-id': String(item.i),
            'aria-label': `Left item: ${item.text}`,
            onClick: () => {
              selectedLeft = selectedLeft === item.i ? null : item.i;
              render();
            },
            onDragstart: (e) => {
              e.dataTransfer.setData('text/plain', String(item.i));
            }
          }, [
            item.text,
            chosen !== undefined ? el('span', { class: 'pill pill--soft', 'aria-hidden': 'true' }, '→') : null
          ]);

          if (state.checked) {
            const correctRightIndex = item.i;
            if (Number(chosen) === correctRightIndex) chip.classList.add('chip--correct');
            else chip.classList.add('chip--wrong');
          }

          leftCol.appendChild(chip);
        });

        rightItems.forEach((target) => {
          const drop = el('div', {
            class: 'dropzone',
            tabindex: '0',
            role: 'button',
            'data-right-id': String(target.i),
            'aria-label': `Target: ${target.text}`,
            onDragover: (e) => {
              e.preventDefault();
            },
            onDrop: async (e) => {
              e.preventDefault();
              const leftId = Number(e.dataTransfer.getData('text/plain'));
              if (!Number.isFinite(leftId)) return;
              state.map[String(leftId)] = target.i;
              state.checked = false;
              selectedLeft = null;
              await save();
              render();
            },
            onClick: async () => {
              if (selectedLeft === null) return;
              state.map[String(selectedLeft)] = target.i;
              state.checked = false;
              selectedLeft = null;
              await save();
              render();
            }
          }, [
            el('div', { class: 'dropzone__label' }, target.text),
            el('div', { class: 'dropzone__hint muted' }, 'Drop or tap')
          ]);

          if (state.checked) {
            const matchedLeftId = Object.entries(state.map).find(([, right]) => Number(right) === target.i)?.[0];
            const ok = matchedLeftId !== undefined && Number(matchedLeftId) === target.i;
            drop.classList.add(ok ? 'dropzone--correct' : 'dropzone--wrong');
          }

          rightCol.appendChild(drop);
        });

        grid.appendChild(leftCol);
        grid.appendChild(rightCol);
        body.appendChild(grid);

        footer.appendChild(el('button', {
          class: 'btn',
          type: 'button',
          onClick: async () => {
            state.map = {};
            state.checked = false;
            selectedLeft = null;
            await save();
            render();
          }
        }, 'Reset'));

        footer.appendChild(el('button', {
          class: 'btn btn--primary',
          type: 'button',
          onClick: async () => {
            state.checked = true;
            await save();
            render();

            const correct = Object.entries(state.map).filter(([l, r]) => Number(l) === Number(r)).length;
            const scorePercent = Math.round((correct / pairs.length) * 100);

            speak(correct === pairs.length ? 'Perfect match!' : 'Good effort!');

            if (meta.isStudent) {
              const res = await api.post('/api/activity_submit.php', {
                activity_id: meta.id,
                score_percent: scorePercent,
                details: { correct, total: pairs.length, map: state.map }
              });
              await autosave.clear(meta.id).catch(() => null);

              if (res && res.ok) {
                body.appendChild(el('div', { class: 'alert alert--success', role: 'status' }, `Saved! You earned ${res.coins_earned} coins.`));
              } else {
                body.appendChild(el('div', { class: 'alert alert--error', role: 'alert' }, 'Could not save your progress.'));
              }
            }
          }
        }, 'Check & Finish'));
      }

      render();
    }
  });
})();
