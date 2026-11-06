# Foydalanuvchi oynalari va ommaviy ochirish

## Ornatish
1. KPI ishlayotgan qora oynani Ctrl+C bilan yoping.
2. ZIPni yangi papkaga toliq oching (Extract All / Извлечь всё).
3. INSTALL-USER-EDITOR.bat ni bosing va mavjud KPI papkasidagi start-native.bat ni tanlang.
4. TAYYOR chiqqach start-native.bat ni qayta oching, migratsiyalar tekshiruvi tugashini kuting. Brauzerda Ctrl+F5 bosing.

API kaliti, mavjud parollar va hujjatlar yangilanishda almashtirilmaydi. Oldingi fayllar va standart SQLite bazasi runtime/user-editor-backup-... da saqlanadi. Bu versiya yangi baza ustuni talab qilmaydi; oldingi tuzilma/profil migratsiyalari paketga kiritilgan. Ornatish hech qanday ommaviy ochirishni bajarmaydi.

## Foydalanuvchilar
Royxat alohida, ism/login, universitet, fakultet, kafedra va holat ustunlari bor. Ism/login, tuzilma va holat boyicha filtr ishlaydi. Royxatda faqat Tahrirlash tugmasi qolgan.

Tahrirlash alohida sahifani ochadi: rasm, profil, HEMIS ID, universitet/fakultet/kafedra, ilmiy daraja/unvon, lavozim, ish turi, stavka, telefon, email, rol, toifa va vaqtinchalik parol. Pastda Saqlash, Bloklash/Blokdan chiqarish va Ochirish bor. Parol bosh qolsa avvalgi parol qoladi. Saqlash hisobning faollik holatini yashirincha ozgartirmaydi; buning uchun bloklash/faollashtirish tugmasi bor.

Bloklash hisobning kirishini toxtatadi va eski sessiyasini bekor qiladi. Blokdan chiqarish kirishni qayta yoqadi. Rol biriktirilmagan hisob baribir faqat profiliga kira oladi. Oz hisobini bloklash/ochirish mumkin emas. Ochirish faqat superadministratorda; oddiy administrator odatdagi xodim hisobini boshqaradi, administrator hisobini boshqarmaydi. Rollarni faqat superadministrator belgilaydi.

## Barchasini ochirish
Foydalanuvchilar va Universitet tuzilmasi oynalarining tepasida faqat superadministratorga korinadi. Server ham GET/POST sorovlaridagi vakolatni tekshiradi. Tugma darhol ochirmaydi: tasdiqlash sahifasida sonlar va amal qamrovi korsatiladi, OCHIRISH deb yozib tasdiqlash kerak. Royxat shu orada ozgarsa eski tasdiq rad etiladi va yangi holatni tekshirish soraladi.

- Foydalanuvchilar: joriy hisobdan tashqari barcha qolgan hisoblar, jumladan boshqa administratorlar royxatdan ochiriladi va kirishi toxtaydi. Joriy superadministrator hisobi qoladi. Tarixiy ariza/dalil/ball/qarorlar uchun bazadagi yozuvlar saqlanadi. HEMIS bu ochirilgan hisoblarni avtomatik qaytarmaydi.
- Tuzilma: barcha universitet, fakultet va kafedralar ochiriladi. Foydalanuvchi hisoblari qoladi, ularga tegishli tuzilma biriktirishlari olinadi. Oldingi tuzilma va boglanishlar auditga yoziladi. Eski ariza/dalil/balllar saqlanadi.

Amal qidiruv yoki filtrdagi qatorlargagina emas, butun royxatga tegishli. Foydalanuvchi va tuzilma uchun tasdiqlashlar alohida, birini ochirish ikkinchisini avtomatik ochirmaydi. Ommaviy amal tranzaksiyada bajariladi: xato bolsa qisman ochirish saqlanmaydi.

## Tekshiruv
Izolyatsiyalangan bazada foydalanuvchi saqlash, foto/profil, bloklash/blokdan chiqarish va sessiya bekor bolishi, yakka ochirish, 320/390 piksel oyna, oddiy administratorga tugmalar yashirilishi va server taqiqi, notoʻgʻri/eskirgan tasdiq, filtrdan qatʼi nazar umumiy qamrov, joriy hisob hamda eski hisobotlar saqlanishi tekshirildi. ZIP ornatgichi ham faqat test nusxada tekshiriladi. Windows fayl tanlash oynasi Linuxda bevosita sinalmagan.
