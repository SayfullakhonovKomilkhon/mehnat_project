# 🚀 Деплой на Render

## Шаг 1: Подготовка репозитория

### 1.1 Создайте репозиторий на GitHub

1. Перейдите на https://github.com/new
2. Создайте новый репозиторий (например: `mehnat-backend`)
3. **Не** добавляйте README, .gitignore или лицензию

### 1.2 Загрузите код на GitHub

```bash
cd C:\Users\sayfu\Desktop\mehnat_new\back_mehnat

# Инициализация Git (если еще не сделано)
git init

# Добавить все файлы
git add .

# Первый коммит
git commit -m "Initial commit - Laravel backend for Mehnat Kodeksi"

# Подключить удаленный репозиторий
git remote add origin https://github.com/YOUR_USERNAME/mehnat-backend.git

# Отправить код
git branch -M main
git push -u origin main
```

---

## Шаг 2: Создание PostgreSQL базы данных на Render

1. Перейдите на https://dashboard.render.com
2. Нажмите **"New +"** → **"PostgreSQL"**
3. Заполните:
   - **Name:** `mehnat-db`
   - **Database:** `mehnat_kodeksi`
   - **User:** `mehnat_user`
   - **Region:** `Frankfurt (EU Central)` или ближайший к вам
   - **Plan:** `Free` (бесплатно 90 дней)
4. Нажмите **"Create Database"**
5. **Скопируйте "Internal Database URL"** - он понадобится позже

---

## Шаг 3: Создание Web Service на Render

1. Нажмите **"New +"** → **"Web Service"**
2. Выберите **"Build and deploy from a Git repository"**
3. Подключите ваш GitHub аккаунт и выберите репозиторий `mehnat-backend`
4. Настройте сервис:

### Основные настройки:

| Параметр | Значение |
|----------|----------|
| **Name** | `mehnat-api` |
| **Region** | Frankfurt (EU Central) |
| **Branch** | `main` |
| **Root Directory** | *(оставьте пустым)* |
| **Runtime** | `Docker` |
| **Dockerfile Path** | `./Dockerfile` |
| **Plan** | `Free` |

---

## Шаг 4: Добавление Environment Variables

Нажмите **"Advanced"** и добавьте следующие переменные окружения:

### 📋 СКОПИРУЙТЕ ЭТИ ПЕРЕМЕННЫЕ:

```
APP_NAME=Mehnat Kodeksi API
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:СГЕНЕРИРУЙТЕ_НОВЫЙ_КЛЮЧ
LOG_CHANNEL=stack
LOG_LEVEL=error
DB_CONNECTION=pgsql
DATABASE_URL=ВСТАВЬТЕ_INTERNAL_DATABASE_URL_ИЗ_ШАГА_2
CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
BROADCAST_DRIVER=log
FILESYSTEM_DISK=local
SESSION_LIFETIME=120
BCRYPT_ROUNDS=12
APP_LOCALE=uz
APP_FALLBACK_LOCALE=uz
APP_AVAILABLE_LOCALES=uz,ru,en
SEED_DATABASE=true
SANCTUM_STATEFUL_DOMAINS=localhost,YOUR_FRONTEND_DOMAIN.vercel.app
CORS_ALLOWED_ORIGINS=https://YOUR_FRONTEND_DOMAIN.vercel.app,http://localhost:3000
```

### Как сгенерировать APP_KEY:

Выполните в терминале:
```bash
php artisan key:generate --show
```

Или используйте онлайн генератор: https://laravel-encryption-key-generator.netlify.app/

---

## Шаг 5: Деплой

1. Нажмите **"Create Web Service"**
2. Дождитесь завершения сборки (5-10 минут)
3. После успешного деплоя, ваш API будет доступен по адресу:
   `https://mehnat-api.onrender.com`

---

## Шаг 6: После первого деплоя

**ВАЖНО:** После первого успешного деплоя:

1. Перейдите в настройки Web Service
2. Измените переменную `SEED_DATABASE` на `false`
3. Это предотвратит повторное заполнение базы данных при следующих деплоях

---

## Шаг 7: Обновление фронтенда

Обновите файл `.env.local` в папке `mehnat_new`:

```env
NEXT_PUBLIC_API_URL=https://mehnat-api.onrender.com/api/v1
```

---

## 🔧 Полезные команды

### Просмотр логов:
В панели Render → ваш сервис → **"Logs"**

### Ручной редеплой:
В панели Render → ваш сервис → **"Manual Deploy"** → **"Deploy latest commit"**

### SSH доступ:
В панели Render → ваш сервис → **"Shell"**

---

## ⚠️ Ограничения бесплатного тарифа

1. **Web Service:** Спит после 15 минут неактивности (первый запрос занимает ~30 сек)
2. **PostgreSQL:** Бесплатно 90 дней, потом $7/месяц
3. **Нет постоянного хранилища:** Файлы storage/ сбрасываются при редеплое

---

## 📞 Поддержка

- Render Docs: https://render.com/docs
- Laravel Docs: https://laravel.com/docs

