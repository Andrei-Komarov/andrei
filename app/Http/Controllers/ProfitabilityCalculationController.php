<?php

namespace App\Http\Controllers;

use App\Models\LaptopListing;
use App\Models\ProfitabilityCalculation;
use App\Models\ProfitabilityPart;
use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfitabilityCalculationController extends Controller
{
    public function index(Request $request): View
    {
        $sort = $request->validate([
            'sort' => ['nullable', Rule::in([
                'product_asc', 'product_desc',
                'purchase_price_asc', 'purchase_price_desc',
                'additional_costs_asc', 'additional_costs_desc',
                'advertising_costs_asc', 'advertising_costs_desc',
                'customs_cost_asc', 'customs_cost_desc',
                'total_cost_asc', 'total_cost_desc',
                'sale_price_asc', 'sale_price_desc',
                'commission_amount_asc', 'commission_amount_desc',
                'net_profit_asc', 'net_profit_desc',
                'roi_percentage_asc', 'roi_percentage_desc',
                'profit_margin_percentage_asc', 'profit_margin_percentage_desc',
                'created_at_asc', 'created_at_desc',
            ])],
        ])['sort'] ?? 'created_at_desc';
        $sortDirection = str_ends_with($sort, '_asc') ? 'asc' : 'desc';
        $sortKey = substr($sort, 0, -strlen('_'.$sortDirection));

        $calculations = $request->user()
            ->profitabilityCalculations()
            ->with('listing')
            ->orderBy(match ($sortKey) {
                'product' => 'listing_title',
                'purchase_price' => 'purchase_price',
                'additional_costs' => 'additional_costs',
                'advertising_costs' => 'advertising_costs',
                'customs_cost' => 'customs_cost',
                'total_cost' => 'total_cost',
                'sale_price' => 'sale_price',
                'commission_amount' => 'commission_amount',
                'net_profit' => 'net_profit',
                'roi_percentage' => 'roi_percentage',
                'profit_margin_percentage' => 'profit_margin_percentage',
                default => 'created_at',
            }, $sortDirection)
            ->orderBy('id', $sortDirection)
            ->paginate(25)
            ->withQueryString();

        $listings = LaptopListing::query()
            ->whereNull('deleted_at')
            ->where('excluded', false)
            ->where('checked', true)
            ->whereHas('searchProfiles', fn (Builder $query) => $query->where('search_profiles.user_id', $request->user()->id))
            ->whereDoesntHave('profitabilityCalculations', fn (Builder $query) => $query->where('user_id', $request->user()->id))
            ->select('laptop_listings.*')
            ->distinct('ebay_item_id')
            ->orderBy('ebay_item_id')
            ->orderBy('title')
            ->get(['id', 'title', 'price', 'shipping_price', 'total_price', 'currency']);
        $parts = ProfitabilityPart::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'price']);
        $siteSettings = SiteSetting::query()->findOrFail(1);

        return view('profitability.index', [
            'calculations' => $calculations,
            'listings' => $listings,
            'parts' => $parts,
            'sort' => $sort,
            'currencyCode' => $siteSettings->profitability_currency,
            'currencyLabel' => $siteSettings->profitability_currency === 'USD' ? 'USD' : 'сом',
            'kgsPerUsd' => $siteSettings->kgs_per_usd,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $partIds = $request->input('part_ids');

        if (is_array($partIds)) {
            $request->merge([
                'part_ids' => array_values(array_filter(
                    $partIds,
                    fn (mixed $partId): bool => $partId !== null && $partId !== '',
                )),
            ]);
        }

        $data = $request->validate([
            'listing_id' => ['required', 'integer'],
            'purchase_price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'additional_costs' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'advertising_costs' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'customs_cost' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'sale_price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'part_ids' => ['nullable', 'array', 'max:30'],
            'part_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('profitability_parts', 'id')->where('is_active', true),
            ],
        ]);

        $listing = LaptopListing::query()
            ->whereKey($data['listing_id'])
            ->whereHas('searchProfiles', fn (Builder $query) => $query->where('search_profiles.user_id', $request->user()->id))
            ->firstOrFail();

        $selectedParts = ProfitabilityPart::query()
            ->where('is_active', true)
            ->whereKey($data['part_ids'] ?? [])
            ->orderBy('name')
            ->get(['id', 'name', 'price']);
        $siteSettings = SiteSetting::query()->findOrFail(1);
        $currencyCode = $siteSettings->profitability_currency;
        $partsSnapshot = $selectedParts
            ->map(function (ProfitabilityPart $part) use ($currencyCode, $siteSettings): array {
                $price = $currencyCode === 'USD'
                    ? (float) $part->price / (float) $siteSettings->kgs_per_usd
                    : (float) $part->price;

                return [
                    'id' => $part->id,
                    'name' => $part->name,
                    'price' => number_format($price, 2, '.', ''),
                ];
            })
            ->all();

        $partsCostCents = collect($partsSnapshot)
            ->sum(fn (array $part): int => $this->moneyToCents((string) $part['price']));
        $totalCostCents = $this->moneyToCents($data['purchase_price'])
            + $this->moneyToCents($data['additional_costs'])
            + $this->moneyToCents($data['advertising_costs'])
            + $this->moneyToCents($data['customs_cost'])
            + $partsCostCents;
        $salePriceCents = $this->moneyToCents($data['sale_price']);
        $commissionAmountCents = 0;
        $netProfitCents = $salePriceCents - $totalCostCents - $commissionAmountCents;
        $totalCost = $this->centsToMoney($totalCostCents);
        $commissionAmount = $this->centsToMoney($commissionAmountCents);
        $netProfit = $this->centsToMoney($netProfitCents);

        $request->user()->profitabilityCalculations()->create([
            'listing_id' => $listing->id,
            'listing_title' => $listing->title,
            'currency_code' => $currencyCode,
            'purchase_price' => $data['purchase_price'],
            'additional_costs' => $data['additional_costs'],
            'advertising_costs' => $data['advertising_costs'],
            'customs_cost' => $data['customs_cost'],
            'parts_snapshot' => $partsSnapshot,
            'sale_price' => $data['sale_price'],
            'commission_percent' => 0,
            'total_cost' => $totalCost,
            'commission_amount' => $commissionAmount,
            'net_profit' => $netProfit,
            'roi_percentage' => $totalCostCents > 0 ? round($netProfitCents / $totalCostCents * 100, 2) : 0,
            'profit_margin_percentage' => $salePriceCents > 0
                ? round($netProfitCents / $salePriceCents * 100, 2)
                : 0,
        ]);

        return to_route('profitability.index')->with('success', 'Расчёт рентабельности сохранён.');
    }

    public function edit(Request $request, ProfitabilityCalculation $calculation): JsonResponse
    {
        $request->user()->profitabilityCalculations()
            ->findOrFail($calculation->id);

        $parts = ProfitabilityPart::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'price']);
        $siteSettings = SiteSetting::query()->findOrFail(1);
        $currencyCode = $siteSettings->profitability_currency;

        return response()->json([
            'calculation' => $calculation,
            'parts' => $parts,
            'currency_code' => $currencyCode,
            'currency_label' => $currencyCode === 'USD' ? 'USD' : 'сом',
            'kgs_per_usd' => $siteSettings->kgs_per_usd,
        ]);
    }

    public function update(Request $request, ProfitabilityCalculation $calculation): RedirectResponse
    {
        $request->user()->profitabilityCalculations()
            ->findOrFail($calculation->id);

        $partIds = $request->input('part_ids');

        if (is_array($partIds)) {
            $request->merge([
                'part_ids' => array_values(array_filter(
                    $partIds,
                    fn (mixed $partId): bool => $partId !== null && $partId !== '',
                )),
            ]);
        }

        $data = $request->validate([
            'purchase_price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'additional_costs' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'advertising_costs' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'customs_cost' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'sale_price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'part_ids' => ['nullable', 'array', 'max:30'],
            'part_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('profitability_parts', 'id')->where('is_active', true),
            ],
        ]);

        $selectedParts = ProfitabilityPart::query()
            ->where('is_active', true)
            ->whereKey($data['part_ids'] ?? [])
            ->orderBy('name')
            ->get(['id', 'name', 'price']);
        $siteSettings = SiteSetting::query()->findOrFail(1);
        $currencyCode = $calculation->currency_code;
        $partsSnapshot = $selectedParts
            ->map(function (ProfitabilityPart $part) use ($currencyCode, $siteSettings): array {
                $price = $currencyCode === 'USD'
                    ? (float) $part->price / (float) $siteSettings->kgs_per_usd
                    : (float) $part->price;

                return [
                    'id' => $part->id,
                    'name' => $part->name,
                    'price' => number_format($price, 2, '.', ''),
                ];
            })
            ->all();

        $partsCostCents = collect($partsSnapshot)
            ->sum(fn (array $part): int => $this->moneyToCents((string) $part['price']));
        $totalCostCents = $this->moneyToCents($data['purchase_price'])
            + $this->moneyToCents($data['additional_costs'])
            + $this->moneyToCents($data['advertising_costs'])
            + $this->moneyToCents($data['customs_cost'])
            + $partsCostCents;
        $salePriceCents = $this->moneyToCents($data['sale_price']);
        $commissionAmountCents = 0;
        $netProfitCents = $salePriceCents - $totalCostCents - $commissionAmountCents;
        $totalCost = $this->centsToMoney($totalCostCents);
        $commissionAmount = $this->centsToMoney($commissionAmountCents);
        $netProfit = $this->centsToMoney($netProfitCents);

        $calculation->update([
            'purchase_price' => $data['purchase_price'],
            'additional_costs' => $data['additional_costs'],
            'advertising_costs' => $data['advertising_costs'],
            'customs_cost' => $data['customs_cost'],
            'parts_snapshot' => $partsSnapshot,
            'sale_price' => $data['sale_price'],
            'total_cost' => $totalCost,
            'commission_amount' => $commissionAmount,
            'net_profit' => $netProfit,
            'roi_percentage' => $totalCostCents > 0 ? round($netProfitCents / $totalCostCents * 100, 2) : 0,
            'profit_margin_percentage' => $salePriceCents > 0
                ? round($netProfitCents / $salePriceCents * 100, 2)
                : 0,
        ]);

        return to_route('profitability.index')->with('success', 'Расчёт обновлён.');
    }

    public function destroy(Request $request, ProfitabilityCalculation $calculation): RedirectResponse
    {
        $request->user()->profitabilityCalculations()
            ->findOrFail($calculation->id)
            ->delete();

        return to_route('profitability.index')->with('success', 'Расчёт удалён из истории.');
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
