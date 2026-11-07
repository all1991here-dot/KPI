# Tasdiqlangan ariza va superadministrator huquqi

Mavjud KPI ilovasi uchun yangilanish paketi.

1. KPI qora oynasini yoping.
2. ZIPni alohida papkaga to‘liq oching (Extract All / Извлечь всё).
3. INSTALL-DECISION.bat ni oching va mavjud dasturdagi start-native.bat ni tanlang.
4. TAYYOR yozuvidan so‘ng shu start-native.bat orqali dasturni qayta oching.
   Launcher qo‘shimcha migratsiyani avtomatik bajaradi.
5. Brauzerda Ctrl+F5 bosing.

Tasdiqlangan arizani faqat superadministrator bekor qiladi. Oxirgi bosqich
oddiy administrator yoki boshqa tekshiruvchiga tegishli bo‘lsa ham, unga
bekor qilish huquqi berilmaydi. Superadministrator marshrutning oxirgi
roliga kiritilmagan bo‘lsa ham bekor qilishi mumkin. Sabab talab qilinadi;
yopiq davr va o‘z arizasiga qaror berish cheklovlari saqlanadi.

Oddiy administrator tasdiqlangan arizani tahrirlash yoki dalilini olib
tashlash orqali ham tasdiqni bekor qila olmaydi. Tasdiqlanmagan arizalar
uchun mavjud tahrirlash vakolatlari saqlanadi.

Superadministratorning amallari hujjatning foydalanuvchiga ko‘rinadigan
“Harakatlar tarixi” bo‘limida ko‘rsatilmaydi. Ichki tizim hodisalari, ball
yozuvlari va audit jurnalida qayd saqlanadi. Xodimga holat o‘zgarishi haqidagi
bildirishnoma kelishda davom etadi, keyingi tekshiruv navbati ham ishlaydi.

Yangi amallarning ko‘rinishi bajarilgan paytdagi rol bilan belgilanadi.
Oldingi yozuvlarda rol nusxasi bo‘lmagani sababli migratsiya hozir
superadministrator bo‘lgan hisoblarning eski yozuvlarini yashiradi.
Eski yozuvlar va hujjatlar o‘chirilmaydi.

Oldingi fayllar va standart SQLite baza runtime/superadmin-decision-backup-*
ga zaxiralanadi. Parollar, dalillar va HEMIS sozlamalari almashtirilmaydi.
Docker/serverda qo‘lda ishga tushirilsa, avval tegishli runtime bilan
`php yii migrate --interactive=0` buyrug‘ini bajaring.
Windows BAT o‘rnatgichi ushbu Linux muhitida bevosita bajarilmagan.

## Tekshiruv natijalari

- 16 ta maxsus tekshiruv: oddiy yakuniy administratorning bekor qilish, tahrirlash
  va dalilni o‘chirish orqali cheklovni aylanib o‘tishi rad etildi; superadministrator
  marshrutdan tashqarida bekor qildi; ball bir marta qaytarildi va qayta tasdiqlandi;
  hujjat tarixi yashirildi, ichki audit saqlandi; eski tarixli baza migratsiyasi tekshirildi.
- Chromium’da haqiqiy UI va CSRF bilan bevosita yuborilgan so‘rovlar, hujjatdagi tarix,
  ichki audit, xodim bildirishnomasi va qayta tekshiruv navbati tekshirildi.
- Umumiy ish jarayoni: 118/118; HTTP tekshiruvlari: 46/46.
- Native ishga tushish, yangi migratsiya, ZIP o‘rnatilishi, takroriy o‘rnatish,
  zaxira, sozlamalarni saqlash va buzilgan faylni rad etish testlari o‘tdi.

Testlar: tests/decision_permissions_fixtures.php, tests/decision_permissions.php,
tests/decision_permissions_browser.py, tests/superadmin_decision_installer.py.
Faqat alohida http-test runtime’da bajaring.
