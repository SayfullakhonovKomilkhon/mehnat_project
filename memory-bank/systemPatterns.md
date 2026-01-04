# Labor Code Portal - System Patterns

## Architecture

### API Structure
```
/api/v1/
├── auth/           # Authentication endpoints
├── profile/        # User profile management
├── 2fa/            # Two-factor authentication
├── sections/       # Public sections
├── chapters/       # Public chapters
├── articles/       # Public articles
├── comments/       # User comments
├── search/         # Search functionality
├── chatbot/        # AI chatbot
└── admin/          # Admin-only endpoints
    ├── sections/
    ├── chapters/
    ├── articles/
    ├── comments/
    ├── users/
    ├── analytics/
    └── logs/
```

### Translation Pattern

Each translatable model has a companion translation model:
- `Section` → `SectionTranslation`
- `Chapter` → `ChapterTranslation`
- `Article` → `ArticleTranslation`

Translation models contain locale-specific content:
```php
$article->translation('ru')->title; // Russian title
$article->translation()->title;     // Current locale title
```

### HasTranslations Trait
```php
trait HasTranslations {
    public function translation(?string $locale = null): ?Model
    public function getTitle(?string $locale = null): ?string
    public function getDescription(?string $locale = null): ?string
    public function hasTranslation(string $locale): bool
}
```

## Security Patterns

### Rate Limiting
- Per-IP for guests
- Per-user for authenticated
- Special limits for auth endpoints

### Login Protection
- Track all login attempts in `login_attempts` table
- Block IP after 5 failures in 1 minute for 15 minutes
- Block email after 5 failures in 5 minutes for 30 minutes

### Role-Based Access
```php
// Middleware
'role:admin,moderator'

// Policy
$this->authorize('update', $article);

// In code
$user->isAdmin()
$user->isAdminOrModerator()
$user->hasPermission('articles.create')
```

## Caching Strategy

- Cache sections list for 1 hour
- Cache individual articles for 30 minutes
- Invalidate on update
- Use locale in cache keys

## Service Layer

Complex logic is encapsulated in services:
- `ArticleSearchService` - Full-text search
- `ChatbotService` - AI responses
- `CommentModerationService` - Comment handling
- `StatisticsService` - Analytics
- `TwoFactorAuthService` - 2FA operations



