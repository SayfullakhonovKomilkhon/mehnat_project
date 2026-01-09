<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\ArticleTranslation;
use App\Models\Chapter;
use App\Models\ChapterTranslation;
use App\Models\Section;
use App\Models\SectionTranslation;
use Illuminate\Database\Seeder;

class Article1Seeder extends Seeder
{
    public function run(): void
    {
        // Create Section 1
        $section = Section::create([
            'order_number' => 1,
            'is_active' => true,
        ]);

        SectionTranslation::create([
            'section_id' => $section->id,
            'locale' => 'uz',
            'title' => 'UMUMIY QOIDALAR',
            'description' => 'Mehnat kodeksining umumiy qoidalari',
        ]);

        SectionTranslation::create([
            'section_id' => $section->id,
            'locale' => 'ru',
            'title' => 'ОБЩИЕ ПОЛОЖЕНИЯ',
            'description' => 'Общие положения Трудового кодекса',
        ]);

        // Create Chapter 1
        $chapter = Chapter::create([
            'section_id' => $section->id,
            'order_number' => 1,
            'is_active' => true,
        ]);

        ChapterTranslation::create([
            'chapter_id' => $chapter->id,
            'locale' => 'uz',
            'title' => 'Asosiy qoidalar',
            'description' => '1-bob',
        ]);

        ChapterTranslation::create([
            'chapter_id' => $chapter->id,
            'locale' => 'ru',
            'title' => 'Основные положения',
            'description' => 'Глава 1',
        ]);

        // Create Article 1
        $article = Article::create([
            'chapter_id' => $chapter->id,
            'article_number' => '1',
            'order_number' => 1,
            'is_active' => true,
        ]);

        ArticleTranslation::create([
            'article_id' => $article->id,
            'locale' => 'uz',
            'title' => 'Ushbu Kodeks bilan tartibga solinadigan munosabatlar',
            'content' => 'Ushbu Kodeks xodimlar, ish beruvchilar va davlat manfaatlarining muvozanatini ta\'minlash hamda ularni muvofiqlashtirish asosida yakka tartibdagi mehnatga oid munosabatlarni va ular bilan bevosita bog\'liq bo\'lgan ijtimoiy munosabatlarni tartibga soladi.',
            'summary' => 'Mehnat kodeksining asosiy maqsadi',
        ]);

        ArticleTranslation::create([
            'article_id' => $article->id,
            'locale' => 'ru',
            'title' => 'Отношения, регулируемые настоящим Кодексом',
            'content' => 'Настоящий Кодекс регулирует индивидуальные трудовые отношения и непосредственно связанные с ними социальные отношения на основе обеспечения баланса интересов работников, работодателей и государства, а также их согласования.',
            'summary' => 'Основная цель Трудового кодекса',
        ]);

        $this->command->info('Article 1 created successfully!');
    }
}

