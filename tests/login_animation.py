"""Verify the uploaded decoration never changes the form or blocks authentication."""
from pathlib import Path
from playwright.sync_api import sync_playwright
import shutil, time
ROOT=Path(__file__).resolve().parents[1]
out=ROOT/'docs/login-animation';out.mkdir(exist_ok=True)
with sync_playwright() as p:
    browser=p.chromium.launch(executable_path=shutil.which('chromium'),headless=True,args=['--no-sandbox'])
    page=browser.new_page(viewport={'width':1440,'height':1050})
    errors=[];page.on('pageerror',lambda e:errors.append(str(e)))
    base='http://127.0.0.1:8001/?r=auth/login'
    for attempt in range(30):
        try:
            page.goto(base,wait_until='networkidle');assert page.locator('.fardu-login-scene').count();break
        except Exception:time.sleep(.5)
    else:raise RuntimeError('Preview unavailable')
    selectors=['.fardu-login-card','.fardu-login-form','.fardu-campus-photo','.fardu-login-brand','input[name=username]','input[name=password]','.login-card button.button']
    def geometry():
        return page.evaluate('''selectors => selectors.map(s=>{const e=document.querySelector(s),r=e.getBoundingClientRect(),c=getComputedStyle(e);return [r.x,r.y,r.width,r.height,c.backgroundColor,c.color,c.fontSize,c.padding,c.borderRadius]})''',selectors)
    for width,height in [(1440,1050),(1000,900),(760,900),(580,900),(390,844),(320,740)]:
        page.set_viewport_size({'width':width,'height':height});page.goto(base,wait_until='networkidle');page.wait_for_timeout(600)
        before=geometry()
        page.evaluate('''()=>{document.querySelector('link[href*="login-animation.css"]').remove();document.querySelector('.login-network-background').remove();const scene=document.querySelector('.fardu-login-scene');scene.replaceWith(scene.querySelector('.fardu-login-card'));}''')
        page.wait_for_timeout(600)
        after=geometry()
        for first,second in zip(before,after):
            assert all(abs(a-b)<1 for a,b in zip(first[:4],second[:4])),(width,first,second)
            assert first[4:]==second[4:],(width,first,second)
        page.goto(base,wait_until='networkidle');page.wait_for_timeout(1400)
        assert page.evaluate('document.documentElement.scrollWidth<=innerWidth'),width
        for selector in ['input[name=username]','input[name=password]','.login-card button.button']:
            assert page.locator(selector).evaluate('(e)=>{const r=e.getBoundingClientRect();return e.contains(document.elementFromPoint(r.x+r.width/2,r.y+r.height/2));}'),(width,selector)
        if width in [1440,390]:page.screenshot(path=str(out/f'login-{width}.png'),full_page=True)
    page.emulate_media(reduced_motion='reduce');page.wait_for_timeout(100)
    assert not page.locator('.login-robot').is_visible()
    assert page.locator('.login-network-background').evaluate('(e)=>getComputedStyle(e,"::before").animationName')=='none'
    page.emulate_media(reduced_motion='no-preference');page.set_viewport_size({'width':1440,'height':1050});page.goto(base,wait_until='networkidle')
    page.locator('input[name=username]').fill('http_admin');page.locator('input[name=password]').fill('OnlyForIsolated-HttpTests2026!')
    page.get_by_role('button',name='Parolni ko‘rsatish',exact=True).click()
    assert page.locator('input[name=password]').get_attribute('type')=='text'
    page.locator('.login-card button.button').click();page.wait_for_load_state('networkidle')
    assert page.title()=='Boshqaruv paneli · KPI',page.title()
    assert not page.locator('[data-login-network]').count()
    assert not errors,errors
    browser.close()
print('PASS: unchanged form geometry/styles at six widths; unobstructed controls, reduced motion, password toggle, real login, animation only on login.')
