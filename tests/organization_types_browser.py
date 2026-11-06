"""Faculty/department/unit separation using realistic HEMIS type labels."""
from pathlib import Path
from playwright.sync_api import sync_playwright
import shutil, re
ROOT=Path(__file__).resolve().parents[1];out=ROOT/'docs/organization-types';out.mkdir(exist_ok=True)
base='http://127.0.0.1:8001/';password='OnlyForIsolated-HttpTests2026!'
with sync_playwright() as p:
    browser=p.chromium.launch(executable_path=shutil.which('chromium'),headless=True,args=['--no-sandbox'])
    context=browser.new_context(viewport={'width':1440,'height':1050});context.route('https://student.fdu.uz/**',lambda r:r.abort())
    page=context.new_page();errors=[];page.on('pageerror',lambda e:errors.append(str(e)))
    def goto(route):return page.goto(base+'?r='+route,wait_until='networkidle')
    def login(name):goto('auth/login');page.locator('[name=username]').fill('http_'+name);page.locator('[name=password]').fill(password);page.locator('.login-card button.button').click();page.wait_for_load_state('networkidle')
    login('admin');goto('admin/structure')
    assert 'Amaliy ingliz' not in page.locator('#faculties').inner_text()
    assert 'Buxgalteriya' not in page.locator('#faculties').inner_text()
    assert 'Chet tillari fakulteti' in page.locator('#faculties').inner_text()
    row=page.locator('#departments tbody tr').filter(has_text='Amaliy ingliz tili kafedrasi');assert 'Chet tillari fakulteti' in row.inner_text()
    assert 'Buxgalteriya hisobi va moliya' in page.locator('#units').inner_text()
    assert 'Turini tekshiring' in page.locator('#units').inner_text()
    page.screenshot(path=str(out/'classified-structure.png'),full_page=True)
    page.locator('#units tbody tr').filter(has_text='Yangi aniqlanmagan tuzilma').get_by_role('link',name='Tahrirlash').click();page.wait_for_load_state('networkidle')
    page.locator('[name=name]').fill('O‘quv bo‘limi');page.locator('[name=kind]').select_option('unit');page.get_by_role('button',name='Saqlash',exact=True).click();page.wait_for_load_state('networkidle')
    goto('admin/structure');assert not page.locator('#units').get_by_text('Turini tekshiring',exact=True).count()
    page.get_by_role('link',name='+ Bo‘lim yaratish',exact=True).click();page.wait_for_load_state('networkidle');assert page.locator('[name=kind]').input_value()=='unit'
    page.locator('[name=name]').fill('Sport markazi');page.locator('[name=parent_id]').select_option('1');page.get_by_role('button',name='Saqlash',exact=True).click();page.wait_for_load_state('networkidle');unit_id=re.search(r'id=(\d+)',page.url).group(1)
    page.screenshot(path=str(out/'unit-editor.png'),full_page=True)
    goto('admin/users');page.locator('tr[data-username="http_office"]').get_by_role('link',name='Tahrirlash').click();page.wait_for_load_state('networkidle')
    assert page.locator('[data-org-level=unit]').input_value()
    page.locator('[data-org-level=unit]').select_option(unit_id);page.get_by_role('button',name='Saqlash',exact=True).click();page.wait_for_load_state('networkidle')
    assert page.locator('[name=org_id]').input_value()==unit_id
    assert page.locator('[data-org-level=faculty]').input_value()==''
    goto('admin/users');row=page.locator('tr[data-username="http_office"]');assert 'Sport markazi' in row.inner_text();assert row.locator('td').nth(2).inner_text()=='Yo‘q'
    assert goto('kpi/reports&group=unit').status==200
    goto('admin/structure-edit&kind=faculty');page.locator('[name=name]').fill('Manual incorrect classification');page.locator('[name=parent_id]').select_option('1');page.get_by_role('button',name='Saqlash',exact=True).click();page.wait_for_load_state('networkidle');manual_id=re.search(r'id=(\d+)',page.url).group(1)
    page.locator('[name=kind]').select_option('department');page.locator('[name=parent_id]').select_option('2');page.get_by_role('button',name='Saqlash',exact=True).click();page.wait_for_load_state('networkidle');assert page.locator('[name=kind]').input_value()=='department'
    goto('admin/structure');assert 'Manual incorrect classification' not in page.locator('#faculties').inner_text();assert 'Manual incorrect classification' in page.locator('#departments').inner_text()
    for width in [390,320]:page.set_viewport_size({'width':width,'height':900});page.wait_for_timeout(100);assert page.evaluate('document.documentElement.scrollWidth<=innerWidth')
    context.clear_cookies();page.set_viewport_size({'width':1440,'height':1050});login('operator');goto('admin/structure-edit&id='+manual_id)
    assert page.locator('[name=kind]').get_attribute('type')=='hidden'
    csrf=page.locator('meta[name=csrf-param]').get_attribute('content');token=page.locator('meta[name=csrf-token]').get_attribute('content')
    assert context.request.post(page.url,form={csrf:token,'operation':'save','kind':'unit','parent_id':'1','name':'Forbidden type change'}).status==403
    assert not errors,errors
    browser.close()
print('PASS: faculty/department/unit separation, unknown review, unit CRUD/editor, office employee assignment, manual type correction, report view, mobile widths and reclassification authorization.')
