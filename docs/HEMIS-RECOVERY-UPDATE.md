# Ochirilgan HEMIS foydalanuvchilarini qayta tiklash

## Sabab
"Superadministrator ochirgan hisob" yozuvi bu hisoblar bazada deleted_at belgisi bilan saqlangani uchun oddiy import ularni otkazib yuborganini bildiradi. Foydalanuvchilar royxati bunday yozuvlarni yashiradi. API javobi kelgan, lekin oddiy import oldingi ochirish qarorini avtomatik bekor qilmagan.

## Ornatish va tiklash
1. KPI qora oynasini Ctrl+C bilan yoping.
2. ZIPni yangi papkaga toliq oching (Extract All / Извлечь всё).
3. INSTALL-HEMIS-RECOVERY.bat ni bosing va mavjud KPI papkasidagi start-native.bat ni tanlang.
4. TAYYOR chiqqach start-native.bat ni qayta oching. Tekshiruvlar tugagach brauzerda Ctrl+F5 bosing.
5. Superadministrator hisobida HEMIS integratsiyasiga kiring.
6. "Ochirilgan HEMIS hisoblarini qayta tiklash" belgisini tanlang va "Xodimlarni import qilish"ni bosing.
7. Foydalanuvchilar royxatida Holat = Barchasi bolsin. Tiklanganlar avval nofaol holatda korinadi.

API kalitini qayta kiritish shart emas. Paketni ornatishning ozi hisoblarni tiklamaydi; tiklash Superadministrator tanlovi bilan importda bajariladi. Keyingi oddiy importlar uchun bu belgi sukut boyicha belgilanmagan.

## Nima saqlanadi
Tiklash avvalgi hisob ID sini ishlatadi, dublikat yangi hisob yaratmaydi. Eski hujjat/dalil/ball tarixi, parol hash, rol va KPI toifasi saqlanadi. HEMIS profili va tashkilot biriktirishi API javobidagi joriy malumot bilan yangilanadi. Hisob nofaol tiklanadi va eski sessiya kaliti yangilanadi; yangi kirishda parolni almashtirish talab qilinadi. Foydalanish uchun kerakli rol/toifa va vaqtinchalik parolni tekshiring, keyin Blokdan chiqarish ni bosing.

Faqat API javobida qaytgan HEMIS ID hisoblari tiklanadi. Qolgan arxiv yozuvlari oz-ozidan ochilmaydi. Oddiy administrator tiklashni yoqa olmaydi; serverda ham huquq tekshiriladi.

## Korsatkichlar
Royxatda nechta ochirilgan HEMIS hisobi yashirilgani korsatiladi va tiklash havolasi bor. Importda Qayta tiklandi va Royxatga yozilgan xodimlar soni chiqadi. HEMIS bir xodimni bir necha ish joyi bilan takror qaytarsa, API yozuvlari soni alohida xodimlar sonidan katta bolishi mumkin. Endi ikkala son ajratilgan. Otkazilgan yozuv bolsa xabar ogohlantirish shaklida chiqadi.

## Tekshiruv
117 ta dastur tekshiruvi, 250 ta ochirilgan hisobni asl ID bilan tiklash, oddiy importda tiklanmaslik, hujjat/parol/rol saqlanishi, takroriy API yozuvi, nofaol hisob royxatda korinishi va superadministrator vakolati izolyatsiyalangan maʼlumotlarda tekshirildi. Jonli universitet API kaliti bu muhitda ishlatilmadi. Windows fayl tanlash oynasi Linuxda bevosita sinalmagan.

Eski dastur fayllari va standart SQLite bazasi runtime/hemis-recovery-backup-... da saqlanadi. API kaliti, lokal sozlamalar, parollar va hujjatlar paketga kiritilmaydi. Yangi baza ustuni talab qilinmaydi; oldingi yangilanish migratsiyalari paketda bor.
