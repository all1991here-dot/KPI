# HEMIS: faol xodimlar va rasmlar

1. KPI qora oynasini yoping.
2. ZIPni «Извлечь всё» bilan alohida papkaga oching.
3. INSTALL-HEMIS-ACTIVE.bat ni oching va mavjud KPI ichidagi start-native.bat ni tanlang.
4. TAYYOR yozuvidan keyin start-native.bat ni qayta oching. Brauzerda Ctrl+F5 bosing.
5. Superadministrator bilan HEMIS integratsiyasi → Xodimlarni import qilish tugmasini bosing.

Yangi va o‘chirilgan hisoblar avtomatik faol O‘qituvchi bo‘ladi. Alohida tiklash belgisini qo‘yish shart emas. Oldingi versiyada importdan keyin faollashtirilmay qolgan hisoblar ham qayta importda faollashadi. Mavjud faol hisoblarning qo‘lda berilgan rollari va alohida bloklangan hisoblar saqlanadi. Rollarni keyin superadministrator o‘zgartiradi. HEMISdagi asl lavozim profilning lavozim maydonida saqlanadi; O‘qituvchi — tizimdagi kirish roli.

HEMIS qaytargan rasmlar ro‘yxat/profil ochilganda yuklanadi va yopiq runtime keshida saqlanadi. API kaliti faqat sozlangan HEMIS serveriga yuboriladi. Rasm mavjud bo‘lmasa «Rasm yo‘q» ko‘rinadi. Yangi hisobga kirish uchun superadministrator vaqtinchalik parol belgilaydi; umumiy standart parol yo‘q.

API kaliti, baza va hujjatlar saqlanadi. O‘chirilgan hisob eski ID bilan tiklanadi; tarixi saqlanadi. O‘rnatgich eski fayllar va standart SQLite bazani runtime/hemis-active-backup-* ichiga zaxiralaydi. O‘rnatishning o‘zi importni boshlamaydi.
