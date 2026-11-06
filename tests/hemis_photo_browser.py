"""Authenticated photo delivery using the locally cached image fixture only."""
from pathlib import Path
from playwright.sync_api import sync_playwright
from urllib.parse import urljoin
import shutil
base='http://127.0.0.1:8001/'
with sync_playwright() as p:
    browser=p.chromium.launch(executable_path=shutil.which('chromium'),headless=True,args=['--no-sandbox'])
    context=browser.new_context();page=context.new_page()
    def login(name):
        context.clear_cookies();page.goto(base+'?r=auth/login');page.locator('[name=username]').fill('http_'+name);page.locator('[name=password]').fill('OnlyForIsolated-HttpTests2026!');page.locator('.login-card button.button').click();page.wait_for_load_state('networkidle')
    login('admin');page.goto(base+'?r=admin/users');img=page.locator('tr[data-username="http_teacher"] img[data-employee-photo]');url=urljoin(base,img.get_attribute('src'))
    response=context.request.get(url);assert response.status==200 and response.headers['content-type']=='image/png';assert 'no-store' in response.headers['cache-control']
    login('teacher');assert context.request.get(url).status==200
    login('other');assert context.request.get(url).status==404
    context.clear_cookies();assert context.request.get(url,max_redirects=0).status==302
    browser.close()
print('PASS: admin and owner receive cached photo; unrelated and anonymous users denied.')
