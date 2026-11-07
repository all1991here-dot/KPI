"""Actual final admin cannot revoke; superadmin can, without a public history entry.
Requires fresh decision_permissions_fixtures.php at port 8001.
"""
from playwright.sync_api import sync_playwright
import shutil
BASE='http://127.0.0.1:8001/?r='
with sync_playwright() as p:
    browser=p.chromium.launch(executable_path=shutil.which('chromium'),headless=True,args=['--no-sandbox'])
    def login(name):
        context=browser.new_context();page=context.new_page();page.goto(BASE+'auth/login',wait_until='networkidle');page.locator('[name=username]').fill('http_'+name);page.locator('[name=password]').fill('OnlyForIsolated-HttpTests2026!');page.locator('.login-card button.button').click();page.wait_for_load_state('networkidle');return context,page
    operator_context,operator=login('operator');operator.goto(BASE+'kpi/view&id=1',wait_until='networkidle')
    assert not operator.get_by_role('button',name='Tasdiqni bekor qilish',exact=True).count()
    assert not operator.get_by_role('link',name='Tahrirlash',exact=True).count()
    assert 'ADMIN-VISIBLE-REAPPROVAL' in operator.content()
    assert 'SUPERADMIN-HIDDEN-REVOKE' not in operator.content()
    csrf=operator.locator('meta[name=csrf-param]').get_attribute('content');token=operator.locator('meta[name=csrf-token]').get_attribute('content')
    response=operator_context.request.post(BASE+'kpi/act&id=1',form={csrf:token,'action':'revoke','reason':'Forged ordinary cancellation'})
    assert 'Tasdiqni faqat superadministrator' in response.text()
    assert operator_context.request.post(BASE+'kpi/create&id=1',form={csrf:token,'title':'Forged approved edit'}).status==403
    response=operator_context.request.post(BASE+'kpi/delete-evidence&id=1',form={csrf:token})
    assert 'faqat superadministrator' in response.text()
    super_context,super_page=login('admin');super_page.goto(BASE+'kpi/view&id=1',wait_until='networkidle')
    assert super_page.get_by_role('button',name='Tasdiqni bekor qilish',exact=True).count()==1
    super_page.locator('[name=reason]').fill('BROWSER-SUPERADMIN-REVOKE')
    super_page.locator('button[name=action][value=revoke]').click();super_page.wait_for_load_state('networkidle')
    assert 'BROWSER-SUPERADMIN-REVOKE' not in super_page.content()
    assert 'HEAD-VISIBLE-APPROVAL' in super_page.content()
    owner_context,owner=login('teacher');owner.goto(BASE+'kpi/view&id=1',wait_until='networkidle')
    assert 'BROWSER-SUPERADMIN-REVOKE' not in owner.content()
    owner.goto(BASE+'kpi/notifications',wait_until='networkidle')
    assert 'BROWSER-SUPERADMIN-REVOKE' in owner.content()
    operator.goto(BASE+'kpi/notifications',wait_until='networkidle')
    assert 'Tekshirish uchun yangi ariza' in operator.content()
    operator.goto(BASE+'kpi/view&id=1',wait_until='networkidle');operator.locator('[name=reason]').fill('BROWSER-ADMIN-REAPPROVAL')
    operator.locator('button[name=action][value=approve]').click();operator.wait_for_load_state('networkidle')
    owner.goto(BASE+'kpi/reports',wait_until='networkidle');assert '15.00' in owner.locator('.report-results tbody').inner_text()
    super_page.goto(BASE+'admin/audit',wait_until='networkidle');assert 'BROWSER-SUPERADMIN-REVOKE' in super_page.content()
    browser.close()
print('PASS: final ordinary-admin UI and forged revoke/edit/evidence requests blocked, superadmin outside route can revoke, document history hidden, audit/notifications retained and score reapproved once.')
