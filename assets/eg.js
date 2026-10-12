/* Entertainment Guarantors theme: header, menu, hero, film slider, reveal, parallax, contact form.
   Works on the editable block content (theme classes); all content stays visible without JavaScript. */
(function () {
  'use strict';
  const d = document;
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const $$ = (sel, root) => Array.from((root || d).querySelectorAll(sel));
  const arrowSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12h16M14 6l6 6-6 6"/></svg>';

  // Header: solid once scrolled; sits under the WordPress admin bar while that is visible (it scrolls away on phones).
  const header = d.querySelector('[data-header]');
  const adminBar = d.getElementById('wpadminbar');
  if (header) {
    const onScroll = () => {
      header.classList.toggle('scrolled', window.scrollY > 24);
      const bar = adminBar ? Math.max(0, adminBar.getBoundingClientRect().bottom) : 0;
      if (adminBar) header.style.top = bar + 'px';
      d.documentElement.style.setProperty('--bar', bar + 'px');
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll);
  }

  // Current page in the menus (also for the fallback menu).
  const norm = (p) => p.replace(/\/+$/, '') || '/';
  const here = norm(location.pathname);
  $$('.menu a, .footer-menu a').forEach((a) => {
    try { if (norm(new URL(a.href, location.href).pathname) === here) a.setAttribute('aria-current', 'page'); } catch (e) { /* ignore */ }
  });

  // Phone menu.
  const toggle = d.querySelector('.menu-toggle');
  const nav = d.getElementById('navigation');
  const closeMenu = () => { if (toggle) { toggle.setAttribute('aria-expanded', 'false'); nav.classList.remove('open'); } };
  if (toggle && nav) {
    toggle.addEventListener('click', () => {
      const open = toggle.getAttribute('aria-expanded') !== 'true';
      toggle.setAttribute('aria-expanded', String(open));
      nav.classList.toggle('open', open);
    });
    d.addEventListener('keydown', (e) => { if (e.key === 'Escape' && nav.classList.contains('open')) { closeMenu(); toggle.focus(); } });
    nav.addEventListener('click', (e) => { if (e.target.closest('a')) closeMenu(); });
    window.addEventListener('resize', () => { if (window.innerWidth > 960) closeMenu(); });
  }

  // Hero headline: each line (split at the line break typed in the editor) rises in; the second is italic.
  $$('.eg-hero-title').forEach((h) => {
    const lines = h.innerHTML.split(/<br\s*\/?>/i).map((s) => s.trim()).filter(Boolean);
    if (!lines.length) return;
    h.setAttribute('aria-label', h.textContent.replace(/\s+/g, ' ').trim());
    h.innerHTML = lines.map((l) => '<span class="line" aria-hidden="true"><span>' + l + '</span></span>').join('');
  });

  // Hero video: the still shows first; the video fades in once playing (HD on wide screens only).
  const video = d.querySelector('.eg-hero-video');
  if (video && !reduce) {
    const connection = navigator.connection || {};
    if (window.innerWidth > 800 && !connection.saveData && video.dataset.hd) video.src = video.dataset.hd;
    video.addEventListener('playing', () => video.classList.add('playing'));
    video.muted = true;
    const start = () => { const p = video.play(); if (p && p.catch) p.catch(() => {}); };
    video.load();
    start();
    d.addEventListener('visibilitychange', () => { if (d.hidden) video.pause(); else start(); });
  }

  // Film slider: progress line, arrows, keyboard and mouse drag (native swipe on touch).
  $$('.eg-slider').forEach((track) => {
    track.setAttribute('tabindex', '0');
    track.setAttribute('aria-label', 'Film posters');
    const ui = d.createElement('div');
    ui.className = 'eg-slider-ui';
    ui.innerHTML = '<div class="eg-progress" aria-hidden="true"><span></span></div>'
      + '<button class="eg-arrow prev" type="button" aria-label="Previous posters" style="transform:scaleX(-1)">' + arrowSvg + '</button>'
      + '<button class="eg-arrow next" type="button" aria-label="Next posters">' + arrowSvg + '</button>';
    track.after(ui);
    const bar = ui.querySelector('.eg-progress span');
    const prev = ui.querySelector('.prev');
    const next = ui.querySelector('.next');
    const update = () => {
      const max = track.scrollWidth - track.clientWidth;
      const ratio = track.scrollWidth ? track.clientWidth / track.scrollWidth : 1;
      bar.style.width = (Math.min(1, ratio) * 100).toFixed(1) + '%';
      bar.style.transform = 'translateX(' + (max > 0 ? (track.scrollLeft / max) * ((1 / Math.min(1, ratio)) - 1) * 100 : 0).toFixed(1) + '%)';
      prev.disabled = track.scrollLeft <= 2;
      next.disabled = track.scrollLeft >= max - 2;
      ui.hidden = max <= 2;
    };
    const step = () => Math.max(180, track.clientWidth * 0.75);
    const go = (dir) => track.scrollBy({ left: dir * step(), behavior: reduce ? 'auto' : 'smooth' });
    prev.addEventListener('click', () => go(-1));
    next.addEventListener('click', () => go(1));
    track.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
    track.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowRight') { e.preventDefault(); go(1); }
      if (e.key === 'ArrowLeft') { e.preventDefault(); go(-1); }
    });
    let startX = 0, startLeft = 0, down = false, moved = false;
    track.addEventListener('pointerdown', (e) => {
      if (e.pointerType !== 'mouse' || e.button !== 0) return;
      down = true; moved = false; startX = e.clientX; startLeft = track.scrollLeft;
    });
    window.addEventListener('pointermove', (e) => {
      if (!down) return;
      const dx = e.clientX - startX;
      if (Math.abs(dx) > 4) { moved = true; track.classList.add('dragging'); }
      if (moved) track.scrollLeft = startLeft - dx;
    });
    const end = () => {
      if (!down) return;
      down = false;
      if (!moved) return;
      track.classList.remove('dragging');
      const card = track.firstElementChild;
      const w = card ? card.getBoundingClientRect().width + parseFloat(getComputedStyle(track).columnGap || 0) : 300;
      track.scrollTo({ left: Math.round(track.scrollLeft / w) * w, behavior: 'smooth' });
    };
    window.addEventListener('pointerup', end);
    window.addEventListener('pointercancel', end);
    track.addEventListener('dragstart', (e) => e.preventDefault());
    track.addEventListener('click', (e) => { if (moved) { e.preventDefault(); moved = false; } }, true);
    update();
    window.addEventListener('load', update);
  });

  // Scroll reveal for the editable sections (classes added here, so nothing hides without JavaScript).
  const revealTargets = $$([
    '.eg-intro-title', '.eg-intro-text p', '.eg-card', '.eg-band-img', '.eg-cta-inner > *', '.eg-lead', '.eg-member',
    '.eg-partners-title', '.eg-logo', '.eg-what-lead > *', '.eg-service', '.eg-insurance-text > *', '.eg-insurance-img',
    '.eg-eyebrow', '.eg-contact-lead', '.eg-person', '.eg-form-card', '.footer-top > *'
  ].join(','));
  if ('IntersectionObserver' in window && !reduce) {
    const batch = new Map();
    const io = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        const parent = entry.target.parentElement;
        const n = batch.get(parent) || 0;
        batch.set(parent, n + 1);
        entry.target.style.transitionDelay = Math.min(n, 5) * 90 + 'ms';
        entry.target.classList.add('in');
        io.unobserve(entry.target);
        setTimeout(() => batch.set(parent, 0), 450);
      });
    }, { rootMargin: '0px 0px -6% 0px', threshold: 0.06 });
    revealTargets.forEach((el) => { el.classList.add('reveal'); io.observe(el); });
  }

  // Gentle parallax inside the framed images.
  const layers = $$('.eg-band-img, .eg-insurance-img').map((box) => ({ box, img: box.querySelector('img') })).filter((l) => l.img);
  if (layers.length && !reduce) {
    let ticking = false;
    const run = () => {
      ticking = false;
      const vh = window.innerHeight;
      layers.forEach(({ box, img }) => {
        const r = box.getBoundingClientRect();
        if (r.bottom < -100 || r.top > vh + 100) return;
        const progress = (r.top + r.height / 2 - vh / 2) / vh;
        img.style.transform = 'translate3d(0,' + (progress * -7).toFixed(2) + '%,0)';
      });
    };
    const request = () => { if (!ticking) { ticking = true; requestAnimationFrame(run); } };
    window.addEventListener('scroll', request, { passive: true });
    window.addEventListener('resize', request);
    run();
  }

  // Contact form: field validation, then an in-page submit (falls back to a normal post).
  $$('[data-eg-form]').forEach((form) => {
    let loaded = Date.now();
    const elapsed = form.querySelector('[data-elapsed]');
    const status = form.querySelector('.form-status');
    const setError = (field, message) => {
      const wrap = field.closest('.field');
      field.setAttribute('aria-invalid', message ? 'true' : 'false');
      if (wrap) wrap.querySelector('.field-error').textContent = message || '';
    };
    const say = (cls, text) => { status.innerHTML = ''; const p = d.createElement('p'); p.className = cls; p.textContent = text; status.appendChild(p); };
    form.addEventListener('submit', async (e) => {
      elapsed.value = String(Date.now() - loaded);
      let first = null;
      form.querySelectorAll('input:not([type=hidden]):not([name=eg_website]), textarea').forEach((field) => {
        const label = field.closest('.field').querySelector('label').firstChild.textContent.trim();
        let message = '';
        if (field.required && !field.value.trim()) message = label + ' is required.';
        else if (field.type === 'email' && field.value && !field.checkValidity()) message = 'Please enter a valid email address.';
        setError(field, message);
        if (message && !first) first = field;
      });
      e.preventDefault();
      if (first) { say('form-error', 'Please check the highlighted fields.'); first.focus(); return; }
      form.classList.add('sending');
      try {
        const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        const result = await response.json();
        if (result.ok) {
          form.reset();
          loaded = Date.now();
          say('form-success', result.message);
        } else {
          say('form-error', result.message);
          Object.entries(result.errors || {}).forEach(([key, message]) => { const field = form.querySelector('[name="eg_' + key + '"]'); if (field) setError(field, message); });
        }
      } catch (error) {
        HTMLFormElement.prototype.submit.call(form);
        return;
      } finally {
        form.classList.remove('sending');
      }
      status.scrollIntoView({ block: 'nearest', behavior: reduce ? 'auto' : 'smooth' });
    });
  });
})();
