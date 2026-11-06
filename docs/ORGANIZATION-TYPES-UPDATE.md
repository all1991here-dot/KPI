# Fakultet, kafedra va boshqa bolimlarni togri ajratish

## Ornatish va eski aralash yozuvlarni tuzatish
1. KPI qora oynasini Ctrl+C bilan yoping.
2. ZIPni yangi papkaga toliq oching (Extract All / Извлечь всё).
3. INSTALL-ORGANIZATION-TYPES.bat ni bosing, mavjud KPI papkasidagi start-native.bat ni tanlang.
4. TAYYOR chiqqach start-native.bat ni qayta oching, migratsiya tugashini kuting. Brauzerda Ctrl+F5 bosing.
5. Superadministrator hisobida HEMIS integratsiyasi → universitetni tanlang → Tuzilmani import qilish ni qayta bosing.

Qayta import mavjud HEMIS ID bilan yozuvni yangilaydi: tashkilotning mahalliy ID si va xodim biriktirishi saqlanadi. Oldingi notogri fakultet turini kafedra yoki bolimga almashtirish endi bloklanmaydi. API kaliti, parollar, dalillar va ballar almashtirilmaydi. Oldingi fayllar va standart SQLite bazasi runtime/organization-types-backup-... da saqlanadi.

## Nima tuzatildi
Oldingi import faqat 12=fakultet, 13=kafedra raqamlariga tayangan. Endi structureType/structure_type nomi, aniq tashkilot nomi va yuqori bolim boglanishi ishlatiladi. Bir xil raqam turli APIlarda boshqa mano bersa, aniq tur nomi raqamdan ustun. Tur nomi yoq bolsa raqam manosini faqat shu API javobidagi boshqa aniq nomli yozuvlardan bilib oladi. Ishonchli mano topilmasa fakultet deb taxmin qilmaydi.

Universitetlar, Fakultetlar, Kafedralar va Bolimlar tortta alohida qismda. Buxgalteriya, boshqarma, markaz, dekanat va boshqa akademik bolmagan tuzilmalar Bolimlar da korsatiladi. Turi nomaʼlum yozuvlar shu yerda Turini tekshiring belgisi bilan alohida turadi.

Eski importda HEMIS tur nomi saqlanmagan edi. Migratsiya faqat nomi aniq kafedra/bolim ekanini bildirgan va xavfsiz boglangan holatni tuzatadi. Oddiy Fizika yoki Adabiyotshunoslik nomidan fakultet/kafedra ekanini taxmin qilmaydi: bunday eski HEMIS yozuvi vaqtincha tekshiruvga olinadi. Qayta import haqiqiy API turini olib uni togri royxatga qaytaradi.

## Boglanishlar
Fakultet universitetga tegishli. Kafedra fakultetga yoki bevosita universitetga qarashli bolishi mumkin. HEMIS fakultetni bildirmasa uni nomiga qarab toqib chiqarmaydi, boglanishni aniqlashtirish belgisi chiqadi. Bolim universitet yoki fakultetga biriktiriladi.

Foydalanuvchi royxati, rekvizitlar oynasi va shaxsiy profilda Bolim alohida maydon. Xodimni bolimga biriktirish ishlaydi. Kafedra va bolim hisobotlari ham ajratilgan; bolim universitet nomi bilan fakultet sifatida korsatilmaydi.

## Qolda tuzatish
Superadministrator Tahrirlash oynasida Tuzilma turi ni Fakultet / Kafedra / Bolim qilib togri tanlaydi va yuqori tuzilmani belgilab saqlaydi. Notoʻgʻri ota turi bilan quyi yozuvlar qoladigan bolsa, avval ularni togri joyga kochirish talab qilinadi.

Tur yoki yuqori bolim qolda ozgartirilsa import bu qarorni saqlaydi. Tur va yuqori bolimni qolda belgilash belgisini ozgarmagan rekvizitlar bilan olib saqlasangiz, keyingi import yana HEMIS turini qollaydi. HEMISning asl tur nomi/kodi rekvizitlarda korsatiladi.

Turlar tuzatilganda foydalanuvchi roli avtomatik almashtirilmaydi. Kafedra mudiri yoki fakultet masuli endi boshqa turdagi/tekshirilmagan tuzilmaga boglanib qolsa, umumiy doiraga kirish berilmaydi; superadministrator mos tuzilma yoki rolni qayta tanlaydi. Yaratish, ochirish, bloklash va rollar boyicha oldingi cheklovlar saqlanadi. Ommaviy ochirish Bolimlarni ham qamrab oladi.

## Tekshiruvlar
Aynan kafedra fakultet sifatida import qilingan holat, raqamlarning boshqa manosi, kirill/lotin tur nomlari, ID va xodim boglanishlarini saqlash, qayta importda dublikat bolmasligi, nomaʼlum tur, qolda tuzatish, ruxsatlar, bolim yaratish va unga xodim biriktirish, hisobot va telefon ekranlari test maʼlumotlarida tekshirildi. Eski baza migratsiyasi ham alohida tekshirildi. Jonli universitet API kaliti bu muhitda ishlatilmadi. Windows fayl tanlash oynasi Linuxda bevosita sinalmagan.
