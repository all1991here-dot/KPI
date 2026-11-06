"""Superadmin CRUD and forged ordinary-admin POST checks against isolated fixtures."""
from pathlib import Path
from playwright.sync_api import sync_playwright
import shutil, re, time
ROOT=Path(__file__).resolve().parents[1];out=ROOT/'docs/management';out.mkdir(exist_ok=True)
base='http://127.0.0.1:8001/';password='OnlyForIsolated-HttpTests2026!'
with sync_playwright() as p:
    browser=p.chromium.launch(executable_path=shutil.which('chromium'),headless=True,args=['--no-sandbox'])
    context=browser.new_context(viewport={'width':1440,'height':1050});context.route('https://student.fdu.uz/**',lambda route:route.abort())
    page=context.new_page();errors=[];page.on('pageerror',lambda e:errors.append(str(e)))
    def goto(route):return page.goto(base+'?r='+route,wait_until='networkidle')
    def login(name):
        goto('auth/login');page.locator('[name=username]').fill('http_'+name);page.locator('[name=password]').fill(password);page.locator('.login-card button.button').click();page.wait_for_load_state('networkidle')
    login('admin');goto('admin/indicators')
    for key,value in {'code':'BROWSER-CUSTOM','name':'Browser custom KPI','section':'BROWSER','section_name':'Browser direction','criterion_code':'BROWSER-1','criterion_name':'Browser condition','base_score':'3'}.items():page.locator(f'[name={key}]').fill(value)
    page.get_by_role('button',name='Mezonni saqlash',exact=True).click();page.wait_for_load_state('networkidle')
    row=page.locator('tbody tr').filter(has_text='BROWSER-CUSTOM');assert row.count()==1
    row.get_by_role('link',name='Tahrirlash').click();page.wait_for_load_state('networkidle');page.locator('[name=name]').fill('Browser edited KPI');page.get_by_role('button',name='Mezonni saqlash',exact=True).click();page.wait_for_load_state('networkidle')
    assert page.locator('tbody tr').filter(has_text='Browser edited KPI').count()==1
    page.screenshot(path=str(out/'indicators.png'),full_page=True)
    page.locator('tbody tr').filter(has_text='BROWSER-CUSTOM').get_by_role('button',name='O‘chirish').click();page.wait_for_load_state('networkidle');assert not page.locator('tbody tr').filter(has_text='BROWSER-CUSTOM').count()
    goto('admin/structure');page.locator('[name=name]').fill('Browser delete university');page.get_by_role('button',name='Saqlash',exact=True).click();page.wait_for_load_state('networkidle')
    page.locator('tbody tr').filter(has_text='Browser delete university').get_by_role('button',name='O‘chirish').click();page.wait_for_load_state('networkidle');assert not page.locator('tbody tr').filter(has_text='Browser delete university').count()
    goto('admin/users')
    for key,value in {'name':'Browser delete employee','username':'browser_delete_employee','password':password}.items():page.locator(f'input[name={key}]').fill(value)
    page.get_by_role('button',name='Saqlash',exact=True).click();page.wait_for_load_state('networkidle')
    row=page.locator('tbody tr').filter(has=page.locator('td',has_text=re.compile('^browser_delete_employee$')));assert row.count()==1
    row.get_by_role('button',name='O‘chirish').click();page.wait_for_load_state('networkidle');assert not page.locator('tbody tr').filter(has_text='browser_delete_employee').count()
    # Capture real target IDs before testing direct unauthorized requests.
    target=page.locator('tbody tr').filter(has=page.locator('td',has_text=re.compile('^http_teacher$'))).get_by_role('link',name='Tahrirlash').get_attribute('href')
    target_id=re.search(r'id=(\d+)',target).group(1)
    context.clear_cookies();login('operator');goto('admin/users')
    assert not page.locator('input[name=username]').count()
    assert not page.get_by_role('button',name='O‘chirish').count()
    goto('admin/structure');assert not page.locator('[name=name]').count();assert not page.get_by_role('button',name='O‘chirish').count()
    assert goto('admin/indicators').status==403
    goto('admin/hemis');assert not page.get_by_role('button',name='Xodimlarni import qilish').count()
    # CSRF-valid POST must still fail server authorization.
    token=page.locator('meta[name=csrf-token]').get_attribute('content')
    csrf=page.locator('meta[name=csrf-param]').get_attribute('content')
    for route,data in [
        ('admin/users',{'username':'forged_user','name':'Forged user','password':password}),
        ('admin/structure',{'name':'Forged org','kind':'university'}),
        ('admin/delete-user&id='+target_id,{}),('admin/delete-structure&id=1',{}),
        ('admin/indicators',{'name':'Forged criterion'}),('admin/rules',{}),
        ('admin/hemis',{'operation':'employees'}),('admin/hemis',{'operation':'structure'}),
    ]:
        response=context.request.post(base+'?r='+route,form={csrf:token,**data});assert response.status==403,(route,response.status)
    assert not errors,errors
    browser.close()
print('PASS: superadmin adds/edits/deletes criteria, adds/deletes structure and employee; admin controls hidden and eight forged POST paths rejected.')
