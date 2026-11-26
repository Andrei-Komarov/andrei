<?php

namespace Tests\Feature;

use App\Models\LaptopListing;
use App\Models\SearchProfile;
use App\Models\User;
use App\Notifications\NewEbayListingNotification;
use App\Notifications\SharedEbayListingNotification;
use App\Services\EbayBrowseService;
use App\Services\ListingSpecificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schedule;
use Mockery\MockInterface;
use Tests\TestCase;

class EbayImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.ebay.client_id' => 'test-client',
            'services.ebay.client_secret' => 'test-secret',
        ]);
        $this->actingAs(User::factory()->admin()->create(), 'admin');
    }

    public function test_command_imports_a_listing_from_ebay(): void
    {
        Http::fake([
            '*identity/*' => Http::response(['access_token' => 'token']),
            '*get_default_category_tree_id*' => Http::response(['categoryTreeId' => '0']),
            '*get_category_subtree*' => Http::response(['categorySubtreeNode' => [
                'childCategoryTreeNodes' => [
                    ['category' => ['categoryId' => '177'], 'leafCategoryTreeNode' => true],
                    ['category' => ['categoryId' => '111422'], 'leafCategoryTreeNode' => true],
                ],
            ]]),
            '*get_item_aspects_for_category*' => Http::response(['aspects' => [
                ['localizedAspectName' => 'Brand', 'aspectValues' => [['localizedValue' => 'Lenovo']]],
                ['localizedAspectName' => 'Processor', 'aspectValues' => [
                    ['localizedValue' => 'Intel Core i7'],
                    ['localizedValue' => 'AMD Ryzen 7'],
                ]],
                ['localizedAspectName' => 'RAM Size', 'aspectValues' => [['localizedValue' => '16 GB']]],
                ['localizedAspectName' => 'Storage Capacity', 'aspectValues' => [['localizedValue' => '1 TB SSD']]],
                ['localizedAspectName' => 'Screen Size', 'aspectValues' => [['localizedValue' => '15.6 in']]],
                ['localizedAspectName' => 'GPU', 'aspectValues' => [['localizedValue' => 'NVIDIA GeForce RTX 4060']]],
                ['localizedAspectName' => 'Operating System', 'aspectValues' => [['localizedValue' => 'Windows 11']]],
            ]]),
            '*buy/browse/v1/item/*' => Http::response([
                'description' => '<p>Полное описание товара от продавца.</p>',
                'additionalImages' => [
                    ['imageUrl' => 'https://i.ebayimg.com/images/g/second/s-l300.jpg'],
                    ['imageUrl' => 'https://i.ebayimg.com/images/g/third/s-l300.jpg'],
                ],
                'estimatedAvailabilities' => [
                    ['estimatedAvailabilityStatus' => 'IN_STOCK'],
                ],
            ]),
            '*buy/browse/*' => Http::response(['total' => 1, 'itemSummaries' => [[
                'itemId' => 'v1|123|0', 'title' => 'ThinkPad T14 Intel Core i7 16 GB RAM 512 GB SSD 15.6 in NVIDIA GeForce RTX 4060', 'itemWebUrl' => 'https://www.ebay.com/itm/123',
                'price' => ['value' => '450.00', 'currency' => 'USD'], 'condition' => 'USED',
                'image' => ['imageUrl' => 'https://i.ebayimg.com/images/g/main/s-l500.jpg'],
                'thumbnailImages' => [
                    ['imageUrl' => 'https://i.ebayimg.com/images/g/first-alternate/s-l300.jpg'],
                ],
                'localizedAspects' => [
                    ['name' => 'Brand', 'value' => 'Lenovo'],
                    ['name' => 'RAM Size', 'value' => '16 GB'],
                    ['name' => 'Processor', 'value' => 'Ryzen 7'],
                    ['name' => 'Operating System', 'value' => 'Windows 11'],
                ],
            ]]]),
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);
        config(['services.telegram.bot_token' => 'test-bot-token']);
        $user = User::factory()->create(['telegram_chat_id' => '123456789']);
        $profile = SearchProfile::factory()->create([
            'user_id' => $user->id,
            'query' => 'ThinkPad',
        ]);
        $this->artisan('ebay:import-laptops', ['profile' => $profile->id])->assertSuccessful();
        $this->assertDatabaseHas(LaptopListing::class, [
            'ebay_item_id' => 'v1|123|0',
            'cpu' => 'Ryzen 7',
            'ram_gb' => 16,
            'storage' => '512 GB SSD',
            'screen' => '15.6 in',
            'gpu' => 'NVIDIA GeForce RTX 4060',
            'seller_description' => 'Полное описание товара от продавца.',
        ]);
        $this->assertSame([
            'brand' => 'Lenovo',
            'ram size' => '16 GB',
            'processor' => 'Ryzen 7',
            'operating system' => 'Windows 11',
        ], LaptopListing::query()->firstOrFail()->specifications);
        $listing = LaptopListing::query()->firstOrFail();
        $this->assertSame([
            ['imageUrl' => 'https://i.ebayimg.com/images/g/second/s-l300.jpg'],
            ['imageUrl' => 'https://i.ebayimg.com/images/g/third/s-l300.jpg'],
        ], $listing->raw_payload['additionalImages']);
        $this->assertSame([
            'https://i.ebayimg.com/images/g/main/s-l1600.jpg',
            'https://i.ebayimg.com/images/g/first-alternate/s-l1600.jpg',
            'https://i.ebayimg.com/images/g/second/s-l1600.jpg',
            'https://i.ebayimg.com/images/g/third/s-l1600.jpg',
        ], $listing->galleryImageUrls());
        $this->assertDatabaseHas('search_profile_activities', [
            'search_profile_id' => $profile->id,
            'action' => 'parse_started',
        ]);
        $this->assertDatabaseHas('search_profile_activities', [
            'search_profile_id' => $profile->id,
            'action' => 'parse_completed',
        ]);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/bottest-bot-token/sendMessage')
            && $request->data()['chat_id'] === '123456789'
            && str_contains($request->data()['text'], 'ThinkPad T14')
            && str_contains($request->data()['text'], 'USD 450.00')
            && str_contains($request->data()['text'], 'https://www.ebay.com/itm/123'));

        LaptopListing::query()->firstOrFail()->delete();
        $this->artisan('ebay:import-laptops', ['profile' => $profile->id])->assertSuccessful();

        $this->assertDatabaseCount('laptop_listings', 1);
        $this->assertSoftDeleted(LaptopListing::withTrashed()->firstOrFail());
        $itemDetailRequests = Http::recorded()
            ->filter(fn (array $recordedRequest): bool => str_contains($recordedRequest[0]->url(), '/buy/browse/v1/item/'));
        $this->assertCount(1, $itemDetailRequests);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/buy/browse/v1/item/')
            && str_ends_with(rawurldecode((string) parse_url($request->url(), PHP_URL_PATH)), '/v1|123|0'));
    }

    public function test_search_retries_after_a_temporary_ebay_dns_failure(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*identity/*' => Http::response(['access_token' => 'token']),
            '*item_summary/search*' => Http::sequence()
                ->pushFailedConnection()
                ->push(['total' => 0, 'itemSummaries' => []]),
        ]);
        $profile = SearchProfile::factory()->create();

        $result = app(EbayBrowseService::class)->importPage($profile, 0);

        $this->assertSame(0, $result['imported']);
        $this->assertTrue($result['done']);
        Http::assertSentCount(3);
    }

    public function test_import_handles_a_listing_title_with_zero_gigabytes_of_ram(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*identity/*' => Http::response(['access_token' => 'token']),
            '*buy/browse/v1/item/*' => Http::response([
                'description' => 'Laptop description',
                'estimatedAvailabilities' => [
                    ['estimatedAvailabilityStatus' => 'IN_STOCK'],
                ],
            ]),
            '*buy/browse/v1/item_summary/search*' => Http::response(['total' => 1, 'itemSummaries' => [[
                'itemId' => 'v1|zero-ram-laptop|0',
                'title' => 'HP laptop 0GB RAM',
                'itemWebUrl' => 'https://www.ebay.com/itm/zero-ram-laptop',
                'shortDescription' => 'Laptop listing',
                'price' => ['value' => '80.00', 'currency' => 'USD'],
                'additionalImages' => [],
                'estimatedAvailabilities' => [
                    ['estimatedAvailabilityStatus' => 'IN_STOCK'],
                ],
            ]]]),
        ]);
        $profile = SearchProfile::factory()->create();

        $result = app(EbayBrowseService::class)->importPage($profile, 0);

        $this->assertSame(1, $result['imported']);
        $this->assertDatabaseHas('laptop_listings', [
            'search_profile_id' => $profile->id,
            'ebay_item_id' => 'v1|zero-ram-laptop|0',
            'ram_gb' => 0,
        ]);
    }

    public function test_import_replaces_a_saved_short_description_with_the_full_seller_description(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*identity/*' => Http::response(['access_token' => 'token']),
            '*buy/browse/v1/item/*' => Http::response([
                'description' => '<div>Полное описание продавца<br>Есть зарядное устройство.</div>',
            ]),
            '*buy/browse/*' => Http::response(['total' => 1, 'itemSummaries' => [[
                'itemId' => 'v1|description-upgrade|0',
                'title' => 'Description upgrade laptop',
                'itemWebUrl' => 'https://www.ebay.com/itm/description-upgrade',
                'shortDescription' => 'Краткое описание',
                'price' => ['value' => '400.00', 'currency' => 'USD'],
            ]]]),
        ]);
        $profile = SearchProfile::factory()->create();
        $listing = LaptopListing::factory()->for($profile)->create([
            'ebay_item_id' => 'v1|description-upgrade|0',
            'seller_description' => 'Краткое описание',
            'raw_payload' => ['shortDescription' => 'Краткое описание'],
        ]);

        app(EbayBrowseService::class)->importPage($profile, 0);

        $this->assertDatabaseHas('laptop_listings', [
            'id' => $listing->id,
            'seller_description' => "Полное описание продавца\nЕсть зарядное устройство.",
        ]);
        $itemDetailRequests = Http::recorded()
            ->filter(fn (array $recordedRequest): bool => str_contains($recordedRequest[0]->url(), '/buy/browse/v1/item/'));
        $this->assertCount(1, $itemDetailRequests);
    }

    public function test_import_saves_listing_when_optional_item_details_request_times_out(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*identity/*' => Http::response(['access_token' => 'token']),
            '*buy/browse/v1/item/*' => Http::failedConnection(),
            '*buy/browse/v1/item_summary/search*' => Http::response(['total' => 1, 'itemSummaries' => [[
                'itemId' => 'v1|details-timeout-laptop|0',
                'title' => 'ThinkPad T14 laptop',
                'itemWebUrl' => 'https://www.ebay.com/itm/details-timeout-laptop',
                'shortDescription' => 'Краткое описание товара',
                'price' => ['value' => '450.00', 'currency' => 'USD'],
            ]]]),
        ]);
        $profile = SearchProfile::factory()->create();

        $result = app(EbayBrowseService::class)->importPage($profile, 0);

        $this->assertSame(1, $result['imported']);
        $this->assertDatabaseHas('laptop_listings', [
            'search_profile_id' => $profile->id,
            'ebay_item_id' => 'v1|details-timeout-laptop|0',
            'seller_description' => 'Краткое описание товара',
        ]);
        $itemDetailRequests = Http::recorded()
            ->filter(fn (array $recordedRequest): bool => str_contains($recordedRequest[0]->url(), '/buy/browse/v1/item/'));
        $this->assertCount(3, $itemDetailRequests);
    }

    public function test_import_skips_accessories_and_listings_with_a_different_processor(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*identity/*' => Http::response(['access_token' => 'token']),
            '*get_default_category_tree_id*' => Http::response(['categoryTreeId' => '0']),
            '*get_category_subtree*' => Http::response(['categorySubtreeNode' => [
                'childCategoryTreeNodes' => [
                    ['category' => ['categoryId' => '177'], 'leafCategoryTreeNode' => true],
                ],
            ]]),
            '*get_item_aspects_for_category*' => Http::response(['aspects' => [
                ['localizedAspectName' => 'Processor', 'aspectValues' => [
                    ['localizedValue' => 'Intel Core i5-1135G7'],
                ]],
            ]]),
            '*buy/browse/v1/item/*' => Http::response([
                'description' => 'Laptop description',
                'estimatedAvailabilities' => [
                    ['estimatedAvailabilityStatus' => 'IN_STOCK'],
                ],
            ]),
            '*buy/browse/v1/item_summary/search*' => Http::response(['total' => 4, 'itemSummaries' => [
                [
                    'itemId' => 'v1|matching-processor-laptop|0',
                    'title' => 'HP ProBook 450 G8 laptop Intel Core i5-1135G7',
                    'itemWebUrl' => 'https://www.ebay.com/itm/matching-processor-laptop',
                    'localizedAspects' => [
                        ['name' => 'Processor', 'value' => 'Intel Core i5-1135G7'],
                    ],
                    'price' => ['value' => '450.00', 'currency' => 'USD'],
                ],
                [
                    'itemId' => 'v1|wrong-processor-laptop|0',
                    'title' => 'HP ProBook 450 G8 laptop Intel Celeron N4020',
                    'itemWebUrl' => 'https://www.ebay.com/itm/wrong-processor-laptop',
                    'localizedAspects' => [
                        ['name' => 'Processor', 'value' => 'Intel Celeron N4020'],
                    ],
                    'price' => ['value' => '250.00', 'currency' => 'USD'],
                ],
                [
                    'itemId' => 'v1|i3-processor-laptop|0',
                    'title' => 'HP ProBook 450 G8 laptop Intel Core i3-1115G4',
                    'itemWebUrl' => 'https://www.ebay.com/itm/i3-processor-laptop',
                    'localizedAspects' => [
                        ['name' => 'Processor', 'value' => 'Intel Core i3-1115G4'],
                    ],
                    'price' => ['value' => '300.00', 'currency' => 'USD'],
                ],
                [
                    'itemId' => 'v1|replacement-keyboard|0',
                    'title' => 'Replacement keyboard for HP ProBook 450 G8 Intel Core i5-1135G7',
                    'itemWebUrl' => 'https://www.ebay.com/itm/replacement-keyboard',
                    'localizedAspects' => [
                        ['name' => 'Processor', 'value' => 'Intel Core i5-1135G7'],
                    ],
                    'price' => ['value' => '25.00', 'currency' => 'USD'],
                ],
            ]]),
        ]);
        $profile = SearchProfile::factory()->create([
            'query' => 'HP ProBook 450 G8',
            'processors' => ['Intel Core i5-1135G7'],
        ]);

        $result = app(EbayBrowseService::class)->importPage($profile, 0);

        $this->assertSame(1, $result['imported']);
        $this->assertDatabaseHas('laptop_listings', [
            'search_profile_id' => $profile->id,
            'ebay_item_id' => 'v1|matching-processor-laptop|0',
        ]);
        $this->assertDatabaseMissing('laptop_listings', [
            'search_profile_id' => $profile->id,
            'ebay_item_id' => 'v1|wrong-processor-laptop|0',
        ]);
        $this->assertDatabaseMissing('laptop_listings', [
            'search_profile_id' => $profile->id,
            'ebay_item_id' => 'v1|i3-processor-laptop|0',
        ]);
        $this->assertDatabaseMissing('laptop_listings', [
            'search_profile_id' => $profile->id,
            'ebay_item_id' => 'v1|replacement-keyboard|0',
        ]);
        Http::assertSentCount(6);
    }

    public function test_import_skips_items_reported_as_out_of_stock_by_ebay(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*identity/*' => Http::response(['access_token' => 'token']),
            '*buy/browse/v1/item/*' => Http::response([
                'description' => 'Ноутбук временно недоступен.',
                'estimatedAvailabilities' => [
                    ['estimatedAvailabilityStatus' => 'OUT_OF_STOCK'],
                ],
            ]),
            '*buy/browse/*' => Http::response(['total' => 1, 'itemSummaries' => [[
                'itemId' => 'v1|out-of-stock-laptop|0',
                'title' => 'Out of stock laptop',
                'itemWebUrl' => 'https://www.ebay.com/itm/out-of-stock-laptop',
                'price' => ['value' => '400.00', 'currency' => 'USD'],
            ]]]),
        ]);
        $this->mock(ListingSpecificationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isAccessoryTitle')->andReturn(false);
            $mock->shouldReceive('matchesSelectedProcessor')->andReturn(true);
            $mock->shouldNotReceive('extract');
        });
        $profile = SearchProfile::factory()->create();

        $result = app(EbayBrowseService::class)->importPage($profile, 0);

        $this->assertSame(1, $result['processed']);
        $this->assertSame(0, $result['imported']);
        $this->assertSame(0, $result['new_listings']);
        $this->assertDatabaseMissing('laptop_listings', [
            'search_profile_id' => $profile->id,
            'ebay_item_id' => 'v1|out-of-stock-laptop|0',
        ]);
        $this->assertDatabaseCount('user_listing_alerts', 0);
        Http::assertSentCount(3);
    }

    public function test_import_skips_search_results_marked_out_of_stock_without_fetching_item_details(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*identity/*' => Http::response(['access_token' => 'token']),
            '*buy/browse/*' => Http::response(['total' => 1, 'itemSummaries' => [[
                'itemId' => 'v1|summary-out-of-stock-laptop|0',
                'title' => 'Out of stock laptop',
                'itemWebUrl' => 'https://www.ebay.com/itm/summary-out-of-stock-laptop',
                'estimatedAvailabilities' => [
                    ['estimatedAvailabilityStatus' => 'OUT_OF_STOCK'],
                ],
                'price' => ['value' => '400.00', 'currency' => 'USD'],
            ]]]),
        ]);
        $this->mock(ListingSpecificationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isAccessoryTitle')->andReturn(false);
            $mock->shouldReceive('matchesSelectedProcessor')->andReturn(true);
            $mock->shouldNotReceive('extract');
        });
        $profile = SearchProfile::factory()->create();

        $result = app(EbayBrowseService::class)->importPage($profile, 0);

        $this->assertSame(0, $result['imported']);
        $this->assertDatabaseMissing('laptop_listings', [
            'search_profile_id' => $profile->id,
            'ebay_item_id' => 'v1|summary-out-of-stock-laptop|0',
        ]);
        Http::assertSentCount(2);
    }

    public function test_import_skips_search_results_that_have_already_ended_on_ebay(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*identity/*' => Http::response(['access_token' => 'token']),
            '*buy/browse/*' => Http::response(['total' => 1, 'itemSummaries' => [[
                'itemId' => 'v1|ended-laptop|0',
                'title' => 'Ended laptop',
                'itemWebUrl' => 'https://www.ebay.com/itm/ended-laptop',
                'itemEndDate' => '2026-10-11T12:00:00.000Z',
                'estimatedAvailabilities' => [
                    ['estimatedAvailabilityStatus' => 'IN_STOCK'],
                ],
                'price' => ['value' => '400.00', 'currency' => 'USD'],
            ]]]),
        ]);
        $this->mock(ListingSpecificationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isAccessoryTitle')->andReturn(false);
            $mock->shouldReceive('matchesSelectedProcessor')->andReturn(true);
            $mock->shouldNotReceive('extract');
        });
        $profile = SearchProfile::factory()->create();
        $this->travelTo(Carbon::parse('2026-10-12 11:32:34', 'UTC'));

        $result = app(EbayBrowseService::class)->importPage($profile, 0);

        $this->assertSame(0, $result['imported']);
        $this->assertDatabaseMissing('laptop_listings', [
            'search_profile_id' => $profile->id,
            'ebay_item_id' => 'v1|ended-laptop|0',
        ]);
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/buy/browse/v1/item/'));
    }

    public function test_import_skips_a_listing_when_item_details_show_that_it_has_ended(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*identity/*' => Http::response(['access_token' => 'token']),
            '*buy/browse/v1/item/*' => Http::response([
                'description' => 'Seller description',
                'itemEndDate' => '2026-10-11T12:00:00.000Z',
                'estimatedAvailabilities' => [
                    ['estimatedAvailabilityStatus' => 'IN_STOCK'],
                ],
            ]),
            '*buy/browse/*' => Http::response(['total' => 1, 'itemSummaries' => [[
                'itemId' => 'v1|details-ended-laptop|0',
                'title' => 'Ended laptop',
                'itemWebUrl' => 'https://www.ebay.com/itm/details-ended-laptop',
                'price' => ['value' => '400.00', 'currency' => 'USD'],
            ]]]),
        ]);
        $this->mock(ListingSpecificationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isAccessoryTitle')->andReturn(false);
            $mock->shouldReceive('matchesSelectedProcessor')->andReturn(true);
            $mock->shouldNotReceive('extract');
        });
        $profile = SearchProfile::factory()->create();
        $this->travelTo(Carbon::parse('2026-10-12 11:32:34', 'UTC'));

        $result = app(EbayBrowseService::class)->importPage($profile, 0);

        $this->assertSame(0, $result['imported']);
        $this->assertDatabaseMissing('laptop_listings', [
            'search_profile_id' => $profile->id,
            'ebay_item_id' => 'v1|details-ended-laptop|0',
        ]);
        Http::assertSentCount(3);
    }

    public function test_ebay_import_schedule_runs_every_minute_in_bishkek_time(): void
    {
        $scheduledImport = collect(Schedule::events())
            ->first(fn ($event): bool => str_contains($event->command, 'ebay:import-laptops'));

        $this->assertNotNull($scheduledImport);
        $this->assertSame('* * * * *', $scheduledImport->expression);
        $this->assertSame('Asia/Bishkek', $scheduledImport->timezone);
    }

    public function test_scheduled_import_runs_only_active_profiles_due_once_per_day(): void
    {
        Http::fake([
            '*identity/*' => Http::response(['access_token' => 'token']),
            '*buy/browse/*' => Http::response(['total' => 0, 'itemSummaries' => []]),
        ]);
        $this->travelTo(Carbon::parse('2026-09-30 16:00:00', 'Asia/Bishkek'));

        $dueProfile = SearchProfile::factory()->create([
            'active' => true,
            'schedule_time' => '08:00:00',
            'schedule_interval_hours' => 4,
            'last_scheduled_date' => null,
        ]);
        $notDueProfile = SearchProfile::factory()->create([
            'active' => true,
            'schedule_time' => '17:01:00',
            'schedule_interval_hours' => 2,
        ]);
        $alreadyScheduledProfile = SearchProfile::factory()->create([
            'active' => true,
            'schedule_time' => '08:00:00',
            'schedule_interval_hours' => 4,
            'last_scheduled_at' => '2026-09-30 10:00:00',
        ]);
        $inactiveProfile = SearchProfile::factory()->create([
            'active' => false,
            'schedule_time' => '08:00:00',
            'schedule_interval_hours' => 4,
        ]);

        $this->artisan('ebay:import-laptops', ['--scheduled' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('search_profiles', [
            'id' => $dueProfile->id,
            'last_scheduled_at' => '2026-09-30 10:00:00',
        ]);
        $this->assertDatabaseHas('search_profiles', [
            'id' => $notDueProfile->id,
            'last_scheduled_at' => null,
        ]);
        $this->assertDatabaseHas('search_profiles', [
            'id' => $alreadyScheduledProfile->id,
            'last_scheduled_at' => '2026-09-30 10:00:00',
        ]);
        $this->assertDatabaseHas('search_profiles', [
            'id' => $inactiveProfile->id,
            'last_scheduled_at' => null,
        ]);
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/item_summary/search'));

        $this->travel(1)->minutes();
        $this->artisan('ebay:import-laptops', ['--scheduled' => true])
            ->assertSuccessful();
        Http::assertSentCount(2);
    }

    public function test_scheduled_profile_sends_its_daily_digest_at_its_start_time_only(): void
    {
        Http::fake([
            '*identity/*' => Http::response(['access_token' => 'token']),
            '*buy/browse/v1/item/*' => Http::response(['description' => 'Seller description']),
            '*buy/browse/*' => Http::response(['total' => 1, 'itemSummaries' => [[
                'itemId' => 'v1|daily-digest-test|0',
                'title' => 'Scheduled digest laptop',
                'itemWebUrl' => 'https://www.ebay.com/itm/daily-digest-test',
                'price' => ['value' => '450.00', 'currency' => 'USD'],
            ]]]),
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);
        config(['services.telegram.bot_token' => 'test-bot-token']);
        $user = User::factory()->create(['telegram_chat_id' => '123456789']);
        $profile = SearchProfile::factory()->create([
            'user_id' => $user->id,
            'schedule_time' => '08:00:00',
            'schedule_interval_hours' => 4,
            'notification_frequency' => 'daily',
        ]);
        $this->travelTo(Carbon::parse('2026-10-02 08:00:00', 'Asia/Bishkek'));

        $this->artisan('ebay:import-laptops', ['--scheduled' => true])->assertSuccessful();

        Http::assertSentCount(4);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/bottest-bot-token/sendMessage')
            && str_contains($request->data()['text'], 'Ежедневная сводка профиля')
            && str_contains($request->data()['text'], 'Scheduled digest laptop'));
        $this->assertDatabaseMissing('search_profile_notification_events', ['sent_at' => null]);

        $this->travel(4)->hours();
        Http::fake([
            '*identity/*' => Http::response(['access_token' => 'token']),
            '*buy/browse/*' => Http::response(['total' => 0, 'itemSummaries' => []]),
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);
        $this->artisan('ebay:import-laptops', ['--scheduled' => true])->assertSuccessful();
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'api.telegram.org'));
    }

    public function test_scheduled_import_notifies_the_profile_owner_when_search_fails(): void
    {
        Http::fake([
            '*identity/*' => Http::response(['access_token' => 'token']),
            '*buy/browse/*' => Http::response(['errors' => [['message' => 'Unavailable']]], 503),
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);
        config(['services.telegram.bot_token' => 'test-bot-token']);
        $user = User::factory()->create(['telegram_chat_id' => '123456789']);
        $profile = SearchProfile::factory()->create([
            'user_id' => $user->id,
            'name' => 'Scheduled profile',
            'query' => 'ThinkPad',
        ]);

        $this->artisan('ebay:import-laptops', ['profile' => $profile->id])
            ->assertFailed();

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/bottest-bot-token/sendMessage')
            && $request->data()['chat_id'] === '123456789'
            && str_contains($request->data()['text'], 'Ошибка поиска')
            && str_contains($request->data()['text'], 'Scheduled profile'));
    }

    public function test_import_sends_search_profile_filters_to_ebay(): void
    {
        Http::fake([
            '*identity/*' => Http::response(['access_token' => 'token']),
            '*get_default_category_tree_id*' => Http::response(['categoryTreeId' => '0']),
            '*get_category_subtree*' => Http::response(['categorySubtreeNode' => [
                'childCategoryTreeNodes' => [
                    ['category' => ['categoryId' => '177'], 'leafCategoryTreeNode' => true],
                    ['category' => ['categoryId' => '111422'], 'leafCategoryTreeNode' => true],
                ],
            ]]),
            '*get_item_aspects_for_category*' => Http::response(['aspects' => [
                ['localizedAspectName' => 'Brand', 'aspectValues' => [
                    ['localizedValue' => 'Lenovo'],
                    ['localizedValue' => 'Apple'],
                ]],
                ['localizedAspectName' => 'Processor', 'aspectValues' => [
                    ['localizedValue' => 'Intel Core i7'],
                    ['localizedValue' => 'AMD Ryzen 7'],
                ]],
                ['localizedAspectName' => 'RAM Size', 'aspectValues' => [
                    ['localizedValue' => '8 GB'],
                    ['localizedValue' => '16 GB'],
                ]],
                ['localizedAspectName' => 'Storage Capacity', 'aspectValues' => [
                    ['localizedValue' => '512 GB SSD'],
                    ['localizedValue' => '1 TB SSD'],
                ]],
                ['localizedAspectName' => 'Screen Size', 'aspectValues' => [
                    ['localizedValue' => '14 in'],
                    ['localizedValue' => '15.6 in'],
                ]],
                ['localizedAspectName' => 'GPU', 'aspectValues' => [
                    ['localizedValue' => 'NVIDIA GeForce RTX 4050'],
                    ['localizedValue' => 'NVIDIA GeForce RTX 4060'],
                ]],
                ['localizedAspectName' => 'Operating System', 'aspectValues' => [['localizedValue' => 'Windows 11']]],
            ]]),
            '*buy/browse/*' => Http::response([
                'total' => 0,
                'itemSummaries' => [],
                'refinements' => [
                    'aspectDistributions' => [[
                        'localizedAspectName' => 'Brand',
                        'aspectValueDistributions' => [['localizedAspectValue' => 'Lenovo', 'matchCount' => 20]],
                    ]],
                ],
            ]),
        ]);
        $profile = SearchProfile::factory()->create([
            'condition' => 'USED',
            'min_price' => 300,
            'max_price' => 1500,
            'brands' => ['Lenovo', 'Apple'],
            'processors' => ['Intel Core i7', 'AMD Ryzen 7'],
            'ram_sizes' => ['8 GB', '16 GB'],
            'storage_capacities' => ['512 GB SSD', '1 TB SSD'],
            'screen_sizes' => ['14 in', '15.6 in'],
            'gpus' => ['NVIDIA GeForce RTX 4050', 'NVIDIA GeForce RTX 4060'],
            'operating_system' => 'Windows 11',
        ]);

        app(EbayBrowseService::class)->import($profile);

        Http::assertSent(function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return str_contains($request->url(), '/item_summary/search')
                && ($query['category_ids'] ?? null) === '175672'
                && ($query['filter'] ?? null) === 'conditions:{USED},price:[300..1500],priceCurrency:USD'
                && ($query['aspect_filter'] ?? null) === 'categoryId:175672,Brand:{Lenovo|Apple},Processor:{Intel Core i7|AMD Ryzen 7},RAM Size:{8 GB|16 GB},Storage Capacity:{512 GB SSD|1 TB SSD},Screen Size:{14 in|15.6 in},GPU:{NVIDIA GeForce RTX 4050|NVIDIA GeForce RTX 4060},Operating System:{Windows 11}';
        });
    }

    public function test_the_same_ebay_listing_is_saved_separately_for_each_profile(): void
    {
        Http::fake([
            '*identity/*' => Http::response(['access_token' => 'token']),
            '*get_default_category_tree_id*' => Http::response(['categoryTreeId' => '0']),
            '*get_category_subtree*' => Http::response(['categorySubtreeNode' => [
                'childCategoryTreeNodes' => [
                    ['category' => ['categoryId' => '177'], 'leafCategoryTreeNode' => true],
                    ['category' => ['categoryId' => '111422'], 'leafCategoryTreeNode' => true],
                ],
            ]]),
            '*get_item_aspects_for_category*' => Http::response(['aspects' => []]),
            '*buy/browse/*' => Http::response(['total' => 1, 'itemSummaries' => [[
                'itemId' => 'v1|shared-listing|0',
                'title' => 'Laptop result',
                'itemWebUrl' => 'https://www.ebay.com/itm/shared-listing',
                'price' => ['value' => '450.00', 'currency' => 'USD'],
            ]]]),
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);
        $firstProfile = SearchProfile::factory()->create();
        $secondProfile = SearchProfile::factory()->create();
        User::query()->findOrFail($firstProfile->user_id)->update(['telegram_chat_id' => '123456789']);
        config(['services.telegram.bot_token' => 'test-bot-token']);
        $service = app(EbayBrowseService::class);

        $firstImport = $service->importPage($firstProfile, 0);
        $secondImport = $service->importPage($secondProfile, 0);
        $repeatedImport = $service->importPage($firstProfile, 0);
        $repeatedSecondImport = $service->importPage($secondProfile, 0);

        $this->assertDatabaseCount('laptop_listings', 2);
        $this->assertDatabaseCount('user_listing_alerts', 1);
        $this->assertDatabaseCount('notifications', 2);
        $this->assertSame(1, $firstImport['new_listings']);
        $this->assertSame(0, $secondImport['new_listings']);
        $this->assertSame(0, $repeatedImport['new_listings']);
        $this->assertSame(0, $repeatedSecondImport['new_listings']);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $firstProfile->user_id,
            'type' => NewEbayListingNotification::class,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $firstProfile->user_id,
            'type' => SharedEbayListingNotification::class,
            'data->smart_alert_type' => 'profile_match',
        ]);
        $this->actingAs(User::query()->findOrFail($firstProfile->user_id))
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Известный лот найден в новом профиле')
            ->assertSee($secondProfile->name);
        $this->assertDatabaseHas('laptop_listings', [
            'search_profile_id' => $firstProfile->id,
            'ebay_item_id' => 'v1|shared-listing|0',
        ]);
        $this->assertDatabaseHas('laptop_listings', [
            'search_profile_id' => $secondProfile->id,
            'ebay_item_id' => 'v1|shared-listing|0',
        ]);
        $telegramRequests = Http::recorded()
            ->filter(fn (array $recordedRequest): bool => str_contains($recordedRequest[0]->url(), 'api.telegram.org'));
        $this->assertCount(2, $telegramRequests);
    }

    public function test_import_does_not_parse_or_update_a_listing_marked_as_sold(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*identity/*' => Http::response(['access_token' => 'token']),
            '*buy/browse/v1/item/*' => Http::response(['description' => 'Available laptop description']),
            '*buy/browse/*' => Http::response(['total' => 1, 'itemSummaries' => [[
                'itemId' => 'v1|sold-listing|0',
                'title' => 'Updated Intel Core i7 32 GB RAM 1 TB SSD laptop',
                'itemWebUrl' => 'https://www.ebay.com/itm/sold-listing',
                'price' => ['value' => '400.00', 'currency' => 'USD'],
            ]]]),
        ]);
        $this->mock(ListingSpecificationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isAccessoryTitle')->andReturn(false);
            $mock->shouldReceive('matchesSelectedProcessor')->andReturn(true);
            $mock->shouldNotReceive('extract');
        });
        $user = User::factory()->create();
        $soldProfile = SearchProfile::factory()->for($user)->create();
        $profile = SearchProfile::factory()->for($user)->create();
        $listing = LaptopListing::factory()->for($soldProfile)->create([
            'ebay_item_id' => 'v1|sold-listing|0',
            'title' => 'Purchased laptop',
            'price' => 700,
            'total_price' => 700,
            'status' => 'buy',
            'sold_at' => '2026-01-01 10:00:00',
            'seen_at' => '2026-01-01 09:00:00',
            'specifications' => ['processor' => 'Intel Core i5'],
            'raw_payload' => ['title' => 'Purchased laptop'],
        ]);
        $listing->priceHistory()->create([
            'price' => 700,
            'currency' => 'USD',
            'observed_at' => '2026-01-01 09:00:00',
        ]);

        $result = app(EbayBrowseService::class)->importPage($profile, 0);

        $this->assertSame(1, $result['processed']);
        $this->assertSame(0, $result['imported']);
        $this->assertSame(0, $result['new_listings']);
        $this->assertDatabaseHas('laptop_listings', [
            'id' => $listing->id,
            'title' => 'Purchased laptop',
            'price' => '700.00',
            'status' => 'buy',
            'sold_at' => '2026-01-01 10:00:00',
            'seen_at' => '2026-01-01 09:00:00',
            'specifications' => json_encode(['processor' => 'Intel Core i5']),
            'raw_payload' => json_encode(['title' => 'Purchased laptop']),
        ]);
        $this->assertDatabaseMissing('laptop_listings', [
            'search_profile_id' => $profile->id,
            'ebay_item_id' => 'v1|sold-listing|0',
        ]);
        $this->assertSame(1, $listing->priceHistory()->count());
        $this->assertDatabaseCount('user_listing_alerts', 0);
    }

    public function test_a_sold_listing_owned_by_another_user_does_not_block_import(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*identity/*' => Http::response(['access_token' => 'token']),
            '*buy/browse/*' => Http::response(['total' => 1, 'itemSummaries' => [[
                'itemId' => 'v1|shared-sold-listing|0',
                'title' => 'Laptop still available',
                'itemWebUrl' => 'https://www.ebay.com/itm/shared-sold-listing',
                'price' => ['value' => '400.00', 'currency' => 'USD'],
            ]]]),
        ]);
        $soldOwner = User::factory()->create();
        $soldProfile = SearchProfile::factory()->for($soldOwner)->create();
        LaptopListing::factory()->for($soldProfile)->create([
            'ebay_item_id' => 'v1|shared-sold-listing|0',
            'status' => 'buy',
            'sold_at' => '2026-01-01 10:00:00',
        ]);
        $profile = SearchProfile::factory()->create();

        $result = app(EbayBrowseService::class)->importPage($profile, 0);

        $this->assertSame(1, $result['imported']);
        $this->assertDatabaseHas('laptop_listings', [
            'search_profile_id' => $profile->id,
            'ebay_item_id' => 'v1|shared-sold-listing|0',
        ]);
    }

    public function test_shared_price_change_is_notified_only_once_across_matching_profiles(): void
    {
        $prices = [600, 600, 550, 550, 550];
        $telegramMessages = [];
        Http::fake([
            '*identity/*' => Http::response(['access_token' => 'token']),
            '*buy/browse/v1/item/*' => Http::response(['description' => 'Shared item description']),
            '*buy/browse/v1/item_summary/search*' => function () use (&$prices) {
                $price = array_shift($prices);

                return Http::response(['total' => 1, 'itemSummaries' => [[
                    'itemId' => 'v1|shared-price-test|0',
                    'title' => 'Shared price laptop',
                    'itemWebUrl' => 'https://www.ebay.com/itm/shared-price-test',
                    'price' => ['value' => (string) $price, 'currency' => 'USD'],
                ]]]);
            },
            'api.telegram.org/*' => function (Request $request) use (&$telegramMessages) {
                $telegramMessages[] = $request->data()['text'];

                return Http::response(['ok' => true], 200);
            },
        ]);
        config(['services.telegram.bot_token' => 'test-bot-token']);
        $user = User::factory()->create(['telegram_chat_id' => '123456789']);
        $firstProfile = SearchProfile::factory()->for($user)->create();
        $secondProfile = SearchProfile::factory()->for($user)->create();
        $service = app(EbayBrowseService::class);

        $service->importPage($firstProfile, 0);
        $service->importPage($secondProfile, 0);
        $service->importPage($firstProfile, 0);
        $this->assertDatabaseCount('notifications', 2);
        $service->importPage($secondProfile, 0);
        $service->importPage($firstProfile, 0);

        $this->assertDatabaseCount('notifications', 3);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'type' => SharedEbayListingNotification::class,
            'data->smart_alert_type' => 'shared_price_change',
            'data->previous_price' => 600,
        ]);
        $this->assertCount(3, $telegramMessages);
        $this->assertSame(1, substr_count(implode("\n", $telegramMessages), 'Цена изменилась в нескольких профилях'));
        $this->assertDatabaseHas('user_listing_alerts', [
            'user_id' => $user->id,
            'ebay_item_id' => 'v1|shared-price-test|0',
            'last_observed_price' => 550,
            'last_observed_currency' => 'USD',
        ]);
        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Цена изменилась в нескольких профилях')
            ->assertSee('USD 600.00')
            ->assertSee('USD 550.00');
    }

    public function test_import_records_price_changes_and_sends_price_drop_and_threshold_alerts(): void
    {
        $prices = [600, 550, 550, 490, 480, 520, 510, 440];
        $telegramMessages = [];
        Http::fake([
            '*identity/*' => Http::response(['access_token' => 'token']),
            '*buy/browse/v1/item/*' => Http::response(['description' => 'Price tracking item description']),
            '*buy/browse/v1/item_summary/search*' => function () use (&$prices) {
                $price = array_shift($prices);

                return Http::response(['total' => 1, 'itemSummaries' => [[
                    'itemId' => 'v1|price-history-test|0',
                    'title' => 'Price tracking laptop',
                    'itemWebUrl' => 'https://www.ebay.com/itm/price-history-test',
                    'price' => ['value' => number_format($price - 20, 2, '.', ''), 'currency' => 'USD'],
                    'shippingOptions' => [['shippingCost' => ['value' => '20.00']]],
                ]]]);
            },
            'api.telegram.org/*' => function (Request $request) use (&$telegramMessages) {
                $telegramMessages[] = $request->data()['text'];

                return Http::response(['ok' => true], 200);
            },
        ]);
        config(['services.telegram.bot_token' => 'test-bot-token']);
        $user = User::factory()->create(['telegram_chat_id' => '123456789']);
        $profile = SearchProfile::factory()->create([
            'user_id' => $user->id,
            'price_alert_threshold' => 500,
        ]);
        $service = app(EbayBrowseService::class);

        foreach ([600, 550, 550, 490, 480, 520, 510] as $_) {
            $service->importPage($profile, 0);
        }

        $listing = LaptopListing::query()->where('ebay_item_id', 'v1|price-history-test|0')->firstOrFail();
        $this->assertSame(
            ['600.00', '550.00', '490.00', '480.00', '520.00', '510.00'],
            $listing->priceHistory()->orderBy('observed_at')->pluck('price')->all(),
        );
        $this->assertNotNull($listing->price_threshold_notified_at);
        $this->assertCount(5, $telegramMessages);
        $this->assertStringContainsString('Было: USD 600.00', $telegramMessages[1]);
        $this->assertStringContainsString('Стало: USD 550.00', $telegramMessages[1]);
        $this->assertStringContainsString('Цена опустилась ниже целевой: USD 500.00', $telegramMessages[2]);
        $this->assertStringNotContainsString('опустилась ниже целевой', $telegramMessages[3]);
        $this->assertStringContainsString('Было: USD 520.00', $telegramMessages[4]);

        $this->actingAs($user);
        $this->patch(route('profiles.update', $profile), [
            'name' => $profile->name,
            'query' => $profile->query,
            'price_alert_threshold' => 450,
            'active' => '1',
        ])->assertRedirect(route('profiles.index'));
        $profile->refresh();
        $service->importPage($profile, 0);

        $this->assertSame(7, $listing->priceHistory()->count());
        $this->assertStringContainsString('Цена опустилась ниже целевой: USD 450.00', $telegramMessages[5]);
    }

    public function test_profile_parse_page_returns_progress_and_saves_listings(): void
    {
        Http::fake([
            '*identity/*' => Http::response(['access_token' => 'token']),
            '*get_default_category_tree_id*' => Http::response(['categoryTreeId' => '0']),
            '*get_category_subtree*' => Http::response(['categorySubtreeNode' => [
                'childCategoryTreeNodes' => [
                    ['category' => ['categoryId' => '177'], 'leafCategoryTreeNode' => true],
                    ['category' => ['categoryId' => '111422'], 'leafCategoryTreeNode' => true],
                ],
            ]]),
            '*get_item_aspects_for_category*' => Http::response(['aspects' => []]),
            '*buy/browse/*' => Http::response([
                'total' => 401,
                'itemSummaries' => array_map(fn (int $index): array => [
                    'itemId' => "v1|parse-test-{$index}|0",
                    'title' => "ThinkPad T14 {$index}",
                    'itemWebUrl' => "https://www.ebay.com/itm/parse-test-{$index}",
                    'price' => ['value' => '450.00', 'currency' => 'USD'],
                ], range(1, 200)),
            ]),
        ]);
        $profile = SearchProfile::factory()->create();

        $response = $this->postJson(route('profiles.parse', $profile), [
            'offset' => 200,
            'run_id' => '53c159b6-7b49-41da-a22b-f99f16042487',
            'imported_total' => 200,
        ])
            ->assertOk()
            ->assertJson([
                'found' => 401,
                'processable' => 401,
                'processed' => 400,
                'imported' => 200,
                'next_offset' => 400,
                'done' => false,
            ])
            ->assertJsonPath('page_item_ids.0', 'v1|parse-test-1|0')
            ->assertJsonPath('page_item_ids.199', 'v1|parse-test-200|0');

        $this->assertDatabaseHas('laptop_listings', [
            'ebay_item_id' => 'v1|parse-test-1|0',
            'search_profile_id' => $profile->id,
        ]);

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/item_summary/search')
            && str_contains($request->url(), 'offset=200')
            && str_contains($request->url(), 'limit=200')
            && str_contains($request->url(), 'sort=price'));
    }

    public function test_profile_parse_page_completes_when_no_laptops_are_found(): void
    {
        Http::fake([
            '*identity/*' => Http::response(['access_token' => 'token']),
            '*buy/browse/*' => Http::response(['total' => 0, 'itemSummaries' => []]),
        ]);
        $profile = SearchProfile::factory()->create();

        $runId = '53c159b6-7b49-41da-a22b-f99f16042487';

        $this->postJson(route('profiles.parse', $profile), [
            'offset' => 0,
            'run_id' => $runId,
            'imported_total' => 0,
        ])
            ->assertOk()
            ->assertJson([
                'found' => 0,
                'processable' => 0,
                'processed' => 0,
                'imported' => 0,
                'next_offset' => 0,
                'done' => true,
            ]);

        $this->assertNotNull($profile->fresh()->last_synced_at);
        $this->assertDatabaseHas('search_profile_activities', [
            'search_profile_id' => $profile->id,
            'action' => 'parse_started',
            'run_id' => $runId,
        ]);
        $this->assertDatabaseHas('search_profile_activities', [
            'search_profile_id' => $profile->id,
            'action' => 'parse_completed',
            'run_id' => $runId,
        ]);
    }

    public function test_profile_parse_page_returns_price_partitions_after_ebay_offset_limit(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*identity/*' => Http::response(['access_token' => 'token']),
            '*buy/browse/v1/item_summary/search*' => function (Request $request) {
                parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $parameters);

                if (($parameters['filter'] ?? '') === 'price:[0..99.99],priceCurrency:USD') {
                    return Http::response(['total' => 9800]);
                }

                if (($parameters['filter'] ?? '') === 'price:[100..100],priceCurrency:USD') {
                    if (($parameters['offset'] ?? null) === '200') {
                        return Http::response([
                            'total' => 250,
                            'itemSummaries' => array_map(fn (int $index): array => [
                                'itemId' => "v1|price-partition-{$index}|0",
                                'title' => 'Existing laptop',
                                'itemWebUrl' => "https://www.ebay.com/itm/price-partition-{$index}",
                                'price' => ['value' => '100.00', 'currency' => 'USD'],
                            ], range(200, 249)),
                        ]);
                    }

                    return Http::response(['total' => 250]);
                }

                if (($parameters['offset'] ?? null) === '9800') {
                    return Http::response([
                        'total' => 11727,
                        'itemSummaries' => array_map(fn (int $index): array => [
                            'itemId' => "v1|price-partition-{$index}|0",
                            'title' => 'Existing laptop',
                            'itemWebUrl' => "https://www.ebay.com/itm/price-partition-{$index}",
                            'price' => ['value' => '100.00', 'currency' => 'USD'],
                        ], range(0, 199)),
                    ]);
                }

                return Http::response(['total' => 0]);
            },
        ]);
        $profile = SearchProfile::factory()->create();
        LaptopListing::factory()
            ->count(250)
            ->for($profile)
            ->sequence(fn ($sequence): array => [
                'ebay_item_id' => "v1|price-partition-{$sequence->index}|0",
                'seller_description' => 'Saved seller description',
                'raw_payload' => ['shortDescription' => 'Search summary'],
            ])
            ->create();

        $this->postJson(route('profiles.parse', $profile), [
            'offset' => 9800,
            'run_id' => '53c159b6-7b49-41da-a22b-f99f16042487',
            'imported_total' => 0,
        ])
            ->assertOk()
            ->assertJsonPath('found', 11727)
            ->assertJsonPath('processable', 10000)
            ->assertJsonPath('processed', 10000)
            ->assertJsonPath('page_processed', 200)
            ->assertJsonPath('next_offset', 10000)
            ->assertJsonPath('done', true)
            ->assertJsonPath('unprocessed_count', 0)
            ->assertJsonPath('next_partitions.0.offset', 200)
            ->assertJsonPath('next_partitions.0.price_min_cents', 10000)
            ->assertJsonPath('next_partitions.0.price_max_cents', 10000)
            ->assertJsonPath('next_partitions.1.offset', 0)
            ->assertJsonPath('next_partitions.1.price_min_cents', 10001)
            ->assertJsonPath('next_partitions.1.price_max_cents', null);

        $this->postJson(route('profiles.parse', $profile), [
            'offset' => 200,
            'run_id' => '53c159b6-7b49-41da-a22b-f99f16042487',
            'imported_total' => 200,
            'total_found' => 11727,
            'price_min_cents' => 10000,
            'price_max_cents' => 10000,
            'has_pending_partitions' => true,
        ])
            ->assertOk()
            ->assertJsonPath('found', 250)
            ->assertJsonPath('processed', 250)
            ->assertJsonPath('page_processed', 50)
            ->assertJsonPath('done', true)
            ->assertJsonPath('next_partitions', []);

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/item_summary/search')
            && str_contains($request->url(), 'offset=9800')
            && str_contains($request->url(), 'limit=200')
            && str_contains($request->url(), 'sort=price'));
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/item_summary/search')
            && str_contains($request->url(), 'offset=200')
            && str_contains($request->url(), 'price%3A%5B100..100%5D'));
    }

    public function test_profile_parse_page_uses_ebay_supported_offset_for_price_partition(): void
    {
        Http::preventStrayRequests();
        $items = array_map(fn (int $index): array => [
            'itemId' => "v1|aligned-price-partition-{$index}|0",
            'title' => 'HP laptop',
            'itemWebUrl' => "https://www.ebay.com/itm/aligned-price-partition-{$index}",
            'price' => ['value' => '80.00', 'currency' => 'USD'],
        ], range(0, 199));
        Http::fake([
            '*identity/*' => Http::response(['access_token' => 'token']),
            '*buy/browse/v1/item_summary/search*' => function (Request $request) use ($items) {
                parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $parameters);
                $offset = (int) ($parameters['offset'] ?? 0);

                if ($offset !== 0 && $offset % 200 !== 0) {
                    return Http::response(['errors' => [[
                        'errorId' => 12515,
                        'message' => 'The offset value must be zero or a multiple of 200.',
                    ]]], 400);
                }

                if ($offset === 9800) {
                    return Http::response(['total' => 12000, 'itemSummaries' => $items]);
                }

                if (($parameters['filter'] ?? '') === 'price:[1..79.99],priceCurrency:USD') {
                    return Http::response(['total' => 9850]);
                }

                if (($parameters['filter'] ?? '') === 'price:[80..80],priceCurrency:USD') {
                    return Http::response([
                        'total' => 500,
                        'itemSummaries' => $parameters['limit'] === '200' ? $items : [],
                    ]);
                }

                return Http::response(['total' => 0, 'itemSummaries' => []]);
            },
        ]);
        $profile = SearchProfile::factory()->create([
            'min_price' => 1,
            'max_price' => 85,
        ]);
        LaptopListing::factory()
            ->count(200)
            ->for($profile)
            ->sequence(fn ($sequence): array => [
                'ebay_item_id' => "v1|aligned-price-partition-{$sequence->index}|0",
                'seller_description' => 'Saved seller description',
                'raw_payload' => ['shortDescription' => 'Search summary'],
            ])
            ->create();

        $firstPage = $this->postJson(route('profiles.parse', $profile), [
            'offset' => 9800,
            'run_id' => '53c159b6-7b49-41da-a22b-f99f16042487',
            'imported_total' => 0,
        ])
            ->assertOk()
            ->assertJsonPath('next_partitions.0.offset', 0);

        $firstPage->assertJsonPath('next_partitions.0.price_min_cents', 8000);
        $firstPage->assertJsonPath('next_partitions.0.price_max_cents', 8000);

        $this->postJson(route('profiles.parse', $profile), [
            'offset' => 0,
            'price_min_cents' => 8000,
            'price_max_cents' => 8000,
            'run_id' => '53c159b6-7b49-41da-a22b-f99f16042487',
            'imported_total' => 200,
            'has_pending_partitions' => true,
        ])
            ->assertOk()
            ->assertJsonPath('page_processed', 200)
            ->assertJsonPath('next_offset', 200);

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/item_summary/search')
            && str_contains($request->url(), 'offset=0')
            && str_contains($request->url(), 'price%3A%5B80..80%5D'));
    }

    public function test_profile_parse_page_rescans_price_ranges_when_ebay_price_order_shifts(): void
    {
        Http::preventStrayRequests();
        $items = array_map(fn (int $index): array => [
            'itemId' => "v1|shifted-price-{$index}|0",
            'title' => 'HP laptop result',
            'itemWebUrl' => "https://www.ebay.com/itm/shifted-price-{$index}",
            'price' => ['value' => '80.00', 'currency' => 'USD'],
            'additionalImages' => [],
            'estimatedAvailabilities' => [
                ['estimatedAvailabilityStatus' => 'IN_STOCK'],
            ],
        ], range(0, 199));
        Http::fake([
            '*identity/*' => Http::response(['access_token' => 'token']),
            '*buy/browse/v1/item_summary/search*' => function (Request $request) use (&$items) {
                parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $parameters);

                if (($parameters['offset'] ?? null) === '9800') {
                    return Http::response(['total' => 12000, 'itemSummaries' => $items]);
                }

                return match ($parameters['filter'] ?? '') {
                    'price:[1..79.99],priceCurrency:USD' => Http::response(['total' => 10500]),
                    'price:[80..80],priceCurrency:USD' => Http::response(['total' => 400]),
                    default => Http::response(['total' => 0]),
                };
            },
        ]);
        $profile = SearchProfile::factory()->create([
            'min_price' => 1,
            'max_price' => 85,
        ]);
        LaptopListing::factory()
            ->count(200)
            ->for($profile)
            ->sequence(fn ($sequence): array => [
                'ebay_item_id' => "v1|shifted-price-{$sequence->index}|0",
                'seller_description' => 'Saved seller description',
            ])
            ->create();

        $this->postJson(route('profiles.parse', $profile), [
            'offset' => 9800,
            'run_id' => '53c159b6-7b49-41da-a22b-f99f16042487',
            'imported_total' => 0,
        ])
            ->assertOk()
            ->assertJsonPath('done', true)
            ->assertJsonPath('next_partitions', [
                ['offset' => 0, 'price_min_cents' => 100, 'price_max_cents' => 7999],
                ['offset' => 0, 'price_min_cents' => 8000, 'price_max_cents' => 8000],
                ['offset' => 0, 'price_min_cents' => 8001, 'price_max_cents' => 8500],
            ])
            ->assertJsonPath('unprocessed_count', 0);
    }

    public function test_profile_parse_page_rejects_invalid_offsets(): void
    {
        $profile = SearchProfile::factory()->create();

        $this->postJson(route('profiles.parse', $profile), [
            'offset' => 10000,
            'run_id' => '53c159b6-7b49-41da-a22b-f99f16042487',
            'imported_total' => 0,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('offset');
    }

    public function test_profile_parse_failure_is_saved_in_search_history(): void
    {
        Http::fake([
            '*identity/*' => Http::response(['access_token' => 'token']),
            '*buy/browse/*' => Http::response(['errors' => [['message' => 'Unavailable']]], 503),
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);
        config(['services.telegram.bot_token' => 'test-bot-token']);
        $user = User::factory()->create(['telegram_chat_id' => '123456789']);
        $profile = SearchProfile::factory()->create([
            'user_id' => $user->id,
            'name' => 'Failure profile',
            'query' => 'ThinkPad',
        ]);
        $this->actingAs($user);
        $runId = '53c159b6-7b49-41da-a22b-f99f16042487';

        $this->postJson(route('profiles.parse', $profile), [
            'offset' => 0,
            'run_id' => $runId,
            'imported_total' => 0,
        ])
            ->assertStatus(502)
            ->assertJsonPath('message', 'Не удалось выполнить поиск eBay. Проверьте подключение и настройки API.');

        $this->assertDatabaseHas('search_profile_activities', [
            'search_profile_id' => $profile->id,
            'action' => 'parse_started',
            'run_id' => $runId,
        ]);
        $this->assertDatabaseHas('search_profile_activities', [
            'search_profile_id' => $profile->id,
            'action' => 'parse_failed',
            'run_id' => $runId,
        ]);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/bottest-bot-token/sendMessage')
            && $request->data()['chat_id'] === '123456789'
            && str_contains($request->data()['text'], 'Ошибка поиска')
            && str_contains($request->data()['text'], 'Failure profile')
            && str_contains($request->data()['text'], 'ThinkPad'));
    }
}
