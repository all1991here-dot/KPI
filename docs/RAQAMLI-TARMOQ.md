# Raqamli tarmoq — kunduzgi animatsion fon

Bu mavjud KPI ilovasi uchun yangilanish paketi, to‘liq dastur emas.

1. KPI qora oynasini yoping.
2. ZIPni alohida papkaga to‘liq oching (Extract All / Извлечь всё).
3. INSTALL-GALAXY.bat faylini ishga tushiring.
4. Mavjud KPI dasturingizdagi start-native.bat faylini tanlang.
5. TAYYOR yozuvidan so‘ng start-native.bat orqali dasturni qayta oching.
6. Brauzerda Ctrl+F5 bosing va quyosh tugmasi bilan kunduzgi rejimni yoqing.

Kunduzgi rejimda to‘lqinlar o‘rniga raqamli tarmoq ishlaydi. Ko‘k tugunlar
mustaqil suzish, markaz atrofida aylanish va oqim bo‘ylab siljish orasida
asta almashadi. Yaqin tugunlar chiziqlar bilan ulanadi, uzoqlashganda
bog‘lanishlar so‘nadi. Ayrim chiziqlar bo‘ylab yorug‘lik nuqtalari yuradi,
tugunlar esa mayin yorishib turadi. Fon ilova ichida, kartalar orqasida.

Animatsiya tungi rejimda va sahifa yashirilganda to‘xtaydi. Harakatni
kamaytirish sozlamasi yoqilsa tarmoq statik ko‘rinadi. Chop etishda yashiriladi.
Tashqi tarmoq, rasm, CDN yoki qo‘shimcha kutubxona talab qilinmaydi.

O‘rnatgich faqat uchta interfeys faylini yangilaydi va oldingi fayllar bilan
standart SQLite bazani runtime/galaxy-backup-* papkasiga zaxiralaydi.
Baza, parollar, hujjatlar va HEMIS sozlamalari almashtirilmaydi.

Tekshiruv: haqiqiy CSS/JS bilan alohida brauzer maketida vaqt davomida
harakatlanish, rejim almashishi, reduced-motion, 390/320 px mobil ekranlar,
chop etish va JavaScript xatolari tekshirildi. ZIP va SHA256 tekshiruvlari o‘tdi.
Windows BAT o‘rnatgichi va to‘liq PHP ilovasi bu Linux muhitida bajarilmagan.
