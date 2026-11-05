# Dizayn v2 — mavjud dasturni yangilash

Yangilanish faqat 6 ta ko‘rinish fayliga tegadi: `services/Ui.php`, `views/layouts/main.php`, `views/kpi/index.php`, `views/auth/login.php`, `web/css/design.css`, `web/js/app.js`. Baza, parollar, config va hujjatlar paketga qo‘shilmagan va almashtirilmaydi.

## Windows’da o‘rnatish

1. `KPI-Design-v2-update.zip`ni yangi, alohida papkaga oching.
2. Ichidagi **`INSTALL-DESIGN.bat`**ni ikki marta bosing.
3. Ochilgan fayl tanlash oynasida hozir ishlayotgan dasturingizning **`start-native.bat`** faylini tanlang. Aynan avval ishga tushirgan KPI papkangizni tanlang.
4. O‘rnatuvchi eski ko‘rinish fayllarini `runtime/design-backup-...` papkasiga saqlaydi va yangi fayllarni ko‘chiradi.
5. Brauzerda **Ctrl+F5** bosing. Dastur yopiq bo‘lsa, uni avvalgidek `start-native.bat` orqali oching.

Windows o‘rnatuvchisi Linux cloud muhitida bevosita bajarilmagan. Unda PowerShell va Windows’ning standart fayl tanlash oynasi ishlatiladi. Fayl tanlash oynasi ochilmasa, quyidagi qo‘lda ko‘chirish usulidan foydalaning.

## Qo‘lda yangilash

1. Ishlayotgan KPI papkangizdagi `services/Ui.php` va `views/` papkasining alohida nusxasini oling.
2. Yangilanish arxividagi **KPI papkasining ichidagi** `services`, `views`, `web` papkalarini mavjud dasturning `start-native.bat` joylashgan papkasiga ko‘chiring.
3. Windows mavjud fayllarni almashtirishni so‘rasa, rozilik bering.
4. Brauzerda Ctrl+F5 bosing. Faqat shu papkalardagi yangilanish fayllari almashtiriladi; boshqa ilova fayllari o‘chirilmaydi.

## Yangiliklar

- To‘q ko‘k yon menyu va binafsha aksentli yagona ko‘rinish.
- Yangilangan KPI kartochkalari, yo‘nalishlar balli va ariza holatlari diagrammasi. Barcha sonlar haqiqiy ma’lumotlardan olinadi.
- Kunduzgi va tungi rejim; tanlov brauzerda eslab qolinadi.
- Telefonda ochilib-yopiladigan menyu; Escape bilan yopish va klaviatura fokusini boshqarish.
- Yangilangan jadvallar, formalar, holat belgilari va administrator sahifalari.
- Parolni ko‘rsatish/yashirish va uzun jadvallarni yon tomonga surish eslatmasi.
- Internet/CDN’siz ishlaydigan SVG ikonlar, shriftlar va uslublar.

## Tekshiruvlar

Chromium’da login, parol ko‘rsatish/yashirish, rejimni almashtirish va sahifa yangilanganda saqlanishi, 13 ta kontent sahifasi, yagona faol menyu, tekshiruv navbati, mobil menyu/Escape/fokus va 390/320 px ekranlarda sahifa eni tekshirildi. Alohida test bazasi ishlatildi. `tests/design_browser.py` qo‘shimcha vizual tekshiruvlarni qayta bajarish uchun saqlangan; Playwright va Chromium kerak.

Shuningdek, mavjud hisoblash/ish jarayonining 46 ta va HTTP oqimining 45 ta tekshiruvi — jami 91 ta — dizayn yangilanishi bilan muvaffaqiyatli yakunlandi. Paket avvalgi ilova nusxasiga qo‘llanganda SQLite baza va `config/local.php` xeshlari o‘zgarmagani tekshirildi.

Skrinshotlar `docs/design-v2/`da. Ular sintetik test hisoblarini ko‘rsatadi, foydalanuvchining shaxsiy ma’lumotlarini emas.

Qaytish kerak bo‘lsa, o‘rnatuvchi yaratgan `runtime/design-backup-...` ichidagi fayllarni dastur ildiziga qayta ko‘chiring, so‘ng Ctrl+F5 bosing.
