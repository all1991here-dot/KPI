# Kirish formasi ortidagi animatsiya

Manba: foydalanuvchi yuborgan KPI_login_zamonaviy_robot_forma_ortidan.html.
Faqat dekorativ tarmoq foni va SVG robot olindi. Namunadagi forma va uning submit handleri kochirilmadi. Mavjud FarDU formasi, surat, logotip, login/parol maydonlari va autentifikatsiya saqlanadi.

1. ZIPni yangi alohida papkaga toliq oching (Extract All / Извлечь всё).
2. INSTALL-LOGIN-ANIMATION.bat ni bosing.
3. Mavjud KPI dasturidagi start-native.bat ni tanlang.
4. TAYYOR chiqqach, kirish sahifasini Ctrl+F5 bilan yangilang.

Paket faqat views/auth/login.php, views/auth/_robot.php, web/css/login-animation.css va web/js/login-animation.js ni yangilaydi. Baza, HEMIS API kaliti va sozlamalar almashtirilmaydi. Nusxalar runtime/login-animation-backup-... da saqlanadi.

Robot va uning yozuvi forma ostidagi dekorativ qatlamda; tugma va maydonlarni bosishga xalal bermaydi. Joy kam bolsa yozuv yashiriladi, telefon ekranida robot uchun joy yetmasa u chiqmaydi. OS harakatni kamaytirish sozlamasida robot va uzluksiz animatsiyalar ochadi. Yashirin tabda canvas va robot taymerlari toxtaydi. Tashqi kutubxona yoki CDN talab qilinmaydi. Dekoratsiya login/parol qiymatlarini oqimaydi va tarmoqqa yubormaydi.

Windows fayl tanlash oynasi Linuxda bevosita sinalmagan. Brauzer tekshiruvi: python tests/login_animation.py (izolyatsiyalangan test serveri).
