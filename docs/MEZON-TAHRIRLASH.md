# Mezonlarni alohida oynada tahrirlash

Mavjud KPI ilovasi uchun yangilanish paketi; to‘liq dastur emas.

1. KPI qora oynasini yoping.
2. ZIPni alohida papkaga to‘liq oching (Extract All / Извлечь всё).
3. INSTALL-MEZON.bat ni oching va mavjud dasturdagi start-native.bat ni tanlang.
4. TAYYOR yozuvidan keyin dasturni qayta oching, brauzerda Ctrl+F5 bosing.

Superadministrator → Mezonlarni boshqarish:
- Ro‘yxatda faqat mezonlar va Tahrirlash havolasi turadi.
- Yangi mezon tugmasi bo‘sh yaratish sahifasini ochadi.
- Tahrirlash alohida sahifani ochadi; pastda Saqlash va O‘chirish mavjud.
- Saqlashdan keyin tahrirlash sahifasida qolasiz.
- Mezonlarga qaytish ro‘yxatni ochadi.
- O‘chirishdan keyin mezon faol katalogdan olinadi va ro‘yxat ochiladi.
- Eski hujjatlar va ballar tarixi saqlanadi. Davr qoidalari alohida boshqariladi.

Yangilanish faqat controller, ikkita ko‘rinish va tashkilot uslubini almashtiradi.
Bazaga, parollarga, dalillarga va HEMIS sozlamalariga tegmaydi.
Oldingi fayllar va standart SQLite baza runtime/indicator-editor-backup-* ga zaxiralanadi.

Windows BAT fayli Linux muhitida bevosita bajarilmagan.

## Tekshiruv natijalari

Alohida SQLite test bazasi, PHP 8.3 va Chromium bilan:
- Alohida yaratish/tahrirlash sahifasi, saqlangandan keyingi manzil, ro‘yxatga qaytish.
- Noto‘g‘ri kodda forma qiymatlari va checkbox holati saqlanishi.
- O‘chirish alohida POST forma orqali ishlashi va o‘chirilgan mezon sahifasi 404 qaytarishi.
- 390 px mobil ko‘rinishda sahifa enidan chiqmasligi.
- Oddiy administrator uchun yangi sahifa va to‘qqizta qalbakilashtirilgan POST yo‘li 403 qaytarishi.
- Yangilash o‘rnatgichining fayllar/baza zaxirasi, takroriy o‘rnatish va buzilgan faylni rad etishi.

Buyruqlar: `python tests/management_browser.py` va `python tests/indicator_editor_installer.py`.
Brauzer testi uchun alohida HTTP hamda organization fixture’lari va 8001-portdagi test ilovasi kerak.
