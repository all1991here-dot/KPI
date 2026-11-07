"""User editor and both bulk delete flows. ONLY isolated http-test fixtures."""
from pathlib import Path
from playwright.sync_api import sync_playwright
import shutil, re
ROOT=Path(__file__).resolve().parents[1];out=ROOT/'docs/user-editor';out.mkdir(exist_ok=True)
base='http://127.0.0.1:8001/';password='OnlyForIsolated-HttpTests2026!'
with sync_playwright() as p:
    browser=p.chromium.launch(executable_path=shutil.which('chromium'),headless=True,args=['--no-sandbox'])
    context=browser.new_context(viewport={'width':1440,'height':1050});context.route('https://student.fdu.uz/**',lambda r:r.fulfill(status=200,content_type='image/png',body=(ROOT/'web/images/fardu-logo.png').read_bytes()))
    page=context.new_page();errors=[];page.on('pageerror',lambda e:errors.append(str(e)))
    def goto(route):return page.goto(base+'?r='+route,wait_until='networkidle')
    def login(page,name):
        page.goto(base+'?r=auth/login',wait_until='networkidle');page.locator('[name=username]').fill('http_'+name);page.locator('[name=password]').fill(password);page.locator('.login-card button.button').click();page.wait_for_load_state('networkidle')
    login(page,'admin');goto('admin/users')
    assert not page.locator('input[name=username]').count()
    assert page.get_by_role('link',name='Barchasini o‘chirish',exact=True).count()==1
    assert not page.get_by_role('button',name='O‘chirish',exact=True).count()
    row=page.locator('tr[data-username="http_teacher"]');assert 'Test University' in row.inner_text() and 'Test Faculty' in row.inner_text() and 'Test Department' in row.inner_text()
    page.screenshot(path=str(out/'users-list.png'),full_page=True)
    row.get_by_role('link',name='Tahrirlash').click();page.wait_for_load_state('networkidle');editor=page.url
    assert 'user-edit' in editor and not page.locator('tbody tr').count()
    assert page.locator('[name="profile[email]"]').input_value()=='teacher@example.test'
    page.locator('[name=name]').fill('Browser Profile Saved');page.locator('[name=position]').fill('Professor');page.locator('[name="profile[phone]"]').fill('+998000000000')
    page.get_by_role('button',name='Saqlash',exact=True).click();page.wait_for_load_state('networkidle')
    assert page.locator('[name=name]').input_value()=='Browser Profile Saved' and page.locator('[name=position]').input_value()=='Professor'
    assert page.locator('[name=org_id]').input_value()=='3'
    assert page.locator('[data-employee-photo]').evaluate('(e)=>e.complete && e.naturalWidth>0')
    page.screenshot(path=str(out/'user-edit.png'),full_page=True)
    # Blocking revokes the teacher's existing session; unblock permits a fresh login.
    teacher_context=browser.new_context();teacher=teacher_context.new_page();login(teacher,'teacher')
    page.get_by_role('button',name='Bloklash',exact=True).click();page.wait_for_load_state('networkidle')
    teacher.goto(base+'?r=kpi/index',wait_until='networkidle');assert teacher.locator('[name=username]').count()==1
    page.get_by_role('button',name='Blokdan chiqarish',exact=True).click();page.wait_for_load_state('networkidle');login(teacher,'teacher');assert teacher.title()=='Shaxsiy KPI paneli'
    for width in [390,320]:
        page.set_viewport_size({'width':width,'height':900});page.wait_for_timeout(100);assert page.evaluate('document.documentElement.scrollWidth<=innerWidth')
    page.set_viewport_size({'width':1440,'height':1050})
    goto('admin/user-edit');page.locator('[name=name]').fill('Delete single fixture');page.locator('[name=username]').fill('delete_single_fixture');page.locator('[name=password]').fill(password);page.get_by_role('button',name='Saqlash',exact=True).click();page.wait_for_load_state('networkidle');page.get_by_role('button',name='O‘chirish',exact=True).click();page.wait_for_load_state('networkidle');assert not page.locator('tr[data-username="delete_single_fixture"]').count()
    # Hidden controls are also protected on the server.
    operator_context=browser.new_context();operator=operator_context.new_page();login(operator,'operator')
    for route in ['admin/users','admin/structure']:
        operator.goto(base+'?r='+route,wait_until='networkidle');assert not operator.get_by_role('link',name='Barchasini o‘chirish',exact=True).count()
        csrf=operator.locator('meta[name=csrf-param]').get_attribute('content');token=operator.locator('meta[name=csrf-token]').get_attribute('content')
        for scope in ['users','structure']:
            assert operator_context.request.get(base+'?r=admin/delete-all&scope='+scope).status==403
            assert operator_context.request.post(base+'?r=admin/delete-all&scope='+scope,form={csrf:token,'confirmation':'OCHIRISH','fingerprint':'0'*64}).status==403
    operator_context.close();teacher_context.close()
    goto('admin/users&q=http_teacher');assert page.locator('tbody tr').count()==1
    page.get_by_role('link',name='Barchasini o‘chirish',exact=True).click();page.wait_for_load_state('networkidle')
    assert 'Joriy hisobingizdan tashqari' in page.locator('body').inner_text()
    page.screenshot(path=str(out/'bulk-users-confirmation.png'),full_page=True)
    csrf=page.locator('meta[name=csrf-param]').get_attribute('content');token=page.locator('meta[name=csrf-token]').get_attribute('content');fp=page.locator('[name=fingerprint]').input_value()
    response=context.request.post(page.url,form={csrf:token,'confirmation':'WRONG','fingerprint':fp});assert 'OCHIRISH deb yozing' in response.text()
    response=context.request.post(page.url,form={csrf:token,'confirmation':'OCHIRISH','fingerprint':'0'*64});assert 'Ro‘yxat o‘zgargan' in response.text()
    page.reload(wait_until='networkidle');page.locator('[name=confirmation]').fill('OCHIRISH');page.get_by_role('button',name='Barchasini o‘chirishni tasdiqlash',exact=True).click();page.wait_for_load_state('networkidle')
    assert page.locator('tbody tr').count()==1 and page.locator('tr[data-username="http_admin"]').count()==1
    goto('admin/structure');page.get_by_role('link',name='Barchasini o‘chirish',exact=True).click();page.wait_for_load_state('networkidle');assert 'Biriktirishi olinadigan foydalanuvchilar' in page.locator('body').inner_text()
    page.locator('[name=confirmation]').fill('OCHIRISH');page.get_by_role('button',name='Barchasini o‘chirishni tasdiqlash',exact=True).click();page.wait_for_load_state('networkidle');assert not page.locator('tbody tr').count()
    goto('admin/users');assert page.locator('tr[data-username="http_admin"]').count()==1
    goto('kpi/reports');assert '5.00' in page.locator('body').inner_text()
    assert not errors,errors
    browser.close()
print('PASS: separate user editor, profile/photo saved, session block/unblock, delete, mobile widths, super-only bulk links and authorization, typed/stale confirmation, unfiltered deletion, own account and old reports preserved.')
