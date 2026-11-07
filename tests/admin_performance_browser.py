"""Native single-process PHP: role pagination, zero photo requests and bounded theme work.
Requires admin_performance_fixtures.php on port 8001.
"""
from pathlib import Path
from playwright.sync_api import sync_playwright
from urllib.parse import urlencode
import shutil
BASE='http://127.0.0.1:8001/?'
OUT=Path(__file__).resolve().parents[1]/'docs/admin-performance';OUT.mkdir(exist_ok=True)
with sync_playwright() as p:
    browser=p.chromium.launch(executable_path=shutil.which('chromium'),headless=True,args=['--no-sandbox'])
    context=browser.new_context(viewport={'width':1440,'height':1000},device_scale_factor=2)
    context.add_init_script("localStorage.setItem('kpi-galaxy-theme','dark');window.networkPaints=0;const oldClear=CanvasRenderingContext2D.prototype.clearRect;CanvasRenderingContext2D.prototype.clearRect=function(...a){if(this.canvas.classList.contains('kpi-digital-network'))window.networkPaints++;return oldClear.apply(this,a)}")
    page=context.new_page();errors=[];photos=[]
    page.on('pageerror',lambda e:errors.append(str(e)))
    page.on('request',lambda r:photos.append(r.url) if 'employee-photo' in r.url or 'student.fdu.uz' in r.url else None)
    def url(route,**params):return BASE+urlencode({'r':route,**params})
    def goto(route,**params):return page.goto(url(route,**params),wait_until='networkidle')
    def login(username):
        goto('auth/login');page.locator('[name=username]').fill(username);page.locator('[name=password]').fill('OnlyForIsolated-HttpTests2026!');page.locator('.login-card button.button').click();page.wait_for_load_state('networkidle')
    login('http_admin');assert page.locator('html').get_attribute('data-theme')=='light'
    assert page.locator('body').get_attribute('data-network-motion')=='static'
    goto('admin/users');assert page.locator('tr[data-user-id]').count()==50
    assert not page.locator('[data-employee-photo]').count()
    assert page.get_by_role('navigation',name='Foydalanuvchi rollari').get_by_role('link').count()==9
    page.screenshot(path=str(OUT/'users-roles.png'))
    seen=set()
    for number in range(1,5):
        goto('admin/users',role='teacher',q='Mass',page=number)
        rows=page.locator('tr[data-user-id]');assert rows.count()==(50 if number<4 else 30)
        for identity in rows.evaluate_all('(rows)=>rows.map(r=>r.dataset.userId)'):assert identity not in seen;seen.add(identity)
        assert all('O‘qituvchi' in text for text in rows.all_inner_texts())
    assert len(seen)==180
    goto('admin/users',role='teacher',q='Mass',status='inactive');assert page.locator('tr[data-user-id]').count()==26
    assert goto('admin/users',role='invalid').status==400
    goto('admin/users',q='http_teacher');assert page.locator('tr[data-username="http_teacher"]').count()==1
    page.locator('tr[data-username="http_teacher"]').get_by_role('link',name='Tahrirlash',exact=True).click();page.wait_for_load_state('networkidle')
    assert not page.locator('[data-employee-photo],[data-photo-check]').count()
    goto('kpi/profile');assert not page.locator('[data-employee-photo]').count()
    goto('admin/users',role='expert')
    before=page.evaluate('window.networkPaints');page.wait_for_timeout(400);assert page.evaluate('window.networkPaints')==before
    # Stress a high-DPI screen and many mode switches; layout stays clickable with a bounded canvas.
    page.set_viewport_size({'width':3840,'height':2160});page.wait_for_timeout(200)
    elapsed=page.evaluate('''()=>{const start=performance.now();for(let i=0;i<40;i++)document.querySelector('[data-theme-toggle]').click();return performance.now()-start}''')
    assert elapsed<2000,elapsed
    page.wait_for_timeout(200)
    assert page.locator('.kpi-digital-network').evaluate('(e)=>e.width*e.height')<1550000
    before=page.evaluate('window.networkPaints');page.wait_for_timeout(400);assert page.evaluate('window.networkPaints')==before
    for _ in range(4):page.locator('[data-theme-toggle]').click()
    page.locator('[data-theme-toggle]').click();page.reload(wait_until='networkidle');assert page.locator('html').get_attribute('data-theme')=='dark'
    page.locator('[data-theme-toggle]').click();page.reload(wait_until='networkidle');assert page.locator('html').get_attribute('data-theme')=='light'
    assert not photos,photos
    # Old cached image URLs must fail quickly without contacting a remote image service.
    response=context.request.get(url('kpi/employee-photo',id=1));assert response.status==404
    teacher_context=browser.new_context();teacher=teacher_context.new_page();teacher.goto(url('auth/login'),wait_until='networkidle');teacher.locator('[name=username]').fill('http_teacher');teacher.locator('[name=password]').fill('OnlyForIsolated-HttpTests2026!');teacher.locator('.login-card button.button').click();teacher.wait_for_load_state('networkidle')
    assert teacher.locator('body').get_attribute('data-network-motion')=='animated'
    first=teacher.locator('.kpi-digital-network').evaluate('(e)=>e.toDataURL()');teacher.wait_for_timeout(200);assert teacher.locator('.kpi-digital-network').evaluate('(e)=>e.toDataURL()')!=first
    teacher.emulate_media(reduced_motion='reduce');teacher.wait_for_timeout(100);first=teacher.locator('.kpi-digital-network').evaluate('(e)=>e.toDataURL()');teacher.wait_for_timeout(200);assert teacher.locator('.kpi-digital-network').evaluate('(e)=>e.toDataURL()')==first
    assert not errors,errors
    browser.close()
print('PASS: light default, saved mode, static admin background, high-DPI/40 toggles, role tabs/search/status, 180 users paginated, zero photo requests, disabled old endpoint, teacher animation and reduced motion.')
