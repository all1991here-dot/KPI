# KPI · Universitet

Yii2 asosidagi o‘zbekcha KPI tizimi. Yuborilgan mezonlar hujjati va 1C manbasi asosida **3 toifa, 4 faoliyat yo‘nalishi, 46 mezon va 139 ko‘rsatkich** kiritilgan.

## Mavjud dastur uchun yangi dizayn

Ishlayotgan v1.1 dasturini qayta o‘rnatmasdan yangilash uchun `releases/KPI-Design-v2-update.zip`dan foydalaning. Uni alohida papkaga oching, `INSTALL-DESIGN.bat`ni bosing va mavjud dasturdagi `start-native.bat` faylini tanlang. Keyin brauzerda Ctrl+F5 bosing. Eski ko‘rinishning nusxasi avtomatik saqlanadi; baza, parollar va dalillar almashtirilmaydi. Batafsil yo‘riqnoma va qo‘lda ko‘chirish usuli: [Dizayn v2](docs/DIZAYN-V2.md).

Yangi interfeys kunduzgi/tungi rejim, mobil menyu, haqiqiy natijalar asosidagi grafiklar va yangilangan forma/jadvallarni o‘z ichiga oladi. Qo‘shimcha CSS, JavaScript va SVG ikonlar lokal saqlanadi; CDN kerak emas.

## Windows’da Docker’siz foydalanish

1. `start-native.bat`ni oching. U loyiha ichidagi `php/php.exe`, `C:\xampp\php\php.exe`, `C:\php\php.exe` yoki PATH’dagi PHP’ni topadi.
2. PHP topilmasa, https://windows.php.net/download/ sahifasidan PHP **8.3 yoki yangiroq, x64 ZIP** paketini oling. ZIP ichidagi barcha fayllarni, jumladan `ext/`ni loyiha ichidagi `php/` papkasiga oching. Natija: `KPI/php/php.exe`.
3. PHP’ni ochishda Windows DLL/VCRUNTIME xatosi bersa, rasmiy Microsoft Visual C++ x64 runtime’ni o‘rnating: https://aka.ms/vs/17/release/vc_redist.x64.exe.
4. `start-native.bat`ni qayta oching. Launcher faqat loyiha uchun `runtime/php-native.ini` yaratadi, kerakli kengaytmalarni yoqadi, bazani tayyorlaydi va brauzerni ochadi. Global `php.ini` o‘zgarmaydi.
5. Birinchi login/parol `runtime/initial-admin.txt`da. Kirgach parolni yangilang. Qora oynani ochiq qoldiring; uni yopish yoki Ctrl+C xizmatni to‘xtatadi.

Standart manzil `http://127.0.0.1:8000`; port band bo‘lsa 8001–8010 orasidagi bo‘sh port tanlanadi va aniq manzil oynada yoziladi. Kompyuter uchun bu usulda **Docker, Composer va MySQL kerak emas**; PHP va paket ichidagi vendor yetarli. Bu mahalliy PHP ishlab chiqish serveri, universitetga ommaviy joylash usuli emas.

Windows `.bat` qismi ushbu Linux muhitida bevosita bajarilmagan. PHP orqali boshlash, baza yaratish va haqiqiy login sahifasi mahalliy smoke-check orqali tekshiriladi. Windows uchun PHP paketining o‘zi ZIP’ga qo‘shilmagan.

## Windows’da foydalanish

1. Docker Desktop’ni o‘rnating va ishga tushiring. Linux containers rejimi kerak.
2. Yetkazilgan ZIP’ni yozish mumkin bo‘lgan oddiy papkaga oching.
3. **`start.bat`** faylini ikki marta bosing. Birinchi safar PHP 8.3 tasviri yuklanadi; Internet kerak.
4. `http://localhost:8000` ochiladi. Birinchi login/parol **`runtime/initial-admin.txt`** faylida yaratiladi va Notepad’da ochiladi.
5. Birinchi kirishda parolni yangilang, keyin `initial-admin.txt` faylini o‘chiring.

Yetkazilgan ZIP’da `vendor/` ham bor; alohida PHP/Composer kerak emas. Git nusxasida launcher bog‘liqliklarni ham o‘rnatadi. Windows launcheri bu Linux muhitida bajarilmagan; undagi Compose buyruqlari Linux’da tekshirilgan.

## Linux / macOS

Docker va Compose o‘rnatilgach loyiha papkasida:

```sh
sh start.sh
```

`http://localhost:8000` ni oching. Kirish ma’lumotlarini `runtime/initial-admin.txt`dan o‘qing. Launcher UID/GID ni joriy foydalanuvchiga moslaydi. Qo‘lda ishlatsangiz shu qiymatlarni saqlang:

```sh
export KPI_UID="$(id -u)"
export KPI_GID="$(id -g)"
docker compose up -d
docker compose stop
```

`setup/init` qayta bajarilganda mavjud baza va parollar o‘chirilmaydi. Ma’lumotlar konteynerda emas, loyiha ichidagi **`runtime/`** papkasida saqlanadi; uni o‘chirmang.

## Administratorning dastlabki sozlashlari

1. **Universitet tuzilmasi:** universitet nomini tahrirlang, fakultet va kafedralarni yarating.
2. **Foydalanuvchilar:** hisob, rol, bo‘lim va KPI toifasini belgilang. Parol 12–128 belgili; yangi/tiklangan parol birinchi kirishda almashtiriladi. Kafedra mudiriga kafedra, fakultet mas’uliga fakultet tanlanadi.
3. **Baholash davrlari:** boshlanish va tugash sanalari bilan davr yarating. Kalendar yili avtomatik taxmin qilinmaydi.
4. **Hisoblash qoidalari:** yangi davr uchun 139 ta qoida yaratiladi. Hujjatda talqini ochiq bo‘lgan **96 ko‘rsatkich dastlab yopiq**. Tasdiqlangan buyruq yoki qaror asosini kiriting, hisoblashni sozlang va oching.
5. **Tasdiqlash bosqichlari:** kafedra → fakultet/ekspert → administrator andozasini tashkilot tartibiga moslang. Har bosqich uchun tekshiruvchi hisob yarating. Xodim o‘z arizasini o‘zi tasdiqlamaydi.

Rollar: o‘qituvchi, kafedra mudiri, fakultet mas’uli, ekspert, KPI administratori, superadministrator, rahbariyat. Kafedra/fakultet rollari ma’lumotni o‘z doirasida ko‘radi.

## Xodim va tekshiruvchi

- **Yangi faoliyat:** davr, ko‘rsatkich, sana, miqdor va dalillarni kiriting. PDF, PNG, JPG/JPEG; har fayl 10 MiB gacha, jami 10 ta.
- Qoralamani saqlang, dalillarni tekshiring, **Tekshiruvga yuborish**ni bosing. Qoralama va qaytarilgan ariza tahrirlanadi.
- Tekshiruvchi **Tekshiruv navbati** orqali dalilni ko‘radi, izoh bilan tasdiqlaydi, qaytaradi yoki rad etadi. Ekspert bahosini tekshiruvchi kiritadi.
- Faqat yakuniy tasdiq reytingga ball qo‘shadi. Dastlabki ball taxminiy; yakuniy bosqichda amaldagi tasdiqlangan limitlar bilan qayta hisoblanadi.
- Qaytarilgan/rad etilgan arizaga egasi sabab bilan apellyatsiya yuboradi.
- Yakuniy rol tasdiqni sabab bilan bekor qiladi. Ball teskari yozuv bilan qaytariladi; 0 balli qaror ham bekor qilinadi.
- Yopiq davrda ariza va qarorlarni o‘zgartirish to‘xtaydi, hisobotlar saqlanadi.

Qoida va marshrut ariza yuborilganda nusxalanadi. Keyingi tahrir oldingi arizalarni o‘zgartirmaydi. Yangi qoida qo‘llanishi kerak bo‘lsa arizani qaytarib, qayta yuborish kerak.

## Hisoblash sozlamalari

- Birlik × miqdor, bir martalik ball, davr bo‘yicha limit va ekspert/sifat bahosi qo‘llanadi. Kasr ballar hisoblanadi.
- **Grant:** administrator `100 MHTEKM summasi`, birlik balli va proporsional yoki to‘liq blok usulini qaror asosi bilan belgilaydi. Xodim `Formula qiymati`ga o‘z ulushining so‘mdagi summasini, `Miqdor`ga 1 kiritadi.
- **Xirsh:** administrator to‘liq indeks yoki yillik o‘sish va qaysi baza qiymati olinishi haqida qaror yozadi. Xodim shu butun qiymatni kiritadi; miqdor 1. Manbadagi birlik stavka 1 ball.
- Til yoki sifat darajalaridan bittasi olinadigan bo‘lsa, qoidalarga bir xil **o‘zaro istisno guruh kodi** bering. Guruhda bir davr uchun bitta tasdiq bo‘ladi. Almashtirishda avvalgi qaror bekor qilinadi.
- Bir xil fayl xeshi bir xodim–davr–ko‘rsatkich doirasidagi faol arizalarda takrorlanmaydi. Boshqa ko‘rsatkichlarda bir dalil ishlatilishi alohida tashkiliy tekshiruvga bog‘liq.
- Limitlar faqat amaldagi tasdiqlardan olinadi. Parallel qarorlarda davr yozuvi bloklanadi. Tasdiq bekor bo‘lib limit bo‘shasa boshqa arizalarning balli avtomatik oshmaydi; ular alohida qayta ko‘rib chiqiladi.

Hammualliflik ulushi, stipendiyalar ro‘yxati va toifa almashishi kabi qoidalar tasdiqlangan tashkilot siyosati asosida belgilanadi. Ish haqiga ustama formulasi manbada yo‘q; dastur ballni o‘zboshimchalik bilan ish haqiga aylantirmaydi.

## Hisobotlar

Xodim, kafedra/bo‘lim, fakultet va universitet kesimlari; davr, toifa, tashkilot filtrlari. Excel `.xlsx` va CSV yuklash mavjud. **Chop etish / PDF** tugmasi brauzerning PDF sifatida saqlash oynasidan foydalanadi; alohida server PDF generatori yo‘q.

## HEMIS

Administrator HEMIS sahifasida HTTPS domeni va API kalitini saqlaydi, ulanishni tekshiradi, avval tuzilmani, keyin xodimlarni import qiladi. API: `/rest/v1/data/department-list`, `/rest/v1/data/employee-list`. Tuzilma turlari 12=fakultet, 13=kafedra; noma’lum/bog‘lanmagan yozuvlar o‘tkaziladi.

Yangi xodimlar **nofaol** yaratiladi. Administrator toifa, vaqtinchalik parol va faollikni tekshiradi. Qayta import rol, parol, toifa yoki faollikni almashtirmaydi. Pasport/JSHIR olinmaydi. API kaliti `runtime/hemis.json`da cookie kaliti bilan shifrlanadi.

Import sun’iy ma’lumotlar bilan tekshirilgan. **Haqiqiy universitet HEMIS ulanishi tekshirilmagan**; domen va kalit kerak. Domen server/proksi tarmoq ruxsatlarida ochiq bo‘lishi kerak. Kalitni chatga yoki Git’ga yubormang. Cloud proksi CA ishlatilsa `KPI_CA_BUNDLE` orqali ishonchli CA to‘plami yo‘lini bering.

## Zaxira nusxasi

SQLite baza `runtime/kpi.sqlite`, dalillar `runtime/evidence/`, maxfiy sozlamalar `config/local.php` va `runtime/hemis.json`da. Zaxira olishda xizmatni to‘xtating:

```sh
docker compose stop
docker compose run --rm --no-deps --user 1000:1000 -e KPI_BACKUP_MAINTENANCE=1 --entrypoint php app yii backup/create
docker compose up -d
```

Linux’da `--user` qiymatini UID:GID bilan moslang. `backups/`dagi ZIP maxfiy sozlamalarni ham o‘z ichiga oladi; xavfsiz joyda saqlang. Tiklash: xizmatni to‘xtating, joriy holat zaxirasini oling, ZIP tarkibini loyiha ildiziga tiklang, xizmatni qayta yoqing. Cookie kalitini saqlang — u HEMIS kalitini ochish uchun ham kerak.

## Administrator parolini tiklash

Terminalda `KPI_ADMIN_PASSWORD` muhit o‘zgaruvchisiga yangi 12+ belgili parolni xavfsiz kiriting, keyin:

```sh
docker compose run --rm --no-deps --user 1000:1000 -e KPI_ADMIN_PASSWORD --entrypoint php app yii setup/reset-password admin
```

Parolni buyruqqa literal yozmang. Keyingi kirishda uni almashtirish talab qilinadi.

## Docker’siz ishlab chiqish

PHP 8.3, Composer 2; `pdo_sqlite`, `mbstring`, `intl`, `curl`, `fileinfo`, `zip`, `dom`, `xml`, `simplexml`, `ctype`, `openssl` kerak.

```sh
composer install --no-dev --prefer-dist --no-interaction --no-scripts
php yii setup/init
php -S 127.0.0.1:8000 -t web web/router.php
```

Lock’dagi GitHub paket arxivlari ayni commitlarning rasmiy `codeload.github.com` manzillariga moslangan, TLS tekshiruvi saqlangan. Lock’ni yangilash uchun `composer update` ishlatmang. Eski PHP 7.4 Docker tasviri bu paketlarga mos emas.

## Universitet serveriga joylash

Launcher faqat shaxsiy kompyuterning loopback manziliga ulanadi. Barcha xodimlarga ochish uchun server/domen va HTTPS kerak; reverse proxy yoki Apache/Nginx’da **faqat `web/` papkasini web root** qiling. `config/`, `runtime/`, `vendor/`, `data/` Internetga ochilmasin. PHP ishlab chiqish serveridan ommaviy xizmat sifatida foydalanmang.

Ushbu topshirishda tashqi hosting, domen yoki HTTPS joylashuvi bajarilmagan. Haqiqiy tashkilot tuzilmasi, hisoblar va normativ qarorlarni administrator kiritadi.

MySQL sozlamasi `config/local.example.php`da bor; migratsiyalar moslashtirilgan. Hozir **SQLite amalda tekshirilgan**, MySQL muhiti alohida tekshiruv talab qiladi.

## Testlar

```sh
docker compose run --rm --no-deps --user 1000:1000 --entrypoint php app tests/run.php
```

Testlar alohida tasodifiy bazada ishlaydi. To‘liq HTTP tekshiruvlari: Python 3 va bo‘sh 8001-port bilan `sh bin/test`. Test hisoblari faqat alohida test runtime’da yaratiladi. Tekshiruv dalillari `docs/TEKSHIRUV.md`da.

`docs/kpi-talablar.md` va `docs/1c-manba-tahlili.md` dastlabki tahlil holatini saqlaydi; joriy foydalanish uchun ushbu README’dan foydalaning.
