<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Models\ArticleComment;

class AuthorExpertSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Adds comments from mehnat_kodeksi.txt to article_comments table
     */
    public function run(): void
    {
        $this->command->info('Starting Article Comments seeder...');
        
        // Try relative path first (for production), then absolute (for local dev)
        $filePath = database_path('seeders/mehnat_kodeksi.txt');
        if (!File::exists($filePath)) {
            // Fallback for local development
            $filePath = '/Users/mac/Desktop/Mehnat/mehnat_kodeksi.txt';
        }
        if (!File::exists($filePath)) {
            $this->command->error("File not found: {$filePath}");
            return;
        }
        
        $content = File::get($filePath);
        $articles = $this->parseArticles($content);
        
        $this->command->info("Found " . count($articles) . " articles with commentary");
        
        $commentCount = 0;
        
        foreach ($articles as $articleNum => $data) {
            // Get article from database
            $article = DB::table('articles')->where('article_number', $articleNum)->first();
            
            if (!$article) {
                $this->command->warn("Article {$articleNum} not found in database, skipping");
                continue;
            }
            
            // Build comment text - combine ШАРҲ and other sections
            $commentParts = [];
            
            if (!empty($data['sharh'])) {
                $commentParts[] = $data['sharh'];
            }
            
            if (!empty($data['misollar'])) {
                $commentParts[] = "\n\n**МИСОЛЛАР:**\n" . $data['misollar'];
            }
            
            if (!empty($data['xalqaro'])) {
                $commentParts[] = "\n\n**ХАЛҚАРО СТАНДАРТЛАР:**\n" . $data['xalqaro'];
            }
            
            if (!empty($data['milliy'])) {
                $commentParts[] = "\n\n**МИЛЛИЙ ҚОНУНЧИЛИК:**\n" . $data['milliy'];
            }
            
            $fullComment = implode('', $commentParts);
            
            if (empty($fullComment)) {
                continue;
            }
            
            // Check if comment already exists
            $exists = ArticleComment::where('article_id', $article->id)->exists();
            
            if (!$exists) {
                ArticleComment::create([
                    'article_id' => $article->id,
                    'comment_uz' => $fullComment,
                    'comment_ru' => null,
                    'comment_en' => null,
                    'author_name' => 'Меҳнат кодекси шарҳи муаллифлари',
                    'author_title' => 'Ҳуқуқшунослик фанлари номзоди',
                    'organization' => 'Ўзбекистон Республикаси Адлия вазирлиги',
                    'legal_references' => !empty($data['xalqaro']) || !empty($data['milliy']) ? [
                        'international' => $data['xalqaro'] ?? null,
                        'national' => $data['milliy'] ?? null,
                    ] : null,
                    'court_practice' => $data['misollar'] ?? null,
                    'recommendations' => null,
                    'status' => ArticleComment::STATUS_APPROVED,
                ]);
                $commentCount++;
                $this->command->info("Added comment for Article {$articleNum}");
            } else {
                $this->command->warn("Comment for Article {$articleNum} already exists, skipping");
            }
        }
        
        $this->command->info("Added {$commentCount} article comments");
    }
    
    /**
     * Parse articles and their commentary from the text file
     */
    private function parseArticles(string $content): array
    {
        $articles = [];
        
        // Split by articles (N-modda pattern)
        $pattern = '/(\d+(?:-\d+)?)-modda\./u';
        $parts = preg_split($pattern, $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        
        for ($i = 1; $i < count($parts); $i += 2) {
            $articleNum = $parts[$i];
            $articleContent = $parts[$i + 1] ?? '';
            
            $data = [
                'sharh' => $this->extractSection($articleContent, 'ШАРҲ'),
                'misollar' => $this->extractSection($articleContent, 'МИСОЛЛАР'),
                'xalqaro' => $this->extractSection($articleContent, 'ХАЛҚАРО СТАНДАРТЛАР'),
                'milliy' => $this->extractSection($articleContent, 'МИЛЛИЙ ҚОНУНЧИЛИК'),
            ];
            
            // Only include if at least one section has content
            if ($data['sharh'] || $data['misollar'] || $data['xalqaro'] || $data['milliy']) {
                $articles[$articleNum] = $data;
            }
        }
        
        return $articles;
    }
    
    /**
     * Extract a specific section from article content
     */
    private function extractSection(string $content, string $sectionName): ?string
    {
        // Pattern to match section name followed by content until next section or article
        $pattern = '/' . preg_quote($sectionName, '/') . ':\s*(.*?)(?=(?:ШАРҲ|МИСОЛЛАР|ХАЛҚАРО СТАНДАРТЛАР|МИЛЛИЙ ҚОНУНЧИЛИК):|(?:\d+(?:-\d+)?-modda\.)|$)/us';
        
        if (preg_match($pattern, $content, $matches)) {
            $text = trim($matches[1]);
            return !empty($text) ? $text : null;
        }
        
        return null;
    }
}
