<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Delete test articles (IDs 4, 6, 7 - test data like "salom", "sadfasdf", etc.)
        DB::table('article_translations')->whereIn('article_id', [4, 6, 7])->delete();
        DB::table('articles')->whereIn('id', [4, 6, 7])->delete();
        
        // Add Section II if not exists
        $section2Id = DB::table('sections')->where('order_number', 2)->value('id');
        if (!$section2Id) {
            $section2Id = DB::table('sections')->insertGetId([
                'order_number' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            DB::table('section_translations')->insert([
                [
                    'section_id' => $section2Id,
                    'locale' => 'uz',
                    'title' => "II Bo'lim. Mehnat shartnomasi",
                    'description' => "Mehnat shartnomasini tuzish, o'zgartirish va bekor qilish tartibi",
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'section_id' => $section2Id,
                    'locale' => 'ru',
                    'title' => 'Раздел II. Трудовой договор',
                    'description' => 'Порядок заключения, изменения и расторжения трудового договора',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'section_id' => $section2Id,
                    'locale' => 'en',
                    'title' => 'Section II. Employment Contract',
                    'description' => 'Procedure for concluding, amending and terminating employment contracts',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
        
        // Add Chapter 3 to Section II
        $chapter3Id = DB::table('chapters')->where('order_number', 3)->value('id');
        if (!$chapter3Id) {
            $chapter3Id = DB::table('chapters')->insertGetId([
                'section_id' => $section2Id,
                'order_number' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            DB::table('chapter_translations')->insert([
                [
                    'chapter_id' => $chapter3Id,
                    'locale' => 'uz',
                    'title' => "3-bob. Mehnat shartnomasini tuzish",
                    'description' => "Mehnat shartnomasini tuzish tartibi va shartlari",
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'chapter_id' => $chapter3Id,
                    'locale' => 'ru',
                    'title' => 'Глава 3. Заключение трудового договора',
                    'description' => 'Порядок и условия заключения трудового договора',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'chapter_id' => $chapter3Id,
                    'locale' => 'en',
                    'title' => 'Chapter 3. Conclusion of Employment Contract',
                    'description' => 'Procedure and conditions for concluding employment contracts',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
        
        // Add real articles to Chapter 1
        $this->addArticle(1, '3', 3, [
            'uz' => [
                'title' => "3-modda. Mehnat munosabatlari sohasidagi asosiy tushunchalar",
                'content' => "Ushbu Kodeksda quyidagi asosiy tushunchalar qo'llaniladi:\n\nmehnat munosabatlari — xodim bilan ish beruvchi o'rtasidagi mehnat shartnomasi asosida yuzaga keladigan munosabatlar;\n\nish beruvchi — mehnat shartnomasi asosida xodimni ishga qabul qilgan yuridik yoki jismoniy shaxs;\n\nxodim — mehnat shartnomasi asosida ish beruvchida ishlayotgan jismoniy shaxs;\n\nmehnat shartnomasi — xodim bilan ish beruvchi o'rtasidagi kelishuv bo'lib, unga ko'ra xodim belgilangan mehnat funktsiyasini bajarishni, ichki mehnat tartib-qoidalariga rioya qilishni zimmasiga oladi, ish beruvchi esa xodimga ish haqi to'lashni va mehnat qonunchiligi bilan belgilangan mehnat sharoitlarini ta'minlashni zimmasiga oladi.",
                'summary' => "Mehnat kodeksidagi asosiy tushunchalar: mehnat munosabatlari, ish beruvchi, xodim, mehnat shartnomasi",
                'keywords' => ['mehnat', 'tushunchalar', 'ish beruvchi', 'xodim'],
            ],
            'ru' => [
                'title' => 'Статья 3. Основные понятия в сфере трудовых отношений',
                'content' => "В настоящем Кодексе применяются следующие основные понятия:\n\nтрудовые отношения — отношения, возникающие между работником и работодателем на основе трудового договора;\n\nработодатель — юридическое или физическое лицо, принявшее работника на работу на основе трудового договора;\n\nработник — физическое лицо, работающее у работодателя на основе трудового договора;\n\nтрудовой договор — соглашение между работником и работодателем, в соответствии с которым работник обязуется выполнять определенную трудовую функцию, соблюдать правила внутреннего трудового распорядка, а работодатель обязуется выплачивать работнику заработную плату и обеспечивать условия труда, предусмотренные трудовым законодательством.",
                'summary' => 'Основные понятия Трудового кодекса: трудовые отношения, работодатель, работник, трудовой договор',
                'keywords' => ['труд', 'понятия', 'работодатель', 'работник'],
            ],
            'en' => [
                'title' => 'Article 3. Basic Concepts in the Field of Labor Relations',
                'content' => "The following basic concepts are used in this Code:\n\nlabor relations — relations arising between an employee and an employer on the basis of an employment contract;\n\nemployer — a legal entity or individual who has hired an employee on the basis of an employment contract;\n\nemployee — an individual working for an employer on the basis of an employment contract;\n\nemployment contract — an agreement between an employee and an employer, according to which the employee undertakes to perform a certain labor function, comply with the internal labor regulations, and the employer undertakes to pay the employee wages and provide working conditions provided by labor legislation.",
                'summary' => 'Basic concepts of the Labor Code: labor relations, employer, employee, employment contract',
                'keywords' => ['labor', 'concepts', 'employer', 'employee'],
            ],
        ]);
        
        $this->addArticle(1, '4', 4, [
            'uz' => [
                'title' => "4-modda. Mehnat qonunchiligining tamoyillari",
                'content' => "Mehnat qonunchiligining asosiy tamoyillari quyidagilardan iborat:\n\n1) mehnat erkinligi;\n2) majburiy mehnatning taqiqlanishi;\n3) kamsitishning taqiqlanishi;\n4) adolatli mehnat sharoitlarini ta'minlash;\n5) xodimlarga teng huquqlar va imkoniyatlar berilishi;\n6) xodimlarning mehnat huquqlarini himoya qilish;\n7) ish haqi to'lashni kafolatlash;\n8) mehnatni muhofaza qilish.\n\nMajburiy mehnat taqiqlanadi. Majburiy mehnat deganda — jismoniy zo'rlik ishlatish yoki zo'rlik bilan qo'rqitish yoxud boshqa ta'sir o'tkazish yo'li bilan shaxsdan uning xohishiga qarshi mehnat talab qilish tushuniladi.",
                'summary' => "Mehnat qonunchiligining asosiy tamoyillari: mehnat erkinligi, kamsitishning taqiqlanishi, adolatli mehnat sharoitlari",
                'keywords' => ['tamoyillar', 'mehnat erkinligi', 'majburiy mehnat'],
            ],
            'ru' => [
                'title' => 'Статья 4. Принципы трудового законодательства',
                'content' => "Основными принципами трудового законодательства являются:\n\n1) свобода труда;\n2) запрещение принудительного труда;\n3) запрещение дискриминации;\n4) обеспечение справедливых условий труда;\n5) предоставление работникам равных прав и возможностей;\n6) защита трудовых прав работников;\n7) гарантирование выплаты заработной платы;\n8) охрана труда.\n\nПринудительный труд запрещен. Под принудительным трудом понимается требование выполнения работы от лица против его воли путем применения физического насилия или угрозы насилием либо иного воздействия.",
                'summary' => 'Основные принципы трудового законодательства: свобода труда, запрет дискриминации, справедливые условия труда',
                'keywords' => ['принципы', 'свобода труда', 'принудительный труд'],
            ],
            'en' => [
                'title' => 'Article 4. Principles of Labor Legislation',
                'content' => "The main principles of labor legislation are:\n\n1) freedom of labor;\n2) prohibition of forced labor;\n3) prohibition of discrimination;\n4) ensuring fair working conditions;\n5) granting employees equal rights and opportunities;\n6) protection of employees' labor rights;\n7) guaranteeing payment of wages;\n8) labor protection.\n\nForced labor is prohibited. Forced labor means requiring a person to perform work against their will through the use of physical violence or threats of violence or other influence.",
                'summary' => 'Main principles of labor legislation: freedom of labor, prohibition of discrimination, fair working conditions',
                'keywords' => ['principles', 'freedom of labor', 'forced labor'],
            ],
        ]);
        
        $this->addArticle(1, '5', 5, [
            'uz' => [
                'title' => "5-modda. Mehnat huquqlarida teng imkoniyatlar",
                'content' => "Mehnat huquqlarini amalga oshirishda barcha fuqarolar teng imkoniyatlarga ega.\n\nJinsi, irqi, millati, tili, dini, ijtimoiy kelib chiqishi, e'tiqodi, shaxsiy va ijtimoiy mavqeiga qarab mehnat sohasida kamsitishga yo'l qo'yilmaydi.\n\nMehnat sohasidagi teng imkoniyatlar xodimlarning ishga qabul qilish, lavozimga ko'tarish, malakasini oshirish, qayta tayyorlash, ish haqi to'lash va boshqa huquqlariga nisbatan qo'llaniladi.\n\nAyollar va erkaklar mehnat huquqlarini amalga oshirishda teng imkoniyatlarga ega.",
                'summary' => "Barcha fuqarolar mehnat huquqlarini amalga oshirishda teng imkoniyatlarga ega, kamsitish taqiqlanadi",
                'keywords' => ['teng huquq', 'kamsitish', 'diskriminatsiya'],
            ],
            'ru' => [
                'title' => 'Статья 5. Равные возможности в трудовых правах',
                'content' => "Все граждане имеют равные возможности в осуществлении трудовых прав.\n\nДискриминация в сфере труда по признакам пола, расы, национальности, языка, религии, социального происхождения, убеждений, личного и общественного положения не допускается.\n\nРавные возможности в сфере труда применяются в отношении прав работников на прием на работу, продвижение по должности, повышение квалификации, переподготовку, оплату труда и других прав.\n\nЖенщины и мужчины имеют равные возможности в осуществлении трудовых прав.",
                'summary' => 'Все граждане имеют равные возможности в трудовых правах, дискриминация запрещена',
                'keywords' => ['равные права', 'дискриминация', 'равенство'],
            ],
            'en' => [
                'title' => 'Article 5. Equal Opportunities in Labor Rights',
                'content' => "All citizens have equal opportunities in exercising labor rights.\n\nDiscrimination in the field of labor based on gender, race, nationality, language, religion, social origin, beliefs, personal and social status is not allowed.\n\nEqual opportunities in the field of labor apply to employees' rights to hiring, promotion, professional development, retraining, remuneration and other rights.\n\nWomen and men have equal opportunities in exercising labor rights.",
                'summary' => 'All citizens have equal opportunities in labor rights, discrimination is prohibited',
                'keywords' => ['equal rights', 'discrimination', 'equality'],
            ],
        ]);
        
        // Add articles to Chapter 3 (Employment Contract)
        if ($chapter3Id) {
            $this->addArticle($chapter3Id, '15', 1, [
                'uz' => [
                    'title' => "15-modda. Mehnat shartnomasi tushunchasi",
                    'content' => "Mehnat shartnomasi — bu xodim bilan ish beruvchi o'rtasidagi kelishuvdir.\n\nMehnat shartnomasiga ko'ra:\n- xodim belgilangan mutaxassislik, malaka yoki lavozim bo'yicha mehnat funktsiyasini bajarishni, ichki mehnat tartib-qoidalariga rioya qilishni zimmasiga oladi;\n- ish beruvchi esa xodimga shu shartnomada belgilangan miqdorda ish haqi to'lashni hamda mehnat qonunchiligi, boshqa normativ-huquqiy hujjatlar va mehnat shartnomasi bilan belgilangan mehnat sharoitlarini ta'minlashni zimmasiga oladi.\n\nMehnat shartnomasi yozma shaklda tuziladi.",
                    'summary' => "Mehnat shartnomasi — xodim va ish beruvchi o'rtasidagi kelishuv",
                    'keywords' => ['shartnoma', 'mehnat shartnomasi', 'kelishuv'],
                ],
                'ru' => [
                    'title' => 'Статья 15. Понятие трудового договора',
                    'content' => "Трудовой договор — это соглашение между работником и работодателем.\n\nПо трудовому договору:\n- работник обязуется выполнять трудовую функцию по определенной специальности, квалификации или должности, соблюдать правила внутреннего трудового распорядка;\n- работодатель обязуется выплачивать работнику заработную плату в размере, установленном этим договором, и обеспечивать условия труда, предусмотренные трудовым законодательством, иными нормативно-правовыми актами и трудовым договором.\n\nТрудовой договор заключается в письменной форме.",
                    'summary' => 'Трудовой договор — соглашение между работником и работодателем',
                    'keywords' => ['договор', 'трудовой договор', 'соглашение'],
                ],
                'en' => [
                    'title' => 'Article 15. Concept of Employment Contract',
                    'content' => "An employment contract is an agreement between an employee and an employer.\n\nUnder the employment contract:\n- the employee undertakes to perform a labor function in a certain specialty, qualification or position, to comply with internal labor regulations;\n- the employer undertakes to pay the employee wages in the amount established by this contract and to provide working conditions provided by labor legislation, other regulatory legal acts and the employment contract.\n\nThe employment contract is concluded in writing.",
                    'summary' => 'Employment contract is an agreement between employee and employer',
                    'keywords' => ['contract', 'employment contract', 'agreement'],
                ],
            ]);
            
            $this->addArticle($chapter3Id, '16', 2, [
                'uz' => [
                    'title' => "16-modda. Mehnat shartnomasini tuzish tartibi",
                    'content' => "Mehnat shartnomasi ish beruvchi va ishga kiruvchi o'rtasida tuziladi.\n\nMehnat shartnomasini tuzish uchun quyidagi hujjatlar talab qilinadi:\n1) passport yoki shaxsni tasdiqlovchi boshqa hujjat;\n2) mehnat daftarchasi (ilgari ishlagan bo'lsa);\n3) ta'lim to'g'risidagi hujjat (kasb talab qilsa);\n4) tibbiy ko'rik natijalari (qonunda nazarda tutilgan hollarda).\n\nMehnat shartnomasi ikki nusxada tuziladi va har bir taraf bir nusxadan oladi.\n\nIsh beruvchi mehnat shartnomasini tuzish sanasidan boshlab uch kun ichida ishga qabul qilish to'g'risida buyruq chiqarishi shart.",
                    'summary' => "Mehnat shartnomasini tuzish tartibi va kerakli hujjatlar",
                    'keywords' => ['shartnoma tuzish', 'hujjatlar', 'ishga qabul'],
                ],
                'ru' => [
                    'title' => 'Статья 16. Порядок заключения трудового договора',
                    'content' => "Трудовой договор заключается между работодателем и поступающим на работу.\n\nДля заключения трудового договора требуются следующие документы:\n1) паспорт или иной документ, удостоверяющий личность;\n2) трудовая книжка (при ее наличии);\n3) документ об образовании (если профессия требует);\n4) результаты медицинского осмотра (в случаях, предусмотренных законом).\n\nТрудовой договор составляется в двух экземплярах, каждая сторона получает по одному экземпляру.\n\nРаботодатель обязан издать приказ о приеме на работу в течение трех дней со дня заключения трудового договора.",
                    'summary' => 'Порядок заключения трудового договора и необходимые документы',
                    'keywords' => ['заключение договора', 'документы', 'прием на работу'],
                ],
                'en' => [
                    'title' => 'Article 16. Procedure for Concluding Employment Contract',
                    'content' => "An employment contract is concluded between the employer and the person applying for work.\n\nThe following documents are required to conclude an employment contract:\n1) passport or other identity document;\n2) employment record book (if available);\n3) education document (if the profession requires);\n4) medical examination results (in cases provided by law).\n\nThe employment contract is drawn up in two copies, each party receives one copy.\n\nThe employer must issue an order on hiring within three days from the date of conclusion of the employment contract.",
                    'summary' => 'Procedure for concluding employment contract and required documents',
                    'keywords' => ['contract conclusion', 'documents', 'hiring'],
                ],
            ]);
        }
        
        // Clear cache for all locales
        $locales = ['uz', 'ru', 'en'];
        foreach ($locales as $locale) {
            \Illuminate\Support\Facades\Cache::forget("sections.all.{$locale}");
            \Illuminate\Support\Facades\Cache::forget("chapters.1.{$locale}");
            \Illuminate\Support\Facades\Cache::forget("chapters.{$chapter3Id}.{$locale}");
        }
    }
    
    private function addArticle(int $chapterId, string $articleNumber, int $order, array $translations): void
    {
        // Check if article already exists
        $exists = DB::table('articles')
            ->where('chapter_id', $chapterId)
            ->where('article_number', $articleNumber)
            ->exists();
            
        if ($exists) {
            return;
        }
        
        $articleId = DB::table('articles')->insertGetId([
            'chapter_id' => $chapterId,
            'article_number' => $articleNumber,
            'order_number' => $order,
            'is_active' => true,
            'translation_status' => 'approved',
            'views_count' => rand(100, 500),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        foreach ($translations as $locale => $data) {
            DB::table('article_translations')->insert([
                'article_id' => $articleId,
                'locale' => $locale,
                'title' => $data['title'],
                'content' => $data['content'],
                'summary' => $data['summary'] ?? null,
                'keywords' => json_encode($data['keywords'] ?? []),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration adds seed data, down migration would remove it
        // but we don't want to do that accidentally
    }
};

