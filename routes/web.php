<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Admin\GitSyncController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\SetupController;
use App\Http\Controllers\Admin\SiteSettingsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return to_route('docs.index');
})->name('home');

// Public Documentation Routes
Route::get('/docs', [DocsController::class, 'index'])->name('docs.index');
Route::get('/docs/{path}', [DocsController::class, 'show'])
    ->where('path', '.*')
    ->name('docs.show');

// Public Feedback Routes
Route::post('/feedback', [\App\Http\Controllers\FeedbackController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('feedback.store');

// Webhook (public, but verified)
Route::post('/webhook/github', [WebhookController::class, 'github'])->name('webhook.github');

// Sitemap
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/admin/setup', [SetupController::class, 'index'])->name('admin.setup');
    Route::post('/admin/setup', [SetupController::class, 'store'])->name('admin.setup.store');

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::get('/activity-logs/{id}', [ActivityLogController::class, 'show'])->name('activity-logs.show');
    Route::get('/activity-logs/export/csv', [ActivityLogController::class, 'export'])->name('activity-logs.export');
    Route::post('/activity-logs/clean', [ActivityLogController::class, 'clean'])->name('activity-logs.clean');

    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
    Route::patch('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');

    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance.edit');

    Route::get('settings/two-factor', [TwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');

    // Admin Pages Management (rate limited)
    Route::prefix('admin')->name('admin.')->middleware('throttle:60,1')->group(function () {
        Route::get('pages', [PageController::class, 'index'])->name('pages.index');
        Route::get('pages/create', [PageController::class, 'create'])->name('pages.create');
        Route::post('pages', [PageController::class, 'store'])->name('pages.store');
        Route::get('pages/{page}/edit', [PageController::class, 'edit'])->name('pages.edit');
        Route::put('pages/{page}', [PageController::class, 'update'])->name('pages.update');
        Route::delete('pages/{page}', [PageController::class, 'destroy'])->name('pages.destroy');
        Route::post('pages/{page}/duplicate', [PageController::class, 'duplicate'])->name('pages.duplicate');
        Route::post('pages/reorder', [PageController::class, 'reorder'])->name('pages.reorder');
        Route::post('pages/{page}/publish', [PageController::class, 'publish'])->name('pages.publish');
        Route::post('pages/{page}/unpublish', [PageController::class, 'unpublish'])->name('pages.unpublish');
        Route::post('pages/{page}/restore-version/{versionId}', [PageController::class, 'restoreVersion'])->name('pages.restore-version');

        // Media Management
        Route::get('media', [MediaController::class, 'index'])->name('media.index');
        Route::post('media', [MediaController::class, 'store'])->name('media.store');
        Route::get('media/{media}', [MediaController::class, 'show'])->name('media.show');
        Route::patch('media/{media}', [MediaController::class, 'update'])->name('media.update');
        Route::delete('media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
        Route::post('media/bulk-destroy', [MediaController::class, 'bulkDestroy'])->name('media.bulk-destroy');
        Route::post('media/folders', [MediaController::class, 'createFolder'])->name('media.folders.store');
        Route::delete('media/folders/{folder}', [MediaController::class, 'destroyFolder'])->name('media.folders.destroy');

        // Feedback Management
        Route::get('feedback', [\App\Http\Controllers\Admin\FeedbackController::class, 'index'])->name('feedback.index');
        Route::get('feedback/export', [\App\Http\Controllers\Admin\FeedbackController::class, 'export'])->name('feedback.export');
        Route::delete('feedback/{response}', [\App\Http\Controllers\Admin\FeedbackController::class, 'destroy'])->name('feedback.destroy');
        Route::get('feedback/forms', [\App\Http\Controllers\Admin\FeedbackController::class, 'forms'])->name('feedback.forms');
        Route::get('feedback/forms/create', [\App\Http\Controllers\Admin\FeedbackController::class, 'createForm'])->name('feedback.forms.create');
        Route::post('feedback/forms', [\App\Http\Controllers\Admin\FeedbackController::class, 'storeForm'])->name('feedback.forms.store');
        Route::get('feedback/forms/{form}/edit', [\App\Http\Controllers\Admin\FeedbackController::class, 'editForm'])->name('feedback.forms.edit');
        Route::put('feedback/forms/{form}', [\App\Http\Controllers\Admin\FeedbackController::class, 'updateForm'])->name('feedback.forms.update');
        Route::delete('feedback/forms/{form}', [\App\Http\Controllers\Admin\FeedbackController::class, 'destroyForm'])->name('feedback.forms.destroy');

        // Site Settings
        Route::get('settings', [SiteSettingsController::class, 'index'])->name('settings.index');
        Route::get('settings/theme', [SiteSettingsController::class, 'theme'])->name('settings.theme');
        Route::put('settings/theme', [SiteSettingsController::class, 'updateTheme'])->name('settings.theme.update');
        Route::get('settings/typography', [SiteSettingsController::class, 'typography'])->name('settings.typography');
        Route::put('settings/typography', [SiteSettingsController::class, 'updateTypography'])->name('settings.typography.update');
        Route::get('settings/layout', [SiteSettingsController::class, 'layout'])->name('settings.layout');
        Route::put('settings/layout', [SiteSettingsController::class, 'updateLayout'])->name('settings.layout.update');
        Route::get('settings/branding', [SiteSettingsController::class, 'branding'])->name('settings.branding');
        Route::post('settings/branding', [SiteSettingsController::class, 'updateBranding'])->name('settings.branding.update');
        Route::delete('settings/branding/logo', [SiteSettingsController::class, 'deleteLogo'])->name('settings.branding.delete-logo');
        Route::get('settings/advanced', [SiteSettingsController::class, 'advanced'])->name('settings.advanced');
        Route::put('settings/advanced', [SiteSettingsController::class, 'updateAdvanced'])->name('settings.advanced.update');

        Route::get('/git-sync', [GitSyncController::class, 'index'])->name('git-sync.index');
        Route::post('/git-sync/manual', [GitSyncController::class, 'manualSync'])->name('git-sync.manual');
        Route::post('/git-sync/test', [GitSyncController::class, 'testConnection'])->name('git-sync.test');
        Route::put('/git-sync/config', [GitSyncController::class, 'updateConfig'])->name('git-sync.config');
        Route::post('/git-sync/rollback/{sync}', [GitSyncController::class, 'rollback'])->name('git-sync.rollback');

    });
});
