"""Build the data-preserving design update from an explicit file allowlist."""
from pathlib import Path
import hashlib
import zipfile

ROOT = Path(__file__).resolve().parents[1]
FILES = (
    'services/Ui.php',
    'views/layouts/main.php',
    'views/kpi/index.php',
    'views/auth/login.php',
    'web/css/design.css',
    'web/js/app.js',
)
INSTRUCTIONS = '''KPI DIZAYN V2

1. ZIPni alohida yangi papkaga oching.
2. INSTALL-DESIGN.bat faylini ikki marta bosing.
3. Oynada hozir ishlayotgan OLDINGI KPI papkangizdagi start-native.bat faylini tanlang.
4. TAYYOR xabaridan keyin brauzerda Ctrl+F5 bosing.

Baza, login, parollar va hujjatlar almashtirilmaydi.
Avvalgi dizayn runtime/design-backup-... papkasiga saqlanadi.

Oynada fayl tanlash ishlamasa:
ZIPdagi KPI papkasining ICHIDAGI services, views va web papkalarini
mavjud dasturdagi shu papkalar bilan birlashtiring. Fayllarni almashtiring.
Keyin Ctrl+F5 bosing. Ichma-ich KPI/KPI papkasi hosil bolmasin.

Batafsil: YORIQNOMA.md.
Windows ornatuvchisi Windowsda bevosita sinalmagan.
Ilova, brauzer va PHP orqali ishga tushirish tekshiruvlari bajarilgan.
'''


def main():
    out = ROOT / 'releases/KPI-Design-v2-update.zip'
    out.parent.mkdir(exist_ok=True)
    manifest = ''.join(hashlib.sha256((ROOT / name).read_bytes()).hexdigest()
                       + '  KPI/' + name + '\n' for name in FILES)
    with zipfile.ZipFile(out, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        for name in FILES:
            archive.write(ROOT / name, 'KPI/' + name)
        archive.write(ROOT / 'bin/install-design.bat', 'INSTALL-DESIGN.bat')
        archive.write(ROOT / 'docs/DIZAYN-V2.md', 'YORIQNOMA.md')
        archive.writestr('BIRINCHI-OQING.txt', INSTRUCTIONS)
        archive.writestr('files.sha256', manifest)
    with zipfile.ZipFile(out) as archive:
        assert archive.testzip() is None
        actual = {name.removeprefix('KPI/') for name in archive.namelist() if name.startswith('KPI/')}
        assert actual == set(FILES), 'Unexpected application paths in patch'
        for name in FILES:
            assert archive.read('KPI/' + name) == (ROOT / name).read_bytes()
    digest = hashlib.sha256(out.read_bytes()).hexdigest()
    out.with_suffix('.sha256').write_text(digest + '  ' + out.name + '\n')
    print(f'{out.name}: {out.stat().st_size} bytes, {len(FILES)} application files. No database or private configuration.')


if __name__ == '__main__':
    main()
