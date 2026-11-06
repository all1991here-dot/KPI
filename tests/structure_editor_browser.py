"""Separate faculty/department lists and dedicated requisites editor actions."""
from pathlib import Path
from playwright.sync_api import sync_playwright
import shutil, re
ROOT=Path(__file__).resolve().parents[1];out=ROOT/'docs/structure-editor';out.mkdir(exist_ok=True)
base='http://127.0.0.1:8001/';password='OnlyForIsolated-HttpTests2026!'
with sync_playwright() as p:
    browser=p.chromium.launch(executable_path=shutil.which('chromium'),headless=True,args=['--no-sandbox'])
    context=browser.new_context(viewport={'width':1440,'height':1050});context.route('https://student.fdu.uz/**',lambda r:r.abort())
    page=context.new_page();errors=[];page.on('pageerror',lambda e:errors.append(str(e)))
    def goto(route):return page.goto(base+'?r='+route,wait_until='networkidle')
    def login(name):
        goto('auth/login');page.locator('[name=username]').fill('http_'+name);page.locator('[name=password]').fill(password);page.locator('.login-card button.button').click();page.wait_for_load_state('networkidle')
    login('admin');goto('admin/structure')
    assert page.locator('#faculties tbody tr').count()==1
    row=page.locator('#departments tbody tr').filter(has_text='Test Department');assert 'Test Faculty' in row.inner_text() and 'Test University' in row.inner_text()
    assert not page.get_by_role('button',name='O‘chirish').count()
    row.get_by_role('link',name='Tahrirlash').click();page.wait_for_load_state('networkidle')
    assert 'structure-edit' in page.url or 'structure%2Dedit' in page.url
    assert not page.locator('#departments').count()
    for field,value in {'short_name':'TD','code':'DEPT-03','head_name':'Test Department Head','phone':'+998 90 000 00 00','email':'department@example.test','address':'Test campus address','notes':'Browser requisites fixture'}.items():page.locator('[name='+field+']').fill(value)
    page.get_by_role('button',name='Saqlash',exact=True).click();page.wait_for_load_state('networkidle')
    assert page.locator('[name=email]').input_value()=='department@example.test'
    assert page.locator('[name=code]').input_value()=='DEPT-03'
    assert page.locator('[name=university_picker]').input_value()=='1'
    assert page.locator('[name=parent_id]').input_value()=='2'
    for name in ['O‘chirish','Saqlash','Bloklash']:assert page.locator('.organization-editor-actions').get_by_role('button',name=name,exact=True).count()==1
    page.screenshot(path=str(out/'edit-desktop.png'),full_page=True)
    page.get_by_role('button',name='Bloklash',exact=True).click();page.wait_for_load_state('networkidle');assert page.get_by_role('button',name='Blokdan chiqarish').count()==1
    goto('admin/structure');row=page.locator('#departments tbody tr').filter(has_text='Test Department');assert 'Bloklangan' in row.inner_text()
    row.get_by_role('link',name='Tahrirlash').click();page.wait_for_load_state('networkidle')
    # Linked records cannot be accidentally deleted. Error stays in the edit window.
    page.get_by_role('button',name='O‘chirish',exact=True).click();page.wait_for_load_state('networkidle');assert 'xodimlarni' in page.locator('.alert.error').inner_text()
    assert page.locator('[name=email]').input_value()=='department@example.test'
    page.get_by_role('button',name='Blokdan chiqarish',exact=True).click();page.wait_for_load_state('networkidle');assert page.get_by_role('button',name='Bloklash',exact=True).count()
    for width in [390,320]:
        page.set_viewport_size({'width':width,'height':900});page.wait_for_timeout(200)
        assert page.evaluate('document.documentElement.scrollWidth<=innerWidth')
        page.get_by_role('button',name='Saqlash',exact=True).scroll_into_view_if_needed()
        if width==390:page.screenshot(path=str(out/'edit-mobile.png'),full_page=True)
    page.set_viewport_size({'width':1440,'height':1050})
    goto('admin/structure-edit&kind=faculty');page.locator('[name=name]').fill('Second Faculty');page.locator('[name=parent_id]').select_option('1');page.get_by_role('button',name='Saqlash',exact=True).click();page.wait_for_load_state('networkidle');faculty=re.search(r'id=(\d+)',page.url).group(1)
    goto('admin/structure-edit&id=3');page.locator('[name=parent_id]').select_option(faculty);page.get_by_role('button',name='Saqlash',exact=True).click();page.wait_for_load_state('networkidle')
    goto('admin/structure');row=page.locator('#departments tbody tr').filter(has_text='Test Department');assert 'Second Faculty' in row.inner_text()
    page.screenshot(path=str(out/'separate-lists.png'),full_page=True)
    goto('admin/structure&university=1&faculty='+faculty);assert page.locator('#faculties tbody tr').count()==1 and page.locator('#departments tbody tr').count()==1
    goto('admin/structure-edit&kind=department&parent_id='+faculty);page.locator('[name=name]').fill('Empty delete department');page.get_by_role('button',name='Saqlash',exact=True).click();page.wait_for_load_state('networkidle');page.get_by_role('button',name='O‘chirish',exact=True).click();page.wait_for_load_state('networkidle');assert not page.locator('#departments tbody tr').filter(has_text='Empty delete department').count()
    context.clear_cookies();login('operator');goto('admin/structure-edit&id=3')
    assert not page.get_by_role('button',name='Bloklash',exact=True).count() and not page.get_by_role('button',name='O‘chirish',exact=True).count()
    token=page.locator('meta[name=csrf-token]').get_attribute('content');csrf=page.locator('meta[name=csrf-param]').get_attribute('content')
    assert context.request.post(base+'?r=admin/structure-edit&id=3',form={csrf:token,'operation':'block'}).status==403
    assert not errors,errors
    browser.close()
print('PASS: separate lists and faculty column, dedicated editor, all requisites persisted, bottom save/block/unblock/delete, linked-delete protection, correct parent after move, filters, mobile widths, superadmin authorization.')
