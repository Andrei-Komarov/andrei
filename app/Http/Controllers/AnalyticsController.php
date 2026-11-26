<?php

namespace App\Http\Controllers;

use App\Models\LaptopListing;
use App\Models\SearchProfile;
use App\Models\SearchProfileActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', 30);
        $since = now()->subDays($period);

        $productsByBrand = LaptopListing::whereNull('deleted_at')
            ->where('excluded', false)
            ->selectRaw('specifications->>\'$.brand\' as brand, count(*) as count')
            ->groupBy('brand')
            ->orderByDesc('count')
            ->get();

        $brandLabels = $productsByBrand->pluck('brand')->filter()->map(fn ($b) => $b ?: 'Без бренда')->toArray();
        $brandCounts = $productsByBrand->pluck('count')->toArray();

        $productsByCondition = LaptopListing::whereNull('deleted_at')
            ->where('excluded', false)
            ->selectRaw('condition, count(*) as count')
            ->groupBy('condition')
            ->orderByDesc('count')
            ->get();

        $conditionLabels = $productsByCondition->pluck('condition')->filter()->map(fn ($c) => match ($c) {
            'New' => 'Новый',
            'Refurbished' => 'Восстановленный',
            'Used' => 'Б/у',
            default => $c,
        })->toArray();
        $conditionCounts = $productsByCondition->pluck('count')->toArray();

        $priceRanges = [
            ['$0–$20', 0, 20],
            ['$20–$50', 20, 50],
            ['$50–$100', 50, 100],
            ['$100–$200', 100, 200],
            ['$200+', 200, 999999],
        ];

        $priceDistribution = [];
        foreach ($priceRanges as [$label, $min, $max]) {
            $count = LaptopListing::whereNull('deleted_at')
                ->where('excluded', false)
                ->where('total_price', '>=', $min)
                ->where('total_price', '<=', $max)
                ->count();
            $priceDistribution[] = ['label' => $label, 'count' => $count];
        }

        $importStats = SearchProfileActivity::where('created_at', '>=', now()->subDays(7))
            ->whereIn('action', ['parse_started', 'parse_completed', 'parse_failed'])
            ->selectRaw('date(created_at) as date, action, count(*) as count')
            ->groupBy('date', 'action')
            ->orderBy('date')
            ->get();

        $importDates = $importStats->pluck('date')->unique()->toArray();
        $importCompleted = [];
        $importFailed = [];
        foreach ($importDates as $date) {
            $importCompleted[] = $importStats->where('date', $date)->where('action', 'parse_completed')->sum('count');
            $importFailed[] = $importStats->where('date', $date)->where('action', 'parse_failed')->sum('count');
        }

        $profileActivity = SearchProfile::withCount([
            'profileListings as total_count' => fn ($q) => $q->whereNull('laptop_listings.deleted_at')->where('laptop_listings.excluded', false),
            'profileListings as new_count' => fn ($q) => $q->whereNull('laptop_listings.deleted_at')->where('laptop_listings.excluded', false)->where('laptop_listings.status', 'new'),
        ])->orderByDesc('total_count')->get();

        $profileNames = $profileActivity->pluck('name')->toArray();
        $profileTotal = $profileActivity->pluck('total_count')->toArray();
        $profileNew = $profileActivity->pluck('new_count')->toArray();

        $priceDrops = DB::table('laptop_listing_price_histories')
            ->join('laptop_listings', 'laptop_listing_price_histories.laptop_listing_id', '=', 'laptop_listings.id')
            ->where('laptop_listing_price_histories.created_at', '>=', $since)
            ->where('laptop_listings.deleted_at', null)
            ->where('laptop_listings.excluded', false)
            ->selectRaw('date(laptop_listing_price_histories.created_at) as date, count(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $priceDropDates = $priceDrops->pluck('date')->toArray();
        $priceDropCounts = $priceDrops->pluck('count')->toArray();

        $newItems = LaptopListing::where('created_at', '>=', $since)
            ->whereNull('deleted_at')
            ->where('excluded', false)
            ->selectRaw('date(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $newItemDates = $newItems->pluck('date')->toArray();
        $newItemCounts = $newItems->pluck('count')->toArray();

        $priceTrend = DB::table('laptop_listing_price_histories')
            ->join('laptop_listings', 'laptop_listing_price_histories.laptop_listing_id', '=', 'laptop_listings.id')
            ->where('laptop_listing_price_histories.created_at', '>=', $since)
            ->where('laptop_listings.deleted_at', null)
            ->where('laptop_listings.excluded', false)
            ->selectRaw('date(laptop_listing_price_histories.created_at) as date, avg(laptop_listing_price_histories.price) as avg_price')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $priceTrendDates = $priceTrend->pluck('date')->toArray();
        $priceTrendAvgValues = $priceTrend->pluck('avg_price')->map(fn ($p) => round($p, 2))->toArray();
        $priceTrendAvg = (object) [
            'average' => count($priceTrendAvgValues) > 0 ? round(collect($priceTrendAvgValues)->avg(), 2) : 0,
            'min' => count($priceTrendAvgValues) > 0 ? round(min($priceTrendAvgValues), 2) : 0,
            'max' => count($priceTrendAvgValues) > 0 ? round(max($priceTrendAvgValues), 2) : 0,
        ];

        $stats = [
            'total_products' => LaptopListing::whereNull('deleted_at')->where('excluded', false)->count(),
            'new_products' => LaptopListing::whereNull('deleted_at')->where('excluded', false)->where('status', 'new')->count(),
            'watching_products' => LaptopListing::whereNull('deleted_at')->where('excluded', false)->where('status', 'watching')->count(),
            'buy_products' => LaptopListing::whereNull('deleted_at')->where('excluded', false)->where('status', 'buy')->count(),
            'excluded_products' => LaptopListing::whereNull('deleted_at')->where('excluded', true)->count(),
            'avg_price' => LaptopListing::whereNull('deleted_at')->where('excluded', false)->avg('total_price') ?? 0,
            'min_price' => LaptopListing::whereNull('deleted_at')->where('excluded', false)->min('total_price') ?? 0,
            'max_price' => LaptopListing::whereNull('deleted_at')->where('excluded', false)->max('total_price') ?? 0,
        ];

        return view('analytics.index', compact(
            'period',
            'stats',
            'productsByBrand',
            'productsByCondition',
            'brandLabels',
            'brandCounts',
            'conditionLabels',
            'conditionCounts',
            'priceDistribution',
            'importDates',
            'importCompleted',
            'importFailed',
            'profileActivity',
            'profileNames',
            'profileTotal',
            'profileNew',
            'priceDropDates',
            'priceDropCounts',
            'newItemDates',
            'newItemCounts',
            'priceTrendDates',
            'priceTrendAvg',
        ));
    }
}
