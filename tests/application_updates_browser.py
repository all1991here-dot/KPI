"""Five-step real workflow: owner notifications, polling and reports before final approval.
Requires a fresh application_updates_fixtures.php runtime on port 8001.
"""
from pathlib import Path
from playwright.sync_api import sync_playwright
import shutil

ROOT=Path(__file__).resolve().parents[1];OUT=ROOT/'docs/application-updates';OUT.mkdir(exist_ok=True)
BASE='http://127.0.0.1:8001/?r='
PASSWORD='OnlyForIsolated-HttpTests2026!'
with sync_playwright() as p:
    browser=p.chromium.launch(executable_path=shutil.which('chromium'),headless=True,args=['--no-sandbox'])
    contexts=[];errors=[]
    def account(name):
        context=browser.new_context(viewport={'width':1440,'height':1000},reduced_motion='reduce');contexts.append(context)
        page=context.new_page();page.on('pageerror',lambda e:errors.append(str(e)))
        page.goto(BASE+'auth/login',wait_until='networkidle');page.locator('[name=username]').fill('http_'+name);page.locator('[name=password]').fill(PASSWORD);page.locator('.login-card button.button').click();page.wait_for_load_state('networkidle')
        return context,page
    teacher_context,teacher=account('teacher')
    reviewers={name:account(name)[1] for name in ['head','faculty','expert','operator','admin']}
    teacher.goto(BASE+'kpi/create',wait_until='networkidle')
    teacher.locator('[name=period_id]').select_option('1')
    teacher.locator('[name=indicator_id]').select_option(label="Ilmiy darajasiz. Fan katalogi va o'quv kontenti")
    for name,value in {'title':'UPDATES-OWNER-APPLICATION','activity_date':'2026-11-07','quantity':'3'}.items():teacher.locator(f'[name={name}]').fill(value)
    teacher.locator('[name="evidence[]"]').set_input_files({'name':'evidence.pdf','mimeType':'application/pdf','buffer':b'%PDF-1.4\nUpdates isolated test\n%%EOF'})
    teacher.get_by_role('button',name='Qoralamani saqlash',exact=True).click();teacher.wait_for_load_state('networkidle')
    application_url=teacher.url
    def reports(status,stage=None,approved=False):
        teacher.goto(BASE+'kpi/reports&period=1',wait_until='networkidle')
        row=teacher.locator('#application-status-report tbody tr').filter(has_text='UPDATES-OWNER-APPLICATION')
        assert row.count()==1
        assert status in row.inner_text()
        if stage:assert stage in row.inner_text()
        assert teacher.locator('.report-results tbody tr').count()==int(approved)
        if approved:assert '15.00' in teacher.locator('.report-results tbody').inner_text()
    def unread(context=teacher_context):return context.request.get(BASE+'kpi/notification-count').json()['unread']
    reports('Qoralama');assert unread()==1
    teacher.goto(BASE+'kpi/notifications',wait_until='networkidle');assert 'Qoralama saqlandi' in teacher.locator('#main').inner_text();assert unread()==0
    teacher.goto(application_url,wait_until='networkidle');teacher.get_by_role('button',name='Tekshiruvga yuborish →',exact=True).click();teacher.wait_for_load_state('networkidle')
    reports('Yuborilgan','Bosqich 1');assert '15.00' in teacher.locator('#application-status-report tbody').inner_text()
    teacher.goto(BASE+'kpi/notifications',wait_until='networkidle');assert 'Ariza tekshiruvga yuborildi' in teacher.locator('#main').inner_text();assert unread()==0
    teacher.goto(BASE+'kpi/reports',wait_until='networkidle')
    def decision(actor,action,reason):
        page=reviewers[actor];page.goto(application_url,wait_until='networkidle');page.locator('[name=reason]').fill(reason)
        page.locator(f'button[name=action][value={action}]').click();page.wait_for_load_state('networkidle')
    decision('head','approve','HEAD-DECISION-OWNER')
    # The teacher remains on the same page: the normal 15-second poll updates the bell.
    teacher.bring_to_front()
    teacher.wait_for_function("document.querySelector('[data-notification-count]').textContent==='1' && !document.querySelector('[data-notification-count]').hidden",timeout=22000)
    teacher.goto(BASE+'kpi/notifications',wait_until='networkidle')
    assert 'HEAD-DECISION-OWNER' in teacher.locator('#main').inner_text();assert unread()==0
    # Receipt persists across logout/login, not just within a session.
    teacher.locator('.logout-button').click();teacher.wait_for_load_state('networkidle')
    teacher_context.close();teacher_context,teacher=account('teacher');assert unread(teacher_context)==0
    reports('Tekshiruvda','Bosqich 2')
    teacher.screenshot(path=str(OUT/'pending-report.png'),full_page=True)
    decision('faculty','return','FACULTY-RETURN-OWNER');assert unread(teacher_context)==1
    reports('Qaytarilgan','Xodim to‘ldirishi kutilmoqda')
    teacher.goto(BASE+'kpi/notifications',wait_until='networkidle');assert 'FACULTY-RETURN-OWNER' in teacher.locator('#main').inner_text()
    teacher.goto(application_url,wait_until='networkidle');teacher.get_by_role('button',name='Tekshiruvga yuborish →',exact=True).click();teacher.wait_for_load_state('networkidle')
    for name in ['head','faculty','expert','operator','admin']:decision(name,'approve',name.upper()+'-APPROVAL-OWNER')
    reports('Tasdiqlangan','Barcha bosqichlar yakunlangan',approved=True)
    decision('admin','revoke','FINAL-REVOKED-OWNER');reports('Tekshiruvda','Bosqich 5')
    decision('admin','approve','FINAL-REAPPROVED-OWNER');reports('Tasdiqlangan',approved=True)
    teacher.goto(BASE+'kpi/notifications',wait_until='networkidle');assert 'FINAL-REVOKED-OWNER' in teacher.locator('#main').inner_text()
    assert unread(teacher_context)==0
    teacher.screenshot(path=str(OUT/'notifications.png'),full_page=True)
    for width in [390,320]:
        teacher.set_viewport_size({'width':width,'height':844});teacher.goto(BASE+'kpi/reports',wait_until='networkidle')
        assert teacher.evaluate('document.documentElement.scrollWidth<=innerWidth+1')
    other_context,other=account('other');assert unread(other_context)==0
    for route in ['kpi/reports','kpi/notifications']:
        other.goto(BASE+route,wait_until='networkidle');assert 'UPDATES-OWNER-APPLICATION' not in other.content()
    # A second tab shares the same persisted read state.
    tab=teacher_context.new_page();tab.goto(BASE+'kpi/notifications',wait_until='networkidle');assert unread(teacher_context)==0
    assert not errors,errors
    browser.close()
print('PASS: draft/submission reports, five approval stages, return/resubmit, revoke/reapprove, 15-second live badge, persistent read receipt, own-only notifications/reports and mobile.')
