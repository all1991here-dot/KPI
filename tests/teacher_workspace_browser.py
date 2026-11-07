"""Personal teacher UI and server-side scoping, including forged filters and exports.
Run against a fresh tests/teacher_workspace_fixtures.php runtime on port 8001.
"""
from pathlib import Path
from urllib.parse import urlencode
from playwright.sync_api import sync_playwright
import csv
import io
import json
import shutil
import zipfile

ROOT=Path(__file__).resolve().parents[1]
BASE='http://127.0.0.1:8001/'
OUT=ROOT/'docs/teacher-workspace'
OUT.mkdir(exist_ok=True)
with sync_playwright() as p:
    browser=p.chromium.launch(executable_path=shutil.which('chromium'),headless=True,args=['--no-sandbox'])
    context=browser.new_context(viewport={'width':1440,'height':1050},reduced_motion='reduce')
    page=context.new_page();errors=[];page.on('pageerror',lambda e:errors.append(str(e)))
    def url(route,**params):return BASE+'?'+urlencode({'r':route,**params})
    def goto(route,**params):return page.goto(url(route,**params),wait_until='networkidle')
    def login(name):
        goto('auth/login');page.locator('[name=username]').fill('http_'+name)
        page.locator('[name=password]').fill('OnlyForIsolated-HttpTests2026!')
        page.locator('.login-card button.button').click();page.wait_for_load_state('networkidle')
    login('teacher')
    assert page.title()=='Shaxsiy KPI paneli'
    assert page.locator('h1').inner_text()=='Shaxsiy KPI paneli'
    assert 'Bo‘limlar reytingi' not in page.locator('#main').inner_text()
    assert page.locator('.academic-metric strong').nth(0).inner_text().startswith('15.00')
    assert page.locator('.academic-metric strong').nth(1).inner_text().startswith('5')
    series=json.loads(page.locator('[data-academic-chart]').get_attribute('data-academic-chart'))
    assert sum(m['cents'] for m in series)==1500
    assert 'PRIVATE-OTHER' not in page.content()
    assert 'PERSONAL-OWN-submitted' in page.content()
    page.locator('[data-theme-toggle]').click()
    page.screenshot(path=str(OUT/'dashboard.png'),full_page=True)
    for route in ['kpi/applications','kpi/notifications','kpi/reports','kpi/profile']:
        assert goto(route).status==200
        assert 'PRIVATE-OTHER' not in page.content(),route
    assert 'PERSONAL-OWN-DECISION' in goto('kpi/notifications').text()
    goto('kpi/applications',mine=0)
    assert page.locator('tbody tr').count()==5
    assert not page.locator('[name=category]').count()
    assert not page.locator('[name=mine]').count()
    assert 'Xodim' not in page.locator('thead').inner_text()
    goto('kpi/criteria',category='DSC')
    assert not page.locator('[name=category]').count()
    assert page.locator('.criterion').count()>0
    assert all(code.startswith('PHD-') for code in page.locator('.criterion .eyebrow').all_inner_texts())
    # Guessed links and foreign evidence remain protected by the existing access policy.
    assert goto('kpi/view',id=6).status==403
    assert goto('kpi/create',id=6).status==403
    assert context.request.get(url('kpi/download',id=1)).status==404
    for group in ['employee','department','unit','faculty','university']:
        params={'group':group,'employee':2,'org':4,'category':'DSC'}
        assert goto('kpi/reports',**params).status==200
        assert not page.locator('[name=group],[name=org],[name=category]').count()
        assert page.locator('tbody tr').count()==1
        assert 'HTTP teacher' in page.locator('tbody').inner_text()
        assert '15.00' in page.locator('tbody').inner_text()
        assert 'PRIVATE-OTHER' not in page.content()
        response=context.request.get(url('kpi/reports',format='csv',**params));assert response.status==200
        rows=list(csv.reader(io.StringIO(response.body().decode('utf-8-sig'))))
        assert len(rows)==2 and rows[1][1]=='HTTP teacher' and rows[1][4]=='15.00',rows
        response=context.request.get(url('kpi/reports',format='xlsx',**params));assert response.status==200
        with zipfile.ZipFile(io.BytesIO(response.body())) as archive:
            xml=archive.read('xl/worksheets/sheet1.xml').decode()
            assert 'HTTP teacher' in xml and '15.00' in xml and 'PRIVATE-OTHER' not in xml
    goto('kpi/index')
    for width in [390,320]:
        page.set_viewport_size({'width':width,'height':844});page.wait_for_timeout(350)
        assert page.evaluate('document.documentElement.scrollWidth<=innerWidth+1')
    page.screenshot(path=str(OUT/'mobile.png'),full_page=True)
    # A second teacher sees their own score and no first-teacher activity.
    context.clear_cookies();login('other')
    assert page.locator('.academic-metric strong').nth(0).inner_text().startswith('99.00')
    assert 'PERSONAL-OWN' not in page.content()
    # Staff retain organization-level controls and totals.
    context.clear_cookies();login('admin')
    assert page.locator('h1').inner_text()=='Universitet KPI boshqaruv paneli'
    assert 'Bo‘limlar reytingi' in page.locator('#main').inner_text()
    goto('kpi/reports',group='university')
    assert page.locator('[name=group]').count()==1
    assert '114.00' in page.locator('tbody').inner_text()
    goto('kpi/criteria',category='DSC')
    assert page.locator('[name=category]').count()==1
    assert all(code.startswith('DSC-') for code in page.locator('.criterion .eyebrow').all_inner_texts())
    assert not errors,errors
    browser.close()
print('PASS: teacher dashboard/counts/chart, own lists/notifications/category, foreign links denied, five forged report groups and CSV/XLSX exports isolated, second teacher and staff controls, mobile, no JS errors.')
