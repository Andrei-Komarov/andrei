<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExcludedWord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminExcludedWordsController extends Controller
{
    public function index(): View
    {
        return view('admin.excluded-words.index', [
            'words' => ExcludedWord::query()->orderBy('word')->paginate(30),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $normalized = mb_trim(mb_strtolower($request->input('word')));

        $request->validate([
            'word' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($normalized) {
                    if (ExcludedWord::query()->whereRaw('LOWER(TRIM(word)) = ?', [$normalized])->exists()) {
                        $fail('Это слово уже существует в списке исключений.');
                    }
                },
            ],
        ]);

        ExcludedWord::query()->create([
            'word' => $normalized,
            'active' => true,
        ]);

        return to_route('admin.excluded-words.index')->with('success', 'Слово добавлено в список исключений.');
    }

    public function update(Request $request, ExcludedWord $excludedWord): RedirectResponse
    {
        $normalized = mb_trim(mb_strtolower($request->input('word')));

        $request->validate([
            'word' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($normalized, $excludedWord) {
                    if (ExcludedWord::query()->where('id', '!=', $excludedWord->id)->whereRaw('LOWER(TRIM(word)) = ?', [$normalized])->exists()) {
                        $fail('Это слово уже существует в списке исключений.');
                    }
                },
            ],
            'active' => ['required', 'boolean'],
        ]);

        $excludedWord->update([
            'word' => $normalized,
            'active' => (bool) $request->input('active'),
        ]);

        return to_route('admin.excluded-words.index')->with('success', 'Слово обновлено.');
    }

    public function destroy(ExcludedWord $excludedWord): RedirectResponse
    {
        $excludedWord->delete();

        return to_route('admin.excluded-words.index')->with('success', 'Слово удалено из списка исключений.');
    }
}
