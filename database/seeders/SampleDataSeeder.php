<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Chapter;
use App\Models\Comment;
use App\Models\Role;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating sample data...');

        // Create sample users
        $this->createSampleUsers();

        // Create sections with chapters and articles
        $this->createSampleContent();

        // Create sample comments
        $this->createSampleComments();

        $this->command->info('Sample data created successfully!');
    }

    /**
     * Create sample users.
     */
    private function createSampleUsers(): void
    {
        // Get all roles
        $roles = [
            'admin' => Role::where('slug', Role::ADMIN)->first(),
            'muallif' => Role::where('slug', Role::MUALLIF)->first(),
            'tarjimon' => Role::where('slug', Role::TARJIMON)->first(),
            'ishchi_guruh' => Role::where('slug', Role::ISHCHI_GURUH)->first(),
            'ekspert' => Role::where('slug', Role::EKSPERT)->first(),
            'moderator' => Role::where('slug', Role::MODERATOR)->first(),
            'user' => Role::where('slug', Role::USER)->first(),
        ];

        // Check if essential roles exist
        if (!$roles['admin'] || !$roles['user']) {
            $this->command->warn('  Essential roles not found, skipping user creation');
            return;
        }

        // Sample users for each role
        $sampleUsers = [
            // Admin
            [
                'email' => 'admin@admin.com',
                'name' => 'Admin User',
                'password' => 'Admin123!',
                'role' => 'admin',
                'locale' => 'uz',
            ],
            // Muallif (Author)
            [
                'email' => 'muallif@gmail.com',
                'name' => 'Muallif Karimov',
                'password' => 'Muallif123!',
                'role' => 'muallif',
                'locale' => 'uz',
            ],
            // Tarjimon (Translator)
            [
                'email' => 'translater@crudbooster.com',
                'name' => 'Tarjimon Rahimov',
                'password' => 'Tarjimon123!',
                'role' => 'tarjimon',
                'locale' => 'ru',
            ],
            // Ishchi Guruh (Working Group)
            [
                'email' => 'workers@gmail.com',
                'name' => 'Ishchi Guruh User',
                'password' => 'Workers123!',
                'role' => 'ishchi_guruh',
                'locale' => 'uz',
            ],
            // Ekspert (Expert)
            [
                'email' => 'expert@gmail.com',
                'name' => 'Ekspert Alimov',
                'password' => 'Expert123!',
                'role' => 'ekspert',
                'locale' => 'uz',
            ],
            // Moderator
            [
                'email' => 'moderator@mehnat-kodeksi.uz',
                'name' => 'Moderator User',
                'password' => 'ModeratorPass123!',
                'role' => 'moderator',
                'locale' => 'ru',
            ],
            // Regular users
            [
                'email' => 'user1@example.com',
                'name' => 'Foydalanuvchi Bir',
                'password' => 'UserPass123!',
                'role' => 'user',
                'locale' => 'uz',
            ],
            [
                'email' => 'user2@example.com',
                'name' => 'Пользователь Два',
                'password' => 'UserPass123!',
                'role' => 'user',
                'locale' => 'ru',
            ],
            [
                'email' => 'user3@example.com',
                'name' => 'User Three',
                'password' => 'UserPass123!',
                'role' => 'user',
                'locale' => 'en',
            ],
        ];

        $createdCount = 0;
        foreach ($sampleUsers as $userData) {
            $role = $roles[$userData['role']] ?? $roles['user'];
            
            if (!$role) {
                $this->command->warn("  Role {$userData['role']} not found, skipping user {$userData['email']}");
                continue;
            }

            User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make($userData['password']),
                    'role_id' => $role->id,
                    'email_verified_at' => now(),
                    'is_active' => true,
                    'preferred_locale' => $userData['locale'],
                ]
            );
            $createdCount++;
        }

        $this->command->info("  Created/verified {$createdCount} sample users");
        
        // Display user credentials table
        $this->command->table(
            ['Email', 'Role', 'Password'],
            array_map(fn($u) => [$u['email'], $u['role'], $u['password']], $sampleUsers)
        );
    }

    /**
     * Create sample content (sections, chapters, articles).
     */
    private function createSampleContent(): void
    {
        // Check if sections already exist
        if (Section::count() > 0) {
            $this->command->info('  Sections already exist, skipping content creation');
            return;
        }

        // Section 1: General Provisions
        $section1 = Section::create([
            'order_number' => 1,
            'is_active' => true,
        ]);

        $section1->translations()->createMany([
            [
                'locale' => 'uz',
                'title' => 'I Bo\'lim. Umumiy qoidalar',
                'description' => 'Mehnat kodeksining umumiy qoidalari',
            ],
            [
                'locale' => 'ru',
                'title' => 'Раздел I. Общие положения',
                'description' => 'Общие положения Трудового кодекса',
            ],
            [
                'locale' => 'en',
                'title' => 'Section I. General Provisions',
                'description' => 'General provisions of the Labor Code',
            ],
        ]);

        // Chapter 1 in Section 1
        $chapter1 = Chapter::create([
            'section_id' => $section1->id,
            'order_number' => 1,
            'is_active' => true,
        ]);

        $chapter1->translations()->createMany([
            [
                'locale' => 'uz',
                'title' => '1-bob. Asosiy qoidalar',
                'description' => 'Mehnat munosabatlarining asosiy qoidalari',
            ],
            [
                'locale' => 'ru',
                'title' => 'Глава 1. Основные положения',
                'description' => 'Основные положения трудовых отношений',
            ],
            [
                'locale' => 'en',
                'title' => 'Chapter 1. Basic Provisions',
                'description' => 'Basic provisions of labor relations',
            ],
        ]);

        // Articles in Chapter 1
        $articles = [
            [
                'number' => '1',
                'uz_title' => '1-modda. Mehnat kodeksining vazifasi',
                'ru_title' => 'Статья 1. Задачи Трудового кодекса',
                'en_title' => 'Article 1. Objectives of the Labor Code',
                'uz_content' => 'O\'zbekiston Respublikasi Mehnat kodeksining vazifasi mehnat munosabatlarini tartibga solish, fuqarolarning mehnat huquqlarini ta\'minlash va himoya qilishdan iborat.',
                'ru_content' => 'Задачами Трудового кодекса Республики Узбекистан являются регулирование трудовых отношений, обеспечение и защита трудовых прав граждан.',
                'en_content' => 'The objectives of the Labor Code of the Republic of Uzbekistan are to regulate labor relations, ensure and protect the labor rights of citizens.',
            ],
            [
                'number' => '2',
                'uz_title' => '2-modda. Mehnat to\'g\'risidagi qonunchilik',
                'ru_title' => 'Статья 2. Законодательство о труде',
                'en_title' => 'Article 2. Labor Legislation',
                'uz_content' => 'Mehnat to\'g\'risidagi qonunchilik ushbu Kodeks va boshqa qonun hujjatlaridan iborat.',
                'ru_content' => 'Законодательство о труде состоит из настоящего Кодекса и иных законодательных актов.',
                'en_content' => 'Labor legislation consists of this Code and other legislative acts.',
            ],
            [
                'number' => '3',
                'uz_title' => '3-modda. Mehnat munosabatlarini tartibga solishning asosiy tamoyillari',
                'ru_title' => 'Статья 3. Основные принципы регулирования трудовых отношений',
                'en_title' => 'Article 3. Basic Principles of Labor Relations Regulation',
                'uz_content' => 'Mehnat munosabatlarini tartibga solishning asosiy tamoyillari: mehnat erkinligi, majburiy mehnatning taqiqlanishi, tenqlik va boshqalar.',
                'ru_content' => 'Основными принципами регулирования трудовых отношений являются: свобода труда, запрет принудительного труда, равенство и другие.',
                'en_content' => 'The basic principles of labor relations regulation are: freedom of labor, prohibition of forced labor, equality, and others.',
            ],
        ];

        foreach ($articles as $index => $articleData) {
            $article = Article::create([
                'chapter_id' => $chapter1->id,
                'article_number' => $articleData['number'],
                'order_number' => $index + 1,
                'is_active' => true,
                'views_count' => rand(100, 5000),
            ]);

            $article->translations()->createMany([
                [
                    'locale' => 'uz',
                    'title' => $articleData['uz_title'],
                    'content' => $articleData['uz_content'],
                    'summary' => mb_substr($articleData['uz_content'], 0, 100) . '...',
                    'keywords' => ['mehnat', 'kodeks', 'qonun'],
                ],
                [
                    'locale' => 'ru',
                    'title' => $articleData['ru_title'],
                    'content' => $articleData['ru_content'],
                    'summary' => mb_substr($articleData['ru_content'], 0, 100) . '...',
                    'keywords' => ['труд', 'кодекс', 'закон'],
                ],
                [
                    'locale' => 'en',
                    'title' => $articleData['en_title'],
                    'content' => $articleData['en_content'],
                    'summary' => mb_substr($articleData['en_content'], 0, 100) . '...',
                    'keywords' => ['labor', 'code', 'law'],
                ],
            ]);
        }

        // Section 2: Labor Contract
        $section2 = Section::create([
            'order_number' => 2,
            'is_active' => true,
        ]);

        $section2->translations()->createMany([
            [
                'locale' => 'uz',
                'title' => 'II Bo\'lim. Mehnat shartnomasi',
                'description' => 'Mehnat shartnomasini tuzish va bekor qilish',
            ],
            [
                'locale' => 'ru',
                'title' => 'Раздел II. Трудовой договор',
                'description' => 'Заключение и прекращение трудового договора',
            ],
            [
                'locale' => 'en',
                'title' => 'Section II. Labor Contract',
                'description' => 'Conclusion and termination of labor contract',
            ],
        ]);

        $this->command->info('  Created 2 sections with chapters and articles');
    }

    /**
     * Create sample comments.
     */
    private function createSampleComments(): void
    {
        // Check if comments already exist
        if (Comment::count() > 0) {
            $this->command->info('  Comments already exist, skipping');
            return;
        }

        $users = User::where('email', 'like', 'user%@example.com')->get();
        $articles = Article::all();

        if ($users->isEmpty() || $articles->isEmpty()) {
            $this->command->warn('  No users or articles found, skipping comments');
            return;
        }

        foreach ($articles as $article) {
            // Create 2-5 comments per article
            $commentCount = rand(2, 5);
            
            for ($i = 0; $i < $commentCount; $i++) {
                $user = $users->random();
                
                Comment::create([
                    'article_id' => $article->id,
                    'user_id' => $user->id,
                    'content' => $this->getRandomComment(),
                    'status' => Comment::STATUS_APPROVED,
                    'likes_count' => rand(0, 50),
                ]);
            }
        }

        $this->command->info('  Created sample comments');
    }

    /**
     * Get random comment text.
     */
    private function getRandomComment(): string
    {
        $comments = [
            'Очень полезная информация, спасибо за разъяснение!',
            'Можете ли вы подробнее объяснить эту статью?',
            'Как это применяется на практике?',
            'Есть ли исключения из этого правила?',
            'Спасибо за четкое изложение информации.',
            'Нашел ответ на свой вопрос в этой статье.',
            'Было бы хорошо добавить примеры из практики.',
            'Очень важная статья для понимания трудовых прав.',
            'Можно ли получить консультацию по применению этой статьи?',
            'Отличное объяснение, теперь все понятно!',
        ];

        return $comments[array_rand($comments)];
    }
}
