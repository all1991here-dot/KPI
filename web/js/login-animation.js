(() => {
  'use strict';
  const canvas = document.querySelector('[data-login-network]');
  const scene = document.querySelector('.fardu-login-scene');
  if (!canvas || !scene) return;
  const reduced = matchMedia('(prefers-reduced-motion: reduce)');
  const ctx = canvas.getContext('2d');
  let points = [], width = 0, height = 0, frame = 0;
  const active = () => !document.hidden && !reduced.matches;
  function draw() {
    frame = 0;
    if (!ctx) return;
    ctx.clearRect(0, 0, width, height);
    const distance = Math.min(175, Math.max(110, width / 7));
    points.forEach((p, i) => {
      for (let j = i + 1; j < points.length; j++) {
        const q = points[j], d = Math.hypot(p.x - q.x, p.y - q.y);
        if (d < distance) {
          ctx.strokeStyle = `rgba(80,195,255,${(1 - d / distance) * .22})`;
          ctx.lineWidth = .8; ctx.beginPath(); ctx.moveTo(p.x, p.y); ctx.lineTo(q.x, q.y); ctx.stroke();
        }
      }
      ctx.beginPath(); ctx.fillStyle = 'rgba(118,222,255,.78)';
      ctx.shadowColor = '#40bdff'; ctx.shadowBlur = 11;
      ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2); ctx.fill(); ctx.shadowBlur = 0;
      if (active()) {
        p.x += p.vx; p.y += p.vy;
        if (p.x < -12) p.x = width + 12; if (p.x > width + 12) p.x = -12;
        if (p.y < -12) p.y = height + 12; if (p.y > height + 12) p.y = -12;
      }
    });
    if (active()) frame = requestAnimationFrame(draw);
  }
  function resize() {
    cancelAnimationFrame(frame);
    width = innerWidth; height = innerHeight;
    if (ctx) {
      const dpr = Math.min(devicePixelRatio || 1, 2);
      canvas.width = Math.round(width * dpr); canvas.height = Math.round(height * dpr);
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      const n = Math.min(85, Math.max(30, Math.round(width * height / 16500)));
      points = Array.from({length:n}, () => ({x:Math.random()*width,y:Math.random()*height,vx:(Math.random()-.5)*.5,vy:(Math.random()-.5)*.5,r:Math.random()*1.7+1}));
      draw();
    }
    resetRobot();
  }
  const robot = scene.querySelector('.login-robot'), bubble = scene.querySelector('.login-robot-bubble');
  const card = scene.querySelector('.fardu-login-card');
  let timers = [], prior = '';
  const later = (fn, ms) => { timers.push(setTimeout(fn, ms)); };
  const clamp = (v, lo, hi) => Math.max(lo, Math.min(hi, v));
  function resetRobot() {
    timers.forEach(clearTimeout); timers = [];
    robot?.classList.remove('is-peeking'); bubble?.classList.remove('show');
    if (robot && bubble && active()) later(peek, 1100);
  }
  function peek() {
    if (!active()) return;
    const p = card.getBoundingClientRect(), a = scene.getBoundingClientRect();
    const rw = robot.offsetWidth, rh = robot.offsetHeight;
    const space = {left:p.left,right:innerWidth-p.right,top:p.top,bottom:innerHeight-p.bottom};
    let sides = Object.keys(space).filter(s => (s !== 'bottom' || !document.querySelector('.login-footer')) && space[s] > (s === 'left' || s === 'right' ? rw*.55 : rh*.4));
    if (!sides.length) { later(peek, 5600); return; }
    const alternatives = sides.filter(s => s !== prior);
    if (alternatives.length) sides = alternatives;
    const side = sides[Math.floor(Math.random()*sides.length)]; prior = side;
    let x = a.width/2, y = a.height/2, hx = 0, hy = 0, tilt = 0;
    if (side === 'left' || side === 'right') {
      x = side === 'left' ? -rw*.27 : a.width+rw*.27;
      y = a.height*(.32+Math.random()*.36); hx = side === 'left' ? rw*.85 : -rw*.85;
      tilt = side === 'left' ? -8 : 8;
    } else {
      x = clamp(a.width*(.25+Math.random()*.5),rw*.5,a.width-rw*.5);
      y = side === 'top' ? -rh*.26 : a.height+rh*.26;
      hy = side === 'top' ? rh*.83 : -rh*.83; tilt = side === 'top' ? -5 : 5;
    }
    robot.style.left = `${x}px`; robot.style.top = `${y}px`;
    robot.style.setProperty('--hide-x',`${hx}px`); robot.style.setProperty('--hide-y',`${hy}px`);
    robot.style.setProperty('--tilt',`${tilt}deg`); robot.classList.add('is-peeking');
    // Show the greeting only when it fits wholly outside the unchanged card.
    const bw = bubble.offsetWidth, bh = bubble.offsetHeight;
    let bx = x, by = y;
    if (side === 'left') bx = -bw/2-12;
    if (side === 'right') bx = a.width+bw/2+12;
    if (side === 'top') by = -bh/2-12;
    if (side === 'bottom') by = a.height+bh/2+12;
    const fits = a.left+bx-bw/2 >= 8 && a.left+bx+bw/2 <= innerWidth-8 && a.top+by-bh/2 >= 8 && a.top+by+bh/2 <= innerHeight-8;
    bubble.style.left = `${bx}px`; bubble.style.top = `${by}px`;
    if (fits) later(() => bubble.classList.add('show'),550);
    later(() => { robot.classList.remove('is-peeking'); bubble.classList.remove('show'); },3700);
    later(peek,5600);
  }
  function resume() { cancelAnimationFrame(frame); if (ctx) draw(); resetRobot(); }
  addEventListener('resize',resize,{passive:true});
  document.addEventListener('visibilitychange',resume);
  reduced.addEventListener('change',resume);
  addEventListener('pagehide',() => { cancelAnimationFrame(frame); timers.forEach(clearTimeout); });
  addEventListener('pageshow',event => { if (event.persisted) resume(); });
  resize();
})();
