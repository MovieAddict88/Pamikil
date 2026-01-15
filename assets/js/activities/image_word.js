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

  window.Pamikil.registerActivity('image_word', {
    async init(ctx) {
      const { root, data, meta, autosave, speak, api } = ctx;

      const items = Array.isArray(data.items) ? data.items : [];
      if (!items.length) {
        root.innerHTML = '<div class="alert alert--error" role="alert">Image-to-word content is missing items.</div>';
        return;
      }

      let state = { matched: {}, selectedImage: null, selectedWord: null };
      if (meta.isStudent && ctx.saved && typeof ctx.saved === 'object') {
        state = { ...state, ...ctx.saved };
        state.matched = state.matched && typeof state.matched === 'object' ? state.matched : {};
      }

      const words = shuffle(items.map((x, i) => ({ i, word: String(x.word || '') })));

      const header = el('div', { class: 'activity__header' }, [
        el('div', { class: 'activity__progress' }, 'Match each picture to the correct word'),
        el('button', { class: 'btn btn--small', type: 'button', onClick: () => speak('Match the picture with the correct word.') }, '🔊 Read')
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

      async function tryMatch() {
        if (state.selectedImage === null || state.selectedWord === null) return;

        const i = Number(state.selectedImage);
        const w = Number(state.selectedWord);
        if (i === w) {
          state.matched[String(i)] = true;
          const word = String(items[i].word || '');
          speak(word);
          state.selectedImage = null;
          state.selectedWord = null;
          await save();
          render();
        } else {
          speak('Try again.');
          state.selectedWord = null;
          await save();
          render();
        }
      }

      function render() {
        body.innerHTML = '';
        footer.innerHTML = '';

        const grid = el('div', { class: 'imgword' });
        const images = el('div', { class: 'imgword__images' });
        const wordList = el('div', { class: 'imgword__words' });

        items.forEach((it, i) => {
          const done = !!state.matched[String(i)];
          const card = el('button', {
            class: 'imgcard' + (done ? ' imgcard--done' : '') + (state.selectedImage === i ? ' imgcard--selected' : ''),
            type: 'button',
            disabled: done,
            'aria-label': `Image ${i + 1}`,
            onClick: async () => {
              state.selectedImage = state.selectedImage === i ? null : i;
              await save();
              await tryMatch();
              render();
            }
          }, [
            el('img', { src: String(it.img || ''), alt: String(it.alt || it.word || 'Image') }),
            done ? el('div', { class: 'pill pill--success' }, 'Matched') : null
          ]);
          images.appendChild(card);
        });

        words.forEach((w) => {
          const done = !!state.matched[String(w.i)];
          const btn = el('button', {
            class: 'chip' + (done ? ' chip--disabled' : '') + (state.selectedWord === w.i ? ' chip--selected' : ''),
            type: 'button',
            disabled: done,
            onClick: async () => {
              state.selectedWord = state.selectedWord === w.i ? null : w.i;
              speak(w.word);
              await save();
              await tryMatch();
              render();
            }
          }, w.word);
          wordList.appendChild(btn);
        });

        grid.appendChild(images);
        grid.appendChild(wordList);
        body.appendChild(grid);

        const matchedCount = Object.keys(state.matched).length;
        body.appendChild(el('p', { class: 'muted' }, `Matched: ${matchedCount}/${items.length}.`));

        footer.appendChild(el('button', {
          class: 'btn',
          type: 'button',
          onClick: async () => {
            state.matched = {};
            state.selectedImage = null;
            state.selectedWord = null;
            await save();
            render();
          }
        }, 'Reset'));

        footer.appendChild(el('button', {
          class: 'btn btn--primary',
          type: 'button',
          disabled: matchedCount !== items.length,
          onClick: async () => {
            const score = 100;
            body.innerHTML = '';
            footer.innerHTML = '';
            body.appendChild(el('h2', { class: 'activity__title' }, 'Great matching!'));

            if (meta.isStudent) {
              const res = await api.post('/api/activity_submit.php', {
                activity_id: meta.id,
                score_percent: score,
                details: { total: items.length }
              });
              await autosave.clear(meta.id).catch(() => null);

              if (res && res.ok) {
                body.appendChild(el('div', { class: 'alert alert--success', role: 'status' }, `Saved! You earned ${res.coins_earned} coins.`));
              } else {
                body.appendChild(el('div', { class: 'alert alert--error', role: 'alert' }, 'Could not save your progress.'));
              }
            }

            footer.appendChild(el('a', { class: 'btn btn--primary', href: '/activities' }, 'Choose another activity'));
          }
        }, 'Finish'));
      }

      render();
      save().catch(() => null);
    }
  });
})();
