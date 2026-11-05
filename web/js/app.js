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
  document.querySelectorAll('[data-nav-group-toggle]').forEach(button => {
    button.addEventListener('click', () => {
      const panel = document.getElementById(button.getAttribute('aria-controls'));
      if (!panel) return;
      const open = button.getAttribute('aria-expanded') !== 'true';
      button.setAttribute('aria-expanded', String(open)); panel.hidden = !open;
    });
  });
  const svgNS = 'http://www.w3.org/2000/svg';
  document.querySelectorAll('[data-academic-chart]').forEach((container, chartIndex) => {
    let all;
    try { all = JSON.parse(container.dataset.academicChart); } catch (_) { return; }
    if (!Array.isArray(all) || !all.length) return;
    const svg = container.querySelector('svg');
    const output = container.querySelector('output');
    const card = container.closest('.academic-trend-card');
    const number = cents => (Number(cents) / 100).toLocaleString('uz-UZ', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    const create = (tag, attrs = {}, text = null) => {
      const element = document.createElementNS(svgNS, tag);
      for (const [key, value] of Object.entries(attrs)) element.setAttribute(key, String(value));
      if (text !== null) element.textContent = text;
      return element;
    };
    const draw = rows => {
      svg.replaceChildren();
      const max = Math.max(100, Math.ceil(Math.max(...rows.map(row => Number(row.cents))) / 1000) * 1000);
      const left = 52, right = 894, top = 22, bottom = 220;
      const points = rows.map((row, index) => ({...row, x: left + index * (right - left) / Math.max(1, rows.length - 1), y: bottom - Number(row.cents) / max * (bottom - top)}));
      svg.appendChild(create('title', {}, 'Faoliyat sanasi bo‘yicha tasdiqlangan KPI ballari'));
      const defs = create('defs');
      const gradient = create('linearGradient', {id: `academic-gradient-${chartIndex}`, x1: 0, y1: 0, x2: 0, y2: 1});
      gradient.append(create('stop', {offset: '0%', 'stop-color': '#1263dc', 'stop-opacity': '.22'}), create('stop', {offset: '100%', 'stop-color': '#1263dc', 'stop-opacity': '.01'}));
      defs.appendChild(gradient); svg.appendChild(defs);
      for (let tick = 0; tick <= 4; tick++) {
        const y = bottom - tick / 4 * (bottom - top);
        svg.append(create('line', {x1: left, y1: y, x2: right, y2: y, class: 'chart-grid-line'}));
        svg.append(create('text', {x: left - 10, y: y + 4, 'text-anchor': 'end', class: 'chart-axis-label'}, number(max * tick / 4)));
      }
      const path = points.map((point, index) => `${index ? 'L' : 'M'}${point.x},${point.y}`).join(' ');
      svg.appendChild(create('path', {d: `${path} L${points[points.length - 1].x},${bottom} L${points[0].x},${bottom} Z`, fill: `url(#academic-gradient-${chartIndex})`, 'aria-hidden': 'true'}));
      svg.appendChild(create('path', {d: path, pathLength: 1, class: 'chart-line is-animated', 'aria-hidden': 'true'}));
      const stride = Math.max(1, Math.ceil(points.length / 12));
      points.forEach((point, index) => {
        const label = `${point.label}: ${number(point.cents)} ball`;
        const circle = create('circle', {cx: point.x, cy: point.y, r: 3.4, class: 'chart-point', tabindex: 0, role: 'img', 'aria-label': label});
        circle.appendChild(create('title', {}, label));
        const show = () => { if (output) output.textContent = label; };
        circle.addEventListener('pointerenter', show); circle.addEventListener('focus', show);
        svg.appendChild(circle);
        if (index % stride === 0 || index === points.length - 1) svg.appendChild(create('text', {x: point.x, y: 248, 'text-anchor': 'middle', class: 'chart-axis-label'}, point.label.split(' ')[0]));
      });
      if (output) output.textContent = rows.every(row => !Number(row.cents)) ? 'Tanlangan oylar bo‘yicha tasdiqlangan ball yo‘q.' : 'Oy bo‘yicha ballni ko‘rish uchun nuqtani tanlang.';
      svg.setAttribute('aria-label', `${rows[0].label} — ${rows[rows.length - 1].label}: oylar bo‘yicha tasdiqlangan KPI ballari`);
    };
    draw(all);
    card?.querySelectorAll('[data-chart-range]').forEach(button => button.addEventListener('click', () => {
      card.querySelectorAll('[data-chart-range]').forEach(item => item.setAttribute('aria-pressed', String(item === button)));
      const length = Number(button.dataset.chartRange);
      draw(length > 0 ? all.slice(-length) : all);
    }));
  });
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
