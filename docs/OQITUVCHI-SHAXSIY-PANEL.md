# O‘qituvchining shaxsiy KPI paneli

Mavjud KPI ilovasi uchun yangilanish. To‘liq dastur emas.

1. KPI qora oynasini yoping.
2. ZIPni alohida papkaga to‘liq oching (Extract All / Извлечь всё).
3. INSTALL-PANEL.bat ni oching, mavjud dasturdagi start-native.bat ni tanlang.
4. TAYYOR yozuvidan keyin dasturni qayta oching, brauzerda Ctrl+F5 bosing.
5. O‘qituvchi hisobi bilan kiring.

O‘qituvchida:
- Shaxsiy KPI paneli, o‘z ballari, arizalari, grafiklari va so‘nggi faoliyatlari.
- Universitet/bo‘limlar reytingi ko‘rsatilmaydi.
- Mening mezonlarim faqat hisobga biriktirilgan toifani ko‘rsatadi.
- Mening natijalarim faqat o‘z tasdiqlangan natijalarini ko‘rsatadi.
- Umumiy toifa, tashkilot va hisobot kesimi tanlovlari yashiriladi.
- Hisobot manzilidagi begona employee/org/category/group parametrlari shaxsiy
  ko‘rinishni kengaytirmaydi. Excel va CSV ham faqat o‘z natijalarini chiqaradi.
- Kafedra, fakultet, ekspert va administrator rollari avvalgi vakolati bilan ishlaydi.

Yangilanish bazaga, parollarga, dalillarga va HEMIS sozlamalariga tegmaydi.
Oldingi fayllar va standart SQLite baza runtime/teacher-workspace-backup-* ga zaxiralanadi.

Tekshiruv: PHP 8.3, alohida SQLite test bazasi va Chromium’da ikki o‘qituvchi
hamda superadministrator hisoblari bilan o‘z ballari, statistika, grafik,
arizalar, bildirishnomalar, toifa, ruxsatsiz ariza/dalil manzillari,
beshta qalbakilashtirilgan hisobot kesimi, CSV/XLSX va 390/320 px ekranlar
tekshirildi. Windows BAT o‘rnatgichi Linux muhitida bevosita bajarilmagan.

Paketni qayta yaratish: python bin/package-teacher-workspace.py
Shaxsiy panel testi: tests/teacher_workspace_fixtures.php bilan yangi alohida
http-test runtime tayyorlab, 8001-portda ishga tushiring; keyin
python tests/teacher_workspace_browser.py buyrug‘ini bajaring.

Qo‘shimcha tekshiruvlar:
- Yangi alohida runtime’da `tests/http_test.py`: 45/45 HTTP tekshiruvi o‘tdi
  (kirish, parol, dalil yuklash, uch bosqichli tasdiq, ball, hisobot, eksport,
  begona ariza/dalilga kirish, CSRF va maxfiy yo‘llar).
- `python tests/teacher_workspace_installer.py`: o‘rnatish, qayta o‘rnatish,
  eski fayllar va baza zaxirasi, maxfiy sozlamalarni saqlash va buzilgan
  payloadni rad etish tekshiruvlari o‘tdi.
- O‘zgargan PHP fayllarida sintaksis xatolari topilmadi.
