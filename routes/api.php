<?php

use App\Http\Controllers\Api\V1\ArticleController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ChapterController;
use App\Http\Controllers\Api\V1\ChatbotController;
use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SectionController;
use App\Http\Controllers\Api\V1\TwoFactorController;
use App\Http\Controllers\Api\V1\Admin\AdminAnalyticsController;
use App\Http\Controllers\Api\V1\Admin\AdminArticleController;
use App\Http\Controllers\Api\V1\Admin\AdminChapterController;
use App\Http\Controllers\Api\V1\Admin\AdminCommentController;
use App\Http\Controllers\Api\V1\Admin\AdminLogController;
use App\Http\Controllers\Api\V1\Admin\AdminSectionController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| API v1 Routes for Labor Code Portal
|
*/

Route::prefix('v1')->group(function () {
    
    /*
    |--------------------------------------------------------------------------
    | Public Routes - No Authentication Required
    |--------------------------------------------------------------------------
    */
    
    // Authentication
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register'])
            ->middleware('throttle:3,60'); // 3 per hour
        
        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:5,1'); // 5 per minute
        
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])
            ->middleware('throttle:3,5'); // 3 per 5 minutes
        
        Route::post('reset-password', [AuthController::class, 'resetPassword'])
            ->middleware('throttle:5,5');
    });
    
    // Sections
    Route::prefix('sections')->group(function () {
        Route::get('/', [SectionController::class, 'index']);
        Route::get('/{id}', [SectionController::class, 'show'])->where('id', '[0-9]+');
        Route::get('/{id}/chapters', [SectionController::class, 'chapters'])->where('id', '[0-9]+');
    });
    
    // Chapters
    Route::prefix('chapters')->group(function () {
        Route::get('/{id}', [ChapterController::class, 'show'])->where('id', '[0-9]+');
        Route::get('/{id}/articles', [ChapterController::class, 'articles'])->where('id', '[0-9]+');
    });
    
    // Articles
    Route::prefix('articles')->group(function () {
        Route::get('/', [ArticleController::class, 'index']);
        Route::get('/{id}', [ArticleController::class, 'show'])->where('id', '[0-9]+');
        Route::get('/number/{number}', [ArticleController::class, 'showByNumber']);
        Route::get('/{id}/comments', [ArticleController::class, 'comments'])->where('id', '[0-9]+');
    });
    
    // Search
    Route::prefix('search')->group(function () {
        Route::get('/', [SearchController::class, 'search']);
        Route::get('/suggestions', [SearchController::class, 'suggestions']);
    });
    
    // Chatbot (public)
    Route::prefix('chatbot')->group(function () {
        Route::post('/', [ChatbotController::class, 'sendMessage'])
            ->middleware('throttle:30,1'); // 30 per minute
        Route::post('/feedback', [ChatbotController::class, 'submitFeedback']);
    });
    
    /*
    |--------------------------------------------------------------------------
    | Protected Routes - Authentication Required
    |--------------------------------------------------------------------------
    */
    
    Route::middleware(['auth:sanctum', 'check.banned'])->group(function () {
        
        // Logout
        Route::post('auth/logout', [AuthController::class, 'logout']);
        
        // Profile
        Route::prefix('profile')->group(function () {
            Route::get('/', [ProfileController::class, 'show']);
            Route::put('/', [ProfileController::class, 'update']);
            Route::put('/password', [ProfileController::class, 'changePassword']);
        });
        
        // Two-Factor Authentication
        Route::prefix('2fa')->group(function () {
            Route::get('/status', [TwoFactorController::class, 'status']);
            Route::post('/enable', [TwoFactorController::class, 'enable']);
            Route::post('/confirm', [TwoFactorController::class, 'confirm']);
            Route::delete('/disable', [TwoFactorController::class, 'disable']);
            Route::post('/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes']);
        });
        
        // Comments (authenticated user actions)
        Route::prefix('articles/{articleId}/comments')->where(['articleId' => '[0-9]+'])->group(function () {
            Route::post('/', [CommentController::class, 'store']);
        });
        
        Route::prefix('comments')->group(function () {
            Route::put('/{id}', [CommentController::class, 'update'])->where('id', '[0-9]+');
            Route::delete('/{id}', [CommentController::class, 'destroy'])->where('id', '[0-9]+');
            Route::post('/{id}/like', [CommentController::class, 'like'])->where('id', '[0-9]+');
        });
        
        // Chatbot history (for authenticated users)
        Route::get('chatbot/history', [ChatbotController::class, 'history']);
        
        /*
        |--------------------------------------------------------------------------
        | Admin Routes - Admin/Moderator Access Required
        |--------------------------------------------------------------------------
        */
        
        Route::prefix('admin')->middleware(['role:admin,moderator,muallif,tarjimon,ishchi_guruh,ekspert', 'log.activity'])->group(function () {
            
            // Sections Management
            Route::prefix('sections')->group(function () {
                Route::get('/', [AdminSectionController::class, 'index']);
                Route::get('/{id}', [AdminSectionController::class, 'show'])->where('id', '[0-9]+');
                Route::post('/', [AdminSectionController::class, 'store'])->middleware('role:admin,ishchi_guruh');
                Route::put('/{id}', [AdminSectionController::class, 'update'])->where('id', '[0-9]+')->middleware('role:admin,moderator,ishchi_guruh');
                Route::delete('/{id}', [AdminSectionController::class, 'destroy'])->where('id', '[0-9]+')->middleware('role:admin,ishchi_guruh');
            });
            
            // Chapters Management
            Route::prefix('chapters')->group(function () {
                Route::get('/', [AdminChapterController::class, 'index']);
                Route::get('/{id}', [AdminChapterController::class, 'show'])->where('id', '[0-9]+');
                Route::post('/', [AdminChapterController::class, 'store'])->middleware('role:admin,ishchi_guruh');
                Route::put('/{id}', [AdminChapterController::class, 'update'])->where('id', '[0-9]+')->middleware('role:admin,moderator,ishchi_guruh');
                Route::delete('/{id}', [AdminChapterController::class, 'destroy'])->where('id', '[0-9]+')->middleware('role:admin,ishchi_guruh');
            });
            
            // Articles Management - tarjimon can view and update (for translations)
            Route::prefix('articles')->group(function () {
                Route::get('/', [AdminArticleController::class, 'index']);
                Route::get('/{id}', [AdminArticleController::class, 'show'])->where('id', '[0-9]+');
                Route::post('/', [AdminArticleController::class, 'store'])->middleware('role:admin,moderator,muallif,ishchi_guruh');
                Route::put('/{id}', [AdminArticleController::class, 'update'])->where('id', '[0-9]+');
                Route::delete('/{id}', [AdminArticleController::class, 'destroy'])->where('id', '[0-9]+')->middleware('role:admin');
            });
            
            // Comments Moderation
            Route::prefix('comments')->group(function () {
                Route::get('/', [AdminCommentController::class, 'index']);
                Route::get('/pending', [AdminCommentController::class, 'pending']);
                Route::post('/{id}/approve', [AdminCommentController::class, 'approve'])->where('id', '[0-9]+');
                Route::post('/{id}/reject', [AdminCommentController::class, 'reject'])->where('id', '[0-9]+');
                Route::delete('/{id}', [AdminCommentController::class, 'destroy'])->where('id', '[0-9]+');
            });
            
            // Analytics
            Route::prefix('analytics')->group(function () {
                Route::get('/dashboard', [AdminAnalyticsController::class, 'dashboard']);
                Route::get('/popular-articles', [AdminAnalyticsController::class, 'popularArticles']);
                Route::get('/chatbot-stats', [AdminAnalyticsController::class, 'chatbotStats']);
                Route::get('/user-activity', [AdminAnalyticsController::class, 'userActivity']);
                Route::get('/content-stats', [AdminAnalyticsController::class, 'contentStats']);
            });
            
            // Activity Logs
            Route::prefix('logs')->group(function () {
                Route::get('/', [AdminLogController::class, 'index']);
                Route::get('/action-types', [AdminLogController::class, 'actionTypes']);
                Route::get('/{modelType}/{modelId}', [AdminLogController::class, 'forModel'])
                    ->where('modelId', '[0-9]+');
            });
        });
        
        /*
        |--------------------------------------------------------------------------
        | Admin Only Routes - Only Admin Access
        |--------------------------------------------------------------------------
        */
        
        Route::prefix('admin')->middleware(['role:admin', 'log.activity'])->group(function () {
            
            // User Management
            Route::prefix('users')->group(function () {
                Route::get('/', [AdminUserController::class, 'index']);
                Route::post('/', [AdminUserController::class, 'store']); // Create new user
                Route::get('/roles', [AdminUserController::class, 'roles']);
                Route::get('/{id}', [AdminUserController::class, 'show'])->where('id', '[0-9]+');
                Route::put('/{id}/role', [AdminUserController::class, 'updateRole'])->where('id', '[0-9]+');
                Route::put('/{id}/status', [AdminUserController::class, 'updateStatus'])->where('id', '[0-9]+');
                Route::delete('/{id}', [AdminUserController::class, 'destroy'])->where('id', '[0-9]+');
            });
        });
    });
});



