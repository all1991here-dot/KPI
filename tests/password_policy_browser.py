"""Admin-issued passwords by default, with the optional change flow verified separately.
Use isolated http-test fixtures. Argument 1 is base URL; argument 2 may be 'enabled'.
"""
from playwright.sync_api import sync_playwright
import shutil
import sys
import time

base=(sys.argv[1] if len(sys.argv)>1 else 'http://127.0.0.1:8001').rstrip('/')+'/?r='
enabled=len(sys.argv)>2 and sys.argv[2]=='enabled'
username='password_policy_'+str(time.time_ns())
initial='Temporary-Password2026!';changed='Updated-Password2026!';reset='Admin-Reset-Password2026!'
with sync_playwright() as p:
    browser=p.chromium.launch(executable_path=shutil.which('chromium'),headless=True,args=['--no-sandbox'])
    admin_context=browser.new_context();admin=admin_context.new_page()
    teacher_context=browser.new_context();teacher=teacher_context.new_page()
    def login(page,user,password):
        page.goto(base+'auth/login',wait_until='networkidle')
        page.locator('[name=username]').fill(user);page.locator('[name=password]').fill(password)
        page.locator('.login-card button.button').click();page.wait_for_load_state('networkidle')
    login(admin,'http_admin','OnlyForIsolated-HttpTests2026!')
    admin.goto(base+'admin/user-edit',wait_until='networkidle')
    for key,value in {'name':'Password policy test','username':username,'password':initial}.items():admin.locator(f'[name={key}]').fill(value)
    admin.locator('[name=role]').select_option('teacher');admin.locator('[name=category]').select_option('DARAJASIZ')
    admin.get_by_role('button',name='Saqlash',exact=True).click();admin.wait_for_load_state('networkidle')
    assert 'id=' in admin.url;editor=admin.url
    login(teacher,username,initial)
    if enabled:
        assert teacher.title()=='Parolni yangilash'
        for key,value in {'old_password':initial,'password':changed,'confirmation':changed}.items():teacher.locator(f'[name={key}]').fill(value)
        teacher.get_by_role('button',name='Parolni saqlash',exact=True).click();teacher.wait_for_load_state('networkidle')
        assert teacher.title()=='Shaxsiy KPI paneli'
        expected=changed
    else:
        assert teacher.title()=='Shaxsiy KPI paneli'
        teacher.goto(base+'kpi/create',wait_until='networkidle')
        assert teacher.locator('[name=indicator_id] option').count()==45
        teacher.goto(base+'kpi/profile',wait_until='networkidle')
        assert not teacher.get_by_role('link',name='Parolni yangilash',exact=True).count()
        teacher.goto(base+'auth/password',wait_until='networkidle')
        assert teacher.title()=='Shaxsiy KPI paneli'
        csrf=teacher.locator('meta[name=csrf-param]').get_attribute('content')
        token=teacher.locator('meta[name=csrf-token]').get_attribute('content')
        response=teacher_context.request.post(base+'auth/password',form={csrf:token,'old_password':initial,'password':changed,'confirmation':changed})
        assert response.status==200 and 'Shaxsiy KPI paneli' in response.text()
        expected=initial
    teacher.locator('.logout-button').click();teacher.wait_for_load_state('networkidle')
    teacher_context.close();teacher_context=browser.new_context();teacher=teacher_context.new_page()
    login(teacher,username,expected);assert teacher.title()=='Shaxsiy KPI paneli'
    teacher.goto(base+'kpi/profile',wait_until='networkidle')
    assert teacher.get_by_role('link',name='Parolni yangilash',exact=True).count()==int(enabled)
    # Admin reset still invalidates the old session, then accepts the replacement password.
    admin.goto(editor,wait_until='networkidle');admin.locator('[name=password]').fill(reset)
    admin.get_by_role('button',name='Saqlash',exact=True).click();admin.wait_for_load_state('networkidle')
    teacher.goto(base+'kpi/index',wait_until='networkidle');assert teacher.locator('[name=username]').count()==1
    login(teacher,username,expected);assert teacher.locator('[name=username]').count()==1
    login(teacher,username,reset)
    assert teacher.title()==('Parolni yangilash' if enabled else 'Shaxsiy KPI paneli')
    if not enabled:
        # The ignored self-service POST must not have changed the password.
        teacher.locator('.logout-button').click();teacher.wait_for_load_state('networkidle')
        login(teacher,username,changed);assert teacher.locator('[name=username]').count()==1
        login(teacher,username,reset);assert teacher.title()=='Shaxsiy KPI paneli'
    browser.close()
print('PASS:', 'optional password change enabled' if enabled else 'admin-issued password without forced change', '; fresh login, admin reset, session invalidation and category options.')
