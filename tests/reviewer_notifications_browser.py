"""Reviewer arrivals over five stages, return/reject/appeal and scoped live badges.
Requires fresh reviewer_notifications_fixtures.php on port 8001.
"""
from pathlib import Path
from playwright.sync_api import sync_playwright
import shutil
BASE='http://127.0.0.1:8001/?r='
OUT=Path(__file__).resolve().parents[1]/'docs/reviewer-notifications';OUT.mkdir(exist_ok=True)
with sync_playwright() as p:
    browser=p.chromium.launch(executable_path=shutil.which('chromium'),headless=True,args=['--no-sandbox','--disable-background-timer-throttling','--disable-renderer-backgrounding'])
    users={};errors=[]
    for name in ['teacher','head','head_peer','head_foreign','faculty','faculty_foreign','expert','operator','admin']:
        context=browser.new_context(viewport={'width':1440,'height':1000},reduced_motion='reduce');page=context.new_page();page.on('pageerror',lambda e:errors.append(str(e)))
        page.goto(BASE+'auth/login',wait_until='networkidle');page.locator('[name=username]').fill('http_'+name);page.locator('[name=password]').fill('OnlyForIsolated-HttpTests2026!');page.locator('.login-card button.button').click();page.wait_for_load_state('networkidle');users[name]=(context,page)
    teacher=users['teacher'][1]
    teacher.goto(BASE+'kpi/create',wait_until='networkidle');teacher.locator('[name=period_id]').select_option('1')
    teacher.locator('[name=indicator_id]').select_option(label="Ilmiy darajasiz. Fan katalogi va o'quv kontenti")
    for name,value in {'title':'REVIEWER-NOTICE-APPLICATION','activity_date':'2026-11-07','quantity':'3'}.items():teacher.locator(f'[name={name}]').fill(value)
    teacher.locator('[name="evidence[]"]').set_input_files({'name':'proof.pdf','mimeType':'application/pdf','buffer':b'%PDF-1.4\nReviewer test\n%%EOF'})
    teacher.get_by_role('button',name='Qoralamani saqlash',exact=True).click();teacher.wait_for_load_state('networkidle');application_url=teacher.url
    def unread(name):return users[name][0].request.get(BASE+'kpi/notification-count').json()['unread']
    def check_arrivals(expected):
        for name in users:
            if name!='teacher':assert unread(name)==int(name in expected),(name,unread(name),expected)
    def submit(appeal=False):
        teacher.goto(application_url,wait_until='networkidle')
        if appeal:
            teacher.locator('[name=reason]').fill('Please reconsider this test application')
            teacher.get_by_role('button',name='Apellyatsiya yuborish',exact=True).click()
        else:teacher.get_by_role('button',name='Tekshiruvga yuborish →',exact=True).click()
        teacher.wait_for_load_state('networkidle')
    def read(name):
        page=users[name][1];page.goto(BASE+'kpi/notifications',wait_until='networkidle')
        assert 'Tekshirish uchun yangi ariza' in page.locator('#main').inner_text()
        assert page.get_by_role('link',name='Arizani tekshirish →',exact=True).count()==1
        assert unread(name)==0
        return page
    def act(name,action):
        page=users[name][1];page.goto(application_url,wait_until='networkidle');page.locator('[name=reason]').fill(name+' test '+action);page.locator(f'button[name=action][value={action}]').click();page.wait_for_load_state('networkidle')
    check_arrivals(set())
    submit();check_arrivals({'head','head_peer'})
    head=users['head'][1];head.bring_to_front()
    head.wait_for_function("document.querySelector('[data-notification-count]').textContent==='1' && !document.querySelector('[data-notification-count]').hidden",timeout=32000)
    read('head');assert unread('head_peer')==1
    head.screenshot(path=str(OUT/'head-inbox.png'),full_page=True)
    act('head','approve');check_arrivals({'faculty'})
    for foreign in ['head_foreign','faculty_foreign']:
        page=users[foreign][1];page.goto(BASE+'kpi/notifications',wait_until='networkidle');assert 'REVIEWER-NOTICE-APPLICATION' not in page.content()
    read('faculty');act('faculty','return');check_arrivals(set())
    submit();check_arrivals({'head','head_peer'})
    act('head','approve');check_arrivals({'faculty'})
    act('faculty','approve');check_arrivals({'expert'})
    read('expert');act('expert','reject');check_arrivals(set())
    submit(appeal=True);check_arrivals({'head','head_peer'})
    act('head','approve');check_arrivals({'faculty'})
    act('faculty','approve');check_arrivals({'expert'})
    read('expert');act('expert','approve');check_arrivals({'operator'})
    read('operator');act('operator','approve');check_arrivals({'admin'})
    admin=read('admin');admin.screenshot(path=str(OUT/'final-inbox.png'),full_page=True)
    act('admin','approve');check_arrivals(set())
    act('admin','revoke');check_arrivals({'admin'})
    read('admin');act('admin','approve');check_arrivals(set())
    for name in ['head','faculty','expert','operator','admin']:
        page=users[name][1];page.goto(BASE+'kpi/notifications',wait_until='networkidle')
        assert not page.get_by_role('link',name='Arizani tekshirish →',exact=True).count()
    assert unread('teacher')>0
    teacher.goto(BASE+'kpi/notifications',wait_until='networkidle');assert 'REVIEWER-NOTICE-APPLICATION' in teacher.content()
    assert not teacher.get_by_role('link',name='Arizani tekshirish →',exact=True).count()
    assert not errors,errors
    browser.close()
print('PASS: five reviewer stages, multiple recipients, live badge, foreign scopes, return/resubmit, reject/appeal, final revoke/reapprove and owner notices.')
