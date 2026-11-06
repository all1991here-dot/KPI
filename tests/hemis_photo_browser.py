"""Authenticated photo delivery using the locally cached image fixture only."""
from pathlib import Path
from playwright.sync_api import sync_playwright
from urllib.parse import urljoin
import re
import shutil
base='http://127.0.0.1:8001/'
with sync_playwright() as p:
    browser=p.chromium.launch(executable_path=shutil.which('chromium'),headless=True,args=['--no-sandbox'])
    context=browser.new_context();page=context.new_page()
    def login(name):
        context.clear_cookies();page.goto(base+'?r=auth/login');page.locator('[name=username]').fill('http_'+name);page.locator('[name=password]').fill('OnlyForIsolated-HttpTests2026!');page.locator('.login-card button.button').click();page.wait_for_load_state('networkidle')
    login('admin');page.goto(base+'?r=admin/users');img=page.locator('tr[data-username="http_teacher"] img[data-employee-photo]');url=urljoin(base,img.get_attribute('src'))
    response=context.request.get(url);assert response.status==200 and response.headers['content-type']=='image/png';assert 'no-store' in response.headers['cache-control']
    page.goto(base+'?r=admin/user-edit&id='+re.search(r'(?:id=|id%3D)(\d+)',url).group(1))
    context.route('**/*check=1',lambda route:route.fulfill(status=200,content_type='application/json',body='{"ok":false,"message":"Rasm uchun SSL sertifikatini tekshiring."}'))
    page.get_by_role('button',name='Rasmni tekshirish',exact=True).click()
    page.locator('[data-photo-result]').filter(has_text='SSL').wait_for()
    context.unroute('**/*check=1')
    login('teacher');assert context.request.get(url).status==200
    assert context.request.get(url+'&check=1').status==403
    login('other');assert context.request.get(url).status==404
    own=page.goto(base+'?r=kpi/profile');own_image=page.locator('[data-employee-photo]')
    # No-photo diagnostic fixture is tested through admin below; no live image request.
    login('admin');page.goto(base+'?r=admin/users')
    other=page.locator('tr[data-username="http_other"] a',has_text='Tahrirlash').get_attribute('href')
    other_id=re.search(r'id=(\d+)',other).group(1)
    report=context.request.get(base+'?r=kpi/employee-photo&id='+other_id+'&check=1')
    assert report.status==200 and report.json()['ok'] is False and 'qayta import' in report.json()['message']
    context.clear_cookies();assert context.request.get(url,max_redirects=0).status==302
    browser.close()
print('PASS: admin and owner receive cached photo; unrelated and anonymous users denied.')
