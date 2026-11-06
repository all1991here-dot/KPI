# Universitet tuzilmasi, HEMIS profili va huquqlar

## Ornatish
1. KPI ishlayotgan qora oynani Ctrl+C bilan yoping.
2. ZIPni yangi alohida papkaga toliq oching (Extract All / Извлечь всё).
3. INSTALL-ORGANIZATION.bat ni ishga tushiring va mavjud KPI papkasidagi start-native.bat ni tanlang.
4. TAYYOR chiqqach start-native.bat ni qayta ishga tushiring. Profil uchun yangi baza ustuni avtomatik migratsiya orqali qoshiladi. Sozlash oynasi tugashini kuting.
5. Brauzerda Ctrl+F5 bosing.

Avvalgi dastur fayllari runtime/organization-backup-... da saqlanadi. Standart SQLite bazasi mavjud bolsa database/ ostiga nusxalanadi. API kaliti, parollar, yuklangan dalillar va mavjud rollar almashtirilmaydi. Kirish foni animatsiyasiga tegilmaydi. Migratsiya faqat nullable hemis_profile ustunini qoshadi; yozuvlarni ochirmaydi.

## Tuzilma va foydalanuvchi biriktirish
- Universitet nomini qolda kiriting. Eski "Universitet" namunasi bolsa, uni universitetning haqiqiy nomiga tahrirlang.
- Fakultet uchun mavjud universitetni; kafedra uchun mavjud fakultetni tanlang.
- Foydalanuvchini tahrirlashda universitet → fakultet → kafedra tanlanadi. Saqlanadigan toliq boglanish alohida korsatiladi; universitet yoki fakultet darajasida ham biriktirish mumkin.
- Kafedra mudiriga kafedra, fakultet masuliga fakultet biriktirilishi shart.
- HEMIS importida avval universitetni tanlab tuzilmani, keyin xodimlarni yuklang. Bir nechta universitet bolsa tanlov majburiy.
- Mavjud qolda kiritilgan fakultet/kafedra nomi va yuqori bolimi aniq mos kelsa shu yozuv HEMIS ID bilan boglanadi. Bir xil nomda bir nechta nomzod bolsa avtomatik taxmin qilinmaydi, yozuv otkaziladi.
- Mavjud mahalliy hisobni HEMIS bilan boglash uchun uning HEMIS ID sini foydalanuvchi formasida kiriting. Bir HEMIS ID ikki hisobda bolmaydi. Faqat ism oxshashligiga qarab hisoblar birlashtirilmaydi.

## HEMIS profili va rasm
F.I.Sh., universitet/fakultet/kafedra, lavozim, ilmiy daraja va unvon, ish turi, stavka, xodim turi, telefon, email va rasm korsatiladi. Qiymat qaytmasa "Yoq", rasm bolmasa/yuklanmasa "Rasm yoq" chiqadi. Profil royxatdagi HEMIS profili bolimida va shaxsiy profilda mavjud.

Rasmlar HEMIS qaytargan HTTPS manzildan yoki uning HTTPS CDN manzilidan brauzerda yuklanadi; API kaliti rasm soroviga biriktirilmaydi, referrer yuborilmaydi. Faqat autentifikatsiyali rasm manzili berilsa server ruxsat bermasligi mumkin va "Rasm yoq" korsatiladi. Jonli HEMIS xodim maʼlumotlari/rasmlari bu muhitda mavjud emas; import JSON fixture va rasm browser fixture orqali tekshirildi.

Import mavjud foydalanuvchining roli, paroli, KPI toifasi va faolligini saqlaydi. Profilning qaytarilmagan qiymatlari yoq deb belgilanadi; eski notogri boglanish yashirincha saqlanmaydi. Bolimi topilmaganlar soni natijada korsatiladi: avval tuzilmani import qilib, keyin xodimlarni qayta yuklash kerak.

## Huquqlar va hujjatlar
- Faqat superadministrator rol beradi, almashtiradi yoki "Rol biriktirilmagan" qilib olib tashlaydi. Administratorning yasama role POST sorovi ham rad etiladi.
- Administrator yaratgan yoki HEMISdan yangi kelgan hisobga avtomatik vakolat berilmaydi. HEMIS hisoblari avvalgidek nofaol ochiladi. Superadministrator rol beradi, administrator kerakli bolim/toifa/faollikni toldiradi.
- Rol biriktirilmagan foydalanuvchi faqat profil, parol almashtirish va chiqishga kira oladi.
- Administrator va superadministrator barcha arizalar va dalillarni, jumladan tasdiqlangan yoki yopiq davrdagi hujjatni tahrirlashi mumkin.
- Bunday tahrir avvalgi ballni ledgerda teskari yozuv bilan bekor qiladi va hujjatni qoralamaga qaytaradi. Egasi/toifasi ozgarmaydi; eski qarorlar tarixi saqlanadi, oldingi/yangi qiymatlar auditga yoziladi. Qayta tasdiqlash kerak. Yopiq davrda qayta yuborish uchun avval davrni ochish kerak.
- Oddiy xodimlarning tahrirlash va tekshirish huquqi kengaytirilmaydi. Oz arizasini ozi tasdiqlashga ruxsat berilmaydi.

## Tekshiruv
PHP hisoblash/huquq/import testlari, 45 HTTP oqimi, browserda uch bosqichli biriktirish, saqlash, profil/rasm va xato fallback, yasama role POST, oddiy administrator tahriri va rolsiz hisob bloklanishi tekshirildi. Windows fayl tanlash oynasi Linuxda bevosita sinalmagan; ZIP ichidagi PHP ornatgich alohida nusxada tekshiriladi.
