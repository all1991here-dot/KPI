# Superadministrator amallar jurnali

Jurnal menyusi va `admin/audit` sahifasi faqat superadministratorga ochiq. Boshqa rollarning to‘g‘ridan-to‘g‘ri kirishi serverda ham rad etiladi.

Yangi yozuvlar: kirish/chiqish va muvaffaqiyatsiz kirish, foydalanuvchi/tuzilma/mezon/davr/qoidalar/marshrut o‘zgarishlari, HEMIS importidagi har bir saqlangan obyekt, barcha rollarning ariza yaratish/tahrirlash/yuborish/qaror/bekor qilish amallari, dalil qo‘shish/o‘chirish/yuklash va hisobot eksporti. Yozuvlarda bajaruvchining o‘sha paytdagi ismi/login/roli, vaqt, obyekt, tegishli oldingi/keyingi qiymatlar, izoh, so‘rov manzili va IP saqlanadi. Parollar, hashlar, sessiya va API kalitlari qayd etilmaydi.

Jurnal 50 tadan sahifalanadi; eski 300 yozuv cheklovi olib tashlandi. Qidiruv, amal, bajaruvchi ID va sana filtrlari mavjud. “Arizalar harakatlari” bo‘limi oldingi review yozuvlarini ham ko‘rsatadi. Avval yozilmagan tafsilotlarni qayta tiklab bo‘lmaydi. Superadministrator amallari oddiy hujjat tarixida yashirin qoladi, ichki jurnalda saqlanadi.

## O‘rnatish

1. KPI test dasturining qora oynasini yoping.
2. ZIPni alohida papkaga chiqaring va `INSTALL-AUDIT.bat`ni ishga tushiring.
3. Mavjud dasturingizdagi `start-native.bat` faylini tanlang.
4. Shu `start-native.bat` bilan qayta oching; yangi indekslar migratsiyasi avtomatik bajariladi. Brauzerda Ctrl+F5 bosing.

O‘rnatuvchi avval fayllar va ma’lumotlar bazasidan `runtime/audit-log-backup-*` zaxirasini oladi. Mavjud loginlar, arizalar, dalillar va sozlamalar saqlanadi.

## Tekshirish

Izolyatsiyalangan runtime bilan `tests/http-fixtures.php`, keyin `tests/http_test.py`; qo‘shimcha `tests/audit-fixtures.php` va `tests/audit_test.py`. Umumiy xizmat sinovlari `tests/run.php`, paket sinovi `tests/audit_installer.py`.
