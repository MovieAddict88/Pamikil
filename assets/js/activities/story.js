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

  function speakParagraphs(speak, text) {
    const chunks = String(text || '').split(/\n\n+/).map((s) => s.trim()).filter(Boolean);
    speak(chunks.join('. '));
  }

  window.Pamikil.registerActivity('story', {
    async init(ctx) {
      const { root, data, meta, autosave, speak, api } = ctx;

      const pages = Array.isArray(data.pages) ? data.pages : [];
      if (!pages.length) {
        root.innerHTML = '<div class="alert alert--error" role="alert">Story content is missing pages.</div>';
        return;
      }

      let state = { index: 0, answers: {}, correct: 0, finished: false };
      if (meta.isStudent && ctx.saved && typeof ctx.saved === 'object') {
        state = { ...state, ...ctx.saved };
        state.answers = state.answers && typeof state.answers === 'object' ? state.answers : {};
        state.correct = Number(state.correct || 0);
        state.index = Math.max(0, Math.min(pages.length - 1, Number(state.index || 0)));
      }

      const header = el('div', { class: 'activity__header' }, [
        el('div', { class: 'activity__progress' }),
        el('button', { class: 'btn btn--small', type: 'button', onClick: () => speakParagraphs(speak, pages[state.index]?.text || '') }, '🔊 Read')
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

      function render() {
        const page = pages[state.index];
        header.querySelector('.activity__progress').textContent = `Page ${state.index + 1} of ${pages.length}`;

        body.innerHTML = '';
        footer.innerHTML = '';

        body.appendChild(el('h2', { class: 'activity__title' }, String(page.title || data.title || 'Story')));
        body.appendChild(el('div', { class: 'story__text' }, String(page.text || '')));

        const q = page.question;
        if (q && typeof q === 'object') {
          const qKey = String(state.index);
          const chosen = typeof state.answers[qKey] === 'number' ? state.answers[qKey] : null;

          body.appendChild(el('h3', {}, String(q.q || q.question || 'Question')));

          const choices = Array.isArray(q.choices) ? q.choices : [];
          const choiceWrap = el('div', { class: 'choices', role: 'list' });

          choices.forEach((c, i) => {
            const text = typeof c === 'string' ? c : String(c.t ?? c.text ?? '');
            const btn = el('button', {
              class: 'choice',
              type: 'button',
              role: 'listitem',
              disabled: chosen !== null,
              onClick: async () => {
                state.answers[qKey] = i;
                const correctIdx = typeof q.answerIndex === 'number' ? q.answerIndex : (typeof q.correctIndex === 'number' ? q.correctIndex : -1);
                const isCorrect = i === correctIdx;
                if (isCorrect) {
                  state.correct += 1;
                  speak('Great!');
                } else {
                  speak('Let’s learn from this.');
                }

                await save();
                render();
              }
            }, text);

            if (chosen !== null) {
              const correctIdx = typeof q.answerIndex === 'number' ? q.answerIndex : -1;
              if (i === chosen && i === correctIdx) btn.classList.add('choice--correct');
              if (i === chosen && i !== correctIdx) btn.classList.add('choice--wrong');
              if (i !== chosen && i === correctIdx) btn.classList.add('choice--hint');
            }

            choiceWrap.appendChild(btn);
          });

          body.appendChild(choiceWrap);

          if (chosen !== null) {
            const correctIdx = typeof q.answerIndex === 'number' ? q.answerIndex : -1;
            const explain = q.explain ? String(q.explain) : '';
            const msg = chosen === correctIdx ? 'Correct!' : 'Nice try — the correct answer is highlighted.';
            body.appendChild(el('div', { class: 'alert alert--info', role: 'status' }, msg + (explain ? ' ' + explain : '')));
          } else {
            body.appendChild(el('div', { class: 'alert alert--info', role: 'status' }, 'Answer the question to continue.'));
          }
        }

        const canGoNext = !page.question || state.answers[String(state.index)] !== undefined;

        footer.appendChild(el('button', {
          class: 'btn',
          type: 'button',
          disabled: state.index === 0,
          onClick: async () => {
            state.index = Math.max(0, state.index - 1);
            await save();
            render();
          }
        }, 'Back'));

        footer.appendChild(el('button', {
          class: 'btn btn--primary',
          type: 'button',
          disabled: !canGoNext,
          onClick: async () => {
            if (state.index < pages.length - 1) {
              state.index += 1;
              await save();
              render();
              return;
            }

            state.finished = true;
            await save();
            await renderResult();
          }
        }, state.index < pages.length - 1 ? 'Next' : 'Finish'));
      }

      async function renderResult() {
        body.innerHTML = '';
        footer.innerHTML = '';

        const questionCount = pages.filter((p) => p.question).length;
        const scorePercent = questionCount ? Math.round((state.correct / questionCount) * 100) : 100;

        body.appendChild(el('h2', { class: 'activity__title' }, 'Story complete!'));
        body.appendChild(el('p', {}, questionCount ? `Questions correct: ${state.correct}/${questionCount} (${scorePercent}%)` : 'Great reading!'));

        if (meta.isStudent) {
          const res = await api.post('/api/activity_submit.php', {
            activity_id: meta.id,
            score_percent: scorePercent,
            details: { correct: state.correct, totalQuestions: questionCount, answers: state.answers }
          });
          await autosave.clear(meta.id).catch(() => null);

          if (res && res.ok) {
            body.appendChild(el('div', { class: 'alert alert--success', role: 'status' }, `You earned ${res.coins_earned} coins. Total: ${res.coins_total}.`));
          } else {
            body.appendChild(el('div', { class: 'alert alert--error', role: 'alert' }, 'Could not save your progress.'));
          }
        }

        footer.appendChild(el('a', { class: 'btn btn--primary', href: window.Pamikil.path('/activities') }, 'Choose another activity'));
      }

      render();
    }
  });
})();
