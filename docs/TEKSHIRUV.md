# KPI v1.0 — topshirish tekshiruvlari

2026-11-05. PHP 8.3 / Apache, Yii 2.0.55, SQLite. Bu natijalar joriy Linux cloud muhitida olingan.

## Bajarilgan

- `sh bin/test`: **46 ta hisoblash/ish jarayoni tekshiruvi** va **45 ta HTTP tekshiruvi** muvaffaqiyatli yakunlandi (jami 91).
- PHP fayllari sintaksisi tekshirildi; xato topilmadi.
- Katalog: 139 ko‘rsatkich, qayta importda takrorlanmaydi. 96 ta normativ qaror bayrog‘i saqlangan.
- Uch bosqichli tasdiq, bosqichni o‘tkazib yuborishga qarshilik, o‘zini tasdiqlashni rad etish, kafedra chegarasi va boshqa xodim daliliga kirishni rad etish tekshirildi.
- PDF fayl haqiqiy HTTP multipart orqali yuklandi va asl baytlari bilan qayta yuklab olindi. PDF deb nomlangan HTML fayl rad etildi.
- Limit, kasr ball, 0 balli tasdiq, bekor qilish/teskari ball yozuvi, apellyatsiya, yopiq davr, noto‘g‘ri sana/toifa, bir xil fayl xeshi va o‘zaro istisno guruhlari tekshirildi.
- Grantning proporsional va to‘liq blok hisoblari, Xirsh butun son qiymati, ekspert chegarasi va katta ballning texnik chegarasi tekshirildi.
- Birinchi kirishdagi parolni yangilash, undan keyin sessiyani saqlash, administratorning foydalanuvchi yaratishi va qoida tasdiqlashi HTTP orqali tekshirildi.
- Xodim, kafedra, fakultet va universitet hisobot sahifalari ishladi. `.xlsx` ZIP tuzilmasi va undagi natija tekshirildi; CSV ham tasdiqlangan ballni qaytardi.
- CSRF’siz POST va o‘zgartiruvchi GET so‘rovlari rad etildi. Web root orqali maxfiy konfiguratsiya, SQLite baza, katalog va vendor fayllari berilmadi.
- HEMIS tashkilot/xodim importi sintetik fixture’larda, qayta yuklash va nofaol yangi hisob holati bilan tekshirildi.
- Chromium’da login va boshqaruv paneli ko‘rildi; JavaScript xatosi bo‘lmadi. Mobil tekshiruvda 390 px viewport va hujjat eni teng bo‘ldi. Skrinshotlar `docs/screenshots/`da; undagi hisoblar test ma’lumotlari.
- `setup/init` takroran bajarildi: mavjud hisob saqlandi, migratsiya qayta qo‘llanmadi.
- Xizmat to‘xtatilgan holda `backup/create` bajarildi; ZIP yaxlitligi tekshirildi. Keyin aynan yetkazilgan `start.sh` orqali Compose xizmati qayta ishga tushdi va login sahifasi HTTP 200 qaytardi.
- Asosiy ilova bazasida test arizalari yoki test hisoblari yo‘q: 1 administrator, 0 ariza, 139 ko‘rsatkich. SQLite `integrity_check`: `ok`.
- Yetkaziladigan ZIP alohida toza papkaga ochildi; o‘sha nusxadan bazani yaratish va takroriy setup muvaffaqiyatli bajarildi. Uning web xizmatida birinchi administrator kirishi va majburiy parol yangilash sahifasi tekshirildi. Baza yaxlitligi `ok`, 139 ko‘rsatkich va 1 administrator mavjud.
- Launcher brauzerni ochishdan avval login sahifasi tayyorligini PHP health-check bilan tekshiradi; ushbu tekshiruv ishlayotgan Compose xizmatida bajarildi.

## Amalda tekshirilmagan / tashkilot sozlashi kerak

- Windows `start.bat` bevosita Windows’da ishga tushirilmadi; undagi Docker/Compose buyruqlari Linux’da bajarildi.
- Haqiqiy HEMIS domeni va API kaliti bilan ulanish tekshirilmagan.
- MySQL muhiti, tashqi hosting, domen, HTTPS reverse proxy va ko‘p foydalanuvchili yuklama tekshiruvi bajarilmagan. Mahalliy foydalanish uchun SQLite tanlangan.
- Baholash davri, xodimlar va 96 ta ochiq talqin bo‘yicha normativ qarorlar administrator tomonidan kiritiladi. Bu qarorlar dastur tomonidan o‘ylab topilmagan.
- PDF brauzerning chop etish / PDF sifatida saqlash oynasi orqali olinadi.

Yetkazilgan ZIP bazasiz va maxfiy sozlamalarsiz beriladi. Birinchi ishga tushirish yangi cookie kaliti, baza va tasodifiy administrator parolini yaratadi.

## v1.1 — Docker’siz mahalliy launcher

`start-native.bat` va `bin/native.php` qo‘shildi. PHP orqali alohida toza runtime’da baza yaratish, 139 ko‘rsatkichni import qilish, PHP built-in serverni ochish va haqiqiy login sahifasini olish muvaffaqiyatli bajarildi (`bin/native.php --smoke`). Native launcher Docker buyruqlarini ishlatmaydi; uni tekshirish uchun ushbu cloud muhitidagi PHP 8.3 runtime’dan foydalanildi. Windows BAT fayli va Windows DLL kengaytmalarini yuklash qismi Windows’da bevosita sinovdan o‘tkazilmagan. Windows PHP distributivi paket ichiga qo‘shilmagan.
