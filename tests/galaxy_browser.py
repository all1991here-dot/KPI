"""Approved galaxy theme, accessible mode switching and responsive layout."""
from pathlib import Path
from playwright.sync_api import sync_playwright
import shutil
out=Path(__file__).resolve().parents[1]/'docs/galaxy';out.mkdir(exist_ok=True)
base='http://127.0.0.1:8001/'
with sync_playwright() as p:
    browser=p.chromium.launch(executable_path=shutil.which('chromium'),headless=True,args=['--no-sandbox'])
    context=browser.new_context(viewport={'width':1440,'height':1000},reduced_motion='reduce')
    page=context.new_page();errors=[];page.on('pageerror',lambda e:errors.append(str(e)))
    page.goto(base+'?r=auth/login',wait_until='networkidle');assert not page.locator('link[href*="galaxy.css"]').count()
    page.locator('[name=username]').fill('http_admin');page.locator('[name=password]').fill('OnlyForIsolated-HttpTests2026!');page.locator('.login-card button.button').click();page.wait_for_load_state('networkidle')
    assert page.locator('html').get_attribute('data-theme')=='light'
    page.locator('[data-theme-toggle]').click();assert page.locator('html').get_attribute('data-theme')=='dark'
    assert page.locator('body').evaluate('(e)=>getComputedStyle(e).backgroundColor')=='rgb(7, 19, 38)'
    assert page.locator('.topbar').evaluate('(e)=>getComputedStyle(e).backgroundColor')!='rgb(255, 255, 255)'
    page.screenshot(path=str(out/'dashboard-desktop.png'),full_page=True)
    page.locator('[data-theme-toggle]').click();assert page.locator('html').get_attribute('data-theme')=='light'
    page.reload(wait_until='networkidle');assert page.locator('html').get_attribute('data-theme')=='light'
    page.locator('[data-theme-toggle]').click()
    for route in ['admin/users','admin/structure','admin/hemis','kpi/create','kpi/criteria','kpi/reports']:
        response=page.goto(base+'?r='+route,wait_until='networkidle');assert response.status==200
        assert page.locator('html').get_attribute('data-theme')=='dark'
        assert page.evaluate('document.documentElement.scrollWidth<=innerWidth+1')
    page.goto(base+'?r=admin/users',wait_until='networkidle');page.screenshot(path=str(out/'users-desktop.png'),full_page=True)
    page.set_viewport_size({'width':390,'height':844});page.goto(base+'?r=kpi/index',wait_until='networkidle')
    assert page.evaluate('document.documentElement.scrollWidth<=innerWidth+1')
    page.locator('[data-menu-toggle]').click();assert page.locator('[data-menu-toggle]').get_attribute('aria-expanded')=='true'
    page.locator('.sidebar [data-menu-close]').click();page.screenshot(path=str(out/'dashboard-mobile.png'),full_page=True)
    page.emulate_media(media='print');assert page.locator('.galaxy-horizon').evaluate('(e)=>getComputedStyle(e).display')=='none'
    assert not errors,errors
    browser.close()
print('PASS: light default, readable surfaces, mode persistence, six internal screens, mobile menu/overflow, print and no JS errors.')
