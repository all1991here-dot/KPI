"""UI assignment, role-forgery rejection, profile/photo fallback, and admin correction."""
from pathlib import Path
from playwright.sync_api import sync_playwright
import shutil, time, re
ROOT=Path(__file__).resolve().parents[1];out=ROOT/'docs/organization';out.mkdir(exist_ok=True)
base='http://127.0.0.1:8001/'
password='OnlyForIsolated-HttpTests2026!'
with sync_playwright() as p:
    browser=p.chromium.launch(executable_path=shutil.which('chromium'),headless=True,args=['--no-sandbox'])
    context=browser.new_context(viewport={'width':1440,'height':1050})
    # A real local image fixture substitutes for HEMIS; no real employee data is requested.
    context.route('https://student.fdu.uz/static/test-photo.png',lambda route:route.fulfill(status=200,content_type='image/png',body=(ROOT/'web/images/fardu-logo.png').read_bytes()))
    page=context.new_page();errors=[];page.on('pageerror',lambda e:errors.append(str(e)))
    def goto(route):page.goto(base+'?r='+route,wait_until='networkidle')
    def login(name):
        goto('auth/login');page.locator('[name=username]').fill('http_'+name);page.locator('[name=password]').fill(password);page.locator('.login-card button.button').click();page.wait_for_load_state('networkidle')
    login('admin');goto('admin/structure-edit&kind=university')
    page.locator('[name=name]').fill('Browser University');page.get_by_role('button',name='Saqlash',exact=True).click();page.wait_for_load_state('networkidle')
    uni=re.search(r'id=(\d+)',page.url).group(1)
    goto('admin/structure-edit&kind=faculty');page.locator('[name=name]').fill('Browser Faculty');page.locator('[name=parent_id]').select_option(uni);page.get_by_role('button',name='Saqlash',exact=True).click();page.wait_for_load_state('networkidle')
    faculty=re.search(r'id=(\d+)',page.url).group(1)
    goto('admin/structure-edit&kind=department');page.locator('[name=name]').fill('Browser Department');page.locator('[name=parent_id]').select_option(faculty);page.get_by_role('button',name='Saqlash',exact=True).click();page.wait_for_load_state('networkidle')
    goto('admin/structure');assert 'Browser Faculty' in page.locator('#departments').inner_text()
    page.screenshot(path=str(out/'structure.png'),full_page=True)
    goto('admin/users')
    row=page.locator('tbody tr').filter(has=page.locator('td',has_text=re.compile('^http_teacher$')))
    teacher_link=row.get_by_role('link',name='Tahrirlash').get_attribute('href')
    page.goto(base+teacher_link,wait_until='networkidle')
    page.locator('[data-org-level=university]').select_option(uni)
    page.locator('[data-org-level=faculty]').select_option(faculty)
    dept=page.locator('[data-org-level=department] option',has_text='Browser Department').get_attribute('value')
    page.locator('[data-org-level=department]').select_option(dept)
    assert page.locator('[name=org_id]').input_value()==dept
    page.get_by_role('button',name='Saqlash',exact=True).click();page.wait_for_load_state('networkidle')
    page.goto(base+teacher_link,wait_until='networkidle')
    assert page.locator('[name=org_id]').input_value()==dept
    assert page.locator('[data-org-level=faculty]').input_value()==faculty
    goto('admin/users')
    row=page.locator('tbody tr').filter(has=page.locator('td',has_text=re.compile('^http_teacher$')))
    row.locator('summary').click();image=row.locator('[data-employee-photo]').last
    image.scroll_into_view_if_needed();page.wait_for_timeout(300)
    assert image.evaluate('(e)=>e.complete && e.naturalWidth>0')
    assert row.get_by_text('teacher@example.test',exact=True).count()
    page.screenshot(path=str(out/'employee-profile.png'),full_page=True)
    # Error fallback must replace a broken image with text, never leave a broken icon.
    image.evaluate('(e)=>e.dispatchEvent(new Event("error"))');assert image.is_hidden()
    assert row.get_by_text('Rasm yo‘q').count()
    goto('auth/logout') if False else None
    context.clear_cookies();login('operator');page.goto(base+teacher_link,wait_until='networkidle')
    assert not page.locator('[name=role]').count()
    page.locator('[name=name]').fill('HTTP teacher admin updated')
    page.get_by_role('button',name='Saqlash',exact=True).click();page.wait_for_load_state('networkidle')
    page.goto(base+teacher_link,wait_until='networkidle')
    # Forge the hidden role POST rather than merely relying on an absent dropdown.
    page.evaluate('''()=>{const f=document.querySelector('form[action*="admin%2Fusers"]');const i=document.createElement('input');i.name='role';i.value='expert';f.append(i);f.requestSubmit();}''')
    page.wait_for_load_state('networkidle');assert 'faqat superadministrator' in page.locator('.alert.error').inner_text()
    context.clear_cookies();login('admin');page.goto(base+teacher_link,wait_until='networkidle')
    assert page.locator('[name=role]').input_value()=='teacher'
    # Edit the already approved HTTP test document as an ordinary administrator.
    goto('kpi/applications');edit_link=page.locator('a[href*="kpi%2Fview"]').first.get_attribute('href')
    context.clear_cookies();login('operator');page.goto(base+edit_link,wait_until='networkidle')
    page.get_by_role('link',name='Tahrirlash',exact=True).click();page.wait_for_load_state('networkidle')
    page.locator('[name=title]').fill('Admin UI corrected document');page.get_by_role('button',name='Qoralamani saqlash',exact=True).click();page.wait_for_load_state('networkidle')
    assert 'Qoralama' in page.locator('body').inner_text()
    context.clear_cookies();goto('auth/login');page.locator('[name=username]').fill('http_unassigned');page.locator('[name=password]').fill(password);page.locator('.login-card button.button').click();page.wait_for_load_state('networkidle')
    response=page.goto(base+'?r=kpi/create',wait_until='networkidle');assert response.status==403
    assert not errors,errors
    browser.close()
print('PASS: manual hierarchy, cascaded user assignment persists, imported profile/photo and fallback, admin role POST rejected, admin document edit, unassigned access denied.')
