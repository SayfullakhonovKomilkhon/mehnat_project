# Labor Code Portal - Technical Context

## Technology Stack

### Backend
- **Laravel 10.x** - PHP Framework
- **PHP 8.2+** - Language
- **PostgreSQL 15** - Primary database
- **Redis** - Cache, sessions, queues

### Authentication
- **Laravel Sanctum** - API token authentication
- **Google 2FA** - Two-factor authentication

### Key Packages
```json
{
  "laravel/framework": "^10.48",
  "laravel/sanctum": "^3.3",
  "pragmarx/google2fa-laravel": "^2.2",
  "bacon/bacon-qr-code": "^2.0",
  "predis/predis": "^2.2"
}
```

## Database Schema

### Core Tables
- `users` - User accounts
- `roles` - User roles (admin, moderator, user)
- `personal_access_tokens` - Sanctum tokens

### Content Tables
- `sections` - Top-level sections
- `section_translations` - Section translations
- `chapters` - Chapters within sections
- `chapter_translations` - Chapter translations
- `articles` - Individual articles
- `article_translations` - Article translations (with full-text search)

### Interaction Tables
- `comments` - User comments
- `comment_likes` - Comment likes

### System Tables
- `login_attempts` - Brute-force protection
- `activity_logs` - Audit trail
- `chatbot_messages` - Chatbot history

## PostgreSQL Features

### Full-Text Search
```sql
-- tsvector column for search
ALTER TABLE article_translations 
ADD COLUMN search_vector tsvector;

-- Trigger for auto-update
CREATE TRIGGER article_translations_search_update
BEFORE INSERT OR UPDATE ON article_translations
FOR EACH ROW EXECUTE FUNCTION update_article_search_vector();
```

## Development Setup

```bash
# Install dependencies
composer install

# Copy environment
cp env.example .env

# Generate key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Start server
php artisan serve
```

## Environment Variables

Key environment variables:
```
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=pgsql
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
FORCE_HTTPS=true
```

## Testing

```bash
# Run all tests
php artisan test

# Run specific suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

Uses SQLite in-memory for testing speed.



