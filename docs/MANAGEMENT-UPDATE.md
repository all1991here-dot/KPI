# Superadministrator boshqaruvi va HEMIS importi

## Ornatish
1. KPI ishlayotgan qora oynani Ctrl+C bilan yoping.
2. ZIPni yangi papkaga toliq oching (Extract All / Извлечь всё).
3. INSTALL-MANAGEMENT.bat ni bosing va mavjud KPI papkasidagi start-native.bat ni tanlang.
4. TAYYOR chiqqach start-native.bat ni qayta oching. Migratsiyalar tugashini kuting, brauzerda Ctrl+F5 bosing.
5. Superadministrator hisobida ishlang (login nomi bilan rol bir xil narsa emas).

API kaliti, parollar, profil va dalillar almashtirilmaydi. Eski dastur fayllari va standart SQLite bazasi runtime/management-backup-... da saqlanadi. Yangi migratsiya user va indicator jadvallariga nullable deleted_at ustunlarini qoshadi; oldingi profil migratsiyasi ham paketda bor. Kirish formasi va animatsiyasi ozgarmaydi.

## Faqat superadministrator
- Universitet/fakultet/kafedra yaratish va ochirish.
- Xodim qoshish va ochirish; HEMIS orqali yangi xodim/tuzilma importi ham shu huquqqa kiradi.
- KPI mezoni yaratish, tahrirlash va ochirish: menyudagi Mezonlarni boshqarish.
- Davr ballari va hisoblash qoidalarini ozgartirish; rollarni berish va olib tashlash.

Administrator mavjud ruxsatlari doirasida xodim profilini va biriktirishni, mavjud tuzilma nomini/boglanishini, ariza va dalillarni tahrirlashi mumkin. Yaratish/ochirish tugmalari yashiriladi, yasama POST sorovlari ham serverda rad etiladi. Hisoblash qoidalari administrator uchun korish rejimida.

## Ochirish va tarix
Tuzilma ichida quyi bolim yoki xodim bolsa, avval ularni kochiring/ochiring; xato sababi oynada korsatiladi. Oz hisobingizni ochira olmaysiz.

Xodim ochirilganda royxatdan olinadi va kirishi bloklanadi. Eski ariza, dalil, ball va qarorlar uchun bazadagi tarixiy yozuv saqlanadi. Keyingi HEMIS importi bu hisobni oz-ozidan qayta ochmaydi.

Mezon ochirilganda yangi arizalarda va amaldagi katalogda korinmaydi; eski hujjat va ball nusxalari saqlanadi. Dastur qayta ochilganda manba XML uni qayta yaratmaydi. Yangi mezon kodi tarixiy/ochirilgan kod bilan takror bolmasin.

Katalog tahriri mavjud davr ballarini yashirincha ozgartirmaydi: davr ballari alohida Hisoblash qoidalarida yangilanadi. Yangi mezon ochiq davrlarga qoshiladi; formula/grant bandi davr qarori orqali ochiladi. Tasdiqlangan hujjatning tarixiy qoida nusxasi saqlanadi.

## HEMIS importi
Avval universitetni qolda toldiring, tuzilmani import qiling, keyin Xodimlarni import qilish ni bosing. Nofaol/rolsiz hisoblar ham Foydalanuvchilar royxatida korinadi. Kirish uchun vaqtinchalik parol, toifa, faollik va rol belgilang.

Yangilanish:
- data.items, items, data.employees, employees, data royxati va ichki employee/_employee profilini taniydi;
- qisqa sahifa qaytsa ham keyingi sahifani tekshiradi; pagination/_meta ni taniydi, takroriy sahifaga tushsa aniq xato beradi;
- kop xodimda vaqt tugashiga sabab bolishi mumkin bolgan yangi nofaol hisoblar parol hashini hisoblashni tezlashtiradi;
- buzilgan yoki ID/F.I.Sh. yoq yozuvni sabab va son bilan korsatadi; bittasi butun importni bekor qilmaydi;
- 0 ta yozuv, migratsiya qilinmagan baza, band login va ochirilgan hisobni alohida bildiradi;
- Oxirgi xodimlar importi bolimida sana, olingan/yangi/yangilangan/otkazilgan/boglanmagan sonlar va sabablar korsatiladi;
- kalit Bearer prefiksi bilan kiritilgan bolsa ham togri Authorization header yaratadi.

Mavjud parol, rol, toifa va faollik importda saqlanadi. Diagnostika hisobotida API kaliti va xom xodim malumotlari yozilmaydi. Jonli universitet API kaliti bilan ulanish bu muhitda tekshirilmagan. Xato davom etsa qizil xabar yoki import sonlarini yuboring, kalitni yubormang.

## Tekshiruvlar
Hisoblash/huquq/import testlari, 45 HTTP oqimi, browserda superadmin CRUD va 8 ta ruxsatsiz POST yoli, 250 xodimli import/reimport, turli API javoblari, eski bazani migratsiya qilish va ZIP ornatgichi alohida nusxalarda tekshiriladi. Windows fayl tanlash oynasi Linuxda bevosita sinalmagan.
