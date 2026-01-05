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
        
        // Add comments to articles
        $articles = DB::table('articles')
            ->where('is_active', true)
            ->where('translation_status', 'approved')
            ->limit(3)
            ->get();
        
        $comments = [
            "Ushbu modda Mehnat kodeksining asosiy maqsadlarini belgilaydi. Amaliyotda bu modda mehnat huquqlarini himoya qilishda muhim ahamiyatga ega. Ish beruvchilar va xodimlar o'rtasidagi munosabatlarni tartibga solishda ushbu moddaga asoslanish kerak.\n\nДанная статья определяет основные цели Трудового кодекса. На практике эта статья имеет важное значение для защиты трудовых прав.",
            "Mehnat qonunchiligi rivojlanib bormoqda. 2023-yildagi o'zgarishlar xodimlarning huquqlarini yanada kengaytirdi. Bu modda asosida ko'plab sud qarorlari qabul qilingan.\n\nТрудовое законодательство продолжает развиваться. Изменения 2023 года еще больше расширили права работников.",
            "Huquqshunoslik nuqtai nazaridan, bu modda konstitutsiyaviy mehnat huquqlarini amalga oshirish mexanizmini belgilaydi. Xalqaro mehnat standartlariga muvofiq keladi.\n\nС правовой точки зрения, данная статья определяет механизм реализации конституционных трудовых прав.",
        ];
        
        $commentIndex = 0;
        foreach ($articles as $article) {
            if (isset($comments[$commentIndex])) {
                // Check if comment already exists for this article
                $existingComment = DB::table('comments')
                    ->where('article_id', $article->id)
                    ->where('user_id', $adminId)
                    ->first();
                    
                if (!$existingComment) {
                    DB::table('comments')->insert([
                        'article_id' => $article->id,
                        'user_id' => $adminId,
                        'parent_id' => null,
                        'content' => $comments[$commentIndex],
                        'status' => 'approved',
                        'likes_count' => rand(5, 25),
                        'created_at' => now()->subDays(rand(1, 30)),
                        'updated_at' => now(),
                    ]);
                }
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
        // This migration adds seed data, down migration is not needed
    }
};

