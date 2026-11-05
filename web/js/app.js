(() => {
  'use strict';
  const root = document.documentElement;
  root.classList.add('has-js');
  let theme = 'light';
  try { theme = localStorage.getItem('kpi-theme') === 'dark' ? 'dark' : 'light'; } catch (_) {}
  const applyTheme = (value) => {
    theme = value;
    root.dataset.theme = value;
    document.querySelectorAll('[data-theme-toggle]').forEach(button => {
      button.setAttribute('aria-pressed', String(value === 'dark'));
      button.setAttribute('aria-label', value === 'dark' ? 'Kunduzgi rejimni yoqish' : 'Tungi rejimni yoqish');
      button.title = button.getAttribute('aria-label');
    });
  };
  applyTheme(theme);
  document.querySelectorAll('[data-theme-toggle]').forEach(button => button.addEventListener('click', () => {
    applyTheme(theme === 'dark' ? 'light' : 'dark');
    try { localStorage.setItem('kpi-theme', theme); } catch (_) {}
  }));
  const sidebar = document.getElementById('app-sidebar');
  const toggle = document.querySelector('[data-menu-toggle]');
  const overlay = document.querySelector('.menu-overlay');
  const workspace = document.getElementById('app-workspace');
  let previousFocus = null;
  const setMenu = (open) => {
    if (!sidebar || !toggle) return;
    if (open) previousFocus = document.activeElement;
    root.classList.toggle('menu-open', open);
    toggle.setAttribute('aria-expanded', String(open));
    if (overlay) overlay.hidden = !open;
    if (workspace) workspace.inert = open;
    if (open) {
      sidebar.setAttribute('role', 'dialog');
      sidebar.setAttribute('aria-modal', 'true');
      sidebar.querySelector('[data-menu-close]')?.focus();
    } else {
      sidebar.removeAttribute('role');
      sidebar.removeAttribute('aria-modal');
      if (previousFocus?.offsetParent) previousFocus.focus();
    }
  };
  toggle?.addEventListener('click', () => setMenu(!root.classList.contains('menu-open')));
  document.querySelectorAll('[data-menu-close]').forEach(button => button.addEventListener('click', () => setMenu(false)));
  document.addEventListener('keydown', event => {
    if (!root.classList.contains('menu-open')) return;
    if (event.key === 'Escape') { event.preventDefault(); setMenu(false); }
    if (event.key === 'Tab') {
      const links = [...sidebar.querySelectorAll('a[href], button:not([disabled])')].filter(element => element.offsetParent);
      const first = links[0], last = links[links.length - 1];
      if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
      else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
    }
  });
  window.matchMedia('(min-width: 1001px)').addEventListener('change', event => { if (event.matches) setMenu(false); });
  document.querySelectorAll('.table-wrap').forEach(wrap => {
    const hint = document.createElement('p'); hint.className = 'table-scroll-hint';
    hint.textContent = '↔ Jadvalning qolgan qismini ko‘rish uchun yon tomonga suring.';
    wrap.parentNode.insertBefore(hint, wrap);
    const update = () => {
      const scrollable = wrap.scrollWidth > wrap.clientWidth + 1;
      hint.hidden = !scrollable; wrap.tabIndex = scrollable ? 0 : -1;
      if (scrollable) { wrap.setAttribute('role', 'region'); wrap.setAttribute('aria-label', 'Yon tomonga suriladigan jadval'); }
      else { wrap.removeAttribute('role'); wrap.removeAttribute('aria-label'); }
    };
    if (window.ResizeObserver) new ResizeObserver(update).observe(wrap);
    window.addEventListener('resize', update, {passive: true}); update();
  });
  // A visible label and a native button keep password visibility keyboard accessible.
  document.querySelectorAll('input[type="password"]').forEach(input => {
    const control = document.createElement('div'); control.className = 'password-control';
    input.parentNode.insertBefore(control, input); control.appendChild(input);
    const button = document.createElement('button'); button.type = 'button'; button.className = 'password-toggle';
    button.textContent = 'Ko‘rsatish'; button.setAttribute('aria-label', 'Parolni ko‘rsatish'); button.setAttribute('aria-pressed', 'false');
    button.addEventListener('click', () => {
      const visible = input.type === 'password'; input.type = visible ? 'text' : 'password';
      button.textContent = visible ? 'Yashirish' : 'Ko‘rsatish';
      button.setAttribute('aria-label', visible ? 'Parolni yashirish' : 'Parolni ko‘rsatish');
      button.setAttribute('aria-pressed', String(visible));
    });
    control.appendChild(button);
  });
})();
