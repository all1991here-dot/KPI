# Administrator oynalari: kunduzgi rejim, rollar va rasmsiz ro‘yxat

Mavjud KPI ilovasi uchun yangilanish paketi.

1. KPI qora oynasini yoping.
2. ZIPni alohida papkaga to‘liq oching (Extract All / Извлечь всё).
3. INSTALL-ADMIN.bat ni oching, mavjud dasturdagi start-native.bat ni tanlang.
4. TAYYOR yozuvidan keyin shu start-native.bat orqali dasturni qayta oching.
5. Brauzerda Ctrl+F5 bosing va qayta kiring.

Yangi rejim sozlamasi birinchi ochishda kunduzgi ko‘rinishni tanlaydi.
Eski versiyadagi saqlangan tungi rejim boshlang‘ich tanlovni o‘zgartirmaydi.
Keyin quyosh/oy tugmasidan o‘zingiz tanlagan rejim shu brauzerda saqlanadi.

KPI administratori va superadministrator kabinetida raqamli fon statik:
uzluksiz chizish yo‘q. Qolgan rollarda animatsiya saqlangan, lekin nuqtalar,
kadrlar soni va canvas xotira hajmi cheklangan. Bir xil o‘lchamda qayta
hisoblash va ketma-ket rejim almashtirishda ortiqcha xotira ajratish kamaytirildi.

Foydalanuvchilar sahifasida:
- har bir rol uchun alohida bo‘lim tugmasi va son;
- barcha rollarda guruh sarlavhalari;
- ism/login, tuzilma va holat bo‘yicha qidiruv;
- bir sahifada ko‘pi bilan 50 ta xodim va sahifalash.

Xodim rasmlari foydalanuvchilar ro‘yxati, tahrirlash va profil sahifalaridan
olib tashlangan. Eski rasm manzillari ham tashqi rasm serveriga ulanmaydi.
Foydalanuvchi rasmi uchun ishlatilgan so‘rov PHP xizmatini kutdirishi mumkin edi;
yangi oynalar bu so‘rovlarni yubormaydi. HEMIS profili, dalillar va mavjud
rasm fayllari o‘chirilmaydi; ular avtomatik yuklanmaydi. Universitet logotipi
va kirish sahifasidagi mahalliy kampus tasviri saqlangan.

Oldingi fayllar va standart SQLite baza runtime/admin-performance-backup-* ga
zaxiralanadi. Login-parollar va KPI hisoblash qoidalari almashtirilmaydi.
Windows BAT o‘rnatgichi Linux muhitida bevosita bajarilmagan.

## Tekshiruv natijalari

- Mahalliy PHP’ning bitta jarayonda ishlaydigan serverida 250 ta qo‘shimcha,
  rasm manzili saqlangan xodim bilan sinovdan o‘tdi.
- 180 ta o‘qituvchini 4 sahifada takrorsiz ko‘rish, rol/ism/holat filtrlari,
  bir sahifada 50 ta yozuv va rollar soni tekshirildi.
- Eski tungi rejim sozlamasi bilan birinchi kirish kunduzgi bo‘ldi; keyingi
  qo‘lda tanlangan rejim qayta ochilganda saqlandi.
- 3840×2160 va yuqori DPI holatida 40 marta rejim almashtirish o‘tdi;
  admin canvasida uzluksiz chizish yo‘q, o‘lchami taxminan 1,5 mln piksel bilan cheklangan.
- Ro‘yxat/tahrirlash/profilda xodim rasmi so‘rovlari bo‘lmadi. Eski rasm
  endpointi tashqi xizmatga ulanmasdan 404 qaytardi.
- O‘qituvchi animatsiyasi, reduced-motion, tungi/kunduzgi almashish,
  foydalanuvchi tahriri, bloklash/ochish, bulk amallar va eski hisobotlar tekshirildi.
- Umumiy testlar: 118/118; HTTP: 46/46. ZIP qayta o‘rnatilishi, baza/sozlamalar
  saqlanishi va buzilgan faylni rad etish testlari o‘tdi.

Testlar: tests/admin_performance_fixtures.php, tests/admin_performance_browser.py,
tests/admin_performance_installer.py, tests/hemis_photo_browser.py,
tests/user_editor_browser.py, tests/galaxy_browser.py. Faqat alohida http-test runtime’da bajaring.
