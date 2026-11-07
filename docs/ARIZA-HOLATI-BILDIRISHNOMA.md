# Ariza holati va bildirishnomalar

Mavjud KPI ilovasi uchun yangilanish paketi.

1. KPI qora oynasini yoping.
2. ZIPni alohida papkaga to‘liq oching (Extract All / Извлечь всё).
3. INSTALL-STATUS.bat ni oching va mavjud dasturdagi start-native.bat ni tanlang.
4. TAYYOR yozuvidan so‘ng aynan shu start-native.bat orqali dasturni qayta oching.
   Launcher birinchi ishga tushishda bildirishnomalarning o‘qilgan holati uchun
   yangi jadvalni avtomatik tayyorlaydi. Mavjud arizalar va ballar saqlanadi.
5. Brauzerda Ctrl+F5 bosing va o‘qituvchi hisobi bilan kiring.

## Bildirishnomalar

Qoralama saqlanishi, ariza yuborilishi, tekshiruvchi tasdig‘i, qaytarish,
rad etish, apellyatsiya va tasdiq bekor qilinishi ariza egasida ko‘rinadi.
Qo‘ng‘iroqdagi o‘qilmagan xabarlar soni ochiq sahifada har 15 soniyada
tekshiriladi. Bildirishnomalar sahifasi ochilganda ko‘rsatilgan xabarlar
o‘qilgan deb belgilanadi. Bu holat chiqib qayta kirganda ham saqlanadi.
Xabarda ariza havolasi, amal, kim bajargani, vaqt, izoh va arizaning hozirgi
holati mavjud. Bu ilova ichidagi bildirishnoma; email yoki Telegram yuborilmaydi.
Oldingi harakatlar tarixidagi xabarlar ham mavjud.

## Hisobot

Mening natijalarim / Reyting va hisobot sahifasida alohida arizalar jadvali bor:
- ariza raqami, faoliyat va ko‘rsatkich;
- baholash davri va faoliyat sanasi;
- holat va joriy tekshiruv bosqichi;
- dastlabki ball va yakuniy tasdiqlangan ball;
- arizani ochish havolasi va sahifalash.

Qoralama va tekshiruvdagi arizalar ham ko‘rinadi. Reyting jami va yuqoridagi
Excel/CSV eksportlari faqat yakuniy tasdiqlangan ballarni hisoblaydi.
O‘qituvchi faqat o‘z arizalarini ko‘radi. Tekshiruvchilar vakolati saqlanadi.

Installer eski fayllar va standart SQLite bazani runtime/application-status-backup-*
ga zaxiralaydi. Parollar, HEMIS sozlamalari va dalillar almashtirilmaydi.
Docker yoki serverda qo‘lda ishga tushirilsa, xizmatni ochishdan avval
`php yii migrate --interactive=0` buyrug‘ini tegishli runtime bilan bajaring.
Windows BAT o‘rnatgichi Linux muhitida bevosita bajarilmagan.

## Tekshiruv natijalari

- Besh bosqichli haqiqiy brauzer oqimi: qoralama, yuborish, kafedra tasdig‘i,
  fakultet qaytarishi, qayta yuborish, barcha besh bosqichdan tasdiq, bekor qilish
  va qayta tasdiqlash o‘tdi.
- Sahifani yangilamasdan 15 soniyalik qo‘ng‘iroq sanog‘i va qayta kirganda
  o‘qilgan xabarlar holati saqlanishi tekshirildi.
- O‘qituvchi shaxsiyligi testi: begona hisobot parametrlari, ariza/dalil
  manzillari, Excel/CSV va boshqa o‘qituvchi ma’lumotlari chiqmasligi o‘tdi.
- `tests/run.php`: 117/117; `tests/http_test.py`: 46/46.
- `bin/native.php --smoke`: yangi jadval bilan ishga tushish tekshiruvi o‘tdi.
- Paket o‘rnatilishi, takroriy o‘rnatish, sozlamalar/bazani saqlash va buzilgan
  faylni rad etish testlari o‘tdi. Mobil ekranlar: 390 va 320 px.

Yangi testlar: tests/application_updates_fixtures.php,
tests/application_updates_browser.py, tests/application_updates_installer.py.
Faqat alohida http-test runtime bilan ishlating.
