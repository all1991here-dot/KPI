# FarDU — tasdiqlangan akademik dizayn

Kirish sahifasi: markazdagi ikki ustunli blok, chapda foydalanuvchi bergan FarDU binosi fotosurati va o‘ngda universitet logotipi hamda kirish formasi. Dashboard: to‘q ko‘k yon menyu, akademik ko‘k va oq kartochkalar, oltita ko‘rsatkich, jarayondagi arizalar, oylar bo‘yicha grafik, yo‘nalishlar va bo‘limlar reytingi.

## O‘rnatish

1. Yangilanish ZIP’ini o‘ng tugma → **Extract All / Извлечь всё** orqali yangi, alohida papkaga to‘liq oching. BAT’ni ZIP ichidan bosmang. `INSTALL-ACADEMIC.bat`, `INSTALL-ACADEMIC.php`, `files.sha256` va `KPI` papkasi birga turishi kerak.
2. `INSTALL-ACADEMIC.bat`ni ishga tushiring.
3. Fayl tanlash oynasida ishlayotgan KPI dasturingizning `start-native.bat` faylini tanlang.
4. “TAYYOR” xabaridan keyin brauzerda Ctrl+F5 bosing. Dastur yopiq bo‘lsa avvalgidek ishga tushiring.

O‘rnatuvchining 2-versiyasi barcha 11 faylning SHA-256 qiymatini va yozish ruxsatlarini oldindan tekshiradi. Mavjud PHP orqali ishlaydi. Xatoda aniq fayl nomi ko‘rsatiladi; nusxalash boshlangan bo‘lsa avvalgi fayllar qaytariladi. Tafsilotlar zaxira papkasidagi `install.log`ga yoziladi.

O‘rnatuvchi avvalgi fayllarni `runtime/academic-backup-...`ga saqlaydi. Baza, parollar, maxfiy sozlamalar va yuklangan hujjatlar paketga kiritilmaydi va almashtirilmaydi. Yangi migratsiya yoki qayta o‘rnatish kerak emas. Paket boshlang‘ich Docker’siz v1.1 va oldingi v2 dizayni ustidan qo‘llanishi mumkin.

Windows BAT oynasi Linux muhitida bevosita sinovdan o‘tkazilmagan. ZIP ichidagi PHP o‘rnatuvchisi alohida nusxalarda o‘rnatish, takroriy o‘rnatish, zaxira nusxasi, buzilgan/yetishmayotgan paket, yozish ruxsati va noto‘g‘ri manzil holatlari bo‘yicha tekshiriladi (`python tests/academic_installer.py`). Muqobil usul: zaxira olgach ZIP’dagi `KPI` papkasining ichidagi papkalarni mavjud dastur ildiziga ko‘chiring va faqat mos fayllarni almashtiring. Ichma-ich `KPI/KPI` papkasi hosil bo‘lmasin.

## Ma’lumotlar va animatsiyalar

- Statistik kartochkalar, arizalar va reytinglar foydalanuvchining ko‘rish huquqi doirasida hisoblanadi.
- Grafik tasdiqlangan arizalarning **faoliyat sanasi** bo‘yicha oylik ball yig‘indisini ko‘rsatadi. Bu bashorat yoki namuna raqamlar emas. Tanlangan davr juda uzun bo‘lsa, uning oxirgi 24 oyi ko‘rsatiladi; sana oralig‘i grafik sarlavhasida yoziladi. Barcha davrlar rejimida grafik joriy kalendar yilini ko‘rsatadi.
- Grafikda 3 oy, 6 oy yoki barcha oylar tanlanadi; klaviatura orqali ham nuqtalardagi qiymatlarni ko‘rish mumkin. To‘liq qiymatlar alohida jadvalda ham bor.
- Kirish bloki, menyu, kartochkalar va grafik uchun qisqa animatsiyalar qo‘shilgan. Operatsion tizimning “harakatni kamaytirish” sozlamasi yoqilsa ular o‘chadi. Ballarning o‘zi sun’iy o‘zgarmaydi.
- Asl FarDU tasvirlari lokal fayllardan olinadi; CDN kerak emas. Rasm va logotip fayli yo‘q bo‘lsa o‘rnatuvchi paket yig‘ish vositasi yakuniy ZIP yaratishni rad etadi.

## Ishlab chiqish holati

Hisoblash/ish jarayonining 49 ta, HTTP oqimining 45 ta tekshiruvi muvaffaqiyatli bajarildi: jami 94 ta. Dashboard grafik yig‘indisi, davr va xodim doirasi uchun tekshiruvlar ham kiritilgan. Asl surat va logotip foydalanuvchi yuborgan arxivdan olindi va dasturga baytma-bayt o‘zgartirmasdan qo‘shildi.

FarDU asl fotosurati va logotipining yuklanishi brauzerda tekshirildi. Fotosurat panelni to‘ldirish uchun faqat CSS orqali joylashtiriladi; asl fayl o‘zgartirilmaydi. Mobil menyuda chiqish tugmasi ham bor.
