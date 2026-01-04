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

  function clamp(n, min, max) {
    return Math.max(min, Math.min(max, n));
  }

  window.Pamikil.registerActivity('quiz', {
    async init(ctx) {
      const { root, data, meta, autosave, speak, api } = ctx;

      const questions = Array.isArray(data.questions) ? data.questions : [];
      if (!questions.length) {
        root.innerHTML = '<div class="alert alert--error" role="alert">Quiz content is missing questions.</div>';
        return;
      }

      let state = {
        index: 0,
        correct: 0,
        answers: [],
        finished: false,
      };

      if (meta.isStudent && ctx.saved && typeof ctx.saved === 'object') {
        state = { ...state, ...ctx.saved };
        state.index = clamp(Number(state.index || 0), 0, questions.length - 1);
        state.correct = clamp(Number(state.correct || 0), 0, questions.length);
        state.answers = Array.isArray(state.answers) ? state.answers : [];
      }

      const header = el('div', { class: 'activity__header' }, [
        el('div', { class: 'activity__progress', 'aria-label': 'Quiz progress' }),
        el('button', { class: 'btn btn--small', type: 'button', onClick: () => speak('Let’s do a quiz!') }, '🔊 Read')
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

      function renderQuestion() {
        const q = questions[state.index];
        const choiceList = Array.isArray(q.choices) ? q.choices : [];

        header.querySelector('.activity__progress').textContent = `Question ${state.index + 1} of ${questions.length}`;

        body.innerHTML = '';
        footer.innerHTML = '';

        const qEl = el('h2', { class: 'activity__title' }, String(q.q || q.question || 'Question'));

        const buttons = el('div', { class: 'choices', role: 'list' });

        const answeredIndex = state.answers[state.index];

        choiceList.forEach((ch, i) => {
          const text = String(ch.t ?? ch.text ?? ch);
          const btn = el('button', {
            class: 'choice',
            type: 'button',
            role: 'listitem',
            'aria-label': `Answer choice ${i + 1}: ${text}`,
            disabled: typeof answeredIndex === 'number',
            onClick: async () => {
              state.answers[state.index] = i;

              const isCorrect = !!(ch.correct);
              if (isCorrect) {
                state.correct += 1;
                speak('Correct!');
              } else {
                speak('Try again next time.');
              }

              await save();
              renderQuestion();
            }
          }, text);

          if (typeof answeredIndex === 'number') {
            const picked = answeredIndex === i;
            const isCorrect = !!(ch.correct);
            if (picked && isCorrect) btn.classList.add('choice--correct');
            if (picked && !isCorrect) btn.classList.add('choice--wrong');
            if (!picked && isCorrect) btn.classList.add('choice--hint');
          }

          buttons.appendChild(btn);
        });

        body.appendChild(qEl);
        if (q.explain) body.appendChild(el('p', { class: 'muted' }, String(q.explain)));
        body.appendChild(buttons);

        if (typeof answeredIndex === 'number') {
          const picked = choiceList[answeredIndex];
          const msg = picked && picked.correct ? 'Nice job!' : 'Good try — check the correct answer highlighted.';
          const explanation = picked && picked.explain ? String(picked.explain) : '';

          body.appendChild(el('div', { class: 'alert alert--info', role: 'status' }, msg + (explanation ? ' ' + explanation : '')));

          const nextLabel = state.index === questions.length - 1 ? 'Finish' : 'Next';
          footer.appendChild(el('button', {
            class: 'btn btn--primary',
            type: 'button',
            onClick: async () => {
              if (state.index < questions.length - 1) {
                state.index += 1;
                await save();
                renderQuestion();
              } else {
                state.finished = true;
                await save();
                renderResult();
              }
            }
          }, nextLabel));
        }
      }

      async function submitResult(scorePercent) {
        if (!meta.isStudent) return null;
        return api.post('/api/activity_submit.php', {
          activity_id: meta.id,
          score_percent: scorePercent,
          details: { correct: state.correct, total: questions.length, answers: state.answers }
        });
      }

      function renderStars(stars) {
        const wrap = el('div', { class: 'stars', 'aria-label': `Rating ${stars} out of 5` });
        for (let i = 1; i <= 5; i++) {
          wrap.appendChild(el('span', { class: i <= stars ? 'star star--on' : 'star', 'aria-hidden': 'true' }, '★'));
        }
        return wrap;
      }

      async function renderResult() {
        body.innerHTML = '';
        footer.innerHTML = '';

        const scorePercent = Math.round((state.correct / questions.length) * 100);

        body.appendChild(el('h2', { class: 'activity__title' }, 'Quiz complete!'));
        body.appendChild(el('p', {}, `Score: ${state.correct}/${questions.length} (${scorePercent}%)`));

        let result = null;
        if (meta.isStudent) {
          result = await submitResult(scorePercent);
          await autosave.clear(meta.id).catch(() => null);
        }

        const stars = result && result.stars ? result.stars : Math.max(1, Math.round(scorePercent / 20));
        body.appendChild(renderStars(stars));

        if (result && result.ok) {
          body.appendChild(el('div', { class: 'alert alert--success', role: 'status' }, `You earned ${result.coins_earned} coins. Total: ${result.coins_total}.`));
          if (Array.isArray(result.badges) && result.badges.length) {
            const b = result.badges.map((x) => `${x.icon || ''} ${x.name || ''}`.trim()).join(' · ');
            body.appendChild(el('div', { class: 'alert alert--info', role: 'status' }, `Badges: ${b}`));
          }
        } else if (meta.isStudent) {
          body.appendChild(el('div', { class: 'alert alert--error', role: 'alert' }, 'Could not save your progress. Try again later.'));
        }

        footer.appendChild(el('a', { class: 'btn btn--primary', href: window.Pamikil.path('/activities') }, 'Choose another activity'));
      }

      renderQuestion();
    }
  });
})();
