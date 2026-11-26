<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\LogActivity;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchedulerControllerTest extends TestCase
{
    public function test_admin_can_clear_schedule_mutexes_from_processes_page(): void
    {
        Schema::dropIfExists('site_settings');
        Schema::create('site_settings', function (Blueprint $table): void {
            $table->id();
            $table->integer('ebay_stuck_threshold_minutes')->nullable();
        });

        $admin = (new User)->forceFill([
            'id' => 1,
            'is_admin' => true,
        ]);

        Artisan::partialMock()
            ->shouldReceive('call')
            ->once()
            ->with('schedule:clear-cache')
            ->andReturn(0);

        $this->withoutVite()
            ->withoutMiddleware(LogActivity::class)
            ->actingAs($admin, 'admin')
            ->get(route('admin.scheduler.processes-page'))
            ->assertOk()
            ->assertSee('Снять замок');

        $this->post(route('admin.scheduler.clear-mutex'))
            ->assertRedirect(route('admin.scheduler.processes-page'))
            ->assertSessionHas('success', 'Блокировка расписания снята (если она существовала).');
    }

    public function test_non_admin_cannot_clear_schedule_mutexes(): void
    {
        $member = (new User)->forceFill([
            'id' => 2,
            'is_admin' => false,
        ]);

        $this->withoutMiddleware(LogActivity::class)
            ->actingAs($member, 'admin')
            ->post(route('admin.scheduler.clear-mutex'))
            ->assertForbidden();
    }
}
