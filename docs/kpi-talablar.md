# KPI tizimi — dastlabki texnik talablar

Manba: foydalanuvchi yuborgan `KPI mezonlar.docx`. Quyidagi ish jarayoni va ma’lumotlar modeli loyiha taklifi; hujjatdagi ballar esa hisoblash uchun manba hisoblanadi.

## Texnologiya

Yii2 Basic, Bootstrap 5, MySQL va Composer. Yuborilgan lock fayldagi paketlar PHP 8.1 yoki yangirog‘ini talab qiladi; PHP 7.4 Docker tasviri ularga mos emas. Ilovani mavjud kod ustida davom ettirish yoki yangi yaratish foydalanuvchi javobidan keyin belgilanadi.

## Xodim toifalari

1. Fan doktori (DSc), professor va DSc, dotsent lavozimlariga ega bo‘lganlar.
2. Fan nomzodi/PhD ilmiy darajasiga ega dotsent, katta o‘qituvchi va o‘qituvchilar.
3. Ilmiy darajasiz katta o‘qituvchi va o‘qituvchilar.

Har bir toifa uchun alohida mezon variantlari saqlanadi. Bir xil nomdagi faoliyat barcha toifalarda bir xil ball bermaydi.

## Yo‘nalishlar

- O‘quv jarayonlarini tashkil etish va ta’lim sifati.
- Ilmiy-tadqiqot faoliyati.
- Xalqaro hamkorlik faoliyati.
- Tashkiliy, ma’naviy-ma’rifiy va jamoatchilik faoliyati.

## Hujjatdagi hisoblash turlari

- Har bir natija uchun: birlik balli × tasdiqlangan natijalar soni.
- Chegarali takroriy faoliyat: masalan, hakamlik 1 ball, jami maksimal 3 ball; mahalla ma’ruzasi 0,5 ball, jami maksimal 3 ball.
- Holatga bog‘liq ball: ilmiy daraja, unvon, til sertifikati kabi ko‘rsatkichlar.
- Ekspert bahosi: darslarni tashkil etish 5 ballgacha; ta’lim sifati a’lo 10, yaxshi 7, qoniqarli 5 ballgacha. Darajasiz toifada qoniqarsiz uchun 0 ball ham aniq berilgan.
- Xirsh indeksi: bir indeks birligi uchun 1 ball; qaysi sana va qaysi baza qiymati olinishi aniqlashtiriladi.
- Grant/tijoratlashtirish: xodim ulushining har 100 MHTEKM miqdoriga 5 ball. Proporsional hisob yoki to‘liq bloklar bilan hisoblash va MHTEKM sanasi aniqlashtiriladi.
- Rag‘batlantiruvchi ko‘rsatkichlar alohida saqlanadi; ularni umumiy natijaga qo‘shish qoidasi tasdiqlanishi kerak.

Masalan, uchta tasdiqlangan fan kontenti 18 ball; beshta hakamlik maksimal 3 ball; sakkizta mahalla ma’ruzasi maksimal 3 ball. Patent DSc va PhD toifalarida 5 ball, darajasiz toifada 7 ball. Chet tilida fan o‘tish PhD toifasida 6 ball, darajasiz toifada 3 ball (har bir fan uchun).

Faqat matnda soniga ko‘paytirish aniq belgilangan bandlar avtomatik takroriy hisoblanadi. Boshqa bandlar uchun qoida tasdiqlanmasdan ko‘paytirish kiritilmaydi. Umumiy natijani 100 ball bilan cheklash hujjatda belgilanmagan.

## Taklif etilayotgan ish jarayoni

O‘qituvchi davr va mezonni tanlaydi, natija va tasdiqlovchi fayl yoki havolani kiritadi. Ariza qoralama sifatida saqlanadi va tekshirishga yuboriladi. Tekshiruvchi uni tasdiqlaydi yoki izoh bilan qaytaradi. Reytingga tasdiqlangan natijalar kiradi. Hisoblash serverda bajariladi; foydalanuvchi yuborgan tayyor ballga ishonilmaydi.

Taklif etilayotgan rollar: o‘qituvchi, tekshiruvchi, rahbar (hisobot), administrator. Kim qaysi xodim natijasini ko‘rishi va tasdiqlashi tashkilot tuzilmasiga qarab belgilanadi.

## Taklif etilayotgan ma’lumotlar modeli

- `user`, `employee_profile`: foydalanuvchi, ilmiy daraja/unvon, lavozim, til mutaxassisligi.
- `faculty`, `department`: tashkilot tuzilmasi.
- `assessment_period`, `employee_assessment`: baholash davri, xodimning shu davrdagi toifasi va qoida versiyasi.
- `criteria_version`, `category`, `criterion`, `criterion_rule`: hujjat versiyasi, yo‘nalish, mezon va toifaga mos hisoblash qoidasi.
- `submission`, `evidence`: faoliyat sanasi, son/miqdor, ariza holati va dalillar.
- `review`, `audit_log`: kim, qachon, nima sababdan tasdiqlagan yoki o‘zgartirgan.

Ball va pul miqdorlari decimal turida saqlanadi. Mezonlar o‘zgarsa, oldingi tasdiqlangan hisobotlar yangi qoida bilan avtomatik qayta hisoblanmaydi. Dalillar ochiq web papkasidan tashqarida saqlanib, ruxsat tekshiruvi orqali beriladi.

## Birinchi ishlaydigan talqin uchun sahifalar

- Kirish va shaxsiy profil.
- Shaxsiy KPI paneli: yo‘nalishlar bo‘yicha ball, tasdiqlangan va kutilayotgan natijalar.
- Toifaga mos mezonlar va dalil yuborish formasi.
- Tekshiruv navbati, dalillarni ko‘rish va qaror izohi.
- Xodim/kafedra/fakultet bo‘yicha davriy hisobot va reyting.
- Administrator uchun xodimlar, davrlar va mezonlar boshqaruvi.

## Hisoblashni qabul qilish tekshiruvlari

- Toifaga mos bo‘lmagan mezon qabul qilinmaydi.
- Sonli band, 0,5 ball va maksimal chegara to‘g‘ri hisoblanadi.
- Qoralama, qaytarilgan va tekshirilayotgan yozuvlar tasdiqlangan jami ballga kirmaydi.
- Takroriy tasdiqlash ballni ikki marta qo‘shmaydi.
- Boshqa xodim dalilini ko‘rish yoki o‘zgartirish ruxsatsiz amalga oshmaydi.
- Eski qoida versiyasidagi hisobot yangi mezon tahriridan keyin o‘zgarmaydi.

## Kodlashdan oldin aniqlashtiriladigan siyosatlar

1. Baholash kalendar yilimi, semestrmi yoki boshqa davrmi?
2. Dars tashkil etish va sifatini kim, qanday shkala bilan baholaydi?
3. Til sertifikatlari orasidan eng yuqorisi olinadimi, amal qilish muddati qanday tekshiriladi?
4. Hammualliflikdagi nashrlar balli bo‘linadimi? Bir faoliyat bir necha mezonga kirishi mumkinmi?
5. Grant balli proporsionalmi yoki 100 MHTEKMlik to‘liq bloklar uchunmi? Qaysi sanadagi MHTEKM olinadi?
6. Daraja/unvon davr ichida o‘zgarsa, xodim toifasi qanday belgilanadi?
7. Rag‘batlantiruvchi ko‘rsatkichlar asosiy ballga qanday qo‘shiladi?
8. Yakuniy ballni mukofot yoki ustamaga aylantirish tartibi bormi? Bu formula hujjatda yo‘q.

PhD jadvalida raqamlash 8 dan 10 ga o‘tadi. Mezonlar bazada hujjat raqamidan mustaqil identifikator oladi, manbadagi raqam ham alohida saqlanadi.
