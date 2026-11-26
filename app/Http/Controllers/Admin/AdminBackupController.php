<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminBackupController extends Controller
{
    public function index(): View
    {
        return view('admin.backup.index', [
            'siteSettings' => SiteSetting::query()->findOrFail(1),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'backup_schedule' => ['required', 'string', 'in:manual,daily,weekly'],
            'backup_retain_count' => ['required', 'integer', 'min:1', 'max:365'],
            'backup_database' => ['boolean'],
            'backup_files' => ['boolean'],
            'backup_path' => ['nullable', 'string', 'max:512'],
            'backup_notify_telegram' => ['boolean'],
        ]);

        $siteSettings = SiteSetting::query()->findOrFail(1);
        $siteSettings->update([
            'backup_schedule' => $validated['backup_schedule'],
            'backup_retain_count' => $validated['backup_retain_count'],
            'backup_database' => $request->has('backup_database'),
            'backup_files' => $request->has('backup_files'),
            'backup_path' => $validated['backup_path'],
            'backup_notify_telegram' => $request->has('backup_notify_telegram'),
        ]);

        return to_route('admin.backup.index')->with('success', 'Настройки резервного копирования сохранены.');
    }

    public function run(Request $request): RedirectResponse
    {
        $exitCode = \Artisan::call('backup:run');

        if ($exitCode === 0) {
            return to_route('admin.backup.index')->with('success', 'Резервное копирование выполнено успешно.');
        }

        return to_route('admin.backup.index')->with('error', 'Резервное копирование завершено с ошибками. Проверьте логи.');
    }
}
