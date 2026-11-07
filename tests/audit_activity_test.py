"""Check recorded business events after http_test.py in its isolated runtime."""
from pathlib import Path
exec(Path(__file__).with_name('http_test.py').read_text().split('for attempt in range(30):')[0])
a=Client();a.login('admin')
for code in ['login','user_save','rule_update','evidence_upload','evidence_download','report_export','application_save','application_submit','application_approve','application_superadmin_approve','request_post','operation_failed']:
    html=a.get('admin/audit',action=code).read().decode()
    check('data-audit-id=' in html,'recorded '+code)
    check(PASSWORD not in html,'no credentials in '+code)
html=a.get('admin/audit',action='application_approve').read().decode()
check('before' in html and 'state' in html and 'reason' in html,'decision records before/after and reason')
c=Client();c.post('auth/login',{'username':'http_missing','password':'DO-NOT-LOG-PASSWORD-2026!'}).read()
html=a.get('admin/audit',action='login_failed').read().decode()
check('http_missing' in html and 'DO-NOT-LOG-PASSWORD-2026!' not in html,'failed login logs identity without attempted password')
a.post('auth/logout',{}).read();a.login('admin')
check('data-audit-id=' in a.get('admin/audit',action='logout').read().decode(),'logout recorded')
print('SUCCESS activity checks:',count)
