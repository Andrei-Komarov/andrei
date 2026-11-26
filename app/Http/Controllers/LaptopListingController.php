<?php

namespace App\Http\Controllers;

use App\Models\LaptopListing;
use App\Models\UserListingNote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaptopListingController extends Controller
{
    public function update(Request $request, LaptopListing $listing): JsonResponse|RedirectResponse
    {
        abort_unless($listing->searchProfiles()->where('search_profiles.user_id', $request->user()->id)->exists(), 404);

        $data = $request->validate(['status' => ['required', 'in:new,watching,buy,skipped']]);
        $listing->update([
            'status' => $data['status'],
            'sold_at' => $data['status'] === 'buy' ? now() : null,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Статус товара обновлён.',
                'status' => $data['status'],
            ]);
        }

        return back();
    }

    public function updateNote(Request $request, LaptopListing $listing): JsonResponse|RedirectResponse
    {
        abort_unless($listing->searchProfiles()->where('search_profiles.user_id', $request->user()->id)->exists(), 404);

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        UserListingNote::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'ebay_item_id' => $listing->ebay_item_id,
            ],
            ['note' => filled($data['note'] ?? null) ? trim($data['note']) : null],
        );

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Заметка к товару сохранена.']);
        }

        return back()->with('success', 'Заметка к товару сохранена.');
    }

    public function updateChecked(Request $request, LaptopListing $listing): JsonResponse
    {
        if (! $listing->searchProfiles()->where('search_profiles.user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Товар не найден.'], 404);
        }

        $data = $request->validate([
            'checked' => ['required', 'boolean'],
        ]);

        $listing->update(['checked' => $data['checked']]);

        return response()->json([
            'message' => $data['checked'] ? 'Товар отмечен как проверенный.' : 'Отметка снята.',
            'checked' => $data['checked'],
        ]);
    }

    public function bulkAction(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'listing_ids' => ['required', 'array', 'min:1'],
            'listing_ids.*' => ['required', 'integer', 'distinct'],
            'bulk_action' => [
                'required',
                Rule::in(['unexclude', 'unbuy', 'new', 'watching', 'buy', 'skipped', 'delete']),
            ],
        ]);
        $listingIds = array_map('intval', $data['listing_ids']);

        $affectedCount = DB::transaction(function () use ($request, $listingIds, $data): int {
            $listings = LaptopListing::query()
                ->whereIn('id', $listingIds)
                ->whereHas('searchProfiles', fn (Builder $query) => $query->where('search_profiles.user_id', $request->user()->id));

            abort_unless((clone $listings)->count() === count($listingIds), 404);

            if ($data['bulk_action'] === 'delete') {
                return $listings->delete();
            }

            if ($data['bulk_action'] === 'unexclude') {
                return $listings->update([
                    'excluded' => false,
                    'excluded_reason' => null,
                ]);
            }

            if ($data['bulk_action'] === 'unbuy') {
                return $listings->update([
                    'status' => null,
                    'sold_at' => null,
                ]);
            }

            return $listings->update([
                'status' => $data['bulk_action'],
                'sold_at' => $data['bulk_action'] === 'buy' ? now() : null,
                'excluded' => false,
                'excluded_reason' => null,
            ]);
        });

        if ($data['bulk_action'] === 'delete') {
            $message = "{$affectedCount} товаров перемещено в удалённые.";
        } elseif ($data['bulk_action'] === 'unexclude') {
            $message = "{$affectedCount} товаров возвращено в список товаров.";
        } elseif ($data['bulk_action'] === 'unbuy') {
            $message = "{$affectedCount} товаров возвращено в список товаров.";
        } else {
            $message = "Статус обновлён у товаров: {$affectedCount}.";
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'affected_count' => $affectedCount,
                'action' => $data['bulk_action'],
            ]);
        }

        return back()->with('success', $message);
    }

    public function export(Request $request): StreamedResponse
    {
        $data = $request->validate([
            'listing_ids' => ['required', 'array', 'min:1', 'max:100'],
            'listing_ids.*' => ['required', 'integer', 'distinct'],
        ]);
        $listingIds = array_map('intval', $data['listing_ids']);
        $listings = LaptopListing::query()
            ->with('searchProfiles:id,name')
            ->whereIn('id', $listingIds)
            ->whereHas('searchProfiles', fn (Builder $query) => $query->where('search_profiles.user_id', $request->user()->id))
            ->orderBy('id')
            ->get();

        abort_unless($listings->count() === count($listingIds), 404);

        return response()->streamDownload(function () use ($listings): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                throw new \RuntimeException('Unable to open the CSV output stream.');
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'Название',
                'Профиль',
                'ID eBay',
                'Ссылка',
                'Цена товара',
                'Доставка',
                'Итого',
                'Валюта',
                'Состояние',
                'Статус',
                'Процессор',
                'RAM, GB',
                'Накопитель',
                'Экран',
                'Видеокарта',
                'Продавец',
                'Рейтинг продавца, %',
                'Дата добавления',
                'Последнее обнаружение',
            ], ',', '"', '', "\r\n");

            foreach ($listings as $listing) {
                fputcsv($output, [
                    $this->spreadsheetSafeText($listing->title),
                    $this->spreadsheetSafeText($listing->searchProfiles->pluck('name')->join(', ')),
                    $this->spreadsheetSafeText($listing->ebay_item_id),
                    $this->spreadsheetSafeText($listing->item_url),
                    $listing->price,
                    $listing->shipping_price,
                    $listing->total_price,
                    $this->spreadsheetSafeText($listing->currency),
                    $this->spreadsheetSafeText($listing->condition),
                    match ($listing->status) {
                        'new' => 'Новый',
                        'watching' => 'Наблюдаю',
                        'buy' => 'Купить',
                        'skipped' => 'Пропустить',
                        default => $this->spreadsheetSafeText($listing->status),
                    },
                    $this->spreadsheetSafeText($listing->cpu),
                    $listing->ram_gb,
                    $this->spreadsheetSafeText($listing->storage),
                    $this->spreadsheetSafeText($listing->screen),
                    $this->spreadsheetSafeText($listing->gpu),
                    $this->spreadsheetSafeText($listing->seller),
                    $listing->seller_feedback,
                    $listing->created_at?->toDateTimeString(),
                    $listing->seen_at?->toDateTimeString(),
                ], ',', '"', '', "\r\n");
            }

            fclose($output);
        }, 'ebay-listings-'.now()->format('Y-m-d-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function destroy(Request $request, LaptopListing $listing): Response
    {
        abort_unless($listing->searchProfiles()->where('search_profiles.user_id', $request->user()->id)->exists(), 404);

        $listing->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Товар перемещён в удалённые.']);
        }

        return back()->with('success', 'Товар перемещён в удалённые.');
    }

    public function restore(Request $request, int $listing): RedirectResponse
    {
        $deletedListing = LaptopListing::onlyTrashed()
            ->whereKey($listing)
            ->whereHas('searchProfiles', fn ($query) => $query->where('search_profiles.user_id', $request->user()->id))
            ->firstOrFail();

        $deletedListing->restore();

        return back()->with('success', 'Товар восстановлен.');
    }

    public function forceDestroy(Request $request, int $listing): RedirectResponse
    {
        $deletedListing = LaptopListing::onlyTrashed()
            ->whereKey($listing)
            ->whereHas('searchProfiles', fn ($query) => $query->where('search_profiles.user_id', $request->user()->id))
            ->firstOrFail();

        $deletedListing->forceDelete();

        return back()->with('success', 'Товар удалён окончательно.');
    }

    public function bulkForceDestroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'listing_ids' => ['required', 'array', 'min:1'],
            'listing_ids.*' => ['required', 'integer', 'distinct'],
        ]);
        $listingIds = array_map('intval', $data['listing_ids']);

        $deletedCount = DB::transaction(function () use ($request, $listingIds): int {
            $listings = LaptopListing::onlyTrashed()
                ->whereIn('id', $listingIds)
                ->whereHas('searchProfiles', fn (Builder $query) => $query->where('search_profiles.user_id', $request->user()->id))
                ->lockForUpdate()
                ->get();

            abort_unless($listings->count() === count($listingIds), 404);

            foreach ($listings as $listing) {
                $listing->forceDelete();
            }

            return $listings->count();
        });

        return back()->with('success', "{$deletedCount} товаров удалено окончательно.");
    }

    private function spreadsheetSafeText(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return preg_match('/^[\x00-\x20]*[=+\-@]/u', $value) === 1
            ? "'".$value
            : $value;
    }
}
