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
        // Delete test chapters with names "привет" and "привет 2"
        $testChapters = DB::table('chapter_translations')
            ->whereIn('title', ['привет', 'привет 2'])
            ->pluck('chapter_id')
            ->toArray();
        
        if (!empty($testChapters)) {
            // Delete articles in these chapters first
            $articleIds = DB::table('articles')
                ->whereIn('chapter_id', $testChapters)
                ->pluck('id')
                ->toArray();
            
            if (!empty($articleIds)) {
                DB::table('article_translations')->whereIn('article_id', $articleIds)->delete();
                DB::table('comments')->whereIn('article_id', $articleIds)->delete();
                DB::table('articles')->whereIn('id', $articleIds)->delete();
            }
            
            DB::table('chapter_translations')->whereIn('chapter_id', $testChapters)->delete();
            DB::table('chapters')->whereIn('id', $testChapters)->delete();
        }
        
        // Get admin user ID (or first user)
        $adminId = DB::table('users')->where('email', 'admin@mehnat.uz')->value('id');
        if (!$adminId) {
            $adminId = DB::table('users')->first()?->id ?? 1;
        }
        
        // Add author comments to articles
        $articles = DB::table('articles')
            ->where('is_active', true)
            ->where('translation_status', 'approved')
            ->limit(5)
            ->get();
        
        $authorComments = [
            [
                'uz' => "Ushbu modda Mehnat kodeksining asosiy maqsadlarini belgilaydi. Amaliyotda bu modda mehnat huquqlarini himoya qilishda muhim ahamiyatga ega. Ish beruvchilar va xodimlar o'rtasidagi munosabatlarni tartibga solishda ushbu moddaga asoslanish kerak.",
                'ru' => "Данная статья определяет основные цели Трудового кодекса. На практике эта статья имеет важное значение для защиты трудовых прав. При регулировании отношений между работодателями и работниками следует опираться на данную статью.",
                'en' => "This article defines the main objectives of the Labor Code. In practice, this article is of great importance for the protection of labor rights. When regulating relations between employers and employees, one should rely on this article."
            ],
            [
                'uz' => "Mehnat qonunchiligi rivojlanib bormoqda. 2023-yildagi o'zgarishlar xodimlarning huquqlarini yanada kengaytirdi. Bu modda asosida ko'plab sud qarorlari qabul qilingan.",
                'ru' => "Трудовое законодательство продолжает развиваться. Изменения 2023 года еще больше расширили права работников. На основе этой статьи принято множество судебных решений.",
                'en' => "Labor legislation continues to develop. The 2023 amendments further expanded the rights of workers. Many court decisions have been made based on this article."
            ],
            [
                'uz' => "Amaliyotda bu modda keng qo'llaniladi. Mehnat huquqlari bo'yicha nizolarni hal qilishda bu modda asosiy hujjat hisoblanadi.",
                'ru' => "На практике эта статья широко применяется. При разрешении трудовых споров эта статья является основным документом.",
                'en' => "In practice, this article is widely applied. When resolving labor disputes, this article is the main document."
            ]
        ];
        
        $expertComments = [
            [
                'uz' => "Huquqshunoslik nuqtai nazaridan, bu modda konstitutsiyaviy mehnat huquqlarini amalga oshirish mexanizmini belgilaydi. Xalqaro mehnat standartlariga muvofiq keladi. ILO konvensiyalari bilan uyg'unlashtirilgan.",
                'ru' => "С правовой точки зрения, данная статья определяет механизм реализации конституционных трудовых прав. Соответствует международным трудовым стандартам. Гармонизирована с конвенциями МОТ.",
                'en' => "From a legal perspective, this article defines the mechanism for implementing constitutional labor rights. Complies with international labor standards. Harmonized with ILO conventions."
            ],
            [
                'uz' => "Ekspert tahlili: Bu modda O'zbekiston mehnat huquqining fundamental asoslaridan biri. Xalqaro amaliyotda shunga o'xshash normalar mavjud. Yevropa Ittifoqi direktivalari bilan taqqoslash mumkin.",
                'ru' => "Экспертный анализ: Эта статья является одной из фундаментальных основ трудового права Узбекистана. В международной практике существуют аналогичные нормы. Можно сравнить с директивами Европейского Союза.",
                'en' => "Expert analysis: This article is one of the fundamental foundations of Uzbekistan's labor law. Similar norms exist in international practice. Can be compared with European Union directives."
            ],
            [
                'uz' => "Ilmiy sharh: Ushbu normaning tarixiy rivojlanishi va zamonaviy talqini haqida batafsil ma'lumot. Olimlar fikriga ko'ra, bu modda mehnat huquqining asosiy prinsiplarini aks ettiradi.",
                'ru' => "Научный комментарий: Подробная информация об историческом развитии и современной интерпретации данной нормы. По мнению ученых, эта статья отражает основные принципы трудового права.",
                'en' => "Scientific commentary: Detailed information about the historical development and modern interpretation of this norm. According to scholars, this article reflects the basic principles of labor law."
            ]
        ];
        
        $commentIndex = 0;
        foreach ($articles as $article) {
            // Add author comment
            if (isset($authorComments[$commentIndex])) {
                DB::table('comments')->insert([
                    'article_id' => $article->id,
                    'user_id' => $adminId,
                    'parent_id' => null,
                    'type' => 'author',
                    'content' => json_encode($authorComments[$commentIndex]),
                    'status' => 'approved',
                    'likes_count' => rand(5, 25),
                    'created_at' => now()->subDays(rand(1, 30)),
                    'updated_at' => now(),
                ]);
            }
            
            // Add expert comment
            if (isset($expertComments[$commentIndex])) {
                DB::table('comments')->insert([
                    'article_id' => $article->id,
                    'user_id' => $adminId,
                    'parent_id' => null,
                    'type' => 'expert',
                    'content' => json_encode($expertComments[$commentIndex]),
                    'status' => 'approved',
                    'likes_count' => rand(10, 50),
                    'created_at' => now()->subDays(rand(1, 30)),
                    'updated_at' => now(),
                ]);
            }
            
            $commentIndex++;
        }
        
        // Clear cache
        $locales = ['uz', 'ru', 'en'];
        foreach ($locales as $locale) {
            \Illuminate\Support\Facades\Cache::forget("sections.all.{$locale}");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove added comments
        DB::table('comments')->whereIn('type', ['author', 'expert'])->delete();
    }
};

