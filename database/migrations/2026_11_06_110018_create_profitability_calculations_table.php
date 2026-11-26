<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('profitability_calculations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->nullable()->constrained('laptop_listings')->nullOnDelete();
            $table->string('listing_title');
            $table->decimal('purchase_price', 10, 2);
            $table->decimal('additional_costs', 10, 2);
            $table->decimal('sale_price', 10, 2);
            $table->decimal('commission_percent', 5, 2);
            $table->decimal('total_cost', 12, 2);
            $table->decimal('commission_amount', 12, 2);
            $table->decimal('net_profit', 12, 2);
            $table->decimal('roi_percentage', 16, 2);
            $table->decimal('profit_margin_percentage', 16, 2);
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profitability_calculations');
    }
};
