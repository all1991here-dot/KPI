# HEMIS ulanishi yangilanishi

1. KPI ishlayotgan qora oynani yoping (Ctrl+C).
2. ZIPni yangi papkaga toliq oching (Extract All / Извлечь всё).
3. INSTALL-HEMIS.bat ni bosing. Mavjud KPI dasturidagi start-native.bat ni tanlang.
4. TAYYOR chiqqach, mavjud start-native.bat bilan dasturni qayta oching.
5. HEMIS sahifasida Ctrl+F5 bosing va Ulanishni tekshirish ni tanlang.

Saqlangan API kaliti, baza, parollar va hujjatlar almashtirilmaydi. Kalit xavfsizlik uchun inputda korsatilmaydi. "API kaliti saqlangan" yozuvi saqlanganligini bildiradi; bosh maydon bilan saqlash oldingi kalitni saqlaydi. Boshqa domen uchun yangi kalit talab qilinadi.

Windows PHP uchun ommaviy CA sertifikatlari paketga kiritilgan. SSL tekshiruvi ochirilmaydi. Xatolar endi DNS, serverga ulanish, timeout, TLS/CA, HTTP 401/403/404 va JSON formatiga ajratiladi. Javob matni va kalit xatoga chiqarilmaydi.

HTTP 401: kalit; 403: API huquqi yoki IP ruxsati; 404 yoki HTML: backend domeni; cURL 60: CA zanjiri/kompyuter sanasi; cURL 6: DNS. Student kabineti domeni REST API domenidan farq qilishi mumkin. HEMIS administratori API kaliti bilan bergan backend domenini kiriting.

Korporativ proksi/antivirus oz sertifikatini ishlatsa, tashkilot administratori bergan CA paketini KPI_CA_BUNDLE orqali korsating. Domen avtomatik almashtirilmaydi; kalit boshqa hostga yonaltirilmaydi.

Haqiqiy HEMIS API kaliti bilan ulanish ushbu muhitda sinovdan otkazilmagan. Windows BAT oynasi ham Linux muhitida bevosita tekshirilmagan. PHP transport xatolari, TLS sozlamalari, ommaviy sertifikatlar va saqlangan kalitning qayta oqilishi avtomatik tekshirilgan.

Avvalgi fayllar runtime/hemis-backup-... da, natija install.log da saqlanadi. Paketni qayta ornatish mumkin; baza migratsiyasi kerak emas.
