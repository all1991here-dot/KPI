# HEMIS rasmlarini tuzatish

1. KPI qora oynasini yoping.
2. ZIPni «Извлечь всё» orqali alohida papkaga oching.
3. INSTALL-HEMIS-PHOTO.bat ni ochib, mavjud KPI ichidagi start-native.bat ni tanlang.
4. TAYYOR yozuvidan keyin start-native.bat ni qayta oching va brauzerda Ctrl+F5 bosing.
5. Rasmlar hanuz ko‘rinmasa HEMIS → Xodimlarni import qilish orqali rasm manzillarini yangilang.
6. Foydalanuvchilar → Tahrirlash → Rasmni tekshirish tugmasini bosing. Ko‘rsatilgan xabarni yuborish mumkin, API kalitini yubormang.

Yangilanish HTTPS rasm yo‘naltirishlarini (3 tagacha), boshqa hostga o‘tishda API kalitini olib tashlashni, sxemasiz manzillarni, fayl nomidagi bo‘shliqlarni va ichki employee obyektidagi rasmlarni qo‘llaydi. Rasm yuklash vaqti 15 soniyaga uzaytirildi. Oldingi muvaffaqiyatsiz rasm keshi yangi urinishni to‘xtatmaydi. Rasmni tekshirish tugmasi avvalgi muvaffaqiyatsiz urinishni kutmasdan qayta tekshiradi va DNS, TLS, HTTP ruxsat yoki yo‘q fayl xatosini ko‘rsatadi.

API kaliti, xodimlar bazasi, rollar va hujjatlar saqlanadi. Yangi/o‘chirilgan HEMIS xodimlarini faol O‘qituvchi qilib import qilish tartibi saqlanadi. O‘rnatgich standart SQLite va o‘zgaradigan fayllarni runtime/hemis-photo-backup-* ga zaxiralaydi. O‘rnatishning o‘zi API chaqirmaydi.

Sinovlar sun’iy API javoblari va lokal rasm bilan bajarildi. Universitetning haqiqiy rasm serveridagi hozirgi xato bu muhitdan tekshirilmagan; «Rasmni tekshirish» tugmasi uni mahalliy kompyuterda aniqlashga yordam beradi.
