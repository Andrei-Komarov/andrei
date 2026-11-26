<?php

namespace App\Services;

use App\Models\LaptopListing;
use App\Models\SearchProfile;

class ListingSpecificationService
{
    public const LAPTOP_BRANDS = [
        'Alienware',
        'Apple',
        'Acer',
        'ASUS',
        'Chuwi',
        'Dell',
        'Framework',
        'Fujitsu',
        'Gateway',
        'Gigabyte',
        'HP',
        'Huawei',
        'Lenovo',
        'LG',
        'Microsoft',
        'MSI',
        'Panasonic',
        'Razer',
        'Samsung',
        'Toshiba',
        'VAIO',
    ];

    /**
     * @param  array<string, mixed>  $aspects
     * @return array{cpu: ?string, ram_gb: ?int, storage: ?string, screen: ?string, gpu: ?string}
     */
    public function extract(string $title, array $aspects): array
    {
        $normalizedAspects = collect($aspects)
            ->mapWithKeys(fn (mixed $value, string $key): array => [mb_strtolower(trim($key)) => is_scalar($value) ? (string) $value : ''])
            ->all();

        return [
            'cpu' => $this->aspect($normalizedAspects, ['processor', 'cpu'])
                ?? $this->match($title, '/\b(?:Intel\s+)?Core\s+(?:Ultra\s+)?i?[3579](?:[- ]\d{4,5}[-A-Z]\d{0,2})?[- ]?(?:\d{1,2}\s*th\s*Gen)?|\bAMD\s+Ryzen\s+[3579](?:[- ]\d{3,5}[-A-Z]{0,2})?|\bRyzen\s+[3579](?:[- ]\d{3,5}[-A-Z]{0,2})?|\bApple\s+M[1-4](?:\s+(?:Pro|Max|Ultra))?/i'),
            'ram_gb' => $this->ram($normalizedAspects)
                ?? $this->titleRam($title),
            'storage' => $this->aspect($normalizedAspects, ['storage capacity', 'hard drive capacity', 'ssd capacity', 'storage type'])
                ?? $this->match($title, '/\b\d+(?:[.,]\d+)?\s*(?:TB|GB)\s*(?:NVMe(?:\s+SSD)?|SSD|HDD|eMMC)\b/i'),
            'screen' => $this->aspect($normalizedAspects, ['screen size'])
                ?? $this->titleScreen($title),
            'gpu' => $this->aspect($normalizedAspects, ['gpu', 'graphics processing type'])
                ?? $this->match($title, '/\b(?:(?:NVIDIA|AMD)\s+)?(?:(?:GeForce|Radeon)\s+)?(?:RTX|GTX)\s*\d{3,4}(?:\s*(?:Ti|SUPER))?\b/i')
                ?? $this->match($title, '/\b(?:NVIDIA\s+)?GeForce\s+MX\s*\d{3}\b|\bIntel\s+(?:Arc|Iris\s+Xe|UHD\s+Graphics)\b/i'),
        ];
    }

    /**
     * @param  array<string, mixed>  $aspects
     */
    public function matchesSelectedProcessor(SearchProfile $profile, string $title, array $aspects): bool
    {
        $selectedProcessors = $profile->processors ?: [$profile->processor];
        $selectedProcessors = collect($selectedProcessors)
            ->filter(fn (mixed $processor): bool => is_string($processor) && trim($processor) !== '')
            ->values();

        if ($selectedProcessors->isEmpty()) {
            return true;
        }

        $actualProcessor = $this->aspect(
            collect($aspects)
                ->mapWithKeys(fn (mixed $value, string $key): array => [
                    mb_strtolower(trim($key)) => is_scalar($value) ? (string) $value : '',
                ])
                ->all(),
            ['processor', 'cpu'],
        );

        if ($actualProcessor !== null) {
            $actualProcessor = $this->normalizeProcessor($actualProcessor);

            return $selectedProcessors->contains(function (string $selectedProcessor) use ($actualProcessor): bool {
                $selectedProcessor = $this->normalizeProcessor($selectedProcessor);

                return $actualProcessor === $selectedProcessor
                    || (preg_match('/^i[3579]$/', $selectedProcessor) === 1
                        && str_starts_with($actualProcessor, $selectedProcessor));
            });
        }

        $normalizedTitle = $this->normalizeProcessor($title);

        return $selectedProcessors->contains(function (string $selectedProcessor) use ($normalizedTitle): bool {
            $selectedProcessor = $this->normalizeProcessor($selectedProcessor);

            return $selectedProcessor !== ''
                && str_contains($normalizedTitle, $selectedProcessor);
        });
    }

    public function isAccessoryTitle(string $title): bool
    {
        return preg_match(
            '/\b(?:for\s+parts(?:\s+only)?|parts\s+only|not\s+working|spares?\s+or\s+repair|laptop\s+parts|notebook\s+parts|replacement\s+(?:laptop\s+)?(?:battery|charger|keyboard|screen|display|lcd|motherboard|fan|hinge|bezel|palmrest|trackpad)|(?:battery|charger|keyboard|screen|display|lcd|motherboard|fan|hinge|bezel|palmrest|trackpad|dock|sleeve|bag|case|cover|adapter)\s+(?:for|replacement|spare|only)|(?:motherboard|palmrest|hinge|bezel|trackpad|touchpad|heatsink|cooling fan|docking station|dock|laptop sleeve|laptop bag|screen assembly|display panel)\b)\b|(?:аккумулятор|батарея|клавиатура|экран|матрица|зарядное устройство|блок питания|запчасти|чехол|сумка)\s+(?:для|замена|оригинальный)?/iu',
            $title,
        ) === 1
            || preg_match(
                '/\b(?:battery|charger|keyboard|screen|display|lcd|motherboard|fan|hinge|bezel|palmrest|trackpad|dock|sleeve|bag|case|cover|adapter)\s*$/iu',
                $title,
            ) === 1;
    }

    /**
     * @return array{cpu: ?string, ram_gb: ?int, storage: ?string, screen: ?string, gpu: ?string}
     */
    public function forListing(LaptopListing $listing): array
    {
        $extracted = $this->extract($listing->title, $listing->specifications ?? []);

        return [
            'cpu' => $listing->cpu ?: $extracted['cpu'],
            'ram_gb' => $listing->ram_gb ?: $extracted['ram_gb'],
            'storage' => $listing->storage ?: $extracted['storage'],
            'screen' => $listing->screen ?: $extracted['screen'],
            'gpu' => $listing->gpu ?: $extracted['gpu'],
        ];
    }

    public function brandForListing(LaptopListing $listing): ?string
    {
        $normalizedSpecifications = collect($listing->specifications ?? [])
            ->mapWithKeys(fn (mixed $value, string $key): array => [mb_strtolower(trim($key)) => $value]);

        foreach (['brand', 'manufacturer'] as $key) {
            $brand = $normalizedSpecifications->get($key);

            if (is_string($brand) && trim($brand) !== '') {
                return trim($brand);
            }

            if (is_array($brand)) {
                $brandValues = collect($brand)
                    ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
                    ->map(fn (string $value): string => trim($value))
                    ->values();

                if ($brandValues->isNotEmpty()) {
                    return $brandValues->implode(', ');
                }
            }
        }

        foreach (self::LAPTOP_BRANDS as $brand) {
            if (preg_match('/\b'.preg_quote($brand, '/').'\b/iu', $listing->title) === 1) {
                return $brand;
            }
        }

        return null;
    }

    /**
     * @param  array<string, string>  $aspects
     * @param  list<string>  $keys
     */
    private function aspect(array $aspects, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (filled($aspects[$key] ?? null)) {
                return trim($aspects[$key]);
            }
        }

        return null;
    }

    /**
     * @param  array<string, string>  $aspects
     */
    private function ram(array $aspects): ?int
    {
        $value = $this->aspect($aspects, ['ram size', 'memory']);

        if ($value === null || preg_match('/\d+/', $value, $matches) !== 1) {
            return null;
        }

        return (int) $matches[0];
    }

    private function titleRam(string $title): ?int
    {
        if (preg_match('/\b(\d{1,3})\s*GB\s*(?:DDR[345]\s*)?RAM\b|\bRAM\s*:?\s*(\d{1,3})\s*GB\b/i', $title, $matches, PREG_UNMATCHED_AS_NULL) !== 1) {
            return null;
        }

        $ram = $matches[1] ?? $matches[2] ?? null;

        return $ram === null ? null : (int) $ram;
    }

    private function titleScreen(string $title): ?string
    {
        if (preg_match('/\b(\d{1,2}(?:\.\d{1,2})?)\s*(inches|inch|in|″|")(?=\s|$|\b)/i', $title, $matches) !== 1) {
            return null;
        }

        return $matches[1].' '.strtolower($matches[2]);
    }

    private function match(string $value, string $pattern): ?string
    {
        return preg_match($pattern, $value, $matches) === 1 ? trim($matches[0]) : null;
    }

    private function normalizeProcessor(string $processor): string
    {
        $processor = mb_strtolower($processor);
        $processor = preg_replace('/\b(?:intel|core|ultra)\b/u', '', $processor) ?? $processor;

        return preg_replace('/[^a-z0-9]+/u', '', $processor) ?? '';
    }
}
