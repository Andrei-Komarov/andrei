<?php

namespace Tests\Feature;

use App\Models\LaptopListing;
use App\Models\SearchProfile;
use App\Models\User;
use App\Models\UserListingNote;
use App\Notifications\NewEbayListingNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardProfileFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_listing_page_can_filter_listings_by_profile_and_shows_profile_listing_count(): void
    {
        $this->withoutVite();
        $selectedProfile = SearchProfile::factory()->create(['name' => 'ThinkPad']);
        $otherProfile = SearchProfile::factory()->create(['name' => 'Dell']);
        LaptopListing::factory()->create([
            'search_profile_id' => $selectedProfile->id,
            'title' => 'ThinkPad result 1',
        ]);
        LaptopListing::factory()->create([
            'search_profile_id' => $selectedProfile->id,
            'title' => 'ThinkPad result 2',
        ]);
        LaptopListing::factory()->create([
            'search_profile_id' => $otherProfile->id,
            'title' => 'Dell result',
        ]);

        $response = $this->get(route('listings.index', ['profile_id' => $selectedProfile->id]))
            ->assertOk()
            ->assertSee('Список товаров')
            ->assertSee('Все товары, найденные вашими профилями поиска')
            ->assertSee('Finder')
            ->assertDontSee('Не авторизован')
            ->assertDontSee('Верхняя навигация')
            ->assertSee('Аккаунт')
            ->assertSee('Товары')
            ->assertSee('Список товаров')
            ->assertSee('Удалённые товары')
            ->assertSee('Список наблюдения')
            ->assertSee('Купить')
            ->assertSee('data-table-key="listings"', false)
            ->assertSee('aria-label="Настроить столбцы таблицы"', false)
            ->assertSee('data-column-key="note"', false)
            ->assertSee('data-column-key="image">Фото', false)
            ->assertSee('data-column-key="brand" data-column-label="Бренд"', false)
            ->assertSee('name="bulk_action"', false)
            ->assertSee('aria-label="Выберите действие для товаров"', false)
            ->assertDontSee('Действие для выбранных')
            ->assertSee('data-confirm-dialog', false)
            ->assertSee('data-confirm-dialog-accept', false)
            ->assertSee('Список профилей')
            ->assertSee('Добавить профиль')
            ->assertSee('История поиска')
            ->assertDontSee(route('admin.settings'))
            ->assertDontSee(route('admin.payments'))
            ->assertDontSee('Телеграм')
            ->assertSee('Вопросы и ответы')
            ->assertSee('Написать администратору')
            ->assertSee(route('notifications.index'))
            ->assertSee('Фильтр по профилю')
            ->assertSee('id="listing-filters-form"', false)
            ->assertSee('form="listing-filters-form"', false)
            ->assertSee('Применить')
            ->assertSee('class="rounded border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Сбросить фильтры</a>', false)
            ->assertSee('ThinkPad')
            ->assertSee('товаров: 2')
            ->assertSee('В профиле «ThinkPad» всего товаров: <strong>2</strong>.', false)
            ->assertSee('ThinkPad result 1')
            ->assertSee('ThinkPad result 2')
            ->assertDontSee('Dell result');

        $content = $response->getContent();
        $listingTitlePosition = strpos($content, '<h1 class="text-2xl font-bold">Список товаров</h1>');
        $applyFiltersPosition = strpos($content, 'form="listing-filters-form"');
        $filterPosition = strpos($content, 'id="profile_id"');
        $tablePosition = strpos($content, '<table');
        $this->assertNotFalse($listingTitlePosition);
        $this->assertNotFalse($applyFiltersPosition);
        $this->assertLessThan($filterPosition, $applyFiltersPosition);
        $this->assertNotFalse($filterPosition);
        $this->assertNotFalse($tablePosition);
        $this->assertLessThan($tablePosition, $filterPosition);
        $imageColumnPosition = strpos($content, 'data-column-key="image">Фото');
        $listingColumnPosition = strpos($content, 'data-column-key="listing"');
        $this->assertNotFalse($imageColumnPosition);
        $this->assertNotFalse($listingColumnPosition);
        $this->assertLessThan($listingColumnPosition, $imageColumnPosition);

        $productsMenuPosition = strpos($content, '>Товары</h2>');
        $searchMenuPosition = strpos($content, '>Поисковые запросы</h2>');
        $newProductsPosition = strpos($content, '>Новые товары</span>');
        $watchlistPosition = strpos($content, '>Список наблюдения</a>');
        $buyPosition = strpos($content, '>Купить</a>');
        $deletedProductsPosition = strpos($content, '>Удалённые товары</a>');
        $productsListPosition = strpos($content, '>Список товаров</a>');
        $this->assertNotFalse($productsMenuPosition);
        $this->assertNotFalse($searchMenuPosition);
        $this->assertNotFalse($newProductsPosition);
        $this->assertNotFalse($watchlistPosition);
        $this->assertNotFalse($buyPosition);
        $this->assertNotFalse($deletedProductsPosition);
        $this->assertNotFalse($productsListPosition);
        $this->assertLessThan($searchMenuPosition, $productsMenuPosition);
        $this->assertLessThan($newProductsPosition, $productsListPosition);
        $this->assertLessThan($searchMenuPosition, $productsListPosition);
        $this->assertLessThan($newProductsPosition, $productsMenuPosition);
        $this->assertLessThan($searchMenuPosition, $newProductsPosition);
        $this->assertLessThan($deletedProductsPosition, $newProductsPosition);
        $this->assertLessThan($watchlistPosition, $newProductsPosition);
        $this->assertLessThan($deletedProductsPosition, $watchlistPosition);
        $this->assertLessThan($buyPosition, $watchlistPosition);
        $this->assertLessThan($deletedProductsPosition, $buyPosition);
        $this->assertLessThan($searchMenuPosition, $deletedProductsPosition);
    }

    public function test_listing_images_open_in_a_scrollable_zoomable_gallery(): void
    {
        $this->withoutVite();
        $listing = LaptopListing::factory()->create([
            'title' => 'Gallery laptop',
            'image_url' => 'https://i.ebayimg.com/images/g/main/s-l500.jpg',
            'raw_payload' => [
                'thumbnailImages' => [
                    ['imageUrl' => 'https://i.ebayimg.com/images/g/side/s-l300.jpg'],
                    ['imageUrl' => 'https://i.ebayimg.com/images/g/back/s-l300.jpg'],
                ],
                'additionalImages' => [
                    ['imageUrl' => 'https://i.ebayimg.com/images/g/keyboard/s-l300.jpg'],
                ],
            ],
        ]);
        $this->get(route('listings.index'))
            ->assertOk()
            ->assertSee('aria-label="Открыть фотографии товара: Gallery laptop"', false)
            ->assertSee('https://i.ebayimg.com/images/g/main/s-l1600.jpg')
            ->assertSee('https://i.ebayimg.com/images/g/side/s-l1600.jpg')
            ->assertSee('https://i.ebayimg.com/images/g/back/s-l1600.jpg')
            ->assertSee('https://i.ebayimg.com/images/g/keyboard/s-l1600.jpg')
            ->assertSee('data-listing-image-gallery-dialog', false)
            ->assertSee('aria-modal="true"', false)
            ->assertSee('fixed left-1/2 top-1/2', false)
            ->assertSee('backdrop:bg-slate-900/60', false)
            ->assertSee('data-gallery-viewport', false)
            ->assertSee('data-gallery-previous', false)
            ->assertSee('data-gallery-next', false)
            ->assertSee('data-gallery-zoom-in', false)
            ->assertSee('data-gallery-zoom-out', false);
    }

    public function test_listing_table_extracts_missing_characteristics_from_existing_titles(): void
    {
        $this->withoutVite();
        LaptopListing::factory()->create([
            'title' => 'Acer Laptop Intel Core i5 8 GB RAM 512 GB SSD 15.6 in NVIDIA GeForce RTX 4060',
        ]);

        $this->get(route('listings.index'))
            ->assertOk()
            ->assertSee('data-listing-specifications', false)
            ->assertSee('CPU: Intel Core i5<br>', false)
            ->assertSee('RAM: 8 GB · SSD: 512 GB SSD<br>', false)
            ->assertSee('Экран: 15.6 in · GPU: NVIDIA GeForce RTX 4060', false);
    }

    public function test_listing_table_displays_all_ebay_item_specifics_and_seller_description(): void
    {
        $this->withoutVite();
        LaptopListing::factory()->create([
            'title' => 'Business laptop',
            'specifications' => [
                'Brand' => 'Lenovo',
                'Model' => 'ThinkPad T14',
                'Operating System' => 'Windows 11 Pro',
                'Connectivity' => 'Wi-Fi, Bluetooth',
            ],
            'seller_description' => 'Продаю ноутбук. Есть оригинальное зарядное устройство.',
        ]);

        $this->get(route('listings.index'))
            ->assertOk()
            ->assertSee('data-column-key="seller-description"', false)
            ->assertSee('data-listing-specifications', false)
            ->assertSee('Brand:')
            ->assertSee('Lenovo')
            ->assertSee('Model:')
            ->assertSee('ThinkPad T14')
            ->assertSee('Operating System:')
            ->assertSee('Windows 11 Pro')
            ->assertSee('Connectivity:')
            ->assertSee('Wi-Fi, Bluetooth')
            ->assertSee('data-listing-seller-description', false)
            ->assertSee('Продаю ноутбук. Есть оригинальное зарядное устройство.');
    }

    public function test_sitebar_shows_the_authenticated_users_name_and_email(): void
    {
        $this->withoutVite();
        $user = User::factory()->create([
            'name' => 'Alex Example',
            'email' => 'alex@example.com',
        ]);

        $this->actingAs($user)
            ->get(route('listings.index'))
            ->assertOk()
            ->assertSee('Alex Example')
            ->assertSee('alex@example.com')
            ->assertDontSee('Не авторизован')
            ->assertDontSee('Верхняя навигация');
    }

    public function test_guest_sees_login_and_registration_links_and_cannot_open_dashboard(): void
    {
        auth()->logout();
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('register'));
    }

    public function test_listing_filter_keeps_status_query_and_rejects_unknown_profiles(): void
    {
        $this->withoutVite();
        $profile = SearchProfile::factory()->create();

        $this->get(route('listings.index', ['profile_id' => $profile->id, 'status' => 'new']))
            ->assertOk()
            ->assertSee('<option value="new" selected>Новый</option>', false);

        $this->get(route('listings.index', ['profile_id' => 999999]))
            ->assertSessionHasErrors('profile_id');
    }

    public function test_listing_page_size_can_be_selected_and_preserves_filters(): void
    {
        $this->withoutVite();
        $profile = SearchProfile::factory()->create();
        LaptopListing::factory()
            ->count(51)
            ->for($profile)
            ->sequence(fn ($sequence): array => [
                'title' => 'Page size listing '.$sequence->index,
                'total_price' => 100 + $sequence->index,
            ])
            ->create();

        $this->get(route('listings.index', [
            'profile_id' => $profile->id,
            'status' => 'new',
            'sort' => 'price_desc',
            'per_page' => 50,
        ]))
            ->assertOk()
            ->assertSee('id="listings-per-page"', false)
            ->assertSee('<option value="50" selected>50</option>', false)
            ->assertSee('Page size listing 50')
            ->assertDontSee('Page size listing 0')
            ->assertSee(route('listings.index', [
                'profile_id' => $profile->id,
                'status' => 'new',
                'sort' => 'price_desc',
            ]))
            ->assertSee('per_page=50', false);
    }

    public function test_listing_pagination_starts_with_five_pages_and_reveals_one_more_on_next_page(): void
    {
        $this->withoutVite();
        $profile = SearchProfile::factory()->create();
        LaptopListing::factory()
            ->count(151)
            ->for($profile)
            ->sequence(fn ($sequence): array => [
                'title' => 'Progressive page listing '.$sequence->index,
                'total_price' => 100 + $sequence->index,
            ])
            ->create();

        $this->get(route('listings.index', ['per_page' => 25]))
            ->assertOk()
            ->assertSee('data-listings-pagination-count', false)
            ->assertSee('Showing')
            ->assertSee('151')
            ->assertSee('data-page-number="5"', false)
            ->assertDontSee('data-page-number="6"', false)
            ->assertSee(route('listings.index', ['per_page' => 25, 'page' => 2]));

        $this->get(route('listings.index', ['per_page' => 25, 'page' => 2]))
            ->assertOk()
            ->assertSee('data-listings-pagination-count', false)
            ->assertSee('26')
            ->assertSee('50')
            ->assertSee('data-page-number="6"', false)
            ->assertDontSee('data-page-number="7"', false);
    }

    public function test_deleted_listing_page_size_can_be_selected_and_is_preserved_in_pagination(): void
    {
        $this->withoutVite();
        $listings = LaptopListing::factory()
            ->count(51)
            ->sequence(fn ($sequence): array => [
                'title' => 'Deleted page size listing '.$sequence->index,
            ])
            ->create();
        $listings->each->delete();

        $this->get(route('listings.deleted', ['per_page' => 50]))
            ->assertOk()
            ->assertSee('id="deleted-listings-per-page"', false)
            ->assertSee('<option value="50" selected>50</option>', false)
            ->assertSee('data-deleted-listings-pagination-count', false)
            ->assertSee('1')
            ->assertSee('50')
            ->assertSee('51')
            ->assertSee(route('listings.deleted', ['per_page' => 50, 'page' => 2]));
    }

    public function test_deleted_listings_sort_applies_before_pagination(): void
    {
        $this->withoutVite();
        $cheaperListing = LaptopListing::factory()->create([
            'title' => 'Cheaper deleted laptop',
            'total_price' => 200,
        ]);
        $moreExpensiveListing = LaptopListing::factory()->create([
            'title' => 'More expensive deleted laptop',
            'total_price' => 900,
        ]);
        $cheaperListing->delete();
        $moreExpensiveListing->delete();

        $response = $this->get(route('listings.deleted', ['sort' => 'price_asc']))
            ->assertOk()
            ->assertSee(route('listings.deleted', ['sort' => 'price_desc']));

        $this->assertSame(
            [$cheaperListing->id, $moreExpensiveListing->id],
            $response->viewData('listings')->getCollection()->modelKeys(),
        );
    }

    public function test_deleted_listing_pagination_starts_with_five_pages_and_reveals_one_more_on_next_page(): void
    {
        $this->withoutVite();
        $listings = LaptopListing::factory()
            ->count(151)
            ->sequence(fn ($sequence): array => [
                'title' => 'Progressive deleted page listing '.$sequence->index,
            ])
            ->create();
        $listings->each->delete();

        $this->get(route('listings.deleted', ['per_page' => 25]))
            ->assertOk()
            ->assertSee('data-deleted-listings-pagination-count', false)
            ->assertSee('data-page-number="5"', false)
            ->assertDontSee('data-page-number="6"', false)
            ->assertSee(route('listings.deleted', ['per_page' => 25, 'page' => 2]));

        $this->get(route('listings.deleted', ['per_page' => 25, 'page' => 2]))
            ->assertOk()
            ->assertSee('26')
            ->assertSee('50')
            ->assertSee('data-page-number="6"', false)
            ->assertDontSee('data-page-number="7"', false);
    }

    public function test_deleted_listing_page_rejects_unsupported_page_sizes(): void
    {
        $this->get(route('listings.deleted', ['per_page' => 75]))
            ->assertSessionHasErrors('per_page');
    }

    public function test_listing_page_rejects_unsupported_page_sizes(): void
    {
        $this->get(route('listings.index', ['per_page' => 75]))
            ->assertSessionHasErrors('per_page');
    }

    public function test_listing_page_filters_by_price_condition_and_added_date(): void
    {
        $this->withoutVite();
        $profile = SearchProfile::factory()->create();
        $this->get(route('listings.index', ['price_min' => 400]))->assertOk();
        $this->get(route('listings.index', ['date_from' => '2026-09-12']))->assertOk();

        $matchingListing = LaptopListing::factory()->create([
            'search_profile_id' => $profile->id,
            'title' => 'Matching listing',
            'total_price' => 450,
            'condition' => 'Pre-Owned',
            'created_at' => '2026-09-15 12:00:00',
        ]);
        LaptopListing::factory()->create([
            'search_profile_id' => $profile->id,
            'title' => 'Too expensive',
            'total_price' => 700,
            'condition' => 'Pre-Owned',
            'created_at' => '2026-09-15 12:00:00',
        ]);
        LaptopListing::factory()->create([
            'search_profile_id' => $profile->id,
            'title' => 'Wrong condition',
            'total_price' => 450,
            'condition' => 'New',
            'created_at' => '2026-09-15 12:00:00',
        ]);
        LaptopListing::factory()->create([
            'search_profile_id' => $profile->id,
            'title' => 'Added too early',
            'total_price' => 450,
            'condition' => 'Pre-Owned',
            'created_at' => '2026-09-10 12:00:00',
        ]);

        $this->get(route('listings.index', [
            'price_min' => 400,
            'price_max' => 500,
            'condition' => 'Pre-Owned',
            'date_from' => '2026-09-12',
            'date_to' => '2026-09-20',
        ]))
            ->assertOk()
            ->assertSee('Matching listing')
            ->assertDontSee('Too expensive')
            ->assertDontSee('Wrong condition')
            ->assertDontSee('Added too early')
            ->assertSee('name="price_min" type="number" min="0" step="0.01" value="400"', false)
            ->assertSee($matchingListing->created_at->format('d.m.Y'));
    }

    public function test_listing_page_searches_by_title_or_seller_description(): void
    {
        $this->withoutVite();
        $profile = SearchProfile::factory()->create();
        LaptopListing::factory()->create([
            'search_profile_id' => $profile->id,
            'title' => 'ThinkPad X1 Carbon',
            'seller_description' => 'Laptop in great condition',
        ]);
        LaptopListing::factory()->create([
            'search_profile_id' => $profile->id,
            'title' => 'MacBook Air',
            'seller_description' => 'Selling my thinkpad collection',
        ]);
        LaptopListing::factory()->create([
            'search_profile_id' => $profile->id,
            'title' => 'Dell Latitude 5510  i5-10210U',
            'seller_description' => null,
        ]);

        $this->get(route('listings.index', ['search' => 'Dell Latitude 5510 i5-10210U', 'search_in' => 'title']))
            ->assertOk()
            ->assertSee('Dell Latitude 5510  i5-10210U')
            ->assertDontSee('ThinkPad X1 Carbon');

        $response = $this->get(route('listings.index', ['search' => 'thinkpad', 'search_in' => 'title']))
            ->assertOk()
            ->assertSee('ThinkPad X1 Carbon')
            ->assertDontSee('MacBook Air')
            ->assertSee('name="search" type="search" value="thinkpad"', false)
            ->assertSee('Искать в')
            ->assertSee('Название лота')
            ->assertSee('Описание продавца');

        $content = $response->getContent();
        $sortPosition = strpos($content, 'id="sort"');
        $searchPosition = strpos($content, 'id="search"');
        $this->assertNotFalse($sortPosition);
        $this->assertNotFalse($searchPosition);
        $this->assertLessThan($searchPosition, $sortPosition);

        $this->get(route('listings.index', ['search' => 'thinkpad', 'search_in' => 'seller_description']))
            ->assertOk()
            ->assertSee('MacBook Air')
            ->assertDontSee('ThinkPad X1 Carbon');
    }

    public function test_listing_page_sorts_by_price_condition_and_appearance_date(): void
    {
        $this->withoutVite();
        $profile = SearchProfile::factory()->create();
        $expensiveListing = LaptopListing::factory()->create([
            'search_profile_id' => $profile->id,
            'title' => 'Expensive listing',
            'total_price' => 900,
            'condition' => 'Used',
            'created_at' => '2026-09-14 12:00:00',
        ]);
        $cheapListing = LaptopListing::factory()->create([
            'search_profile_id' => $profile->id,
            'title' => 'Cheap listing',
            'total_price' => 200,
            'condition' => 'New',
            'created_at' => '2026-09-20 12:00:00',
        ]);

        $response = $this->get(route('listings.index', ['sort' => 'price_desc']))->assertOk();
        $this->assertLessThan(
            strpos($response->getContent(), 'Cheap listing'),
            strpos($response->getContent(), 'Expensive listing'),
        );

        $ascendingConditionResponse = $this->get(route('listings.index', ['sort' => 'condition_asc']))
            ->assertOk()
            ->assertSee('Состояние ↑');
        $ascendingConditionIds = $ascendingConditionResponse->viewData('listings')->getCollection()->modelKeys();
        $this->assertLessThan(
            array_search($expensiveListing->id, $ascendingConditionIds),
            array_search($cheapListing->id, $ascendingConditionIds),
        );

        $descendingConditionResponse = $this->get(route('listings.index', ['sort' => 'condition_desc']))
            ->assertOk()
            ->assertSee('Состояние ↓');
        $descendingConditionIds = $descendingConditionResponse->viewData('listings')->getCollection()->modelKeys();
        $this->assertLessThan(
            array_search($cheapListing->id, $descendingConditionIds),
            array_search($expensiveListing->id, $descendingConditionIds),
        );

        $response = $this->get(route('listings.index', ['sort' => 'date_asc']))->assertOk();
        $this->assertLessThan(
            strpos($response->getContent(), 'Cheap listing'),
            strpos($response->getContent(), 'Expensive listing'),
        );
        $this->assertNotSame($expensiveListing->id, $cheapListing->id);
    }

    public function test_listing_page_displays_and_sorts_by_laptop_brand(): void
    {
        $this->withoutVite();
        $profile = SearchProfile::factory()->create();
        $lenovoListing = LaptopListing::factory()->create([
            'search_profile_id' => $profile->id,
            'title' => 'Lenovo laptop entry',
            'specifications' => ['brand' => 'Lenovo'],
        ]);
        $hpListing = LaptopListing::factory()->create([
            'search_profile_id' => $profile->id,
            'title' => 'HP laptop entry',
            'specifications' => ['Brand' => 'HP'],
        ]);

        $ascendingResponse = $this->get(route('listings.index', ['sort' => 'brand_asc']))
            ->assertOk()
            ->assertSee('Бренд ↑')
            ->assertSee('data-listing-brand', false)
            ->assertSee('HP')
            ->assertSee('Lenovo');
        $ascendingIds = $ascendingResponse->viewData('listings')->getCollection()->modelKeys();
        $this->assertLessThan(array_search($lenovoListing->id, $ascendingIds), array_search($hpListing->id, $ascendingIds));

        $descendingResponse = $this->get(route('listings.index', ['sort' => 'brand_desc']))
            ->assertOk()
            ->assertSee('Бренд ↓');
        $descendingIds = $descendingResponse->viewData('listings')->getCollection()->modelKeys();
        $this->assertLessThan(array_search($hpListing->id, $descendingIds), array_search($lenovoListing->id, $descendingIds));
    }

    public function test_listing_page_sorts_by_title_across_the_paginated_results(): void
    {
        $this->withoutVite();
        $firstListing = LaptopListing::factory()->create(['title' => 'Alpha laptop']);
        $lastListing = LaptopListing::factory()->create(['title' => 'Zulu laptop']);

        $response = $this->get(route('listings.index', ['sort' => 'title_desc']))
            ->assertOk()
            ->assertSee(route('listings.index', ['sort' => 'title_asc']));

        $this->assertSame(
            [$lastListing->id, $firstListing->id],
            $response->viewData('listings')->getCollection()->modelKeys(),
        );
    }

    public function test_listing_page_sorts_by_computed_value_score_before_pagination(): void
    {
        $this->withoutVite();
        $profile = SearchProfile::factory()->create();
        $listings = collect([160, 170, 180, 190])->map(fn (int $price): LaptopListing => LaptopListing::factory()->create([
            'search_profile_id' => $profile->id,
            'title' => "Comparable laptop {$price}",
            'condition' => 'USED',
            'currency' => 'USD',
            'total_price' => $price,
            'shipping_price' => 0,
            'cpu' => 'Intel Core i7',
            'ram_gb' => 16,
            'storage' => '512 GB SSD',
            'screen' => '15.6 in',
            'gpu' => 'NVIDIA GeForce RTX 4060',
        ]));

        $response = $this->get(route('listings.index', ['sort' => 'value_desc']))
            ->assertOk()
            ->assertSee(route('listings.index', ['sort' => 'value_asc']));

        $this->assertSame(
            $listings->pluck('id')->all(),
            $response->viewData('listings')->getCollection()->modelKeys(),
        );
    }

    public function test_listing_page_sorts_by_the_current_users_notes(): void
    {
        $profile = SearchProfile::factory()->create();
        $firstListing = LaptopListing::factory()->create([
            'search_profile_id' => $profile->id,
            'ebay_item_id' => 'v1|note-sort-first|0',
        ]);
        $lastListing = LaptopListing::factory()->create([
            'search_profile_id' => $profile->id,
            'ebay_item_id' => 'v1|note-sort-last|0',
        ]);
        UserListingNote::query()->create([
            'user_id' => auth()->id(),
            'ebay_item_id' => $firstListing->ebay_item_id,
            'note' => 'Alpha note',
        ]);
        UserListingNote::query()->create([
            'user_id' => auth()->id(),
            'ebay_item_id' => $lastListing->ebay_item_id,
            'note' => 'Zulu note',
        ]);

        $response = $this->get(route('listings.index', ['sort' => 'note_asc']))->assertOk();

        $this->assertSame(
            [$firstListing->id, $lastListing->id],
            $response->viewData('listings')->getCollection()->modelKeys(),
        );
    }

    public function test_listing_page_displays_brand_from_title_when_ebay_aspect_is_missing(): void
    {
        $this->withoutVite();
        $listing = LaptopListing::factory()->create([
            'title' => 'Dell Latitude 5420 laptop',
            'specifications' => [],
        ]);

        $response = $this->get(route('listings.index'))->assertOk();
        preg_match('/<td class="p-3" data-listing-brand>\s*(.*?)\s*<\/td>/s', $response->getContent(), $brandCell);

        $this->assertSame('Dell', trim(strip_tags($brandCell[1] ?? '')));
    }

    public function test_listing_table_displays_a_comparative_value_assessment(): void
    {
        $this->withoutVite();
        $profile = SearchProfile::factory()->create();
        $attributes = [
            'search_profile_id' => $profile->id,
            'condition' => 'USED',
            'currency' => 'USD',
            'cpu' => 'Intel Core i7',
            'ram_gb' => 16,
            'storage' => '1 TB SSD',
            'screen' => '15.6 in',
            'gpu' => 'NVIDIA GeForce RTX 4060',
        ];
        $listing = LaptopListing::factory()->create([
            ...$attributes,
            'title' => 'Assessment target',
            'total_price' => 800,
            'shipping_price' => 40,
        ]);

        foreach ([900, 1000, 1100] as $price) {
            LaptopListing::factory()->create([
                ...$attributes,
                'total_price' => $price,
                'shipping_price' => 0,
            ]);
        }

        $this->get(route('listings.index'))
            ->assertOk()
            ->assertSee('Оценка выгодности')
            ->assertSee('94 / 100')
            ->assertSee('Факторы · аналогов: 3')
            ->assertSee('Цена с доставкой: 60/60')
            ->assertSee('Медиана: USD 1 000.00');

        $listing->forceFill([
            'cpu' => null,
            'ram_gb' => null,
            'storage' => null,
            'screen' => null,
            'gpu' => null,
        ])->save();
        $this->get(route('listings.index'))
            ->assertOk()
            ->assertSee('Недостаточно данных')
            ->assertSee('Найдено похожих товаров: 0 из 3 необходимых.');
    }

    public function test_listing_price_history_is_visible_to_its_owner_only(): void
    {
        $this->withoutVite();
        $profile = SearchProfile::factory()->create(['price_alert_threshold' => 400]);
        $listing = LaptopListing::factory()->create([
            'search_profile_id' => $profile->id,
            'title' => 'Tracked price laptop',
            'total_price' => 350,
        ]);
        $listing->priceHistory()->create([
            'price' => 450,
            'currency' => 'USD',
            'observed_at' => now()->subDay(),
        ]);
        $listing->priceHistory()->create([
            'price' => 350,
            'currency' => 'USD',
            'observed_at' => now(),
        ]);

        $this->get(route('listings.index'))
            ->assertOk()
            ->assertSee(route('listings.price-history', $listing))
            ->assertSee('История цены');

        $this->get(route('listings.price-history', $listing))
            ->assertOk()
            ->assertSee('data-table-key="price-history"', false)
            ->assertSee('data-column-key="changed-at"', false)
            ->assertSee('Tracked price laptop')
            ->assertSee('Текущая цена с доставкой:')
            ->assertSee('USD 400.00')
            ->assertSee('USD 450.00')
            ->assertSee('USD 350.00');

        $sortedHistory = $this->get(route('listings.price-history', [
            'listing' => $listing,
            'sort' => 'price_asc',
        ]))
            ->assertOk()
            ->assertSee(route('listings.price-history', [
                'listing' => $listing,
                'sort' => 'price_desc',
            ]));
        $this->assertSame(
            ['350.00', '450.00'],
            $sortedHistory->viewData('history')->getCollection()->pluck('price')->all(),
        );

        $otherUser = User::factory()->create();
        $this->actingAs($otherUser)
            ->get(route('listings.price-history', $listing))
            ->assertNotFound();
    }

    public function test_watchlist_shows_only_the_current_users_active_watched_listings(): void
    {
        $this->withoutVite();
        $profile = SearchProfile::factory()->create();
        LaptopListing::factory()->create([
            'search_profile_id' => $profile->id,
            'title' => 'Watched laptop',
            'status' => 'watching',
        ]);
        LaptopListing::factory()->create([
            'search_profile_id' => $profile->id,
            'title' => 'New laptop',
            'status' => 'new',
        ]);
        $deletedListing = LaptopListing::factory()->create([
            'search_profile_id' => $profile->id,
            'title' => 'Deleted watched laptop',
            'status' => 'watching',
        ]);
        $deletedListing->delete();

        $otherProfile = SearchProfile::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);
        LaptopListing::factory()->create([
            'search_profile_id' => $otherProfile->id,
            'title' => 'Other users watched laptop',
            'status' => 'watching',
        ]);

        $this->get(route('listings.watchlist', ['status' => 'new']))
            ->assertOk()
            ->assertSee('Список наблюдения')
            ->assertSee('Товары, отмеченные статусом «Наблюдаю»')
            ->assertSee('Watched laptop')
            ->assertSee('<option value="watching" selected>Наблюдаю</option>', false)
            ->assertDontSee('New laptop')
            ->assertDontSee('Deleted watched laptop')
            ->assertDontSee('Other users watched laptop');
    }

    public function test_matching_ebay_listings_show_the_other_profiles_that_found_them(): void
    {
        $this->withoutVite();
        $primaryProfile = SearchProfile::factory()->create(['name' => 'Primary profile']);
        $matchingProfile = SearchProfile::factory()->create(['name' => 'Matching profile']);
        $otherOwnerProfile = SearchProfile::factory()->create([
            'name' => 'Other owner profile',
            'user_id' => User::factory()->create()->id,
        ]);
        $sharedItemId = 'v1|shared-across-profiles|0';
        $primaryListing = LaptopListing::factory()->create([
            'search_profile_id' => $primaryProfile->id,
            'ebay_item_id' => $sharedItemId,
            'title' => 'Laptop found in two profiles',
        ]);
        LaptopListing::factory()->create([
            'search_profile_id' => $matchingProfile->id,
            'ebay_item_id' => $sharedItemId,
            'title' => 'Laptop found in two profiles',
        ]);
        LaptopListing::factory()->create([
            'search_profile_id' => $otherOwnerProfile->id,
            'ebay_item_id' => $sharedItemId,
            'title' => 'Laptop found by another owner',
        ]);

        $this->get(route('listings.index'))
            ->assertOk()
            ->assertSee('Найден в 2 профилях')
            ->assertSee('Primary profile')
            ->assertSee('Matching profile')
            ->assertDontSee('Other owner profile');

        $user = User::findOrFail($primaryProfile->user_id);
        $user->notify(new NewEbayListingNotification($primaryListing, $primaryProfile));

        $this->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Также найдено в профилях: Matching profile')
            ->assertDontSee('Other owner profile');
    }

    public function test_listing_table_uses_one_shared_status_control_for_matching_profiles(): void
    {
        $this->withoutVite();
        $firstProfile = SearchProfile::factory()->create(['name' => 'First ThinkPad']);
        $secondProfile = SearchProfile::factory()->create(['name' => 'Second ThinkPad']);
        $sharedItemId = 'v1|combined-card-listing|0';
        $firstListing = LaptopListing::factory()->create([
            'search_profile_id' => $firstProfile->id,
            'ebay_item_id' => $sharedItemId,
            'title' => 'Combined ThinkPad listing',
            'status' => 'new',
        ]);
        $secondListing = LaptopListing::factory()->create([
            'search_profile_id' => $secondProfile->id,
            'ebay_item_id' => $sharedItemId,
            'title' => 'Combined ThinkPad listing',
            'status' => 'watching',
        ]);
        $otherOwnerProfile = SearchProfile::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);
        $otherOwnerListing = LaptopListing::factory()->create([
            'search_profile_id' => $otherOwnerProfile->id,
            'ebay_item_id' => $sharedItemId,
            'title' => 'Private duplicate listing',
            'status' => 'new',
        ]);

        $response = $this->get(route('listings.index'))
            ->assertOk()
            ->assertSee('Combined ThinkPad listing')
            ->assertSee('Найден в 2 профилях')
            ->assertSee('First ThinkPad')
            ->assertSee('Second ThinkPad')
            ->assertDontSee('<details class="mt-1 text-xs text-slate-600" open>', false)
            ->assertSee('class="w-36 min-w-36 p-3" data-column-key="statuses" data-column-label="Статусы"', false)
            ->assertDontSee('data-profile-status', false)
            ->assertSee('class="sr-only"', false)
            ->assertDontSee('inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-50 text-blue-800', false)
            ->assertDontSee('data-status-icon=', false)
            ->assertSee('Все профили')
            ->assertSee('h-3.5 w-3.5', false)
            ->assertSee('M6 8h12l1 13H5L6 8Zm3', false)
            ->assertSee('M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20ZM7 12h10', false)
            ->assertSee('h-6 w-6 shrink-0 items-center justify-center', false)
            ->assertSee('class="flex shrink-0 flex-nowrap gap-0.5"', false)
            ->assertSee('aria-label="Все профили: установить статус «Новый»"', false)
            ->assertSee('aria-label="Все профили: установить статус «Наблюдаю»"', false)
            ->assertSee('w-8 px-2 py-3', false)
            ->assertSee(route('profiles.edit', $firstProfile))
            ->assertSee(route('profiles.edit', $secondProfile))
            ->assertSee('Наблюдаю')
            ->assertSee('data-listing-ids=\'['.$firstListing->id.','.$secondListing->id.']\'', false)
            ->assertSee('name="listing_ids[]"', false);
        $this->assertSame(1, substr_count($response->getContent(), 'data-listing-row'));
        $this->assertSame(1, substr_count($response->getContent(), 'data-listing-status-form'));

        $this->get(route('listings.index', ['status' => 'watching']))
            ->assertOk()
            ->assertSee('Combined ThinkPad listing')
            ->assertSee('Найден в 2 профилях');

        $this->get(route('listings.index', ['profile_id' => $secondProfile->id]))
            ->assertOk()
            ->assertSee('Combined ThinkPad listing')
            ->assertSee('First ThinkPad')
            ->assertSee('Second ThinkPad');

        $this->patch(route('listings.update', $secondListing), ['status' => 'buy'])
            ->assertRedirect();
        $this->assertSame('buy', $firstListing->fresh()->status);
        $this->assertSame('buy', $secondListing->fresh()->status);
        $this->assertNotNull($firstListing->fresh()->sold_at);
        $this->assertNotNull($secondListing->fresh()->sold_at);
        $this->assertSame('new', $otherOwnerListing->fresh()->status);
        $this->assertNull($otherOwnerListing->fresh()->sold_at);
    }

    public function test_listing_note_is_shared_across_profiles_for_the_same_ebay_item(): void
    {
        $this->withoutVite();
        $firstProfile = SearchProfile::factory()->create(['name' => 'Note profile one']);
        $secondProfile = SearchProfile::factory()->create(['name' => 'Note profile two']);
        $sharedItemId = 'v1|shared-note-listing|0';
        $firstListing = LaptopListing::factory()->create([
            'search_profile_id' => $firstProfile->id,
            'ebay_item_id' => $sharedItemId,
            'title' => 'Laptop with shared note',
        ]);
        LaptopListing::factory()->create([
            'search_profile_id' => $secondProfile->id,
            'ebay_item_id' => $sharedItemId,
            'title' => 'Laptop with shared note',
        ]);

        $this->get(route('listings.index'))
            ->assertOk()
            ->assertSee('data-column-key="note" data-column-label="Заметка к товару"', false)
            ->assertSee('data-listing-note-form', false)
            ->assertSee('data-listing-note', false)
            ->assertSee('Заметка к товару')
            ->assertDontSee('Автосохранение включено')
            ->assertDontSee('Сохранить заметку');

        $tableContent = $this->get(route('listings.index'))->getContent();
        $sellerColumnPosition = strpos($tableContent, 'data-column-key="seller"');
        $noteColumnPosition = strpos($tableContent, 'data-column-key="note"');
        $this->assertNotFalse($sellerColumnPosition);
        $this->assertNotFalse($noteColumnPosition);
        $this->assertGreaterThan($sellerColumnPosition, $noteColumnPosition);

        $this->patchJson(route('listings.notes.update', $firstListing), [
            'note' => 'Проверить продавца перед покупкой',
        ])
            ->assertOk()
            ->assertExactJson(['message' => 'Заметка к товару сохранена.']);

        $this->assertDatabaseHas('user_listing_notes', [
            'user_id' => $firstProfile->user_id,
            'ebay_item_id' => $sharedItemId,
            'note' => 'Проверить продавца перед покупкой',
        ]);
        $this->get(route('listings.index'))
            ->assertOk()
            ->assertSee('Проверить продавца перед покупкой');
        $this->get(route('listings.index', ['profile_id' => $secondProfile->id]))
            ->assertOk()
            ->assertSee('Проверить продавца перед покупкой');

        $this->patch(route('listings.notes.update', $firstListing), ['note' => ''])
            ->assertRedirect();
        $this->assertDatabaseHas('user_listing_notes', [
            'user_id' => $firstProfile->user_id,
            'ebay_item_id' => $sharedItemId,
            'note' => null,
        ]);
    }

    public function test_listing_note_cannot_be_changed_by_another_user_or_exceed_2000_characters(): void
    {
        $listing = LaptopListing::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)
            ->patch(route('listings.notes.update', $listing), ['note' => 'Private note'])
            ->assertNotFound();
        $this->assertDatabaseCount('user_listing_notes', 0);

        $this->actingAs(User::findOrFail($listing->searchProfile->user_id))
            ->from(route('listings.index'))
            ->patch(route('listings.notes.update', $listing), ['note' => str_repeat('a', 2001)])
            ->assertRedirect(route('listings.index'))
            ->assertSessionHasErrors('note');
        $this->assertDatabaseCount('user_listing_notes', 0);

        $this->patchJson(route('listings.notes.update', $listing), ['note' => str_repeat('a', 2001)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('note');
        $this->assertDatabaseCount('user_listing_notes', 0);
    }

    public function test_buy_list_shows_only_the_current_users_active_listings_marked_to_buy(): void
    {
        $this->withoutVite();
        $profile = SearchProfile::factory()->create();
        LaptopListing::factory()->create([
            'search_profile_id' => $profile->id,
            'title' => 'Laptop to buy',
            'status' => 'buy',
        ]);
        LaptopListing::factory()->create([
            'search_profile_id' => $profile->id,
            'title' => 'Watched laptop in other section',
            'status' => 'watching',
        ]);
        LaptopListing::factory()->create([
            'search_profile_id' => $profile->id,
            'title' => 'New laptop in other section',
            'status' => 'new',
        ]);

        $otherProfile = SearchProfile::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);
        LaptopListing::factory()->create([
            'search_profile_id' => $otherProfile->id,
            'title' => 'Other users laptop to buy',
            'status' => 'buy',
        ]);

        $this->get(route('listings.buy', ['status' => 'new']))
            ->assertOk()
            ->assertSee('Товары, отмеченные статусом «Купить»')
            ->assertSee('Laptop to buy')
            ->assertSee('<option value="buy" selected>Купить</option>', false)
            ->assertDontSee('Watched laptop in other section')
            ->assertDontSee('New laptop in other section')
            ->assertDontSee('Other users laptop to buy');
    }

    public function test_listing_filter_validates_price_range_dates_and_sort_order(): void
    {
        $this->get(route('listings.index', [
            'price_min' => 500,
            'price_max' => 100,
            'date_from' => '2026-09-20',
            'date_to' => '2026-09-10',
            'sort' => 'unsafe',
        ]))
            ->assertSessionHasErrors(['price_min', 'price_max', 'date_from', 'date_to', 'sort']);
    }

    public function test_listing_status_can_be_changed_with_a_quick_action(): void
    {
        $this->withoutVite();
        $listing = LaptopListing::factory()->create();

        $this->get(route('listings.index'))
            ->assertOk()
            ->assertSee('установить статус «Купить»')
            ->assertSee('установить статус «Наблюдаю»')
            ->assertSee('data-listing-status-form', false);

        $this->patch(route('listings.update', $listing), ['status' => 'buy'])
            ->assertRedirect();

        $this->assertSame('buy', $listing->fresh()->status);
        $this->assertNotNull($listing->fresh()->sold_at);

        $this->patch(route('listings.update', $listing), ['status' => 'watching'])
            ->assertRedirect();

        $this->assertSame('watching', $listing->fresh()->status);
        $this->assertNull($listing->fresh()->sold_at);
    }

    public function test_listing_status_ajax_update_returns_json_without_a_redirect(): void
    {
        $listing = LaptopListing::factory()->create();

        $this->patchJson(route('listings.update', $listing), ['status' => 'buy'])
            ->assertOk()
            ->assertExactJson([
                'message' => 'Статус товара обновлён.',
                'status' => 'buy',
            ]);

        $this->assertSame('buy', $listing->fresh()->status);
        $this->assertNotNull($listing->fresh()->sold_at);
    }

    public function test_listing_page_offers_bulk_actions_for_selected_products(): void
    {
        $this->withoutVite();
        $listing = LaptopListing::factory()->create(['title' => 'Bulk action laptop']);

        $response = $this->get(route('listings.index'))
            ->assertOk()
            ->assertSee('data-bulk-listings', false)
            ->assertSee('name="bulk_action"', false)
            ->assertSee('name="listing_ids[]"', false)
            ->assertSee('value="'.$listing->id.'"', false)
            ->assertSee('Выбрать все товары на странице')
            ->assertSee('Переместить в удалённые')
            ->assertSee('data-export-listings', false)
            ->assertSee('aria-label="Скачать выбранные CSV (Excel)"', false)
            ->assertSee('M12 3v12m0 0 4-4m-4 4-4-4M5 17v4h14v-4', false)
            ->assertDontSee('Выберите товары в таблице')
            ->assertDontSee('Файл в UTF-8 можно открыть в Excel.')
            ->assertDontSee('Оценка ориентировочная: учитывает цену с доставкой, состояние и заполненность характеристик.')
            ->assertDontSee('Цена сравнивается минимум с тремя похожими товарами того же профиля, состояния и валюты.');

        $bulkActionsPosition = strpos($response->getContent(), 'data-bulk-listings');
        $exportPosition = strpos($response->getContent(), 'data-export-listings');
        $columnSettingsPosition = strpos($response->getContent(), 'data-column-settings data-table-key="listings"');
        $this->assertNotFalse($bulkActionsPosition);
        $this->assertNotFalse($exportPosition);
        $this->assertNotFalse($columnSettingsPosition);
        $this->assertGreaterThan($bulkActionsPosition, $exportPosition);
        $this->assertGreaterThan($exportPosition, $columnSettingsPosition);
    }

    public function test_bulk_listing_action_updates_status_for_owned_products(): void
    {
        $firstListing = LaptopListing::factory()->create(['status' => 'new']);
        $secondListing = LaptopListing::factory()->create(['status' => 'watching']);

        $this->postJson(route('listings.bulk-action'), [
            'listing_ids' => [$firstListing->id, $secondListing->id],
            'bulk_action' => 'buy',
        ])
            ->assertOk()
            ->assertJsonPath('affected_count', 2)
            ->assertJsonPath('action', 'buy');

        foreach ([$firstListing, $secondListing] as $listing) {
            $this->assertSame('buy', $listing->fresh()->status);
            $this->assertNotNull($listing->fresh()->sold_at);
        }
    }

    public function test_bulk_listing_action_moves_selected_products_to_trash(): void
    {
        $firstListing = LaptopListing::factory()->create();
        $secondListing = LaptopListing::factory()->create();

        $this->postJson(route('listings.bulk-action'), [
            'listing_ids' => [$firstListing->id, $secondListing->id],
            'bulk_action' => 'delete',
        ])
            ->assertOk()
            ->assertJsonPath('affected_count', 2)
            ->assertJsonPath('action', 'delete');

        $this->assertSoftDeleted($firstListing);
        $this->assertSoftDeleted($secondListing);
    }

    public function test_bulk_listing_action_can_delete_more_than_one_hundred_selected_products(): void
    {
        $listings = LaptopListing::factory()->count(101)->create();
        $listingIds = $listings->modelKeys();

        $this->postJson(route('listings.bulk-action'), [
            'listing_ids' => $listingIds,
            'bulk_action' => 'delete',
        ])
            ->assertOk()
            ->assertJsonPath('affected_count', 101)
            ->assertJsonPath('action', 'delete');

        $this->assertSame(
            101,
            LaptopListing::onlyTrashed()->whereIn('id', $listingIds)->count(),
        );
    }

    public function test_bulk_listing_action_rejects_other_users_products_without_partial_updates(): void
    {
        $ownedListing = LaptopListing::factory()->create(['status' => 'new']);
        $otherProfile = SearchProfile::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);
        $otherListing = LaptopListing::factory()->create([
            'search_profile_id' => $otherProfile->id,
            'status' => 'new',
        ]);

        $this->postJson(route('listings.bulk-action'), [
            'listing_ids' => [$ownedListing->id, $otherListing->id],
            'bulk_action' => 'buy',
        ])->assertNotFound();

        $this->assertSame('new', $ownedListing->fresh()->status);
        $this->assertSame('new', $otherListing->fresh()->status);
    }

    public function test_bulk_listing_action_validates_ids_and_action(): void
    {
        $this->postJson(route('listings.bulk-action'), [
            'listing_ids' => [],
            'bulk_action' => 'force-delete',
        ])->assertUnprocessable()->assertJsonValidationErrors(['listing_ids', 'bulk_action']);
    }

    public function test_selected_listings_can_be_exported_as_excel_compatible_csv(): void
    {
        $profile = SearchProfile::factory()->create(['name' => 'My profile']);
        $selectedListing = LaptopListing::factory()->create([
            'search_profile_id' => $profile->id,
            'title' => '=HYPERLINK("https://example.com")',
            'ebay_item_id' => 'item-123',
            'item_url' => 'https://www.ebay.com/itm/item-123',
            'price' => 100,
            'shipping_price' => 5,
            'total_price' => 105,
            'currency' => 'USD',
            'condition' => 'Used',
            'status' => 'watching',
            'cpu' => 'Intel Core i7',
            'ram_gb' => 16,
            'storage' => '512 GB SSD',
            'seller' => 'Example seller',
            'created_at' => '2026-10-01 10:30:00',
            'seen_at' => '2026-10-02 12:00:00',
        ]);
        LaptopListing::factory()->create([
            'search_profile_id' => $profile->id,
            'title' => 'Not selected',
        ]);

        $response = $this->post(route('listings.export'), [
            'listing_ids' => [$selectedListing->id],
        ])->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertHeader('content-disposition');

        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString("'=HYPERLINK(\"\"https://example.com\"\")", $csv);
        $this->assertStringContainsString('item-123', $csv);
        $this->assertStringContainsString('My profile', $csv);
        $this->assertStringContainsString('Наблюдаю', $csv);
        $this->assertStringNotContainsString('Not selected', $csv);
    }

    public function test_listing_export_rejects_products_owned_by_another_user(): void
    {
        $otherProfile = SearchProfile::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);
        $otherListing = LaptopListing::factory()->create(['search_profile_id' => $otherProfile->id]);

        $this->postJson(route('listings.export'), [
            'listing_ids' => [$otherListing->id],
        ])->assertNotFound();
    }

    public function test_listing_export_requires_distinct_selected_ids(): void
    {
        $listing = LaptopListing::factory()->create();

        $this->postJson(route('listings.export'), [
            'listing_ids' => [$listing->id, $listing->id],
        ])->assertUnprocessable()->assertJsonValidationErrors('listing_ids.1');
    }

    public function test_listing_can_be_deleted_viewed_in_trash_and_restored(): void
    {
        $this->withoutVite();
        $listing = LaptopListing::factory()->create([
            'title' => 'Recoverable laptop',
            'image_url' => 'https://i.ebayimg.com/images/g/deleted-main/s-l1600.jpg',
            'raw_payload' => [
                'thumbnailImages' => [
                    ['imageUrl' => 'https://i.ebayimg.com/images/g/deleted-side/s-l1600.jpg'],
                ],
            ],
        ]);

        $page = $this->get(route('listings.index'))
            ->assertOk()
            ->assertSee('data-listing-row', false)
            ->assertDontSee('data-delete-listing', false)
            ->assertDontSee('<th class="p-3">Действие</th>', false)
            ->assertDontSee('>Удалить</button>', false);
        $this->assertSame(1, substr_count($page->getContent(), 'data-listing-row'));

        $this->from(route('listings.index'))
            ->delete(route('listings.destroy', $listing))
            ->assertRedirect(route('listings.index'))
            ->assertSessionHas('success', 'Товар перемещён в удалённые.');

        $this->assertSoftDeleted($listing);
        $this->get(route('listings.index'))
            ->assertOk()
            ->assertDontSee('Recoverable laptop');

        $deletedPage = $this->get(route('listings.deleted'))
            ->assertOk()
            ->assertSee('data-table-key="deleted-listings"', false)
            ->assertSee('data-column-key="image">Фото', false)
            ->assertSee('data-column-key="actions"', false)
            ->assertSee('data-deleted-listing-select', false)
            ->assertSee(route('listings.bulk-force-destroy'))
            ->assertSee('Удалённые товары')
            ->assertSee('data-deleted-selection-count', false)
            ->assertDontSee('Выберите товары')
            ->assertSee('data-confirm-dialog-form', false)
            ->assertSee('id="site-confirm-dialog"', false)
            ->assertSee('role="alertdialog"', false)
            ->assertSee('aria-modal="true"', false)
            ->assertSee('fixed left-1/2 top-1/2', false)
            ->assertSee('data-confirm-message="bulk-delete"', false)
            ->assertDontSee('onsubmit=', false)
            ->assertSee('Recoverable laptop')
            ->assertSee('https://i.ebayimg.com/images/g/deleted-main/s-l1600.jpg')
            ->assertSee('https://i.ebayimg.com/images/g/deleted-side/s-l1600.jpg')
            ->assertSee('aria-label="Открыть фотографии товара: Recoverable laptop"', false)
            ->assertSee('data-listing-image-gallery-dialog', false)
            ->assertSee(route('listings.restore', $listing->id))
            ->assertSee(route('listings.force-destroy', $listing->id))
            ->assertSee('Удалить навсегда');

        $deletedPageContent = $deletedPage->getContent();
        $bulkDeletePosition = strpos($deletedPageContent, 'Удалить выбранные навсегда');
        $columnSettingsPosition = strpos($deletedPageContent, 'data-column-settings data-table-key="deleted-listings"');
        $imageColumnPosition = strpos($deletedPageContent, 'data-column-key="image">Фото');
        $selectionColumnPosition = strpos($deletedPageContent, 'data-column-key="selection"');
        $tablePosition = strpos($deletedPageContent, '<table');
        $this->assertNotFalse($bulkDeletePosition);
        $this->assertNotFalse($columnSettingsPosition);
        $this->assertNotFalse($tablePosition);
        $this->assertNotFalse($imageColumnPosition);
        $this->assertNotFalse($selectionColumnPosition);
        $this->assertLessThan($imageColumnPosition, $selectionColumnPosition);
        $this->assertLessThan($tablePosition, $bulkDeletePosition);
        $this->assertLessThan($tablePosition, $columnSettingsPosition);

        $this->from(route('listings.deleted'))
            ->patch(route('listings.restore', $listing->id))
            ->assertRedirect(route('listings.deleted'))
            ->assertSessionHas('success', 'Товар восстановлен.');

        $this->assertNotSoftDeleted($listing);
        $this->get(route('listings.index'))
            ->assertOk()
            ->assertSee('Recoverable laptop');
        $this->get(route('listings.deleted'))
            ->assertOk()
            ->assertDontSee('Recoverable laptop');
    }

    public function test_listing_deletion_returns_json_for_asynchronous_requests(): void
    {
        $listing = LaptopListing::factory()->create(['title' => 'Ajax deleted laptop']);

        $this->deleteJson(route('listings.destroy', $listing))
            ->assertOk()
            ->assertExactJson(['message' => 'Товар перемещён в удалённые.']);

        $this->assertSoftDeleted($listing);
    }

    public function test_users_cannot_view_or_restore_other_users_deleted_listings(): void
    {
        $this->withoutVite();
        $otherUser = User::factory()->create();
        $otherProfile = SearchProfile::factory()->create(['user_id' => $otherUser->id]);
        $otherListing = LaptopListing::factory()->create([
            'search_profile_id' => $otherProfile->id,
            'title' => 'Private deleted laptop',
        ]);
        $otherListing->delete();

        $this->get(route('listings.deleted'))
            ->assertOk()
            ->assertDontSee('Private deleted laptop');

        $this->patch(route('listings.restore', $otherListing->id))->assertNotFound();
        $this->delete(route('listings.force-destroy', $otherListing->id))->assertNotFound();
        $this->assertSoftDeleted($otherListing);
    }

    public function test_owner_can_permanently_delete_a_listing_from_the_trash(): void
    {
        $listing = LaptopListing::factory()->create();
        $listing->delete();

        $this->from(route('listings.deleted'))
            ->delete(route('listings.force-destroy', $listing->id))
            ->assertRedirect(route('listings.deleted'))
            ->assertSessionHas('success', 'Товар удалён окончательно.');

        $this->assertDatabaseMissing('laptop_listings', ['id' => $listing->id]);
    }

    public function test_owner_can_permanently_delete_multiple_listings_from_the_trash(): void
    {
        $listings = LaptopListing::factory()->count(2)->create();
        $listings->each->delete();

        $this->from(route('listings.deleted'))
            ->post(route('listings.bulk-force-destroy'), [
                'listing_ids' => $listings->modelKeys(),
            ])
            ->assertRedirect(route('listings.deleted'))
            ->assertSessionHas('success', '2 товаров удалено окончательно.');

        foreach ($listings as $listing) {
            $this->assertDatabaseMissing('laptop_listings', ['id' => $listing->id]);
        }
    }

    public function test_owner_can_permanently_delete_more_than_one_hundred_listings_from_the_trash(): void
    {
        $listings = LaptopListing::factory()->count(101)->create();
        $listings->each->delete();

        $this->from(route('listings.deleted'))
            ->post(route('listings.bulk-force-destroy'), [
                'listing_ids' => $listings->modelKeys(),
            ])
            ->assertRedirect(route('listings.deleted'))
            ->assertSessionHas('success', '101 товаров удалено окончательно.');

        $this->assertSame(
            0,
            LaptopListing::withTrashed()->whereIn('id', $listings->modelKeys())->count(),
        );
    }

    public function test_bulk_permanent_deletion_rejects_foreign_or_active_listings_without_partial_deletion(): void
    {
        $trashedListing = LaptopListing::factory()->create();
        $trashedListing->delete();

        $otherUser = User::factory()->create();
        $otherProfile = SearchProfile::factory()->create(['user_id' => $otherUser->id]);
        $foreignListing = LaptopListing::factory()->create(['search_profile_id' => $otherProfile->id]);
        $foreignListing->delete();

        $this->post(route('listings.bulk-force-destroy'), [
            'listing_ids' => [$trashedListing->id, $foreignListing->id],
        ])->assertNotFound();

        $this->assertSoftDeleted($trashedListing);
        $this->assertSoftDeleted($foreignListing);

        $activeListing = LaptopListing::factory()->create();
        $this->post(route('listings.bulk-force-destroy'), [
            'listing_ids' => [$trashedListing->id, $activeListing->id],
        ])->assertNotFound();

        $this->assertSoftDeleted($trashedListing);
        $this->assertDatabaseHas('laptop_listings', ['id' => $activeListing->id, 'deleted_at' => null]);
    }

    public function test_bulk_permanent_deletion_requires_distinct_selected_ids(): void
    {
        $listing = LaptopListing::factory()->create();
        $listing->delete();

        $this->from(route('listings.deleted'))
            ->post(route('listings.bulk-force-destroy'), [
                'listing_ids' => [$listing->id, $listing->id],
            ])
            ->assertRedirect(route('listings.deleted'))
            ->assertSessionHasErrors('listing_ids.1');

        $this->assertSoftDeleted($listing);
    }

    public function test_bulk_permanent_deletion_requires_at_least_one_selected_listing(): void
    {
        $listing = LaptopListing::factory()->create();
        $listing->delete();

        $this->from(route('listings.deleted'))
            ->post(route('listings.bulk-force-destroy'), ['listing_ids' => []])
            ->assertRedirect(route('listings.deleted'))
            ->assertSessionHasErrors('listing_ids');

        $this->assertSoftDeleted($listing);
    }

    public function test_listing_page_only_shows_listings_and_profiles_belonging_to_current_user(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $ownedProfile = SearchProfile::factory()->create([
            'user_id' => $owner->id,
            'name' => 'My profile',
        ]);
        $otherProfile = SearchProfile::factory()->create([
            'user_id' => $otherUser->id,
            'name' => 'Other profile',
        ]);
        LaptopListing::factory()->create([
            'search_profile_id' => $ownedProfile->id,
            'title' => 'My listing',
        ]);
        $otherListing = LaptopListing::factory()->create([
            'search_profile_id' => $otherProfile->id,
            'title' => 'Other users private listing',
        ]);

        $this->actingAs($owner)
            ->get(route('listings.index'))
            ->assertOk()
            ->assertSee('My profile')
            ->assertSee('My listing')
            ->assertDontSee('Other profile')
            ->assertDontSee('Other users private listing');

        $this->patch(route('listings.update', $otherListing), ['status' => 'buy'])->assertNotFound();
    }

    public function test_listing_page_shows_unread_notification_count_in_header_and_can_mark_them_read(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $profile = SearchProfile::factory()->create([
            'user_id' => $user->id,
            'name' => 'ThinkPad profile',
        ]);
        $listing = LaptopListing::factory()->create([
            'search_profile_id' => $profile->id,
            'title' => 'New ThinkPad T14',
        ]);
        $user->notify(new NewEbayListingNotification($listing, $profile));
        $notification = $user->notifications()->firstOrFail();

        $this->actingAs($user)
            ->get(route('listings.index'))
            ->assertOk()
            ->assertSee('Новые товары')
            ->assertSee('href="'.route('notifications.index').'"', false)
            ->assertSee('aria-label="Уведомления. Непрочитанных: 1. Открыть список"', false)
            ->assertSee('data-unread-notification-count', false)
            ->assertDontSee('new-listings-heading', false)
            ->assertDontSee('Непрочитанных уведомлений: 1');

        $this->from(route('listings.index'))
            ->patch(route('notifications.read', $notification))
            ->assertRedirect(route('listings.index'));

        $this->assertNotNull($notification->fresh()->read_at);
        $this->get(route('listings.index'))
            ->assertSee('aria-label="Уведомления. Непрочитанных: 0. Открыть список"', false)
            ->assertDontSee('data-unread-notification-count', false);

        $this->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Новых товаров пока нет');
    }

    public function test_users_cannot_mark_another_users_listing_notification_as_read(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $profile = SearchProfile::factory()->create(['user_id' => $owner->id]);
        $listing = LaptopListing::factory()->create(['search_profile_id' => $profile->id]);
        $owner->notify(new NewEbayListingNotification($listing, $profile));
        $notification = $owner->notifications()->firstOrFail();

        $this->actingAs($otherUser)
            ->from(route('listings.index'))
            ->patch(route('notifications.read', $notification))
            ->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_admin_settings_page_displays_theme_choices_and_admin_sidebar(): void
    {
        $this->withoutVite();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertSee('Настройки')
            ->assertSee('data-theme-selector', false)
            ->assertSee("localStorage.getItem('laptop-finder-theme')", false)
            ->assertSee('html.dark body', false)
            ->assertSee('Белая тема')
            ->assertSee('Тёмная тема')
            ->assertSee('Пользователи и роли')
            ->assertSee('Платежи')
            ->assertSee('Вид валюты')
            ->assertSee('Сомы (KGS)')
            ->assertSee('Доллары (USD)')
            ->assertSee('Административная панель')
            ->assertDontSee('Главное меню')
            ->assertSee('Меню администратора');
    }

    public function test_home_route_redirects_to_the_all_listings_page(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('listings.index'));
    }
}
