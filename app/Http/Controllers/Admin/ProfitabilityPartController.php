<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProfitabilityCalculation;
use App\Models\ProfitabilityPart;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfitabilityPartController extends Controller
{
    public function index(): View
    {
        return view('admin.profitability-parts', [
            'parts' => ProfitabilityPart::query()->orderBy('name')->paginate(25),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:profitability_parts,name'],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
        ]);

        ProfitabilityPart::query()->create($data);

        return to_route('admin.profitability-parts.index')->with('success', 'Запчасть добавлена.');
    }

    public function update(Request $request, ProfitabilityPart $part): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('profitability_parts', 'name')->ignore($part->id)],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'is_active' => ['required', 'boolean'],
        ]);

        $oldPrice = $part->getOriginal('price');
        $newPrice = $data['price'];

        $part->update($data);

        if ((string) $oldPrice !== (string) $newPrice) {
            $this->recalculateAffectedCalculations($part->id, $oldPrice, $newPrice);
        }

        return to_route('admin.profitability-parts.index')->with('success', 'Запчасть обновлена.');
    }

    public function destroy(ProfitabilityPart $part): RedirectResponse
    {
        $part->update(['is_active' => false]);

        return to_route('admin.profitability-parts.index')->with('success', 'Запчасть скрыта из списка выбора.');
    }

    public function forceDelete(ProfitabilityPart $part): RedirectResponse
    {
        $part->delete();

        return to_route('admin.profitability-parts.index')->with('success', 'Запчасть удалена.');
    }

    /**
     * Пересчитывает все расчёты рентабельности, где используется данная запчасть.
     */
    private function recalculateAffectedCalculations(int $partId, string|int|float $oldPrice, string|int|float $newPrice): void
    {
        $siteSettings = SiteSetting::query()->findOrFail(1);

        // Находим все расчёты, где есть эта запчасть в parts_snapshot
        $calculations = ProfitabilityCalculation::query()
            ->whereRaw("exists (select 1 from jsonb_array_elements(parts_snapshot::jsonb) as elem where elem->>'id' = ?::text)", [(string) $partId])
            ->get();

        foreach ($calculations as $calculation) {
            $partsSnapshot = $calculation->parts_snapshot ?? [];
            $currencyCode = $calculation->currency_code;

            // Обновляем цену запчасти в snapshot
            $priceChanged = false;
            foreach ($partsSnapshot as &$partData) {
                if (isset($partData['id']) && (int) $partData['id'] === $partId) {
                    $newPartPrice = $currencyCode === 'USD'
                        ? (float) $newPrice / (float) $siteSettings->kgs_per_usd
                        : (float) $newPrice;

                    $oldPartPrice = $currencyCode === 'USD'
                        ? (float) $oldPrice / (float) $siteSettings->kgs_per_usd
                        : (float) $oldPrice;

                    $partData['price'] = number_format($newPartPrice, 2, '.', '');
                    $priceChanged = true;
                    break;
                }
            }
            unset($partData);

            if (! $priceChanged) {
                continue;
            }

            $calculation->parts_snapshot = $partsSnapshot;

            // Пересчитываем стоимость запчастей
            $partsCostCents = collect($partsSnapshot)
                ->sum(fn (array $part): int => $this->moneyToCents((string) $part['price']));

            // Пересчитываем общие затраты
            $totalCostCents = $this->moneyToCents($calculation->purchase_price)
                + $this->moneyToCents($calculation->additional_costs)
                + $this->moneyToCents($calculation->advertising_costs)
                + $this->moneyToCents($calculation->customs_cost)
                + $partsCostCents;

            $salePriceCents = $this->moneyToCents($calculation->sale_price);
            $commissionAmountCents = 0;
            $netProfitCents = $salePriceCents - $totalCostCents - $commissionAmountCents;

            $calculation->total_cost = $this->centsToMoney($totalCostCents);
            $calculation->commission_amount = $this->centsToMoney($commissionAmountCents);
            $calculation->net_profit = $this->centsToMoney($netProfitCents);
            $calculation->roi_percentage = $totalCostCents > 0 ? round($netProfitCents / $totalCostCents * 100, 2) : 0;
            $calculation->profit_margin_percentage = $salePriceCents > 0
                ? round($netProfitCents / $salePriceCents * 100, 2)
                : 0;

            $calculation->save();
        }
    }

    private function moneyToCents(string|int|float $amount): int
    {
        return (int) round((float) $amount * 100);
    }

    private function centsToMoney(int $amount): float
    {
        return $amount / 100;
    }
}
