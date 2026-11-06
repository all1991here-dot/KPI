# Fakultet va kafedralar, alohida rekvizitlar oynasi

## Ornatish
1. KPI qora oynasini Ctrl+C bilan yoping.
2. ZIPni yangi papkaga toliq oching (Extract All / Извлечь всё).
3. INSTALL-STRUCTURE-EDITOR.bat ni bosing, mavjud KPI papkasidagi start-native.bat ni tanlang.
4. TAYYOR chiqqach start-native.bat ni qayta oching. Baza migratsiyasi tugashini kuting, brauzerda Ctrl+F5 bosing.

Paket avvalgi tuzilma/superadmin/HEMIS tuzatishlarini ham oz ichiga oladi. API kaliti, parollar va hujjatlar almashtirilmaydi. Eski fayllar va standart SQLite bazasi runtime/structure-editor-backup-... da saqlanadi. Yangi migratsiya faqat qoshimcha org rekvizitlari va blocked_at ustunini qoshadi. Kirish dizayni va animatsiyasi ozgarmaydi.

## Royxat
Universitetlar, fakultetlar va kafedralar uchta alohida bolimda. Fakultet jadvalida universitet; kafedra jadvalida kafedra, fakultet va universitet alohida ustunlarda korsatiladi. Universitet/fakultet boyicha filtr va fakultetning kafedralariga otish havolasi bor.

Royxatda faqat Tahrirlash tugmasi qoladi. Fakultet biriktirilmagan eski yozuv taxminan boglanmaydi: aniq ogohlantiruvchi matn chiqadi va tahrirlashda togri fakultet tanlanadi.

## Rekvizitlar oynasi
Tahrirlash alohida sahifani ochadi; aralash royxat yoki uning ustidagi umumiy forma ochilmaydi. Toʻliq/qisqa nom, kod, turi, universitet/fakultet, HEMIS ID, rahbar F.I.Sh., telefon, email, manzil, izoh, holat va xodim soni mavjud. Kafedra oynasida universitet va fakultet alohida tanlanadi. Mavjud bolim turi almashtirilmaydi.

Pastdagi tugmalar:
- Saqlash: kiritilgan rekvizitlarni tekshiradi va saqlaydi.
- Bloklash / Blokdan chiqarish: bolim faoliyat holatini almashtiradi.
- Ochirish: bolimni ochiradi; xodim yoki quyi bolim mavjud bolsa, sababini korsatadi va ochirmaydi.

Bloklash/ochirish alohida amal: ular rekvizitlarda hali saqlanmagan ozgarishlarni saqlamaydi. Rekvizitlar uchun avval Saqlash bosing.

## Bloklash va vakolat
Yaratish, ochirish va bloklash faqat superadministratorda. Administrator mavjud rekvizitlarni tahrirlaydi; blokni boshqa yuqori bolimga kochirish orqali chetlab otolmaydi. HEMIS ID boglanishini faqat superadministrator ozgartiradi.

Universitet/fakultet bloklansa, quyi bolimlari ham samarali bloklangan hisoblanadi. Yangi xodim/bolim biriktirish, yangi faoliyat yaratish va tekshiruvga yuborish toxtaydi. Oldingi hujjatlar va tizimga kirish saqlanadi. Oldingi hujjatlarni administrator tahrirlashi mumkin. HEMIS ham blokni chetlab otmaydi: bloklangan fakultet ostiga yangi kafedra yaratmaydi, yangi xodimni shu bolimga boglamaydi va sababni import hisobotida korsatadi.

Yuqori bolim bloki davom etsa, kafedraning oz blokini olish yetarli emas: sahifada qaysi yuqori bolim bloklanganligi yoziladi. Holatni tiklash uchun tegishli yuqori bolimni blokdan chiqaring.

## Tekshiruv
Izolyatsiyalangan bazada hisoblash/huquqlar/bloklash/HEMIS testlari, 45 HTTP oqimi, brauzerda alohida royxatlar, rekvizitlarni saqlash, ota fakultetni almashtirish, bloklash/blokdan chiqarish, boglangan bolimni ochirishdan himoya, bosh bolimni ochirish, 390/320 piksel ekran va ruxsatsiz bloklash POST tekshirildi. Migratsiya eski hisoblar, parollar va API sozlamalarini saqlaydi. Windows fayl tanlash oynasi Linuxda bevosita tekshirilmagan.
