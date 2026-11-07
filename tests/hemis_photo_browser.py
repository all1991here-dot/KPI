"""Staff photo UI and legacy endpoints are disabled; no remote image fetch is needed."""
from playwright.sync_api import sync_playwright
import shutil
base='http://127.0.0.1:8001/'
with sync_playwright() as p:
    browser=p.chromium.launch(executable_path=shutil.which('chromium'),headless=True,args=['--no-sandbox'])
    context=browser.new_context();page=context.new_page();photo_requests=[]
    page.on('request',lambda r:photo_requests.append(r.url) if 'employee-photo' in r.url or 'student.fdu.uz' in r.url else None)
    for name in ['admin','teacher','other']:
        context.clear_cookies();page.goto(base+'?r=auth/login',wait_until='networkidle')
        page.locator('[name=username]').fill('http_'+name);page.locator('[name=password]').fill('OnlyForIsolated-HttpTests2026!');page.locator('.login-card button.button').click();page.wait_for_load_state('networkidle')
        for route in (['admin/users','admin/user-edit&id=1','kpi/profile'] if name=='admin' else ['kpi/profile']):
            page.goto(base+'?r='+route,wait_until='networkidle');assert not page.locator('[data-employee-photo],[data-photo-check]').count()
        assert context.request.get(base+'?r=kpi/employee-photo&id=1').status==404
        assert context.request.get(base+'?r=kpi/employee-photo&id=1&check=1').status==404
    assert not photo_requests,photo_requests
    context.clear_cookies();assert context.request.get(base+'?r=kpi/employee-photo&id=1',max_redirects=0).status==302
    browser.close()
print('PASS: no employee images or photo requests in users/edit/profile; legacy photo endpoints disabled and anonymous access requires login.')
