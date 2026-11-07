"""All seven role scopes in dashboard, lists, reports, CSV and direct links.
Requires fresh role_scope_fixtures.php on port 8001.
"""
from pathlib import Path
from playwright.sync_api import sync_playwright
from urllib.parse import urlencode
import csv,io,json,shutil
BASE='http://127.0.0.1:8001/?'
OUT=Path(__file__).resolve().parents[1]/'docs/role-scope';OUT.mkdir(exist_ok=True)
scopes={
 'teacher':(10,{'A'},'Shaxsiy KPI paneli'),
 'head':(238,{'A','B-APPROVED','B-UNREVIEWED','NESTED','HEAD','B-DRAFT'},'Kafedra KPI paneli'),
 'faculty':(275,{'A','B-APPROVED','B-UNREVIEWED','NESTED','HEAD','B-DRAFT','C','FACULTY'},'Fakultet KPI paneli'),
 'expert':(29,{'B-APPROVED','EXPERT'},'Ekspert KPI paneli'),
 'operator':(365,None,'Universitet KPI boshqaruv paneli'),
 'admin':(365,None,'Universitet KPI boshqaruv paneli'),
 'manager':(365,None,'Universitet KPI boshqaruv paneli'),
}
all_keys={'A','B-APPROVED','B-UNREVIEWED','NESTED','HEAD','B-DRAFT','C','FACULTY','EXPERT','ADMIN','SUPER','MANAGEMENT','D','EXPERT-QUEUE'}
with sync_playwright() as p:
    browser=p.chromium.launch(executable_path=shutil.which('chromium'),headless=True,args=['--no-sandbox']);users={};errors=[]
    def url(route,**params):return BASE+urlencode({'r':route,**params})
    for name,(score,keys,title) in scopes.items():
        allowed=all_keys if keys is None else keys
        context=browser.new_context(viewport={'width':1440,'height':1000},reduced_motion='reduce');page=context.new_page();users[name]=(context,page);page.on('pageerror',lambda e:errors.append(str(e)))
        page.goto(url('auth/login'),wait_until='networkidle');page.locator('[name=username]').fill('http_'+name);page.locator('[name=password]').fill('OnlyForIsolated-HttpTests2026!');page.locator('.login-card button.button').click();page.wait_for_load_state('networkidle')
        assert page.locator('h1').inner_text()==title,name
        assert page.locator('.academic-metric strong').nth(0).inner_text().startswith(f'{score}.00'),name
        assert page.locator('.academic-metric strong').nth(1).inner_text().startswith(str(len(allowed))),name
        series=json.loads(page.locator('[data-academic-chart]').get_attribute('data-academic-chart'));assert sum(m['cents'] for m in series)==score*100,name
        if name in ['head','faculty','expert']:page.screenshot(path=str(OUT/f'{name}.png'),full_page=True)
        page.goto(url('kpi/applications'),wait_until='networkidle')
        actual=set(page.locator('tbody tr td:first-child b').all_inner_texts());assert actual=={'SCOPE-'+key for key in allowed},(name,actual)
        page.goto(url('kpi/reports'),wait_until='networkidle')
        assert page.locator('#application-status-report tbody tr').count()==len(allowed),name
        totals=page.locator('.report-results tbody td.score').all_inner_texts();assert round(sum(float(v.replace(',','').replace(' ','')) for v in totals),2)==score,name
        response=context.request.get(url('kpi/reports',format='csv'));rows=list(csv.reader(io.StringIO(response.body().decode('utf-8-sig'))));assert sum(float(r[4]) for r in rows[1:])==score,name
        if name=='manager':assert context.request.get(url('kpi/view',id=13)).status==200
    context,page=users['expert']
    assert context.request.get(url('kpi/view',id=3)).status==403
    page.goto(url('kpi/reports',employee=2),wait_until='networkidle')
    assert page.locator('#application-status-report tbody tr').count()==1
    assert 'SCOPE-B-APPROVED' in page.locator('#application-status-report').inner_text()
    assert 'SCOPE-B-UNREVIEWED' not in page.content()
    page.goto(url('kpi/reports',group='university'),wait_until='networkidle');assert 'Universitet jami' not in page.locator('[name=group]').inner_text()
    page.goto(url('kpi/applications',queue=1),wait_until='networkidle');assert page.locator('tbody tr').count()==1
    page.locator('tbody tr').get_by_role('link',name='Ko‘rish →',exact=True).click();page.wait_for_load_state('networkidle')
    assigned_url=page.url
    page.locator('[name=reason]').fill('Expert returns before approval');page.locator('button[name=action][value=return]').click();page.wait_for_load_state('networkidle')
    assert 'applications' in page.url and 'queue=1' in page.url
    owner_context=browser.new_context();owner=owner_context.new_page();owner.goto(url('auth/login'),wait_until='networkidle');owner.locator('[name=username]').fill('http_scope_d');owner.locator('[name=password]').fill('OnlyForIsolated-HttpTests2026!');owner.locator('.login-card button.button').click();owner.wait_for_load_state('networkidle')
    owner.goto(assigned_url,wait_until='networkidle');owner.get_by_role('button',name='Tekshiruvga yuborish →',exact=True).click();owner.wait_for_load_state('networkidle')
    page.goto(assigned_url,wait_until='networkidle')
    page.locator('[name=reason]').fill('Scope browser expert approval');page.locator('button[name=action][value=approve]').click();page.wait_for_load_state('networkidle')
    page.goto(url('kpi/index'),wait_until='networkidle');assert page.locator('.academic-metric strong').nth(0).inner_text().startswith('34.00')
    for width in [390,320]:
        page.set_viewport_size({'width':width,'height':844});page.wait_for_timeout(350);assert page.evaluate('document.documentElement.scrollWidth<=innerWidth+1')
    assert not errors,errors
    browser.close()
print('PASS: seven role totals, counts/charts, exact application sets, report tables/CSV, direct links/forged filters, expert queue and post-approval scope, mobile.')
