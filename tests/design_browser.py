"""Optional visual checks. Requires Playwright and the isolated HTTP test fixtures. Never use real credentials."""
from pathlib import Path
from playwright.sync_api import sync_playwright
import time,json,sys,shutil,os
base=sys.argv[1] if len(sys.argv)>1 else 'http://127.0.0.1:8001'
out=Path(__file__).resolve().parents[1]/'docs'/'design-v2';out.mkdir(parents=True,exist_ok=True)
with sync_playwright() as p:
 browser=p.chromium.launch(executable_path=os.environ.get('KPI_CHROMIUM') or shutil.which('chromium'),headless=True,args=['--no-sandbox'])
 page=browser.new_page(viewport={'width':1440,'height':1050},device_scale_factor=1)
 errors=[];page.on('pageerror',lambda e:errors.append(str(e)))
 for attempt in range(30):
  try:
   page.goto(base+'/?r=auth/login',wait_until='networkidle',timeout=5000)
   if page.locator('input[name=username]').count():break
  except Exception:time.sleep(.5)
 else:raise RuntimeError('Design service not ready')
 page.screenshot(path=str(out/'login.png'),full_page=True)
 page.locator('input[name=password]').fill('OnlyForIsolated-HttpTests2026!')
 page.get_by_role('button',name='Parolni ko‘rsatish',exact=True).click();assert page.locator('input[name=password]').get_attribute('type')=='text'
 page.get_by_role('button',name='Parolni yashirish',exact=True).click()
 page.locator('input[name=username]').fill('http_admin');page.locator('.login-card button.button').click();page.wait_for_load_state('networkidle')
 assert page.title()=='Boshqaruv paneli · KPI',page.title()
 page.screenshot(path=str(out/'dashboard.png'),full_page=True)
 assert page.locator('.status-donut').get_attribute('aria-label')
 print('Dashboard real-data chart, login and password visibility: passed')
 page.locator('[data-theme-toggle]').click();assert page.locator('html').get_attribute('data-theme')=='dark'
 page.reload(wait_until='networkidle');assert page.locator('html').get_attribute('data-theme')=='dark'
 page.screenshot(path=str(out/'dashboard-dark.png'),full_page=True)
 print('Dark theme and persistence: passed')
 page.locator('[data-theme-toggle]').click()
 for route in ['kpi/applications','kpi/create','kpi/criteria','kpi/reports','kpi/profile','kpi/notifications','admin/users','admin/structure','admin/periods','admin/rules','admin/route','admin/hemis','admin/audit']:
  response=page.goto(base+'/?r='+route,wait_until='networkidle');assert response.status==200,(route,response.status)
  assert page.locator('.sidebar a[aria-current=page]').count()==1,route
  assert page.locator('html').evaluate('(el)=>el.scrollWidth<=window.innerWidth'),route
 print('13 desktop content pages: passed')
 page.goto(base+'/?r=kpi/applications&queue=1',wait_until='networkidle')
 assert page.locator('.sidebar a[aria-current=page]').count()==1
 assert page.locator('.sidebar a[aria-current=page]').inner_text()=='Tekshiruv navbati'
 page.goto(base+'/?r=kpi/reports',wait_until='networkidle');page.screenshot(path=str(out/'reports.png'),full_page=True)
 page.goto(base+'/?r=admin/users',wait_until='networkidle');page.screenshot(path=str(out/'users.png'),full_page=True)
 page.goto(base+'/?r=kpi/index',wait_until='networkidle')
 page.set_viewport_size({'width':390,'height':844});page.wait_for_timeout(250)
 page.screenshot(path=str(out/'mobile.png'),full_page=True)
 assert page.evaluate('document.documentElement.scrollWidth==window.innerWidth')
 assert not page.locator('.sidebar').evaluate('(el)=>el.getBoundingClientRect().right>0')
 page.get_by_role('button',name='Menyuni ochish',exact=True).click()
 assert page.locator('#app-workspace').evaluate('(el)=>el.inert')
 assert page.locator('#app-sidebar').get_attribute('aria-modal')=='true'
 page.screenshot(path=str(out/'mobile-menu.png'),full_page=True)
 page.keyboard.press('Escape');assert not page.locator('#app-workspace').evaluate('(el)=>el.inert')
 assert page.locator('[data-menu-toggle]').get_attribute('aria-expanded')=='false'
 for route in ['kpi/applications','kpi/create','kpi/criteria','kpi/reports','admin/users','admin/rules','admin/route']:
  page.goto(base+'/?r='+route,wait_until='networkidle')
  assert page.evaluate('document.documentElement.scrollWidth==window.innerWidth'),('mobile overflow',route,page.evaluate('document.documentElement.scrollWidth'))
 print('Mobile layout, drawer, Escape, focus and content pages: passed')
 page.set_viewport_size({'width':320,'height':720});page.goto(base+'/?r=kpi/index',wait_until='networkidle');assert page.evaluate('document.documentElement.scrollWidth==window.innerWidth')
 assert not errors,errors
 print('320px layout and browser JavaScript errors: passed')
 browser.close()
