(function () {
  'use strict';

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const baseMeta = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '/';
  const basePrefix = baseMeta.replace(/\/$/, '');

  function path(p) {
    const url = String(p || '');
    if (url.startsWith('http://') || url.startsWith('https://')) return url;
    if (!url.startsWith('/')) return basePrefix + '/' + url;
    return basePrefix + url;
  }

  const storage = {
    get(key, fallback) {
      try {
        const v = localStorage.getItem(key);
        return v === null ? fallback : JSON.parse(v);
      } catch (_) {
        return fallback;
      }
    },
    set(key, value) {
      try {
        localStorage.setItem(key, JSON.stringify(value));
      } catch (_) {}
    }
  };

  const prefsKey = 'pamikil:prefs:v1';
  const prefs = storage.get(prefsKey, { contrast: false, fontScale: 1 });

  function applyPrefs() {
    document.documentElement.classList.toggle('hc', !!prefs.contrast);
    document.documentElement.style.setProperty('--font-scale', String(prefs.fontScale || 1));

    const btn = document.querySelector('[data-action="toggle-contrast"]');
    if (btn) btn.setAttribute('aria-pressed', prefs.contrast ? 'true' : 'false');
  }

  function clamp(n, min, max) {
    return Math.max(min, Math.min(max, n));
  }

  function initPrefsUi() {
    document.addEventListener('click', (e) => {
      const target = e.target;
      if (!(target instanceof HTMLElement)) return;

      const action = target.getAttribute('data-action');
      if (!action) return;

      if (action === 'toggle-contrast') {
        prefs.contrast = !prefs.contrast;
        storage.set(prefsKey, prefs);
        applyPrefs();
      }

      if (action === 'font-plus') {
        prefs.fontScale = clamp((prefs.fontScale || 1) + 0.1, 0.8, 1.6);
        storage.set(prefsKey, prefs);
        applyPrefs();
      }

      if (action === 'font-minus') {
        prefs.fontScale = clamp((prefs.fontScale || 1) - 0.1, 0.8, 1.6);
        storage.set(prefsKey, prefs);
        applyPrefs();
      }
    });
  }

  async function apiJson(url, payload) {
    const res = await fetch(path(url), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken
      },
      body: JSON.stringify(payload)
    });

    const text = await res.text();
    let data;
    try {
      data = JSON.parse(text);
    } catch (_) {
      data = { ok: false, message: text };
    }

    if (!res.ok) {
      return { ok: false, status: res.status, ...data };
    }

    return data;
  }

  async function apiGetJson(url) {
    const res = await fetch(path(url), { headers: { 'X-CSRF-Token': csrfToken } });
    const data = await res.json().catch(() => ({ ok: false }));
    if (!res.ok) return { ok: false, status: res.status, ...data };
    return data;
  }

  function speak(text) {
    try {
      if (!('speechSynthesis' in window)) return;
      const utter = new SpeechSynthesisUtterance(text);
      utter.rate = 1;
      utter.pitch = 1.1;
      window.speechSynthesis.cancel();
      window.speechSynthesis.speak(utter);
    } catch (_) {}
  }

  const activities = {};

  function registerActivity(type, module) {
    activities[type] = module;
  }

  function getActivityData() {
    const el = document.getElementById('activity-data');
    if (!el) return {};
    try {
      return JSON.parse(el.textContent || '{}');
    } catch (_) {
      return {};
    }
  }

  function initRoleDependentFields() {
    const roleInputs = document.querySelectorAll('input[name="role"]');
    if (!roleInputs.length) return;

    function apply() {
      const role = document.querySelector('input[name="role"]:checked')?.value;
      document.querySelectorAll('[data-role-dependent="student"]').forEach((el) => {
        if (!(el instanceof HTMLElement)) return;
        el.style.display = role === 'student' ? '' : 'none';
      });
    }

    roleInputs.forEach((i) => i.addEventListener('change', apply));
    apply();
  }

  async function initHeartbeat(playerEl) {
    const requires = playerEl.getAttribute('data-requires-heartbeat') === '1';
    if (!requires) return;

    async function tick() {
      await apiJson('/api/heartbeat.php', { t: Date.now() }).catch(() => null);
    }

    await tick();
    window.setInterval(tick, 60 * 1000);
  }

  const autosave = {
    async load(activityId) {
      return apiGetJson(`/api/autosave_get.php?activity_id=${encodeURIComponent(activityId)}`);
    },
    async save(activityId, state) {
      return apiJson('/api/autosave_save.php', { activity_id: activityId, state });
    },
    async clear(activityId) {
      return apiJson('/api/autosave_clear.php', { activity_id: activityId });
    }
  };

  async function initActivityPlayer() {
    const playerEl = document.querySelector('[data-activity-player]');
    if (!playerEl) return;

    await initHeartbeat(playerEl);

    const root = document.getElementById('activity-root');
    if (!root) return;

    const meta = window.PAMIKIL?.activity;
    if (!meta) return;

    const type = meta.type;
    const module = activities[type];
    if (!module || typeof module.init !== 'function') {
      root.innerHTML = '<div class="alert alert--error" role="alert">Activity module failed to load.</div>';
      return;
    }

    const data = getActivityData();
    const activityId = meta.id;

    let saved = null;
    if (meta.isStudent) {
      const res = await autosave.load(activityId);
      if (res.ok && res.state) saved = res.state;
    }

    module.init({
      root,
      data,
      saved,
      meta,
      api: {
        post: apiJson,
        get: apiGetJson,
      },
      autosave,
      speak,
    });
  }

  window.Pamikil = window.Pamikil || {};
  window.Pamikil.registerActivity = registerActivity;
  window.Pamikil.apiJson = apiJson;
  window.Pamikil.speak = speak;
  window.Pamikil.path = path;

  applyPrefs();

  document.addEventListener('DOMContentLoaded', () => {
    initPrefsUi();
    initRoleDependentFields();
    initActivityPlayer();
  });
})();
