/* Entertainment Guarantors theme: header, reveal, hero video, poster slider, parallax, contact form. */
(function () {
  'use strict';
  const d = document;
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const $$ = (sel, root) => Array.from((root || d).querySelectorAll(sel));

  // Header: solid after scrolling, current page, mobile menu.
  const header = d.querySelector('[data-header]');
  const toggle = d.querySelector('.menu-toggle');
  const nav = d.getElementById('navigation');
  const adminBar = d.getElementById('wpadminbar');
  if (header) {
    // Solid once scrolled; sits under the WordPress admin bar while that is visible (it scrolls away on phones).
    const onScroll = () => {
      header.classList.toggle('scrolled', window.scrollY > 12);
      const bar = adminBar ? Math.max(0, adminBar.getBoundingClientRect().bottom) : 0;
      if (adminBar) header.style.top = bar + 'px';
      d.documentElement.style.setProperty('--bar', bar + 'px');
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll);
  }
  const norm = (p) => p.replace(/\/+$/, '') || '/';
  const here = norm(location.pathname);
  $$('.menu a').forEach((a) => {
    let path = '';
    try { path = norm(new URL(a.href, location.href).pathname); } catch (e) { return; }
    if (path === here) a.setAttribute('aria-current', 'page');
  });
  const closeMenu = () => {
    if (!toggle) return;
    toggle.setAttribute('aria-expanded', 'false');
    if (nav) nav.classList.remove('open');
  };
  if (toggle && nav) {
    toggle.addEventListener('click', () => {
      const open = toggle.getAttribute('aria-expanded') !== 'true';
      toggle.setAttribute('aria-expanded', String(open));
      nav.classList.toggle('open', open);
    });
    d.addEventListener('keydown', (e) => { if (e.key === 'Escape') { closeMenu(); toggle.focus(); } });
    nav.addEventListener('click', (e) => { if (e.target.closest('a')) closeMenu(); });
    window.addEventListener('resize', () => { if (window.innerWidth > 900) closeMenu(); });
  }

  // Scroll reveal with a small stagger for siblings that appear together.
  const items = $$('[data-reveal]');
  if ('IntersectionObserver' in window && !reduce) {
    const batch = new Map();
    const io = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        const parent = entry.target.parentElement;
        const n = batch.get(parent) || 0;
        batch.set(parent, n + 1);
        entry.target.style.transitionDelay = Math.min(n, 4) * 90 + 'ms';
        entry.target.classList.add('in');
        io.unobserve(entry.target);
        setTimeout(() => batch.set(parent, 0), 400);
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
    items.forEach((el) => io.observe(el));
  } else {
    items.forEach((el) => el.classList.add('in'));
  }

  // Hero video: the still shows first; the video fades in once it can play (HD only on wide screens).
  const video = d.querySelector('.hero-video');
  if (video && !reduce) {
    const connection = navigator.connection || {};
    if (window.innerWidth > 800 && !connection.saveData && video.dataset.hd) {
      video.src = video.dataset.hd;
    }
    video.addEventListener('playing', () => video.classList.add('playing'));
    video.muted = true;
    const start = () => { const p = video.play(); if (p && p.catch) p.catch(() => {}); };
    video.load();
    start();
    d.addEventListener('visibilitychange', () => { if (d.hidden) video.pause(); else start(); });
  }

  // Poster slider: arrows, keyboard, mouse drag.
  $$('[data-slider]').forEach((box) => {
    const track = box.querySelector('.slider-track');
    const prev = box.querySelector('.prev');
    const next = box.querySelector('.next');
    const step = () => Math.max(160, track.clientWidth * 0.8);
    const update = () => {
      const max = track.scrollWidth - track.clientWidth - 2;
      prev.disabled = track.scrollLeft <= 2;
      next.disabled = track.scrollLeft >= max;
    };
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
      if (moved) {
        track.classList.remove('dragging');
        const w = track.querySelector('.poster').offsetWidth + 24;
        track.scrollTo({ left: Math.round(track.scrollLeft / w) * w, behavior: 'smooth' });
      }
    };
    window.addEventListener('pointerup', end);
    window.addEventListener('pointercancel', end);
    track.addEventListener('dragstart', (e) => e.preventDefault());
    update();
  });

  // Gentle parallax for the full-width image and the services photo.
  const layers = $$('[data-parallax]').map((box) => ({ box, img: box.querySelector('img') })).filter((l) => l.img);
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
