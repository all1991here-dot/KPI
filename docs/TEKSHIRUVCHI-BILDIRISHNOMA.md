# Tekshiruvchilarga yangi ariza haqida bildirishnoma

Mavjud KPI ilovasi uchun yangilanish paketi.

1. KPI qora oynasini yoping.
2. ZIPni alohida papkaga to‘liq oching (Extract All / Извлечь всё).
3. INSTALL-REVIEW.bat ni oching va mavjud dasturdagi start-native.bat ni tanlang.
4. TAYYOR yozuvidan so‘ng shu start-native.bat orqali dasturni qayta oching.
   Launcher tekshiruvchilar xabarlarining o‘qilgan holati uchun qo‘shimcha jadval
   yaratadi. Mavjud arizalar, qarorlar va ballar almashtirilmaydi.
5. Brauzerda Ctrl+F5 bosing.

Ariza yuborilganda birinchi bosqichdagi tegishli tekshiruvchilar xabar oladi.
Tasdiqdan so‘ng keyingi bosqich mas’ullari xabar oladi. Bir bosqichga tegishli
bir nechta faol hisob bo‘lsa, har biri o‘z kabinetida xabarni ko‘radi.

Qo‘ng‘iroqdagi son ochiq sahifada har 15 soniyada yangilanadi. Bildirishnomada
“Tekshirish uchun yangi ariza”, xodim, ariza va bosqich ko‘rinadi.
“Arizani tekshirish” tugmasi kerakli arizani ochadi.

Faqat joriy bosqichga vakolatli hisoblar xabar oladi. Kafedra/fakultet chegarasi,
faollik, rol va arizaning yuborilgan paytdagi marshruti hisobga olinadi.
O‘z arizasini tekshirish uchun xabar berilmaydi. Yopiq davrda faol tekshiruv
xabari berilmaydi. Bosqich yakunlansa, ariza qaytarilsa, rad etilsa yoki egasi
qaytarib olsa, eski navbat xabari yo‘qoladi. Qarorlar ariza tarixida saqlanadi.
Qayta yuborish va apellyatsiya yana birinchi bosqichga, tasdiqni bekor qilish
esa oxirgi bosqichga yangi xabar chiqaradi.

Bir tekshiruvchining o‘qigan holati boshqa tekshiruvchiga ta’sir qilmaydi.
Yangilanishdan oldingi amaldagi tekshiruv navbati ham xabarlarda chiqadi.
Ariza egasining avvalgi bildirishnomalari va shaxsiy hisobotlari saqlanadi.
Bu ilova ichidagi xabarlar; email yoki Telegram jo‘natilmaydi.

Eski fayllar va standart SQLite baza runtime/reviewer-notification-backup-* ga
zaxiralanadi. Parollar, dalillar va HEMIS sozlamalari almashtirilmaydi.
Docker/serverda qo‘lda ishga tushirilsa, avval tegishli runtime bilan
`php yii migrate --interactive=0` buyrug‘ini bajaring.
Windows BAT o‘rnatgichi ushbu Linux muhitida bevosita bajarilmagan.

## Tekshiruv natijalari

- Brauzerda beshta bosqichning har biridagi mas’ulga xabar kelishi, bir bosqichdagi
  ikki tekshiruvchi, boshqa kafedra/fakultetga xabar chiqmasligi, qo‘ng‘iroq sanog‘i,
  qaytarish/qayta yuborish, rad etish/apellyatsiya va yakuniy tasdiqni bekor qilish
  sinovdan o‘tdi. O‘qituvchining xabarlari saqlanishi tekshirildi.
- 10 ta alohida xizmat tekshiruvi: yangilanishdan oldingi navbat, alohida o‘qilgan
  holatlar, orqaga surilmaydigan o‘qish belgisi, o‘zini tekshirishni chiqarib tashlash,
  nofaol hisob, yopiq davr va tashkilot chegaralari o‘tdi.
- Umumiy ish jarayoni: 117/117; HTTP tekshiruvlari: 46/46.
- Native launcher yangi jadval bilan ishga tushdi; ZIP o‘rnatish, qayta o‘rnatish,
  zaxira va buzilgan faylni rad etish testlari o‘tdi.

Testlar: tests/reviewer_notifications_fixtures.php,
tests/reviewer_notifications_browser.py, tests/reviewer_notification_receipts.php,
tests/reviewer_notifications_installer.py. Faqat alohida http-test runtime’da bajaring.
