<?php

namespace App\Services;

use App\Models\LaptopListing;
use Illuminate\Support\Collection;

class ListingValueAssessmentService
{
    private const MINIMUM_COMPARABLES = 3;

    private const FEATURES = [
        'cpu' => 2,
        'ram_gb' => 2,
        'storage' => 2,
        'screen' => 1,
        'gpu' => 2,
    ];

    /**
     * @param  Collection<int, LaptopListing>  $listings
     * @return array<int, array{score: int|null, comparison_count: int, factors: array<int, array{label: string, score: int, maximum: int, explanation: string}>}>
     */
    public function assessPage(Collection $listings): array
    {
        $comparableListings = LaptopListing::query()
            ->whereIn('search_profile_id', $listings->pluck('search_profile_id')->unique())
            ->whereIn('condition', $listings->pluck('condition')->filter()->unique())
            ->whereIn('currency', $listings->pluck('currency')->unique())
            ->whereNotNull('total_price')
            ->get(['id', 'search_profile_id', 'condition', 'currency', 'total_price', 'shipping_price', ...array_keys(self::FEATURES)])
            ->groupBy(fn (LaptopListing $listing): string => $this->comparisonGroupKey($listing));

        $assessments = [];

        foreach ($listings as $listing) {
            $candidates = $listing->condition === null || $listing->total_price === null
                ? collect()
                : $comparableListings
                    ->get($this->comparisonGroupKey($listing), collect())
                    ->reject(fn (LaptopListing $candidate): bool => $candidate->is($listing))
                    ->filter(fn (LaptopListing $candidate): bool => $this->isSimilar($listing, $candidate))
                    ->values();

            $assessments[$listing->id] = $this->assess($listing, $candidates);
        }

        return $assessments;
    }

    /**
     * @param  Collection<int, LaptopListing>  $comparables
     * @return array{score: int|null, comparison_count: int, factors: array<int, array{label: string, score: int, maximum: int, explanation: string}>}
     */
    public function assess(LaptopListing $listing, Collection $comparables): array
    {
        $price = (float) $listing->total_price;
        $medianComparablePrice = $this->median($comparables->pluck('total_price')->map(fn (mixed $value): float => (float) $value));
        $priceFactor = $this->priceFactor($price, $medianComparablePrice, (string) $listing->currency);
        $conditionFactor = $this->conditionFactor($listing->condition);
        $characteristicsFactor = $this->characteristicsFactor($listing);
        $shippingFactor = $this->shippingFactor($listing);
        $factors = [
            [
                'label' => 'Цена с доставкой',
                'score' => $priceFactor['score'],
                'maximum' => 60,
                'explanation' => $priceFactor['explanation'],
            ],
            [
                'label' => 'Состояние',
                'score' => $conditionFactor['score'],
                'maximum' => 15,
                'explanation' => $conditionFactor['explanation'],
            ],
            [
                'label' => 'Характеристики',
                'score' => $characteristicsFactor['score'],
                'maximum' => 15,
                'explanation' => $characteristicsFactor['explanation'],
            ],
            [
                'label' => 'Доставка',
                'score' => $shippingFactor['score'],
                'maximum' => 10,
                'explanation' => $shippingFactor['explanation'],
            ],
        ];

        if ($comparables->count() < self::MINIMUM_COMPARABLES || $medianComparablePrice <= 0) {
            return [
                'score' => null,
                'comparison_count' => $comparables->count(),
                'factors' => $factors,
            ];
        }

        return [
            'score' => (int) collect($factors)->sum('score'),
            'comparison_count' => $comparables->count(),
            'factors' => $factors,
        ];
    }

    private function comparisonGroupKey(LaptopListing $listing): string
    {
        return implode('|', [
            $listing->search_profile_id,
            mb_strtolower((string) $listing->currency),
            mb_strtolower((string) $listing->condition),
        ]);
    }

    private function isSimilar(LaptopListing $listing, LaptopListing $candidate): bool
    {
        $comparedWeight = 0;
        $matchingWeight = 0;

        foreach (self::FEATURES as $feature => $weight) {
            $listingValue = $this->normalizedFeatureValue($listing->{$feature});
            $candidateValue = $this->normalizedFeatureValue($candidate->{$feature});

            if ($listingValue === null || $candidateValue === null) {
                continue;
            }

            $comparedWeight += $weight;

            if ($listingValue === $candidateValue) {
                $matchingWeight += $weight;
            }
        }

        return $comparedWeight >= 3 && $matchingWeight / $comparedWeight >= 0.5;
    }

    private function normalizedFeatureValue(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $normalizedValue = mb_strtolower(trim((string) $value));

        return $normalizedValue === '' ? null : $normalizedValue;
    }

    private function median(Collection $prices): float
    {
        $orderedPrices = $prices->sort()->values();
        $count = $orderedPrices->count();

        if ($count === 0) {
            return 0;
        }

        $middleIndex = intdiv($count, 2);

        if ($count % 2 === 1) {
            return $orderedPrices->get($middleIndex);
        }

        return ($orderedPrices->get($middleIndex - 1) + $orderedPrices->get($middleIndex)) / 2;
    }

    /**
     * @return array{score: int, explanation: string}
     */
    private function priceFactor(float $price, float $medianPrice, string $currency): array
    {
        if ($medianPrice <= 0) {
            return ['score' => 0, 'explanation' => 'Не удалось определить медианную цену аналогов.'];
        }

        $differencePercent = (($medianPrice - $price) / $medianPrice) * 100;
        $score = (int) round(max(0, min(60, 30 + ($differencePercent * 1.5))));
        if ($differencePercent > 0) {
            $explanation = 'На '.number_format($differencePercent, 1, ',', ' ').'% ниже медианы похожих товаров.';
        } elseif ($differencePercent < 0) {
            $explanation = 'На '.number_format(abs($differencePercent), 1, ',', ' ').'% выше медианы похожих товаров.';
        } else {
            $explanation = 'Соответствует медианной цене похожих товаров.';
        }

        return [
            'score' => $score,
            'explanation' => $explanation.' Медиана: '.$currency.' '.number_format($medianPrice, 2, '.', ' ').'.',
        ];
    }

    /**
     * @return array{score: int, explanation: string}
     */
    private function conditionFactor(?string $condition): array
    {
        $normalizedCondition = mb_strtolower((string) $condition);

        $score = match (true) {
            in_array($normalizedCondition, ['new', 'brand new', 'new with tags', 'new other'], true) => 15,
            str_contains($normalizedCondition, 'refurbished') => 12,
            in_array($normalizedCondition, ['open box', 'like new'], true) => 13,
            in_array($normalizedCondition, ['used', 'pre-owned', 'pre owned', 'very good', 'good', 'acceptable'], true) => 9,
            default => 5,
        };

        return [
            'score' => $score,
            'explanation' => $condition === null
                ? 'Состояние товара не указано.'
                : "Состояние «{$condition}» оценено по фиксированной шкале.",
        ];
    }

    /**
     * @return array{score: int, explanation: string}
     */
    private function characteristicsFactor(LaptopListing $listing): array
    {
        $availableCount = collect(array_keys(self::FEATURES))
            ->filter(fn (string $feature): bool => filled($listing->{$feature}))
            ->count();
        $score = $availableCount * 3;

        return [
            'score' => $score,
            'explanation' => "Заполнено характеристик: {$availableCount} из ".count(self::FEATURES).'.',
        ];
    }

    /**
     * @return array{score: int, explanation: string}
     */
    private function shippingFactor(LaptopListing $listing): array
    {
        if ($listing->shipping_price === null) {
            return [
                'score' => 5,
                'explanation' => 'Стоимость доставки не указана; цена сравнения включает доступную итоговую стоимость.',
            ];
        }

        $shippingShare = (float) $listing->total_price > 0
            ? (float) $listing->shipping_price / (float) $listing->total_price
            : 0;
        $score = match (true) {
            $shippingShare === 0.0 => 10,
            $shippingShare <= 0.05 => 10,
            $shippingShare <= 0.10 => 7,
            $shippingShare <= 0.20 => 4,
            default => 1,
        };

        return [
            'score' => $score,
            'explanation' => $shippingShare === 0.0
                ? 'Бесплатная доставка.'
                : 'Доставка составляет '.number_format($shippingShare * 100, 1, ',', ' ').'% итоговой цены.',
        ];
    }
}
