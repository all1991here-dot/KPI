# src.rar — Yii2 uchun 1C manbasi tahlili

Arxiv xavfsiz alohida katalogga ochildi: 241 fayl, 1 675 629 bayt. BSL modullari bajarilmadi; bu manba kodining statik tahlili. 1C bazasi, haqiqiy ma’lumotlar va ishlayotgan HEMIS ulanishi tekshirilmadi.

## Qayta foydalaniladigan qismlar

| 1C manbasi | Yii2 tizimida qo‘llanishi |
| --- | --- |
| `DataProcessors/KPIKatalogYuklash/Templates/Katalog/Ext/Template.txt` | Mezonlar va ko‘rsatkichlar uchun boshlang‘ich ma’lumotlar; barqaror kodlar, ballar, chegaralar va manba matni |
| `KPIHisoblashServer` va `KPIQoidaServis` | Alohida hisoblash xizmati, davr va qoida versiyasini tekshirish |
| `KPIArizaServer` | Ariza holatlari, dalillar, dastlabki hisob, o‘zgarmas identifikator va tarix |
| `KPITasdiqServer` | Sozlanadigan tasdiqlash marshruti, apellyatsiya va qarorni bekor qilish |
| `KPIHuquqServer`, `Roles/*/Ext/Rights.xml` | Yii2 RBAC hamda xodim/kafedra/fakultet bo‘yicha yozuvlarga kirish chegaralari |
| `KPIKabinetServer`, `DataProcessors/XodimKabineti` | Shaxsiy panel, faoliyatlar, arizalar, hujjatlar, reyting, hisobotlar, apellyatsiya, bildirishnomalar va profil |
| `KPIHisobotServer`, `Reports` | Shaxsiy, kafedra, fakultet va universitet hisobotlari, arizalar monitoringi; PDF/XLSX eksporti |
| `KPIHemisServer` | HEMIS tashkilot tuzilmasi va xodimlarni sahifalab import qilish, mavjud yozuvlarni identifikator orqali yangilash |
| Yetti `*Sinov` moduli | PHP testlariga aylantiriladigan hisoblash, ruxsat, ariza, tasdiq, kabinet, hisobot va HEMIS ssenariylari |

BSL kodi PHP’da bevosita ishlamaydi. Ma’lumotlar va tekshirilgan biznes qoidalari ko‘chiriladi, xizmatlar Yii2 uchun qayta yoziladi.

## Katalogning aniq tarkibi

- 3 xodim toifasi.
- Har toifada to‘rttadan yo‘nalish: jami 12 yozuv.
- 46 mezon: DSc uchun 14, PhD uchun 17, darajasiz uchun 15. PhD hujjatidagi raqamlash bo‘shlig‘i alohida mezon emas.
- 139 ko‘rsatkich: DSc 41, PhD 54, darajasiz 44.
- Hisoblash turlari: `BirlikUchun` 53, `QatiyBall` 64, `Ballgacha` 3, `DarajaShkalasi` 9, `ChegaraliYigindi` 4, `XirshIndeksi` 3, `PulFormulasi` 3.

Ko‘rsatkichlarda manba sahifasi, bo‘limi, matni va ochiq qolgan savollar ham saqlangan. Ular Yii2 ma’lumotlar bazasida yo‘qotilmasligi kerak.

## Muhim: normativ aniqlashtirish bayrog‘i

139 ko‘rsatkichdan **96 tasida `Tasdiq=true`**, 43 tasida `false`. Bu oddiy ariza tasdig‘i bilan bir xil emas: `MaydonlarniAniqlash` va `HisobniYozish` bu bayroqni normativ ochiq qolganligi sabab hisoblash/saqlashni bloklash uchun ishlatadi.

Shuning uchun faqat grant va Xirsh emas, fan kontenti, sifat baholari, ko‘plab nashrlar, til sertifikatlari va boshqa bandlar ham hozirgi ariza qatlamida bloklangan. Hisoblash xizmatining o‘zi ayrim shu bandlarni hisoblay olsa ham, bu arizani yakunigacha yuborish ishlashini isbotlamaydi. Yii2’da `requires_policy_decision` va oddiy `requires_review` alohida maydonlar bo‘lishi kerak. Bayroqlarni avtomatik olib tashlamaymiz.

## Ish jarayoni

Holatlar: qoralama, yuborilgan, tekshiruvda, qaytarilgan, tasdiqlangan, rad etilgan. Apellyatsiya qaytarilgan yoki rad etilgan arizani qayta yuborilgan holatga o‘tkazadi va sababni tarixga yozadi.

Andoza marshruti:

1. Kafedra mudiri tekshiruvi.
2. Fakultet mas’uli yoki ekspert tekshiruvi.
3. KPI administratori yoki superadministratorning yakuniy tasdig‘i.

Manbaning o‘zida bu andoza rasmiy tartib sifatida tasdiqlanmaganligi yozilgan. Yii2’da marshrut sozlanadigan bo‘ladi.

Rollar: `Oqituvchi`, `KafedraMudiri`, `FakultetMasuli`, `Ekspert`, `KPIAdministrator`, `SuperAdministrator`, `Rahbariyat`.

Tasdiqlangan ball alohida registrga yoziladi. Qaror bekor bo‘lsa eski yozuv o‘chirilmaydi, qarama-qarshi ishorali yozuv qo‘shiladi. Shu model Yii2’da ball operatsiyalari jurnali orqali saqlanishi mumkin.

## Ma’lumotlar modeli uchun qo‘shimchalar

Oldingi `kpi-talablar.md` modeliga quyidagilar qo‘shiladi:

- `indicator`: mezondan alohida, hisoblanadigan eng kichik ko‘rsatkich.
- `approval_route`, `approval_step`: rol, navbat va yakuniy qadam.
- `score_entry`: tasdiq yoki bekor qilish bilan bog‘langan musbat/manfiy ball yozuvi.
- `appeal`: sabab va dastlabki arizaga bog‘lanish.
- `employee_history`: lavozim, daraja, unvon va toifa o‘zgarishlari.
- HEMIS identifikatorlari va takroriy importni boshqarish.

## Dalillar va integratsiya

Manba PDF, PNG, JPG/JPEG dalillarni qabul qiladi, har bir fayl uchun 10 MiB chegara va fayl boshlang‘ich baytlarini tekshirish mavjud. Yii2’da buni MIME tekshiruvi, xususiy saqlash va har yuklab olishda ruxsat nazorati bilan amalga oshiramiz.

HEMIS modulida `/rest/v1/data/department-list` va `/rest/v1/data/employee-list` yo‘llari, Bearer autentifikatsiyasi, sahifalash va xodim rasmlarini olish mavjud. Bu manba kodining imkoniyati; ishlayotgan integratsiya tasdig‘i emas. Tizimni ishlab chiqish uchun hozircha haqiqiy HEMIS kaliti talab qilinmaydi.

## Ko‘chirishda tuzatilishi yoki aniqlashtirilishi kerak

1. `MavjudNatijalar` limit hisobida yuborilgan, tekshiruvdagi va hatto qaytarilgan arizalarni ham sanaydi. Natijada tasdiqlanmagan ariza limitni band qilishi mumkin. Yakuniy ball uchun faqat amaldagi tasdiqlarni hisoblash va dastlabki taxminni alohida ko‘rsatish kerak.
2. `DalilKaliti` sifatida arizaning UUID identifikatori berilgan. Yangi UUID bilan o‘sha fayl qayta yuborilishini bu tekshiruvning o‘zi aniqlamaydi. Fayl xeshi yoki faoliyat identifikatori bo‘yicha takroriylik siyosati kerak.
3. Limit qoldig‘i xodim–davr–ko‘rsatkich darajasida umumiy bo‘lsa-da, tasdiqlash blokirovkasi ariza darajasida. Yii2 tranzaksiyasida umumiy limit uchun ham raqobat nazorati zarur.
4. `BekorniBajarish` tasdiqlangan ball yig‘indisi 0 yoki manfiy bo‘lsa bekor qilishni rad etadi. Nol ball bilan tasdiqlangan ariza holatini qayta ko‘rib chiqish alohida test qilinishi kerak.
5. Hisoblash xodimning joriy daraja va lavozimidan toifani aniqlaydi. Tarix registri mavjud bo‘lsa ham, tarixiy sanadagi profilni hisobga olish siyosati alohida belgilanadi.
6. Foydalanuvchisiz/privilegiyali 1C rejimiga oid ruxsat istisnolari Yii2 web so‘rovlariga ko‘chirilmaydi; autentifikatsiyasiz kirish yopiq bo‘ladi.
7. Kabinetning ayrim jami balllari barcha davrlarni yig‘adi. Yii2’da tanlangan davr va barcha davrlar yig‘indisi aniq ajratiladi.

Bu bandlar statik tahlildan kelib chiqqan; 1C’da ishlatib tasdiqlangan xatolar deb ko‘rsatilmaydi.

## Keyingi ishlab chiqish uchun ketma-ketlik

1. Yii2 bazasi, foydalanuvchi va rollar, tashkilot tuzilmasi.
2. Tekshirilgan 139 ko‘rsatkichli katalogni import qilish; ochiq normativlarni alohida belgilash.
3. Ariza va dalillar, hisoblash xizmati va maqsadli PHP testlari.
4. Tasdiqlash marshruti, ball jurnali, bekor qilish va apellyatsiya.
5. Kabinet, reytinglar va eksport.
6. Mustaqil ishlaydigan tizim tayyor bo‘lgach HEMIS integratsiyasi.

1C sinov modullari o‘qildi, lekin 1C runtime bo‘lmagani sabab ular ishga tushirilmadi. Tayyor ishlaydigan Yii2 ilovasi hali yaratilmagan.
