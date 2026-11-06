"""UI recovery visibility, with API response simulated only in an isolated fixture DB."""
from pathlib import Path
from playwright.sync_api import sync_playwright
import subprocess, shutil
ROOT=Path(__file__).resolve().parents[1];out=ROOT/'docs/hemis-recovery';out.mkdir(exist_ok=True)
base='http://127.0.0.1:8001/';password='OnlyForIsolated-HttpTests2026!'
with sync_playwright() as p:
    browser=p.chromium.launch(executable_path=shutil.which('chromium'),headless=True,args=['--no-sandbox'])
    context=browser.new_context(viewport={'width':1440,'height':1050});context.route('https://student.fdu.uz/**',lambda r:r.abort());page=context.new_page();errors=[];page.on('pageerror',lambda e:errors.append(str(e)))
    def goto(route):return page.goto(base+'?r='+route,wait_until='networkidle')
    def login(name):goto('auth/login');page.locator('[name=username]').fill('http_'+name);page.locator('[name=password]').fill(password);page.locator('.login-card button.button').click();page.wait_for_load_state('networkidle')
    login('admin');goto('admin/users');assert not page.locator('tr[data-username="hemis_recovery_fixture"]').count();assert 'yashirilgan' in page.locator('.alert.warning').inner_text()
    page.get_by_role('link',name='HEMIS orqali qayta tiklash',exact=True).click();page.wait_for_load_state('networkidle')
    checkbox=page.get_by_role('checkbox',name='O‘chirilgan HEMIS hisoblarini qayta tiklash',exact=True);assert not checkbox.is_checked();checkbox.check();assert checkbox.is_checked()
    assert checkbox.locator('xpath=ancestor::form').locator('[name=operation]').input_value()=='employees'
    page.screenshot(path=str(out/'restore-option.png'),full_page=True)
    result=subprocess.run(['docker','exec','--user','1000:1000','-w','/app','-e','KPI_RUNTIME_DIR=/app/runtime/http-test-hemis-recovery-20261106','kpi-app-1','php','tests/hemis_recovery_fixtures.php','restore'],capture_output=True,text=True,timeout=60);assert result.returncode==0,result.stdout+result.stderr
    goto('admin/users');row=page.locator('tr[data-username="hemis_recovery_fixture"]');assert row.count()==1 and 'Nofaol' in row.inner_text();assert not page.locator('.alert.warning').count()
    page.screenshot(path=str(out/'restored-list.png'),full_page=True)
    goto('admin/hemis');assert page.get_by_text('Qayta tiklangan',exact=True).count()
    context.clear_cookies();login('operator');goto('admin/hemis');assert not page.locator('[name=restore_deleted]').count()
    csrf=page.locator('meta[name=csrf-param]').get_attribute('content');token=page.locator('meta[name=csrf-token]').get_attribute('content')
    assert context.request.post(base+'?r=admin/hemis',form={csrf:token,'operation':'employees','restore_deleted':'1'}).status==403
    assert not errors,errors;browser.close()
print('PASS: deleted-list explanation, explicit unchecked recovery option, restored inactive employee visible, restored counts and superadmin-only control.')
