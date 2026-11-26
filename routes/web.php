<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\AdminAccountController;
use App\Http\Controllers\Admin\AdminBackupController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminEbaySettingsController;
use App\Http\Controllers\Admin\AdminExcludedWordsController;
use App\Http\Controllers\Admin\AdminLogController;
use App\Http\Controllers\Admin\AdminSchedulerController;
use App\Http\Controllers\Admin\AdminSecurityController;
use App\Http\Controllers\Admin\AdminSEOController;
use App\Http\Controllers\Admin\AdminSessionController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\ProfitabilityPartController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LaptopListingController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\ListingNotificationController;
use App\Http\Controllers\ProfitabilityCalculationController;
use App\Http\Controllers\SearchProfileController;
use App\Http\Controllers\SearchSummaryController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegistrationController::class, 'create'])->name('register');
    Route::post('/register', [RegistrationController::class, 'store'])->middleware('throttle:5,1')->name('register.store');
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->middleware('throttle:5,1')->name('password.store');
});

Route::post('/logout', [SessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

Route::get('/admin/login', [AdminSessionController::class, 'create'])->name('admin.login');
Route::post('/admin/login', [AdminSessionController::class, 'store'])->middleware('throttle:5,1')->name('admin.login.store');
Route::post('/admin/logout', [AdminSessionController::class, 'destroy'])->middleware('auth:admin')->name('admin.logout');

Route::middleware(['auth', 'verified', 'log.activity', 'log.login'])->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/listings', [ListingController::class, 'index'])->name('listings.index');
    Route::get('/listings/watchlist', [ListingController::class, 'watchlist'])->name('listings.watchlist');
    Route::get('/listings/buy', [ListingController::class, 'buyList'])->name('listings.buy');
    Route::get('/listings/deleted', [ListingController::class, 'deleted'])->name('listings.deleted');
    Route::get('/listings/excluded', [ListingController::class, 'excluded'])->name('listings.excluded');
    Route::get('/listings/price-changes', [ListingController::class, 'priceChanges'])->name('listings.price-changes');
    Route::get('/listings/{listing}', [ListingController::class, 'show'])->name('listings.show');
    Route::get('/listings/{listing}/price-history', [ListingController::class, 'priceHistory'])->name('listings.price-history');
    Route::patch('/listings/{listing}/note', [LaptopListingController::class, 'updateNote'])->name('listings.notes.update');
    Route::patch('/listings/{listing}/checked', [LaptopListingController::class, 'updateChecked'])->name('listings.checked.update');
    Route::post('/listings/export', [LaptopListingController::class, 'export'])->name('listings.export');
    Route::patch('/listings/{listing}', [LaptopListingController::class, 'update'])->name('listings.update');
    Route::post('/listings/bulk-action', [LaptopListingController::class, 'bulkAction'])->name('listings.bulk-action');
    Route::delete('/listings/{listing}', [LaptopListingController::class, 'destroy'])->name('listings.destroy');
    Route::delete('/listings/{listing}/permanently', [LaptopListingController::class, 'forceDestroy'])->name('listings.force-destroy');
    Route::post('/listings/deleted/bulk-delete', [LaptopListingController::class, 'bulkForceDestroy'])->name('listings.bulk-force-destroy');
    Route::patch('/listings/{listing}/restore', [LaptopListingController::class, 'restore'])->name('listings.restore');
    Route::get('/notifications', [ListingNotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [ListingNotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::patch('/notifications/read-all', [ListingNotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::get('/profiles', [SearchProfileController::class, 'index'])->name('profiles.index');
    Route::get('/profiles/create', [SearchProfileController::class, 'create'])->name('profiles.create');
    Route::get('/profiles/history', [SearchProfileController::class, 'history'])->name('profiles.history');
    Route::get('/search-summary', SearchSummaryController::class)->name('search-summary');
    Route::get('/profitability', [ProfitabilityCalculationController::class, 'index'])->name('profitability.index');
    Route::post('/profitability', [ProfitabilityCalculationController::class, 'store'])->name('profitability.store');
    Route::get('/profitability/{calculation}/edit', [ProfitabilityCalculationController::class, 'edit'])->name('profitability.edit');
    Route::patch('/profitability/{calculation}', [ProfitabilityCalculationController::class, 'update'])->name('profitability.update');
    Route::delete('/profitability/{calculation}', [ProfitabilityCalculationController::class, 'destroy'])->name('profitability.destroy');
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    // Account routes
    Route::get('/account/profile', [AccountController::class, 'profile'])->name('account.profile');
    Route::patch('/account/profile', [AccountController::class, 'updateProfile'])->name('account.profile.update');
    Route::get('/account/password', [AccountController::class, 'password'])->name('account.password');
    Route::patch('/account/password', [AccountController::class, 'updatePassword'])->name('account.password.update');
    Route::get('/account/notifications', [AccountController::class, 'notifications'])->name('account.notifications');
    Route::patch('/account/notifications', [AccountController::class, 'updateNotifications'])->name('account.notifications.update');
    Route::get('/account/security', [AccountController::class, 'security'])->name('account.security');
    Route::post('/account/security/2fa/enable', [AccountController::class, 'enableTwoFactor'])->name('account.security.2fa.enable');
    Route::post('/account/security/2fa/disable', [AccountController::class, 'disableTwoFactor'])->name('account.security.2fa.disable');
    Route::post('/account/security/logout-others', [AccountController::class, 'logoutOtherSessions'])->name('account.security.logout-others');

    Route::get('/profiles/filter-options', [SearchProfileController::class, 'filterOptions'])->name('profiles.filter-options');
    Route::post('/profiles', [SearchProfileController::class, 'store'])->name('profiles.store');
    Route::post('/profiles/{profile}/analyze-filters', [SearchProfileController::class, 'analyzeFilters'])->name('profiles.analyze-filters');
    Route::get('/profiles/{profile}/edit', [SearchProfileController::class, 'edit'])->name('profiles.edit');
    Route::patch('/profiles/{profile}', [SearchProfileController::class, 'update'])->name('profiles.update');
    Route::delete('/profiles/{profile}', [SearchProfileController::class, 'destroy'])->name('profiles.destroy');
    Route::post('/profiles/{profile}/parse', [SearchProfileController::class, 'parsePage'])->name('profiles.parse');
    Route::get('/profiles/{profile}/recent-activities', [SearchProfileController::class, 'recentActivities'])->name('profiles.recent-activities');
    Route::get('/profiles/{profile}/listings', [SearchProfileController::class, 'getListings'])->name('profiles.listings');
});

Route::middleware(['auth:admin', 'admin', 'log.activity'])->group(function (): void {
    Route::get('/admin', AdminController::class)->name('admin.index');
    Route::get('/admin/settings', [SettingsController::class, 'index'])->name('admin.settings');
    Route::patch('/admin/settings/profitability', [SettingsController::class, 'updateProfitability'])->name('admin.settings.profitability.update');
    Route::patch('/admin/settings/telegram', [SettingsController::class, 'updateTelegram'])->name('admin.settings.telegram.update');
    Route::patch('/admin/settings/timezone', [SettingsController::class, 'updateTimezone'])->name('admin.settings.timezone.update');
    Route::patch('/admin/settings/import', [SettingsController::class, 'updateImportSettings'])->name('admin.settings.import.update');
    Route::patch('/admin/settings/appearance', [SettingsController::class, 'updateAppearance'])->name('admin.settings.appearance.update');
    Route::patch('/admin/settings/email-smtp', [SettingsController::class, 'updateEmailSMTP'])->name('admin.settings.email-smtp.update');
    Route::patch('/admin/settings/system', [SettingsController::class, 'updateSystem'])->name('admin.settings.system.update');
    Route::post('/admin/settings/maintenance', [SettingsController::class, 'toggleMaintenance'])->name('admin.settings.maintenance.toggle');
    Route::post('/admin/settings/clear-cache', [SettingsController::class, 'clearCache'])->name('admin.settings.clear-cache');
    Route::patch('/admin/settings/search-filters', [SettingsController::class, 'updateSearchFilters'])->name('admin.settings.search-filters.update');
    Route::patch('/admin/settings/catalog', [SettingsController::class, 'updateCatalog'])->name('admin.settings.catalog.update');
    Route::patch('/admin/settings/users', [SettingsController::class, 'updateUsers'])->name('admin.settings.users.update');
    Route::patch('/admin/settings/notifications', [SettingsController::class, 'updateNotifications'])->name('admin.settings.notifications.update');
    Route::patch('/admin/settings/footer', [SettingsController::class, 'updateFooter'])->name('admin.settings.footer.update');
    Route::patch('/admin/settings/caching', [SettingsController::class, 'updateCaching'])->name('admin.settings.caching.update');
    Route::patch('/admin/settings/email-notifications', [SettingsController::class, 'updateEmailNotifications'])->name('admin.settings.email-notifications.update');
    Route::patch('/admin/settings/currencies', [SettingsController::class, 'updateCurrencies'])->name('admin.settings.currencies.update');
    Route::patch('/admin/settings/api-and-webhooks', [SettingsController::class, 'updateApiAndWebhooks'])->name('admin.settings.api-and-webhooks.update');
    Route::patch('/admin/settings/localization', [SettingsController::class, 'updateLocalization'])->name('admin.settings.localization.update');

    Route::get('/admin/security', [AdminSecurityController::class, 'security'])->name('admin.security.security');
    Route::patch('/admin/security', [AdminSecurityController::class, 'updateSecurity'])->name('admin.security.security.update');
    Route::get('/admin/security/cors', [AdminSecurityController::class, 'cors'])->name('admin.security.cors');
    Route::patch('/admin/security/cors', [AdminSecurityController::class, 'updateCors'])->name('admin.security.cors.update');
    Route::get('/admin/security/advanced', [AdminSecurityController::class, 'advanced'])->name('admin.security.advanced');
    Route::patch('/admin/security/advanced', [AdminSecurityController::class, 'updateAdvanced'])->name('admin.security.advanced.update');
    Route::view('/admin/payments', 'payments')->name('admin.payments');
    Route::get('/admin/profitability-parts', [ProfitabilityPartController::class, 'index'])->name('admin.profitability-parts.index');
    Route::post('/admin/profitability-parts', [ProfitabilityPartController::class, 'store'])->name('admin.profitability-parts.store');
    Route::patch('/admin/profitability-parts/{part}', [ProfitabilityPartController::class, 'update'])->name('admin.profitability-parts.update');
    Route::delete('/admin/profitability-parts/{part}', [ProfitabilityPartController::class, 'destroy'])->name('admin.profitability-parts.destroy');
    Route::delete('/admin/profitability-parts/{part}/force', [ProfitabilityPartController::class, 'forceDelete'])->name('admin.profitability-parts.force-delete');
    Route::patch('/admin/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('admin.users.role');
    Route::get('/admin/users/{user}/edit', [AdminUserController::class, 'edit'])->name('admin.users.edit');
    Route::patch('/admin/users/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');
    Route::get('/admin/logs/user-activity', [AdminLogController::class, 'userActivity'])->name('admin.logs.user-activity');
    Route::get('/admin/logs/system-errors', [AdminLogController::class, 'systemErrors'])->name('admin.logs.system-errors');
    Route::delete('/admin/users/{user}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');
    Route::get('/admin/scheduler', [AdminSchedulerController::class, 'index'])->name('admin.scheduler.index');
    Route::post('/admin/scheduler/schedule', [AdminSchedulerController::class, 'updateSchedule'])->name('admin.scheduler.schedule.update');
    Route::post('/admin/scheduler/run-import', [AdminSchedulerController::class, 'runImport'])->name('admin.scheduler.run-import');
    Route::delete('/admin/scheduler/clear-log', [AdminSchedulerController::class, 'clearLog'])->name('admin.scheduler.clear-log');
    Route::get('/admin/scheduler/processes-page', [AdminSchedulerController::class, 'processes'])->name('admin.scheduler.processes-page');
    Route::get('/admin/scheduler/processes', [AdminSchedulerController::class, 'getRunningProcesses'])->name('admin.scheduler.processes');
    Route::get('/admin/scheduler/dispatcher', [AdminSchedulerController::class, 'getScheduleDispatcher'])->name('admin.scheduler.dispatcher');
    Route::post('/admin/scheduler/kill-process', [AdminSchedulerController::class, 'killProcess'])->name('admin.scheduler.kill-process');
    Route::post('/admin/scheduler/stop-schedule', [AdminSchedulerController::class, 'stopSchedule'])->name('admin.scheduler.stop-schedule');
    Route::post('/admin/scheduler/clear-mutex', [AdminSchedulerController::class, 'clearScheduleMutex'])->name('admin.scheduler.clear-mutex');
    Route::post('/admin/scheduler/restart-schedule', [AdminSchedulerController::class, 'restartSchedule'])->name('admin.scheduler.restart-schedule');

    Route::get('/admin/excluded-words', [AdminExcludedWordsController::class, 'index'])->name('admin.excluded-words.index');
    Route::post('/admin/excluded-words', [AdminExcludedWordsController::class, 'store'])->name('admin.excluded-words.store');
    Route::patch('/admin/excluded-words/{excludedWord}', [AdminExcludedWordsController::class, 'update'])->name('admin.excluded-words.update');
    Route::delete('/admin/excluded-words/{excludedWord}', [AdminExcludedWordsController::class, 'destroy'])->name('admin.excluded-words.destroy');

    Route::get('/admin/seo', [AdminSEOController::class, 'seo'])->name('admin.seo.seo');
    Route::patch('/admin/seo', [AdminSEOController::class, 'updateSEO'])->name('admin.seo.seo.update');
    Route::get('/admin/seo/advanced', [AdminSEOController::class, 'advancedSEO'])->name('admin.seo.advanced');
    Route::patch('/admin/seo/advanced', [AdminSEOController::class, 'updateAdvancedSEO'])->name('admin.seo.advanced.update');

    Route::get('/admin/ebay', [AdminEbaySettingsController::class, 'index'])->name('admin.ebay.index');
    Route::patch('/admin/ebay', [AdminEbaySettingsController::class, 'updateConnection'])->name('admin.ebay.update');
    Route::get('/admin/ebay/delays', [AdminEbaySettingsController::class, 'delays'])->name('admin.ebay.delays');
    Route::patch('/admin/ebay/delays', [AdminEbaySettingsController::class, 'updateDelays'])->name('admin.ebay.delays.update');
    Route::get('/admin/ebay/account', [AdminEbaySettingsController::class, 'account'])->name('admin.ebay.account');
    Route::patch('/admin/ebay/account', [AdminEbaySettingsController::class, 'updateAccount'])->name('admin.ebay.account.update');
    Route::get('/admin/ebay/advanced', [AdminEbaySettingsController::class, 'advanced'])->name('admin.ebay.advanced');
    Route::patch('/admin/ebay/advanced', [AdminEbaySettingsController::class, 'updateAdvanced'])->name('admin.ebay.advanced.update');
    Route::get('/admin/ebay/extra', [AdminEbaySettingsController::class, 'extra'])->name('admin.ebay.extra');
    Route::patch('/admin/ebay/extra', [AdminEbaySettingsController::class, 'updateExtra'])->name('admin.ebay.extra.update');
    Route::get('/admin/ebay/monitoring', [AdminEbaySettingsController::class, 'monitoring'])->name('admin.ebay.monitoring');
    Route::patch('/admin/ebay/monitoring', [AdminEbaySettingsController::class, 'updateMonitoring'])->name('admin.ebay.monitoring.update');
    Route::patch('/admin/ebay/api-limit', [AdminEbaySettingsController::class, 'updateApiLimit'])->name('admin.ebay.api-limit.update');

    Route::get('/admin/backup', [AdminBackupController::class, 'index'])->name('admin.backup.index');
    Route::patch('/admin/backup', [AdminBackupController::class, 'update'])->name('admin.backup.update');
    Route::post('/admin/backup/run', [AdminBackupController::class, 'run'])->name('admin.backup.run');

    // Admin Account routes
    Route::get('/admin/account/profile', [AdminAccountController::class, 'profile'])->name('admin.account.profile');
    Route::patch('/admin/account/profile', [AdminAccountController::class, 'updateProfile'])->name('admin.account.profile.update');
    Route::get('/admin/account/password', [AdminAccountController::class, 'password'])->name('admin.account.password');
    Route::patch('/admin/account/password', [AdminAccountController::class, 'updatePassword'])->name('admin.account.password.update');
    Route::get('/admin/account/notifications', [AdminAccountController::class, 'notifications'])->name('admin.account.notifications');
    Route::patch('/admin/account/notifications', [AdminAccountController::class, 'updateNotifications'])->name('admin.account.notifications.update');
    Route::get('/admin/account/security', [AdminAccountController::class, 'security'])->name('admin.account.security');
    Route::post('/admin/account/security/2fa/enable', [AdminAccountController::class, 'enableTwoFactor'])->name('admin.account.security.2fa.enable');
    Route::post('/admin/account/security/2fa/disable', [AdminAccountController::class, 'disableTwoFactor'])->name('admin.account.security.2fa.disable');
    Route::post('/admin/account/security/logout-others', [AdminAccountController::class, 'logoutOtherSessions'])->name('admin.account.security.logout-others');

    Route::middleware('can:admin')->group(function (): void {
        // Placeholder for future admin-only routes
    });
});
