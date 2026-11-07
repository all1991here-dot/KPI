# Admin bergan login-parol bilan ishlash

Mavjud KPI ilovasi uchun yangilanish paketi.

1. KPI qora oynasini yoping.
2. ZIPni alohida papkaga to‘liq oching (Extract All / Извлечь всё).
3. INSTALL-LOGIN.bat ni oching va mavjud dasturdagi start-native.bat ni tanlang.
4. TAYYOR yozuvidan keyin dasturni qayta oching va brauzerda Ctrl+F5 bosing.
5. Foydalanuvchi administrator bergan amaldagi login-parol bilan panelga kiradi.

Majburiy parol yangilash oynasi hozircha o‘chirilgan. Profilidagi parol
almashtirish havolasi yashiriladi; eski auth/password manzili ham panelga qaytadi.
Parolni shu eski manzilga POST yuborib almashtirish ham to‘xtatilgan.
Administrator foydalanuvchini tahrirlash orqali kirish parolini belgilaydi.
Bloklangan hisoblar bloklanganicha qoladi; rollar va ma’lumot ko‘rish huquqlari saqlanadi.

Yangilanish hech kimning parolini avtomatik almashtirmaydi yoki eski parolga
qaytarmaydi. Parol avval o‘zgartirilgan bo‘lsa, administrator yangi kirish
parolini belgilashi kerak. Baza, dalillar va HEMIS sozlamalari almashtirilmaydi.
Eski fayllar va standart SQLite baza runtime/admin-password-backup-* ga zaxiralanadi.

Keyin parol yangilashni qayta yoqish uchun xizmat muhitida
KPI_PASSWORD_CHANGE_ENABLED=1 belgilanadi va xizmat qayta ishga tushiriladi.
Mavjud must_change_password belgilari bazada saqlangan, qayta yoqilganda amal qiladi.
config/local.php va shaxsiy sozlamalar bu paket bilan almashtirilmaydi.

Windows BAT o‘rnatgichi ushbu Linux muhitida bevosita bajarilmagan.

Tekshiruv natijalari:
- Haqiqiy PHP 8.3/SQLite ilovasida administrator yaratgan o‘qituvchining birinchi
  kirishi, qayta kirishi, yangi admin paroli, eski sessiyaning bekor bo‘lishi tekshirildi.
- O‘chirilgan parol oynasi GET/POST orqali ochilmaydi va parolni o‘zgartirmaydi.
- Alohida xizmatda KPI_PASSWORD_CHANGE_ENABLED=1 bilan eski parol almashtirish
  jarayonining qayta yoqilishi va ishlashi ham tekshirildi.
- Umumiy HTTP testlari: 46/46 o‘tdi.
- Paketni o‘rnatish, qayta o‘rnatish, baza/sozlamalarni saqlash, fayllarni
  zaxiralash va buzilgan paketni rad etish sinovlari o‘tdi.

Testlar: tests/password_policy_browser.py, tests/http_test.py,
tests/admin_password_installer.py. Faqat alohida http-test runtime bilan ishlating.
