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

  window.Pamikil.registerActivity('flashcards', {
    async init(ctx) {
      const { root, data, meta, autosave, speak, api } = ctx;

      const cards = Array.isArray(data.cards) ? data.cards : [];
      if (!cards.length) {
        root.innerHTML = '<div class="alert alert--error" role="alert">Flashcards content is missing cards.</div>';
        return;
      }

      let state = { index: 0, seen: {}, flipped: false, finished: false };
      if (meta.isStudent && ctx.saved && typeof ctx.saved === 'object') {
        state = { ...state, ...ctx.saved };
        state.seen = state.seen && typeof state.seen === 'object' ? state.seen : {};
        state.index = Math.max(0, Math.min(cards.length - 1, Number(state.index || 0)));
      }

      const header = el('div', { class: 'activity__header' }, [
        el('div', { class: 'activity__progress' }),
        el('button', { class: 'btn btn--small', type: 'button', onClick: () => speak(String(cards[state.index]?.front || '')) }, '🔊 Read')
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

      function renderCard() {
        header.querySelector('.activity__progress').textContent = `Card ${state.index + 1} of ${cards.length}`;
        body.innerHTML = '';
        footer.innerHTML = '';

        const card = cards[state.index];
        state.seen[String(state.index)] = true;

        const flip = el('button', {
          class: 'flashcard' + (state.flipped ? ' flashcard--flipped' : ''),
          type: 'button',
          'aria-label': 'Flashcard. Press to flip.',
          onClick: async () => {
            state.flipped = !state.flipped;
            await save();
            renderCard();
          },
          onKeydown: async (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
              e.preventDefault();
              state.flipped = !state.flipped;
              await save();
              renderCard();
            }
          }
        }, [
          el('div', { class: 'flashcard__inner' }, [
            el('div', { class: 'flashcard__front' }, String(card.front ?? '')),
            el('div', { class: 'flashcard__back' }, String(card.back ?? '')),
          ])
        ]);

        body.appendChild(flip);
        body.appendChild(el('p', { class: 'muted' }, 'Tip: tap the card to flip.'));

        const seenCount = Object.keys(state.seen).length;
        const allSeen = seenCount >= cards.length;

        footer.appendChild(el('button', {
          class: 'btn',
          type: 'button',
          disabled: state.index === 0,
          onClick: async () => {
            state.index = Math.max(0, state.index - 1);
            state.flipped = false;
            await save();
            renderCard();
          }
        }, 'Back'));

        footer.appendChild(el('button', {
          class: 'btn btn--primary',
          type: 'button',
          onClick: async () => {
            if (state.index < cards.length - 1) {
              state.index += 1;
              state.flipped = false;
              await save();
              renderCard();
            } else {
              state.finished = true;
              await save();
              await finish();
            }
          }
        }, state.index < cards.length - 1 ? 'Next' : (allSeen ? 'Finish' : 'Finish (after viewing all)')));

        footer.appendChild(el('button', {
          class: 'btn',
          type: 'button',
          disabled: !allSeen,
          onClick: async () => {
            state.finished = true;
            await save();
            await finish();
          }
        }, 'I viewed them all'));
      }

      async function finish() {
        body.innerHTML = '';
        footer.innerHTML = '';

        body.appendChild(el('h2', { class: 'activity__title' }, 'Flashcards complete!'));
        body.appendChild(el('p', {}, 'Great practice.'));

        if (meta.isStudent) {
          const res = await api.post('/api/activity_submit.php', {
            activity_id: meta.id,
            score_percent: 100,
            details: { seenCount: Object.keys(state.seen).length, total: cards.length }
          });
          await autosave.clear(meta.id).catch(() => null);

          if (res && res.ok) {
            body.appendChild(el('div', { class: 'alert alert--success', role: 'status' }, `You earned ${res.coins_earned} coins.`));
          } else {
            body.appendChild(el('div', { class: 'alert alert--error', role: 'alert' }, 'Could not save your progress.'));
          }
        }

        footer.appendChild(el('a', { class: 'btn btn--primary', href: window.Pamikil.path('/activities') }, 'Choose another activity'));
      }

      renderCard();
      save().catch(() => null);
    }
  });
})();
