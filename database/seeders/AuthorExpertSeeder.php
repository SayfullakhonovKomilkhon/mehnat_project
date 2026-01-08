<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Models\User;
use App\Models\Role;

class AuthorExpertSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Adds author comments and expert conclusions from mehnat_kodeksi.txt
     */
    public function run(): void
    {
        $this->command->info('Starting Author Comments and Expertises seeder...');
        
        $filePath = '/Users/mac/Desktop/Mehnat/mehnat_kodeksi.txt';
        if (!File::exists($filePath)) {
            $this->command->error("File not found: {$filePath}");
            return;
        }
        
        // Get muallif and ekspert users
        $muallifRole = Role::where('slug', 'muallif')->first();
        $ekspertRole = Role::where('slug', 'ekspert')->first();
        
        $muallifUser = User::where('role_id', $muallifRole?->id)->first();
        $ekspertUser = User::where('role_id', $ekspertRole?->id)->first();
        
        if (!$muallifUser || !$ekspertUser) {
            $this->command->error("Muallif or Ekspert user not found. Please run SampleDataSeeder first.");
            return;
        }
        
        $this->command->info("Using Muallif user: {$muallifUser->name} (ID: {$muallifUser->id})");
        $this->command->info("Using Ekspert user: {$ekspertUser->name} (ID: {$ekspertUser->id})");
        
        $content = File::get($filePath);
        $articles = $this->parseArticles($content);
        
        $this->command->info("Found " . count($articles) . " articles with commentary");
        
        $authorCount = 0;
        $expertCount = 0;
        
        foreach ($articles as $articleNum => $data) {
            // Get article from database
            $article = DB::table('articles')->where('article_number', $articleNum)->first();
            
            if (!$article) {
                $this->command->warn("Article {$articleNum} not found in database, skipping");
                continue;
            }
            
            // Add author comment (ШАРҲ)
            if (!empty($data['sharh'])) {
                $exists = DB::table('author_comments')
                    ->where('article_id', $article->id)
                    ->where('user_id', $muallifUser->id)
                    ->exists();
                    
                if (!$exists) {
                    DB::table('author_comments')->insert([
                        'article_id' => $article->id,
                        'user_id' => $muallifUser->id,
                        'author_title' => 'Ҳуқуқшунослик фанлари номзоди',
                        'organization' => 'Ўзбекистон Республикаси Адлия вазирлиги',
                        'comment_uz' => $data['sharh'],
                        'comment_ru' => null,
                        'comment_en' => null,
                        'status' => 'approved',
                        'moderated_by' => $muallifUser->id,
                        'moderated_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $authorCount++;
                }
            }
            
            // Add expertise (ХАЛҚАРО СТАНДАРТЛАР, МИЛЛИЙ ҚОНУНЧИЛИК, МИСОЛЛАР)
            $hasExpertData = !empty($data['xalqaro']) || !empty($data['milliy']) || !empty($data['misollar']);
            
            if ($hasExpertData) {
                $exists = DB::table('expertises')
                    ->where('article_id', $article->id)
                    ->where('user_id', $ekspertUser->id)
                    ->exists();
                    
                if (!$exists) {
                    $legalReferences = [];
                    if (!empty($data['xalqaro'])) {
                        $legalReferences['international'] = $data['xalqaro'];
                    }
                    if (!empty($data['milliy'])) {
                        $legalReferences['national'] = $data['milliy'];
                    }
                    
                    $expertComment = "Ушбу модда бўйича экспертиза хулосаси.\n\n";
                    if (!empty($data['xalqaro'])) {
                        $expertComment .= "Халқаро стандартларга мувофиқлиги текширилган.\n";
                    }
                    if (!empty($data['milliy'])) {
                        $expertComment .= "Миллий қонунчиликка мувофиқлиги тасдиқланган.\n";
                    }
                    
                    DB::table('expertises')->insert([
                        'article_id' => $article->id,
                        'user_id' => $ekspertUser->id,
                        'expert_comment' => $expertComment,
                        'legal_references' => json_encode($legalReferences),
                        'court_practice' => null,
                        'recommendations' => $data['misollar'] ?? null,
                        'status' => 'approved',
                        'moderated_by' => $ekspertUser->id,
                        'moderated_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $expertCount++;
                }
            }
        }
        
        $this->command->info("Added {$authorCount} author comments");
        $this->command->info("Added {$expertCount} expertises");
        $this->command->info('Author Comments and Expertises seeder completed!');
    }
    
    /**
     * Parse articles from the text file
     */
    private function parseArticles(string $content): array
    {
        $articles = [];
        $lines = explode("\n", $content);
        
        $currentArticle = null;
        $currentSection = null;
        $buffer = '';
        
        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            
            // Detect new article
            if (preg_match('/^(\d+)-модда\./u', $trimmedLine, $matches)) {
                // Save previous article data
                if ($currentArticle && $currentSection && !empty($buffer)) {
                    $this->saveSection($articles, $currentArticle, $currentSection, $buffer);
                }
                
                $currentArticle = $matches[1];
                $currentSection = null;
                $buffer = '';
                
                if (!isset($articles[$currentArticle])) {
                    $articles[$currentArticle] = [
                        'sharh' => '',
                        'misollar' => '',
                        'xalqaro' => '',
                        'milliy' => '',
                    ];
                }
                continue;
            }
            
            // Detect sections
            if ($trimmedLine === 'ШАРҲ:' || $trimmedLine === 'ШАРҲ') {
                if ($currentArticle && $currentSection && !empty($buffer)) {
                    $this->saveSection($articles, $currentArticle, $currentSection, $buffer);
                }
                $currentSection = 'sharh';
                $buffer = '';
                continue;
            }
            
            if ($trimmedLine === 'МИСОЛЛАР' || $trimmedLine === 'МИСОЛЛАР:') {
                if ($currentArticle && $currentSection && !empty($buffer)) {
                    $this->saveSection($articles, $currentArticle, $currentSection, $buffer);
                }
                $currentSection = 'misollar';
                $buffer = '';
                continue;
            }
            
            if ($trimmedLine === 'ХАЛҚАРО СТАНДАРТЛАР' || $trimmedLine === 'ХАЛҚАРО СТАНДАРТЛАР:') {
                if ($currentArticle && $currentSection && !empty($buffer)) {
                    $this->saveSection($articles, $currentArticle, $currentSection, $buffer);
                }
                $currentSection = 'xalqaro';
                $buffer = '';
                continue;
            }
            
            if ($trimmedLine === 'МИЛЛИЙ ҚОНУНЧИЛИК' || $trimmedLine === 'МИЛЛИЙ ҚОНУНЧИЛИК:') {
                if ($currentArticle && $currentSection && !empty($buffer)) {
                    $this->saveSection($articles, $currentArticle, $currentSection, $buffer);
                }
                $currentSection = 'milliy';
                $buffer = '';
                continue;
            }
            
            // Accumulate content for current section
            if ($currentArticle && $currentSection) {
                $buffer .= $line . "\n";
            }
        }
        
        // Save last article
        if ($currentArticle && $currentSection && !empty($buffer)) {
            $this->saveSection($articles, $currentArticle, $currentSection, $buffer);
        }
        
        return $articles;
    }
    
    /**
     * Save section content to articles array
     */
    private function saveSection(array &$articles, string $articleNum, string $section, string $content): void
    {
        if (isset($articles[$articleNum][$section])) {
            $articles[$articleNum][$section] = trim($content);
        }
    }
}

