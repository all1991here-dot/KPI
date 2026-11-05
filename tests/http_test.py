"""End-to-end checks against tests/http-fixtures.php; never use real accounts."""
import urllib.request, urllib.parse, urllib.error, http.cookiejar, re, io, zipfile, sys, time
BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://127.0.0.1:8001'
PASSWORD = 'OnlyForIsolated-HttpTests2026!'
count = 0
def check(value, name):
    global count
    if not value: raise AssertionError(name)
    count += 1
    print('PASS', name)
class Client:
    def __init__(self): self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
    def get(self, route, **params):
        request = urllib.request.Request(BASE + '/?' + urllib.parse.urlencode({'r':route, **params}))
        return self.opener.open(request, timeout=40)
    def token(self, route, **params):
        html = self.get(route, **params).read().decode()
        match = re.search(r'name="_csrf" value="([^"]+)"', html)
        if not match: raise AssertionError('Missing CSRF: '+route)
        return match.group(1)
    def post(self, route, data, params=None, upload=None):
        data = dict(data); data['_csrf'] = self.token('auth/login' if route=='auth/login' else 'kpi/create')
        url = BASE + '/?' + urllib.parse.urlencode({'r':route, **(params or {})})
        if upload:
            boundary='----KpiTestBoundary2026';parts=[]
            for key,value in data.items():parts.append((f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n').encode())
            filename,body,mime=upload
            parts.append((f'--{boundary}\r\nContent-Disposition: form-data; name="evidence[]"; filename="{filename}"\r\nContent-Type: {mime}\r\n\r\n').encode()+body+b'\r\n')
            parts.append((f'--{boundary}--\r\n').encode());encoded=b''.join(parts);headers={'Content-Type':'multipart/form-data; boundary='+boundary}
        else:encoded=urllib.parse.urlencode(data).encode();headers={}
        return self.opener.open(urllib.request.Request(url,encoded,headers),timeout=60)
    def login(self, name):
        r=self.post('auth/login',{'username':'http_'+name,'password':PASSWORD})
        check('Boshqaruv paneli' in r.read().decode(),name+' login')
def fail_status(call,status,name):
    try:call()
    except urllib.error.HTTPError as e:check(e.code==status,name);return
    raise AssertionError('Expected '+str(status)+': '+name)

for attempt in range(30):
    try:
        if urllib.request.urlopen(BASE+'/?r=auth/login',timeout=2).status==200: break
    except (urllib.error.URLError, TimeoutError): pass
    time.sleep(0.5)
else: raise AssertionError('Web server did not become ready')
admin=Client();admin.login('admin')
for route in ['kpi/index','kpi/applications','kpi/criteria','kpi/reports','kpi/profile','kpi/notifications','admin/users','admin/structure','admin/periods','admin/rules','admin/route','admin/hemis','admin/audit']:
    response=admin.get(route);check(response.status==200 and b'<!doctype html>' in response.read().lower(),'page '+route)
admin.post('admin/users',{'name':'UI Created Teacher','username':'http_created','role':'teacher','category':'PHD','org_id':3,'degree':'PhD','position':'Dotsent','password':PASSWORD,'active':1}).read()
check('http_created' in admin.get('admin/users').read().decode(),'create user through admin form')
created=Client();created.login('created')
check('Parolni yangilash' in created.get('kpi/index').read().decode(),'temporary password requires change')
response=created.post('auth/password',{'old_password':PASSWORD,'password':'Changed-HttpPassword2026!','confirmation':'Changed-HttpPassword2026!'})
check('Parol yangilandi.' in response.read().decode(),'first login password change works')
for group in ['department','faculty','university']:
    check(admin.get('kpi/reports',group=group).status==200,'report group '+group)
rules=admin.get('admin/rules',period=1,category='PHD').read().decode()
marker=rules.index('PHD-NASHR-Q1Q2');rule_id=int(re.search(r'(?:&amp;|&)id=(\d+)',rules[marker:marker+600]).group(1))
updated=admin.post('admin/rules',{'enabled':1,'calc_type':'BirlikUchun','base_score':8,'upper_score':'','cap_score':'','formula_divisor':'','formula_mode':'','exclusive_group':'http_nashr','decision':'Test policy: per-article rule approved for fixture only'},params={'period':1,'category':'PHD','id':rule_id}).read().decode()
check('Qoida saqlandi.' in updated,'policy rule can be enabled with decision')
teacher=Client();teacher.login('teacher')
fail_status(lambda:teacher.get('admin/users'),403,'teacher blocked from admin')
fail_status(lambda:teacher.get('auth/logout'),405,'GET logout blocked')
fail_status(lambda:teacher.opener.open(urllib.request.Request(BASE+'/?r=auth/logout',b'')),400,'CSRF required')
catalog=teacher.get('kpi/create').read().decode();indicator=re.search(r'<option value="(\d+)">PhD[^<]*[Pp]atent',catalog)
if not indicator:
    # Resolve the official indicator through the rules table rather than guessing an ID.
    data=admin.get('admin/rules',period=1,category='PHD').read().decode()
    marker=data.index('PHD-LOYIHA-PATENT');chunk=data[max(0,marker-350):marker+600]
    # Initial import preserves the ordering: use options text in creation form.
    options=re.findall(r'<option value="(\d+)">([^<]+)</option>',catalog)
    matches=[x for x in options if 'patent' in x[1].lower() or 'ixtiro' in x[1].lower()]
    check(bool(matches),'patent option found');indicator_id=matches[0][0]
else:indicator_id=indicator.group(1)
payload={'period_id':1,'indicator_id':indicator_id,'title':'HTTP end-to-end patent','activity_date':'2026-06-01','quantity':1,'notes':'Functional upload check'}
pdf=b'%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n'
bad=teacher.post('kpi/create',payload,upload=('fake.pdf',b'<script>not a pdf</script>','application/pdf')).read().decode()
check('Faqat haqiqiy PDF' in bad,'fake PDF rejected by server MIME check')
response=teacher.post('kpi/create',payload,upload=('evidence.pdf',pdf,'application/pdf'));html=response.read().decode()
match=re.search(r'ARIZA #(\d+)',html);check(bool(match),'create draft with real HTTP upload');app_id=int(match.group(1))
file_id=int(re.search(r'r=kpi%2Fdownload(?:&amp;|&)id=(\d+)',html).group(1))
check(teacher.get('kpi/download',id=file_id).read()==pdf,'download original evidence')
other=Client();other.login('other')
fail_status(lambda:other.get('kpi/view',id=app_id),403,'other teacher cannot view application')
fail_status(lambda:other.get('kpi/download',id=file_id),404,'other teacher cannot download evidence')
html=teacher.post('kpi/act',{'action':'submit'},params={'id':app_id}).read().decode();check('Yuborilgan' in html,'submit with CSRF')
for role in ['head','expert','admin']:
    reviewer=Client();reviewer.login(role);html=reviewer.post('kpi/act',{'action':'approve','reason':'HTTP '+role+' verified'},params={'id':app_id}).read().decode()
check('Tasdiqlangan' in html and '5.00' in html,'full HTTP approval gives 5 points')
report=admin.get('kpi/reports',period=1).read().decode();check('HTTP teacher' in report and '5.00' in report,'report reflects approved results')
xlsx=admin.get('kpi/reports',period=1,format='xlsx').read()
with zipfile.ZipFile(io.BytesIO(xlsx)) as z:
    check('HTTP teacher' in z.read('xl/worksheets/sheet1.xml').decode(),'valid XLSX export contains results')
csv=admin.get('kpi/reports',period=1,format='csv').read().decode('utf-8-sig');check('HTTP teacher' in csv,'CSV export contains results')
fail_status(lambda:teacher.get('kpi/act',id=app_id),405,'mutation GET rejected')
# Verify the documented web root never exposes configuration or uploads.
for path in ['/config/local.php','/runtime/kpi.sqlite','/data/criteria.xml','/vendor/autoload.php']:
    try:
        r=urllib.request.urlopen(BASE+path,timeout=10);body=r.read()
        check(b'cookieKey' not in body and not body.startswith(b'SQLite format') and b'<Katalog' not in body,'private path '+path)
    except urllib.error.HTTPError as e:check(e.code in (400,403,404),'private path '+path)
print(f'\nSUCCESS: {count} HTTP checks completed.')
