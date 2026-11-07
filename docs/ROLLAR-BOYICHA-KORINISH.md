# KPI ma’lumotlarining rol bo‘yicha ko‘rinishi

Mavjud KPI ilovasi uchun yangilanish paketi.

1. KPI qora oynasini yoping.
2. ZIPni alohida papkaga to‘liq oching (Extract All / Извлечь всё).
3. INSTALL-ROLES.bat ni oching va mavjud dasturdagi start-native.bat ni tanlang.
4. TAYYOR yozuvidan so‘ng shu start-native.bat orqali dasturni qayta oching.
5. Brauzerda Ctrl+F5 bosing va tegishli rol bilan qayta kiring.

| Rol | KPI ma’lumotlari doirasi |
|---|---|
| O‘qituvchi | Faqat o‘z arizalari va natijalari |
| Kafedra mudiri | O‘ziniki va o‘z kafedrasi hamda quyi tuzilmalariniki |
| Fakultet mas’uli | O‘ziniki va o‘z fakulteti hamda quyi tuzilmalariniki |
| Ekspert | O‘ziniki va o‘zi tasdiqlagan arizalar |
| KPI administratori | Universitet bo‘yicha barcha KPI ma’lumotlari |
| Superadministrator | Universitet bo‘yicha barcha KPI ma’lumotlari |
| Rahbariyat | Universitet bo‘yicha barcha KPI ma’lumotlari |

Shu doira panel kartalari, jami ball, grafiklar, yo‘nalishlar, arizalar ro‘yxati,
hisobot va Excel/CSV eksportlariga qo‘llanadi. Kafedra/fakultet doirasidagi
qoralamalar ham ko‘rinadi, ularni tahrirlash vakolati esa alohida saqlanadi.
Kafedra/fakultet noto‘g‘ri biriktirilgan hisob faqat o‘z ma’lumotini ko‘radi.

Ekspert bir xodimning bitta arizasini tasdiqlasa, shu xodimning boshqa
arizalari avtomatik ochilmaydi. Bir arizani bir necha marta ko‘rib chiqqan
bo‘lsa ham KPI yig‘indisida ariza bir marta hisoblanadi.

Ekspert hali tasdiqlamagan yangi arizalar uning umumiy KPI paneliga
qo‘shilmaydi; joriy bosqichi ekspertga tegishli bo‘lsa “Tekshiruv navbati”
va bildirishnomalarda chiqadi. Ekspert uni ochib, dalilni ko‘rib qaror beradi.
Tasdiqlagandan keyin ariza ekspertning KPI doirasiga ham kiradi.

Umumiy universitet hisobot kesimi faqat uchta global rolga ko‘rsatiladi.
Boshqa rollarda URL parametrlarini o‘zgartirish ruxsat doirasini kengaytirmaydi.
Tasdiqlangan arizani o‘zgartirish/bekor qilish huquqi faqat superadministratorda qoladi.

Oldingi fayllar va standart SQLite baza runtime/role-scope-backup-* ga zaxiralanadi.
Parollar, dalillar va HEMIS sozlamalari almashtirilmaydi. Docker/serverda
qo‘lda ishga tushirilsa, avval tegishli runtime bilan
`php yii migrate --interactive=0` buyrug‘ini bajaring.
Windows BAT o‘rnatgichi ushbu Linux muhitida bevosita bajarilmagan.

## Tekshiruv natijalari

- 20 ta maxsus tekshiruv: yettita rolning arizalar soni va jami balli,
  ekspertning yozuv bo‘yicha chegarasi, boshqa arizalarni ocholmasligi,
  yangi tekshiruv navbati/bildirishnomasi va tasdiqdan keyingi doirasi o‘tdi.
- Chromium’da yettita hisob bilan panel, grafik, arizalar to‘plami, hisobot,
  CSV, bevosita manzil va filtrlarni o‘zgartirish tekshirildi.
- Ekspert tasdiqlamasdan qaytarganda navbatga qaytishi, xodim qayta yuborishi,
  so‘ng ekspert tasdiqlashi va ballning panelga kirishi tekshirildi.
- Mobil ekranlar: 390 va 320 px.
- Umumiy ish jarayoni: 118/118; HTTP tekshiruvlari: 46/46.
- ZIP o‘rnatilishi, takroriy o‘rnatish, zaxira va buzilgan faylni rad etish o‘tdi.

Testlar: tests/role_scope_fixtures.php, tests/role_scope.php,
tests/role_scope_browser.py, tests/role_scope_installer.py.
Faqat alohida http-test runtime bilan bajaring.
