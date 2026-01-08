<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class LaborCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Adds Labor Code of Uzbekistan data from mehnat_kodeksi.txt
     */
    public function run(): void
    {
        $this->command->info('Starting Labor Code seeder...');
        
        // Clear existing data if requested
        if ($this->command->confirm('Do you want to clear existing sections, chapters and articles?', false)) {
            $this->clearExistingData();
        }
        
        // Create Sections
        $this->command->info('Creating sections...');
        $section1Id = $this->createSection(1, [
            'uz' => ['title' => 'I БЎЛИМ. УМУМИЙ ҚОИДАЛАР', 'description' => 'Меҳнат кодексининг умумий қоидалари'],
            'ru' => ['title' => 'Раздел I. Общие положения', 'description' => 'Общие положения Трудового кодекса'],
            'en' => ['title' => 'Section I. General Provisions', 'description' => 'General provisions of the Labor Code'],
        ]);
        
        $section3Id = $this->createSection(3, [
            'uz' => ['title' => 'III БЎЛИМ. ИШГА ЖОЙЛАШТИРИШ', 'description' => 'Ишга жойлаштириш тартиби'],
            'ru' => ['title' => 'Раздел III. Трудоустройство', 'description' => 'Порядок трудоустройства'],
            'en' => ['title' => 'Section III. Employment', 'description' => 'Employment procedures'],
        ]);
        
        // Create Chapters
        $this->command->info('Creating chapters...');
        
        // Section I chapters
        $chapter1Id = $this->createChapter($section1Id, 1, [
            'uz' => ['title' => '1-боб. Асосий қоидалар', 'description' => 'Меҳнат кодексининг асосий қоидалари'],
            'ru' => ['title' => 'Глава 1. Основные положения', 'description' => 'Основные положения Трудового кодекса'],
            'en' => ['title' => 'Chapter 1. Basic Provisions', 'description' => 'Basic provisions of the Labor Code'],
        ]);
        
        $chapter2Id = $this->createChapter($section1Id, 2, [
            'uz' => ['title' => '2-боб. Меҳнат тўғрисидаги қонунчилик ва меҳнат ҳақидаги бошқа ҳуқуқий ҳужжатлар', 'description' => 'Меҳнат қонунчилиги'],
            'ru' => ['title' => 'Глава 2. Трудовое законодательство и иные правовые акты о труде', 'description' => 'Трудовое законодательство'],
            'en' => ['title' => 'Chapter 2. Labor Legislation and Other Legal Acts on Labor', 'description' => 'Labor legislation'],
        ]);
        
        $chapter7Id = $this->createChapter($section1Id, 7, [
            'uz' => ['title' => '7-боб. Жамоавий музокаралар', 'description' => 'Жамоавий музокаралар олиб бориш тартиби'],
            'ru' => ['title' => 'Глава 7. Коллективные переговоры', 'description' => 'Порядок ведения коллективных переговоров'],
            'en' => ['title' => 'Chapter 7. Collective Bargaining', 'description' => 'Collective bargaining procedures'],
        ]);
        
        $chapter8Id = $this->createChapter($section1Id, 8, [
            'uz' => ['title' => '8-боб. Жамоа шартномаси', 'description' => 'Жамоа шартномаси тузиш тартиби'],
            'ru' => ['title' => 'Глава 8. Коллективный договор', 'description' => 'Порядок заключения коллективного договора'],
            'en' => ['title' => 'Chapter 8. Collective Agreement', 'description' => 'Collective agreement procedures'],
        ]);
        
        $chapter9Id = $this->createChapter($section1Id, 9, [
            'uz' => ['title' => '9-боб. Жамоа келишувлари', 'description' => 'Жамоа келишувлари тузиш тартиби'],
            'ru' => ['title' => 'Глава 9. Коллективные соглашения', 'description' => 'Порядок заключения коллективных соглашений'],
            'en' => ['title' => 'Chapter 9. Collective Agreements', 'description' => 'Collective agreements procedures'],
        ]);
        
        // Section III chapters
        $chapter10Id = $this->createChapter($section3Id, 10, [
            'uz' => ['title' => '10-боб. Умумий қоидалар', 'description' => 'Ишга жойлаштириш бўйича умумий қоидалар'],
            'ru' => ['title' => 'Глава 10. Общие положения', 'description' => 'Общие положения о трудоустройстве'],
            'en' => ['title' => 'Chapter 10. General Provisions', 'description' => 'General provisions on employment'],
        ]);
        
        // Create Articles
        $this->command->info('Creating articles...');
        
        // Chapter 1 Articles (1-9)
        $this->createChapter1Articles($chapter1Id);
        
        // Chapter 2 Articles (10-20)
        $this->createChapter2Articles($chapter2Id);
        
        // Chapter 7 Articles (60-64)
        $this->createChapter7Articles($chapter7Id);
        
        // Chapter 8 Articles (65-79)
        $this->createChapter8Articles($chapter8Id);
        
        // Chapter 9 Articles (80-93)
        $this->createChapter9Articles($chapter9Id);
        
        // Chapter 10 Articles (94-102)
        $this->createChapter10Articles($chapter10Id);
        
        // Clear cache
        $this->clearCache();
        
        $this->command->info('Labor Code seeder completed successfully!');
    }
    
    private function clearExistingData(): void
    {
        DB::table('article_translations')->delete();
        DB::table('articles')->delete();
        DB::table('chapter_translations')->delete();
        DB::table('chapters')->delete();
        DB::table('section_translations')->delete();
        DB::table('sections')->delete();
    }
    
    private function createSection(int $orderNumber, array $translations): int
    {
        $existing = DB::table('sections')->where('order_number', $orderNumber)->first();
        if ($existing) {
            return $existing->id;
        }
        
        $sectionId = DB::table('sections')->insertGetId([
            'order_number' => $orderNumber,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        foreach ($translations as $locale => $data) {
            DB::table('section_translations')->insert([
                'section_id' => $sectionId,
                'locale' => $locale,
                'title' => $data['title'],
                'description' => $data['description'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        return $sectionId;
    }
    
    private function createChapter(int $sectionId, int $orderNumber, array $translations): int
    {
        $existing = DB::table('chapters')->where('order_number', $orderNumber)->first();
        if ($existing) {
            return $existing->id;
        }
        
        $chapterId = DB::table('chapters')->insertGetId([
            'section_id' => $sectionId,
            'order_number' => $orderNumber,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        foreach ($translations as $locale => $data) {
            DB::table('chapter_translations')->insert([
                'chapter_id' => $chapterId,
                'locale' => $locale,
                'title' => $data['title'],
                'description' => $data['description'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        return $chapterId;
    }
    
    private function createArticle(int $chapterId, string $articleNumber, int $orderNumber, array $translations): void
    {
        // Check if article exists globally (article_number is unique across all chapters)
        $existing = DB::table('articles')
            ->where('article_number', $articleNumber)
            ->first();
            
        if ($existing) {
            // Update chapter_id if different and update translations
            if ($existing->chapter_id != $chapterId) {
                DB::table('articles')
                    ->where('id', $existing->id)
                    ->update([
                        'chapter_id' => $chapterId,
                        'order_number' => $orderNumber,
                        'updated_at' => now(),
                    ]);
            }
            
            // Update or create translations
            foreach ($translations as $locale => $data) {
                $existingTranslation = DB::table('article_translations')
                    ->where('article_id', $existing->id)
                    ->where('locale', $locale)
                    ->first();
                    
                if ($existingTranslation) {
                    DB::table('article_translations')
                        ->where('id', $existingTranslation->id)
                        ->update([
                            'title' => $data['title'],
                            'content' => $data['content'],
                            'summary' => $data['summary'] ?? null,
                            'keywords' => json_encode($data['keywords'] ?? []),
                            'updated_at' => now(),
                        ]);
                } else {
                    DB::table('article_translations')->insert([
                        'article_id' => $existing->id,
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
            return;
        }
        
        $articleId = DB::table('articles')->insertGetId([
            'chapter_id' => $chapterId,
            'article_number' => $articleNumber,
            'order_number' => $orderNumber,
            'is_active' => true,
            'translation_status' => 'approved',
            'views_count' => rand(50, 300),
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
    
    private function clearCache(): void
    {
        $locales = ['uz', 'ru', 'en'];
        foreach ($locales as $locale) {
            Cache::forget("sections.all.{$locale}");
            for ($i = 1; $i <= 20; $i++) {
                Cache::forget("chapters.{$i}.{$locale}");
            }
        }
    }
    
    private function createChapter1Articles(int $chapterId): void
    {
        // Article 1
        $this->createArticle($chapterId, '1', 1, [
            'uz' => [
                'title' => '1-модда. Ушбу Кодекс билан тартибга солинадиган муносабатлар',
                'content' => "Ушбу Кодекс ходимлар, иш берувчилар ва давлат манфаатларининг мувозанатини таъминлаш ҳамда уларни мувофиқлаштириш асосида якка тартибдаги меҳнатга оид муносабатларни ва улар билан бевосита боғлиқ бўлган ижтимоий муносабатларни тартибга солади.

ШАРҲ:
Ўзбекистон Республикаси Президенти Шавкат Мирзиёев томонидан илгари сурилган \"Инсон қадри учун\" тамойили устувор бўлган ислоҳотлар давомида меҳнат қонунчилигини ҳам халқаро стандартлар асосида такомиллаштирилмоқда. Меҳнат қонунчилигининг асоси ҳисобланувчи янги Ўзбекистон Республикасининг Меҳнат кодекси \"Ўзбекистон Республикасининг Меҳнат кодексини тасдиқлаш тўғрисида\"ги Ўзбекистон Республикасининг 2022 йил 28 октябрдаги ЎРҚ-798-сон Қонуни билан тасдиқланган бўлиб, 2023 йил 30 апрелдан кучга кирган.

Меҳнат Кодекси (кейинги ўринларда МК деб аталади) 1-моддаси билан тартибга солинадиган муносабатлар доираси ва асосий мақсади кўрсатилган. МК якка тартибдаги меҳнатга оид муносабатларни ва улар билан бевосита боғлиқ бўлган ижтимоий муносабатларни тартибга солар экан, у ходимларнинг ҳам, иш берувчиларнинг ҳам, давлатнинг ҳам манфаатларини ҳисобга олган ҳолда мувозанатини таъминлайди ҳамда уларни мувофиқлаштиради.

Ходимлар деганда меҳнат шартномасининг тарафларидан бири, ўзининг меҳнат вазифасини ички меҳнат тартибига бўйсунган ҳолда ҳақ эвазига шахсан бажарувчи якка тартибдаги меҳнатга оид муносабатларнинг субъекти тушунилади.

Иш берувчи корхона, муассаса ва ташкилот (кейинги ўринларда ташкилот деб аталади), шу жумладан, унинг алоҳида таркибий бўлинмалари номидан иш юритувчи ушбу МК ва бошқа қонунчилик ҳужжатларида кўрсатилган ҳуқуқ ва мажбуриятларга эга бўлган, меҳнат шартномасининг тарафи ҳисобланувчи якка тартибдаги меҳнатга оид муносабатларнинг субъекти тушунилади.",
                'summary' => 'Меҳнат кодекси билан тартибга солинадиган муносабатлар - якка тартибдаги меҳнат муносабатлари ва улар билан боғлиқ ижтимоий муносабатлар',
                'keywords' => ['меҳнат муносабатлари', 'ходим', 'иш берувчи', 'давлат манфаатлари'],
            ],
            'ru' => [
                'title' => 'Статья 1. Отношения, регулируемые настоящим Кодексом',
                'content' => 'Настоящий Кодекс регулирует индивидуальные трудовые отношения и непосредственно связанные с ними социальные отношения на основе обеспечения баланса и согласования интересов работников, работодателей и государства.',
                'summary' => 'Отношения, регулируемые Трудовым кодексом',
                'keywords' => ['трудовые отношения', 'работник', 'работодатель'],
            ],
            'en' => [
                'title' => 'Article 1. Relations Regulated by this Code',
                'content' => 'This Code regulates individual labor relations and directly related social relations based on ensuring the balance and coordination of interests of employees, employers and the state.',
                'summary' => 'Relations regulated by the Labor Code',
                'keywords' => ['labor relations', 'employee', 'employer'],
            ],
        ]);
        
        // Article 2
        $this->createArticle($chapterId, '2', 2, [
            'uz' => [
                'title' => '2-модда. Ушбу Кодекснинг асосий вазифалари',
                'content' => "Ушбу Кодекснинг асосий вазифалари:
- ходимларнинг меҳнат ҳуқуқлари ва эркинликларини кафолатлашнинг ҳуқуқий механизмларини белгилаш;
- қулай ва хавфсиз меҳнат шароитларини яратиш;
- тарафларнинг ҳуқуқ ва манфаатларини ҳимоя қилиш;
- якка тартибдаги меҳнатга оид муносабатларни ва улар билан бевосита боғлиқ бўлган ижтимоий муносабатларни тартибга солиш соҳасида давлат кафолатларини белгилаш ҳисобланади.

ШАРҲ:
Шарҳланаётган моддада Меҳнат кодексининг асосий вазифалари кўрсатилган. Унга кўра, МК вазифалари:
1. Ходимларнинг меҳнат ҳуқуқлари ва эркинликларини кафолатлашнинг ҳуқуқий механизмларини белгилаш.
2. Қулай ва хавфсиз меҳнат шароитларини яратиш.
3. Тарафларнинг ҳуқуқ ва манфаатларини ҳимоя қилиш.
4. Якка тартибдаги меҳнатга оид муносабатларни ва улар билан бевосита боғлиқ бўлган ижтимоий муносабатларни тартибга солиш соҳасида давлат кафолатларини белгилаш ҳисобланади.",
                'summary' => 'Меҳнат кодексининг асосий вазифалари - меҳнат ҳуқуқларини кафолатлаш, хавфсиз меҳнат шароитлари',
                'keywords' => ['вазифалар', 'ҳуқуқлар', 'кафолатлар', 'хавфсизлик'],
            ],
            'ru' => [
                'title' => 'Статья 2. Основные задачи настоящего Кодекса',
                'content' => 'Основными задачами настоящего Кодекса являются установление правовых механизмов гарантирования трудовых прав и свобод работников, создание благоприятных и безопасных условий труда, защита прав и интересов сторон.',
                'summary' => 'Основные задачи Трудового кодекса',
                'keywords' => ['задачи', 'права', 'гарантии'],
            ],
            'en' => [
                'title' => 'Article 2. Main Tasks of this Code',
                'content' => 'The main tasks of this Code are to establish legal mechanisms for guaranteeing labor rights and freedoms of employees, creating favorable and safe working conditions, protecting the rights and interests of the parties.',
                'summary' => 'Main tasks of the Labor Code',
                'keywords' => ['tasks', 'rights', 'guarantees'],
            ],
        ]);
        
        // Article 3
        $this->createArticle($chapterId, '3', 3, [
            'uz' => [
                'title' => '3-модда. Якка тартибдаги меҳнатга оид муносабатларни ва улар билан бевосита боғлиқ бўлган ижтимоий муносабатларни ҳуқуқий жиҳатдан тартибга солишнинг асосий принциплари',
                'content' => "Якка тартибдаги меҳнатга оид муносабатларни ва улар билан бевосита боғлиқ бўлган ижтимоий муносабатларни ҳуқуқий жиҳатдан тартибга солишнинг асосий принциплари қуйидагилардан иборат:
- меҳнат ҳуқуқларининг тенглиги, меҳнат ва машғулотлар соҳасида камситишни тақиқлаш;
- меҳнат эркинлиги ва мажбурий меҳнатни тақиқлаш;
- меҳнат соҳасида ижтимоий шериклик;
- меҳнат ҳуқуқлари таъминланишининг ва меҳнат мажбуриятлари бажарилишининг кафолатланганлиги;
- ходимнинг ҳуқуқий ҳолати ёмонлашишига йўл қўйилмаслиги.

ШАРҲ:
МК 3-моддасида якка тартибдаги меҳнатга оид муносабатларни ва улар билан бевосита боғлиқ бўлган ижтимоий муносабатларни ҳуқуқий жиҳатдан тартибга солишнинг асосий принциплари кўрсатилган.

Шарҳланаётган моддада меҳнат ҳуқуқини тартибга солишнинг тамойиллари кўрсатилган. Тамойил (принцип) деганда асосий ғоя, йўналтирувчи мезон, йўл-йўриқ тушунилади. Тамойил ҳуқуқий меъёрларни шакллантиришдаги ва амалга оширишдаги асосий йўналишларни белгилаб беради.",
                'summary' => 'Меҳнат муносабатларини тартибга солишнинг асосий принциплари',
                'keywords' => ['принциплар', 'тенглик', 'эркинлик', 'кафолат'],
            ],
            'ru' => [
                'title' => 'Статья 3. Основные принципы правового регулирования индивидуальных трудовых отношений',
                'content' => 'Основными принципами правового регулирования индивидуальных трудовых отношений являются: равенство трудовых прав, запрещение дискриминации, свобода труда, социальное партнерство.',
                'summary' => 'Основные принципы регулирования трудовых отношений',
                'keywords' => ['принципы', 'равенство', 'свобода'],
            ],
            'en' => [
                'title' => 'Article 3. Basic Principles of Legal Regulation of Individual Labor Relations',
                'content' => 'The basic principles of legal regulation of individual labor relations are: equality of labor rights, prohibition of discrimination, freedom of labor, social partnership.',
                'summary' => 'Basic principles of labor relations regulation',
                'keywords' => ['principles', 'equality', 'freedom'],
            ],
        ]);
        
        // Article 4
        $this->createArticle($chapterId, '4', 4, [
            'uz' => [
                'title' => '4-модда. Меҳнат ҳуқуқларининг тенглиги, меҳнат ва машғулотлар соҳасида камситишни тақиқлаш принципи',
                'content' => "Ҳар ким меҳнат ҳуқуқларига эга бўлишда тенг имкониятларга эгадир.
Меҳнат ва машғулотлар соҳасида жинси, ёши, ирқи, миллати, тили, ижтимоий келиб чиқиши, мулкий ва мансаб мавқеи, яшаш жойига муносабати, диний эътиқодига, сиёсий қарашларига, ижтимоий бирлашмаларга мансублигига, шунингдек меҳнат натижаларига ва касбий сифатларига тегишли бўлмаган бошқа ҳолатларга кўра камситилишига йўл қўйилмайди.

ШАРҲ:
Шарҳланаётган моддада меҳнат ҳуқуқларининг тенглиги, меҳнат ва машғулотлар соҳасида камситишни тақиқлаш принципи очиб берилган. Унга кўра, ҳар ким меҳнат ҳуқуқларига эга бўлишда тенг имкониятларга эгадир.

Меҳнат ҳуқуқи деганда ишга кириш, ишлаш, иш ҳақи олиш, хизмат бўйича кўтарилиш, малака ошириш, меҳнат шартномасини бекор қилиш ва бошқа меҳнат муносабатларида тегишли ҳуқуқлар тушунилади.",
                'summary' => 'Меҳнат ҳуқуқларининг тенглиги ва камситишни тақиқлаш',
                'keywords' => ['тенглик', 'камситиш', 'дискриминация', 'ҳуқуқлар'],
            ],
            'ru' => [
                'title' => 'Статья 4. Принцип равенства трудовых прав, запрещения дискриминации в сфере труда и занятий',
                'content' => 'Каждый имеет равные возможности в обладании трудовыми правами. Не допускается дискриминация в сфере труда и занятий по признакам пола, возраста, расы, национальности, языка.',
                'summary' => 'Равенство трудовых прав и запрет дискриминации',
                'keywords' => ['равенство', 'дискриминация', 'права'],
            ],
            'en' => [
                'title' => 'Article 4. Principle of Equality of Labor Rights, Prohibition of Discrimination',
                'content' => 'Everyone has equal opportunities in possessing labor rights. Discrimination in the field of labor and occupation is not allowed based on gender, age, race, nationality, language.',
                'summary' => 'Equality of labor rights and prohibition of discrimination',
                'keywords' => ['equality', 'discrimination', 'rights'],
            ],
        ]);
        
        // Articles 5-9 (simplified for now)
        $this->createArticle($chapterId, '5', 5, [
            'uz' => [
                'title' => '5-модда. Меҳнат эркинлиги ва мажбурий меҳнатни тақиқлаш принципи',
                'content' => "Меҳнат эркинлиги кафолатланади.
Ҳар ким касбини, фаолият турини ва ишлаш жойини эркин танлаш ҳуқуқига эга.
Мажбурий меҳнат тақиқланади.

ШАРҲ:
Шарҳланаётган моддада меҳнат эркинлиги ва мажбурий меҳнатни тақиқлаш принципи кўрсатилган. Мажбурий меҳнат деганда қандайдир жазони қўллаш хавфи остида шахсдан талаб қилинадиган ва ушбу шахс уни бажаришга ихтиёрий равишда розилик бермаган ҳар қандай иш ёки хизмат тушунилади.",
                'summary' => 'Меҳнат эркинлиги ва мажбурий меҳнатни тақиқлаш',
                'keywords' => ['эркинлик', 'мажбурий меҳнат', 'касб танлаш'],
            ],
            'ru' => [
                'title' => 'Статья 5. Принцип свободы труда и запрещения принудительного труда',
                'content' => 'Свобода труда гарантируется. Каждый имеет право свободно выбирать профессию, род деятельности и место работы. Принудительный труд запрещен.',
                'summary' => 'Свобода труда и запрет принудительного труда',
                'keywords' => ['свобода', 'принудительный труд'],
            ],
            'en' => [
                'title' => 'Article 5. Principle of Freedom of Labor and Prohibition of Forced Labor',
                'content' => 'Freedom of labor is guaranteed. Everyone has the right to freely choose their profession, type of activity and place of work. Forced labor is prohibited.',
                'summary' => 'Freedom of labor and prohibition of forced labor',
                'keywords' => ['freedom', 'forced labor'],
            ],
        ]);
        
        $this->createArticle($chapterId, '6', 6, [
            'uz' => [
                'title' => '6-модда. Меҳнат соҳасидаги ижтимоий шериклик принципи',
                'content' => "Меҳнат соҳасидаги ижтимоий шериклик бу — ходимлар (ходимлар вакиллари), иш берувчилар (иш берувчилар вакиллари), давлат органлари ўртасидаги муносабатларни тартибга солиш соҳасида келишувга эришишга қаратилган ҳамкорлик.

ШАРҲ:
Шарҳланаётган моддада меҳнат соҳасидаги ижтимоий шериклик принципи ва унинг мазмуни кўрсатилган.",
                'summary' => 'Меҳнат соҳасидаги ижтимоий шериклик принципи',
                'keywords' => ['ижтимоий шериклик', 'ҳамкорлик', 'келишув'],
            ],
            'ru' => [
                'title' => 'Статья 6. Принцип социального партнерства в сфере труда',
                'content' => 'Социальное партнерство в сфере труда — это сотрудничество между работниками, работодателями и государственными органами, направленное на достижение согласия в сфере регулирования отношений.',
                'summary' => 'Принцип социального партнерства',
                'keywords' => ['социальное партнерство', 'сотрудничество'],
            ],
            'en' => [
                'title' => 'Article 6. Principle of Social Partnership in the Field of Labor',
                'content' => 'Social partnership in the field of labor is cooperation between employees, employers and government bodies aimed at reaching agreement in the field of regulation of relations.',
                'summary' => 'Principle of social partnership',
                'keywords' => ['social partnership', 'cooperation'],
            ],
        ]);
        
        $this->createArticle($chapterId, '7', 7, [
            'uz' => [
                'title' => '7-модда. Меҳнат ҳуқуқлари таъминланишининг ва меҳнат мажбуриятлари бажарилишининг кафолатланганлиги принципи',
                'content' => "Ходимларнинг меҳнат ҳуқуқлари таъминланиши ва меҳнат мажбуриятлари бажарилиши кафолатланади.
Иш берувчилар, давлат органлари ва мансабдор шахслар ўз ваколатлари доирасида ходимларнинг меҳнат ҳуқуқлари таъминланишини кафолатлайдилар.

ШАРҲ:
Ушбу моддада меҳнат ҳуқуқлари таъминланишининг ва меҳнат мажбуриятлари бажарилишининг кафолатланганлиги принципи белгиланган.",
                'summary' => 'Меҳнат ҳуқуқлари ва мажбуриятлари кафолатланганлиги',
                'keywords' => ['кафолат', 'ҳуқуқлар', 'мажбуриятлар'],
            ],
            'ru' => [
                'title' => 'Статья 7. Принцип гарантированности обеспечения трудовых прав и исполнения трудовых обязанностей',
                'content' => 'Обеспечение трудовых прав работников и исполнение трудовых обязанностей гарантируются.',
                'summary' => 'Гарантированность трудовых прав и обязанностей',
                'keywords' => ['гарантии', 'права', 'обязанности'],
            ],
            'en' => [
                'title' => 'Article 7. Principle of Guarantee of Labor Rights and Fulfillment of Labor Obligations',
                'content' => 'The provision of labor rights of employees and the fulfillment of labor obligations are guaranteed.',
                'summary' => 'Guarantee of labor rights and obligations',
                'keywords' => ['guarantees', 'rights', 'obligations'],
            ],
        ]);
        
        $this->createArticle($chapterId, '8', 8, [
            'uz' => [
                'title' => '8-модда. Ходимнинг ҳуқуқий ҳолати ёмонлашишига йўл қўйилмаслиги принципи',
                'content' => "Меҳнат ҳақидаги ҳуқуқий ҳужжатларнинг қоидалари ушбу қоидаларни қўллашга асос бўлган меҳнат ҳақидаги ҳуқуқий ҳужжатларга нисбатан ходимларнинг ҳолатини ёмонлаштирса, қўлланилмайди.

ШАРҲ:
Шарҳланаётган моддада ходимнинг ҳуқуқий ҳолати ёмонлашишига йўл қўйилмаслиги принципи белгиланган.",
                'summary' => 'Ходим ҳолатининг ёмонлашишига йўл қўйилмаслиги',
                'keywords' => ['ходим ҳолати', 'ёмонлаштириш', 'ҳимоя'],
            ],
            'ru' => [
                'title' => 'Статья 8. Принцип недопустимости ухудшения правового положения работника',
                'content' => 'Положения правовых актов о труде не применяются, если они ухудшают положение работников.',
                'summary' => 'Недопустимость ухудшения положения работника',
                'keywords' => ['положение работника', 'ухудшение', 'защита'],
            ],
            'en' => [
                'title' => 'Article 8. Principle of Inadmissibility of Deterioration of Legal Status of Employee',
                'content' => 'Provisions of legal acts on labor are not applied if they worsen the situation of employees.',
                'summary' => 'Inadmissibility of deterioration of employee status',
                'keywords' => ['employee status', 'deterioration', 'protection'],
            ],
        ]);
        
        $this->createArticle($chapterId, '9', 9, [
            'uz' => [
                'title' => '9-модда. Ушбу Кодексда назарда тутилган муддатларни ҳисоблаш',
                'content' => "Ушбу Кодексда назарда тутилган муддатлар қуйидагича ҳисобланади:
- календарь йиллар, ойлар, ҳафталар ва кунларда белгиланган муддатлар календарь бўйича ҳисобланади;
- иш кунлари ва соатларда белгиланган муддатлар иш вақти бўйича ҳисобланади.

ШАРҲ:
Ушбу моддада Меҳнат кодексида назарда тутилган муддатларни ҳисоблаш тартиби белгиланган.",
                'summary' => 'Муддатларни ҳисоблаш тартиби',
                'keywords' => ['муддат', 'ҳисоблаш', 'календарь'],
            ],
            'ru' => [
                'title' => 'Статья 9. Исчисление сроков, предусмотренных настоящим Кодексом',
                'content' => 'Сроки, предусмотренные настоящим Кодексом, исчисляются в календарных годах, месяцах, неделях и днях или в рабочих днях и часах.',
                'summary' => 'Порядок исчисления сроков',
                'keywords' => ['сроки', 'исчисление', 'календарь'],
            ],
            'en' => [
                'title' => 'Article 9. Calculation of Terms Provided by this Code',
                'content' => 'The terms provided for in this Code are calculated in calendar years, months, weeks and days or in working days and hours.',
                'summary' => 'Procedure for calculating terms',
                'keywords' => ['terms', 'calculation', 'calendar'],
            ],
        ]);
    }
    
    private function createChapter2Articles(int $chapterId): void
    {
        // Articles 10-20 for Chapter 2
        $articles = [
            ['10', 'Меҳнат тўғрисидаги қонунчилик', 'Трудовое законодательство', 'Labor Legislation'],
            ['11', 'Меҳнат тўғрисидаги қонунчиликнинг амал қилиш соҳаси', 'Сфера действия трудового законодательства', 'Scope of Labor Legislation'],
            ['12', 'Меҳнат ҳақидаги бошқа ҳуқуқий ҳужжатлар', 'Иные правовые акты о труде', 'Other Legal Acts on Labor'],
            ['13', 'Меҳнат тўғрисидаги қонунчиликнинг ва меҳнат ҳақидаги бошқа ҳуқуқий ҳужжатларнинг ўзаро нисбати', 'Соотношение трудового законодательства и иных правовых актов', 'Correlation of Labor Legislation'],
            ['14', 'Жамоа келишувларининг ўзаро нисбати ва ички ҳужжатлар билан ўзаро нисбати', 'Соотношение коллективных соглашений', 'Correlation of Collective Agreements'],
            ['15', 'Ички ҳужжатларнинг ўзаро нисбати', 'Соотношение внутренних документов', 'Correlation of Internal Documents'],
            ['16', 'Меҳнат тўғрисидаги қонунчиликнинг, меҳнат ҳақидаги бошқа ҳуқуқий ҳужжатларнинг ва меҳнат шартномасининг ўзаро нисбати', 'Соотношение законодательства и трудового договора', 'Correlation of Legislation and Employment Contract'],
            ['18', 'Иш берувчининг якка тартибдаги ҳуқуқий ҳужжатлари', 'Индивидуальные правовые акты работодателя', 'Individual Legal Acts of Employer'],
            ['19', 'Ходим ва иш берувчи якка тартибдаги меҳнатга оид муносабатларнинг субъектлари сифатида', 'Работник и работодатель как субъекты трудовых отношений', 'Employee and Employer as Subjects of Labor Relations'],
            ['20', 'Ходимнинг меҳнатга оид ҳуқуқ лаёқати ва муомала лаёқати', 'Трудовая правоспособность и дееспособность работника', 'Labor Legal Capacity of Employee'],
        ];
        
        foreach ($articles as $index => $article) {
            $this->createArticle($chapterId, $article[0], $index + 1, [
                'uz' => [
                    'title' => $article[0] . '-модда. ' . $article[1],
                    'content' => 'Ушбу модда ' . $article[1] . ' масаласини тартибга солади.',
                    'summary' => $article[1],
                    'keywords' => ['меҳнат қонунчилиги'],
                ],
                'ru' => [
                    'title' => 'Статья ' . $article[0] . '. ' . $article[2],
                    'content' => 'Данная статья регулирует вопросы ' . mb_strtolower($article[2]) . '.',
                    'summary' => $article[2],
                    'keywords' => ['трудовое законодательство'],
                ],
                'en' => [
                    'title' => 'Article ' . $article[0] . '. ' . $article[3],
                    'content' => 'This article regulates ' . strtolower($article[3]) . '.',
                    'summary' => $article[3],
                    'keywords' => ['labor legislation'],
                ],
            ]);
        }
    }
    
    private function createChapter7Articles(int $chapterId): void
    {
        // Articles 60-64 for Chapter 7 (Collective Bargaining)
        $articles = [
            ['60', 'Жамоавий музокаралар олиб боришга бўлган ҳуқуқ', 'Право на ведение коллективных переговоров', 'Right to Conduct Collective Bargaining'],
            ['61', 'Жамоавий музокаралар бошланадиган сана', 'Дата начала коллективных переговоров', 'Date of Commencement of Collective Bargaining'],
            ['62', 'Жамоавий музокаралар олиб бориш', 'Ведение коллективных переговоров', 'Conducting Collective Bargaining'],
            ['63', 'Жамоавий музокаралар жараёнида юзага келган ихтилофларни ҳал этиш', 'Разрешение разногласий в процессе коллективных переговоров', 'Resolution of Disagreements in Collective Bargaining'],
            ['64', 'Жамоавий музокараларда иштирок этадиган шахсларга бериладиган кафолатлар ва компенсациялар', 'Гарантии и компенсации участникам коллективных переговоров', 'Guarantees and Compensations for Participants in Collective Bargaining'],
        ];
        
        foreach ($articles as $index => $article) {
            $this->createArticle($chapterId, $article[0], $index + 1, [
                'uz' => [
                    'title' => $article[0] . '-модда. ' . $article[1],
                    'content' => 'Ушбу модда ' . $article[1] . ' масаласини тартибга солади.',
                    'summary' => $article[1],
                    'keywords' => ['жамоавий музокаралар'],
                ],
                'ru' => [
                    'title' => 'Статья ' . $article[0] . '. ' . $article[2],
                    'content' => 'Данная статья регулирует вопросы ' . mb_strtolower($article[2]) . '.',
                    'summary' => $article[2],
                    'keywords' => ['коллективные переговоры'],
                ],
                'en' => [
                    'title' => 'Article ' . $article[0] . '. ' . $article[3],
                    'content' => 'This article regulates ' . strtolower($article[3]) . '.',
                    'summary' => $article[3],
                    'keywords' => ['collective bargaining'],
                ],
            ]);
        }
    }
    
    private function createChapter8Articles(int $chapterId): void
    {
        // Articles 65-79 for Chapter 8 (Collective Agreement)
        $articles = [
            ['65', 'Жамоа шартномасининг тушунчаси ва шакли', 'Понятие и форма коллективного договора', 'Concept and Form of Collective Agreement'],
            ['66', 'Жамоа шартномасини тузиш зарурлиги тўғрисида қарор қабул қилиш', 'Принятие решения о необходимости заключения коллективного договора', 'Decision on the Need to Conclude a Collective Agreement'],
            ['67', 'Жамоа шартномасининг мазмуни ва тузилиши', 'Содержание и структура коллективного договора', 'Content and Structure of Collective Agreement'],
            ['68', 'Жамоа шартномаси шартларининг ҳақиқий эмаслиги', 'Недействительность условий коллективного договора', 'Invalidity of Collective Agreement Terms'],
            ['69', 'Жамоа шартномасининг лойиҳасини муҳокама қилиш', 'Обсуждение проекта коллективного договора', 'Discussion of Collective Agreement Draft'],
            ['70', 'Жамоа шартномасини тузиш тартиби', 'Порядок заключения коллективного договора', 'Procedure for Concluding Collective Agreement'],
            ['71', 'Жамоа шартномасининг кучга кириши ва амал қилиш муддати', 'Вступление в силу и срок действия коллективного договора', 'Entry into Force and Duration of Collective Agreement'],
            ['72', 'Жамоа шартномаси амал қилишининг шахслар доираси бўйича татбиқ этилиши', 'Применение коллективного договора по кругу лиц', 'Application of Collective Agreement by Scope of Persons'],
            ['73', 'Ташкилот қайта ташкил этилган тақдирда жамоа шартномаси амал қилишининг сақланиб қолиши', 'Сохранение действия коллективного договора при реорганизации', 'Preservation of Collective Agreement in Reorganization'],
            ['74', 'Ташкилотнинг мулкдори ўзгарганда жамоа шартномаси амал қилишининг сақланиб қолиши', 'Сохранение действия при смене собственника', 'Preservation upon Change of Owner'],
            ['75', 'Ташкилот тугатилаётганда жамоа шартномаси амал қилишининг сақланиб қолиши', 'Сохранение действия при ликвидации организации', 'Preservation upon Liquidation'],
            ['76', 'Жамоа шартномаси амал қилишининг бошқа ҳолларда сақланиб қолиши', 'Сохранение действия в других случаях', 'Preservation in Other Cases'],
            ['77', 'Жамоа шартномасига ўзгартиш ва қўшимчалар киритиш', 'Внесение изменений и дополнений в коллективный договор', 'Amendments to Collective Agreement'],
            ['78', 'Ходимларни жамоа шартномаси билан таништириш', 'Ознакомление работников с коллективным договором', 'Familiarization of Employees with Collective Agreement'],
            ['79', 'Жамоа шартномасининг бажарилиши устидан назорат', 'Контроль за выполнением коллективного договора', 'Monitoring of Collective Agreement Implementation'],
        ];
        
        foreach ($articles as $index => $article) {
            $this->createArticle($chapterId, $article[0], $index + 1, [
                'uz' => [
                    'title' => $article[0] . '-модда. ' . $article[1],
                    'content' => 'Ушбу модда ' . $article[1] . ' масаласини тартибга солади.',
                    'summary' => $article[1],
                    'keywords' => ['жамоа шартномаси'],
                ],
                'ru' => [
                    'title' => 'Статья ' . $article[0] . '. ' . $article[2],
                    'content' => 'Данная статья регулирует вопросы ' . mb_strtolower($article[2]) . '.',
                    'summary' => $article[2],
                    'keywords' => ['коллективный договор'],
                ],
                'en' => [
                    'title' => 'Article ' . $article[0] . '. ' . $article[3],
                    'content' => 'This article regulates ' . strtolower($article[3]) . '.',
                    'summary' => $article[3],
                    'keywords' => ['collective agreement'],
                ],
            ]);
        }
    }
    
    private function createChapter9Articles(int $chapterId): void
    {
        // Articles 80-93 for Chapter 9 (Collective Agreements)
        $articles = [
            ['80', 'Жамоа келишувларининг тушунчаси ва шакли', 'Понятие и форма коллективных соглашений', 'Concept and Form of Collective Agreements'],
            ['81', 'Жамоа келишувлари турлари', 'Виды коллективных соглашений', 'Types of Collective Agreements'],
            ['82', 'Жамоа келишувларининг мазмуни', 'Содержание коллективных соглашений', 'Content of Collective Agreements'],
            ['83', 'Жамоа келишувини тузиш тартиби', 'Порядок заключения коллективного соглашения', 'Procedure for Concluding Collective Agreement'],
            ['84', 'Жамоа келишувлари шартларининг ҳақиқий эмаслиги', 'Недействительность условий коллективных соглашений', 'Invalidity of Collective Agreement Terms'],
            ['85', 'Жамоа келишувининг кучга кириши ва амал қилиш муддати', 'Вступление в силу и срок действия', 'Entry into Force and Duration'],
            ['86', 'Жамоа келишувлари қўшилиш тўғрисида', 'О присоединении к коллективным соглашениям', 'On Joining Collective Agreements'],
            ['87', 'Жамоа келишувларини эълон қилиш', 'Опубликование коллективных соглашений', 'Publication of Collective Agreements'],
            ['88', 'Жамоа келишувларини хабардор қилиш тартибида рўйхатдан ўтказиш', 'Регистрация коллективных соглашений в уведомительном порядке', 'Notification Registration of Collective Agreements'],
            ['89', 'Жамоа келишувларига ўзгартиш ва қўшимчалар киритиш', 'Внесение изменений и дополнений', 'Amendments and Additions'],
            ['90', 'Жамоа келишувларининг шахслар доираси бўйича амал қилиши', 'Действие коллективных соглашений по кругу лиц', 'Scope of Application by Persons'],
            ['91', 'Жамоа келишувларининг кучга кириши ва амал қилиш муддати', 'Вступление в силу и срок действия', 'Entry into Force and Duration'],
            ['92', 'Жамоа келишувларини эълон қилиш', 'Опубликование', 'Publication'],
            ['93', 'Жамоа келишувларининг бажарилиши устидан назорат', 'Контроль за выполнением', 'Monitoring of Implementation'],
        ];
        
        foreach ($articles as $index => $article) {
            $this->createArticle($chapterId, $article[0], $index + 1, [
                'uz' => [
                    'title' => $article[0] . '-модда. ' . $article[1],
                    'content' => 'Ушбу модда ' . $article[1] . ' масаласини тартибга солади.',
                    'summary' => $article[1],
                    'keywords' => ['жамоа келишуви'],
                ],
                'ru' => [
                    'title' => 'Статья ' . $article[0] . '. ' . $article[2],
                    'content' => 'Данная статья регулирует вопросы ' . mb_strtolower($article[2]) . '.',
                    'summary' => $article[2],
                    'keywords' => ['коллективное соглашение'],
                ],
                'en' => [
                    'title' => 'Article ' . $article[0] . '. ' . $article[3],
                    'content' => 'This article regulates ' . strtolower($article[3]) . '.',
                    'summary' => $article[3],
                    'keywords' => ['collective agreement'],
                ],
            ]);
        }
    }
    
    private function createChapter10Articles(int $chapterId): void
    {
        // Articles 94-102 for Chapter 10 (Employment - General Provisions)
        $articles = [
            ['94', 'Ишга жойлашиш ҳуқуқи', 'Право на трудоустройство', 'Right to Employment'],
            ['95', 'Ишга жойлаштириш бўйича давлат кафолатлари', 'Государственные гарантии по трудоустройству', 'State Guarantees for Employment'],
            ['96', 'Аҳолининг ижтимоий эҳтиёжманд тоифаларини ишга жойлаштириш соҳасидаги қўшимча кафолатлар', 'Дополнительные гарантии для социально уязвимых категорий', 'Additional Guarantees for Socially Vulnerable Categories'],
            ['97', 'Ишга жойлаштиришда меҳнат органларининг вазифалари', 'Задачи органов труда по трудоустройству', 'Tasks of Labor Bodies in Employment'],
            ['98', 'Иш қидираётган шахсларни рўйхатга олиш', 'Регистрация лиц, ищущих работу', 'Registration of Job Seekers'],
            ['99', 'Ишсиз деб тан олиш', 'Признание безработным', 'Recognition as Unemployed'],
            ['100', 'Ишсиз мақомини бериш рад этилиши мумкин бўлган ҳолатлар', 'Случаи отказа в присвоении статуса безработного', 'Cases of Refusal to Grant Unemployed Status'],
            ['101', 'Ишсиз мақомини тўхтатиш', 'Прекращение статуса безработного', 'Termination of Unemployed Status'],
            ['102', 'Хусусий бандлик агентликлари', 'Частные агентства занятости', 'Private Employment Agencies'],
        ];
        
        foreach ($articles as $index => $article) {
            $this->createArticle($chapterId, $article[0], $index + 1, [
                'uz' => [
                    'title' => $article[0] . '-модда. ' . $article[1],
                    'content' => 'Ушбу модда ' . $article[1] . ' масаласини тартибга солади.',
                    'summary' => $article[1],
                    'keywords' => ['ишга жойлаштириш', 'бандлик'],
                ],
                'ru' => [
                    'title' => 'Статья ' . $article[0] . '. ' . $article[2],
                    'content' => 'Данная статья регулирует вопросы ' . mb_strtolower($article[2]) . '.',
                    'summary' => $article[2],
                    'keywords' => ['трудоустройство', 'занятость'],
                ],
                'en' => [
                    'title' => 'Article ' . $article[0] . '. ' . $article[3],
                    'content' => 'This article regulates ' . strtolower($article[3]) . '.',
                    'summary' => $article[3],
                    'keywords' => ['employment', 'job placement'],
                ],
            ]);
        }
    }
}

