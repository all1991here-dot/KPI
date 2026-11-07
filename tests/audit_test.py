"""Permissions, pagination, filters and secret redaction on an isolated audit fixture."""
from pathlib import Path
exec(Path(__file__).with_name('http_test.py').read_text().split('for attempt in range(30):')[0])
a=Client();a.login('admin')
ids=[]
for page in range(1,8):
    html=a.get('admin/audit',action='audit_test',page=page).read().decode()
    rows=re.findall(r'data-audit-id="(\d+)"',html);ids+=rows
    check(len(rows)==(50 if page<7 else 21),'pagination '+str(page))
    check('NEVER_EXPOSE_ME' not in html and 'YASHIRILGAN' in html,'secret redaction '+str(page))
check(len(set(ids))==321,'all 321 records accessible without duplicates')
check('data-audit-id=' not in a.get('admin/audit',q='missing-query-value').read().decode(),'search filters rows')
fail_status(lambda:a.get('admin/audit',**{'from':'2026-02-30'}),400,'invalid date rejected')
check(a.get('admin/audit',source='history').status==200,'earlier application history available')
for role in ['operator','teacher','head','faculty','expert','manager']:
    c=Client();c.login(role)
    check('r=admin%2Faudit' not in c.get('kpi/index').read().decode(),'journal hidden for '+role)
    fail_status(lambda:c.get('admin/audit'),403,'journal access denied for '+role)
print('SUCCESS audit checks:',count)
