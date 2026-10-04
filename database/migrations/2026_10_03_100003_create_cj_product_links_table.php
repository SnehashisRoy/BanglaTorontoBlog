<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cj_product_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('cj_product_id');
            $table->string('cj_variant_id')->nullable();
            $table->decimal('cj_cost_price', 10, 2);
            $table->string('cj_warehouse_country', 10)->nullable();
            $table->timestamp('cj_last_synced_at');
            $table->timestamps();

            $table->index(['cj_product_id', 'cj_variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cj_product_links');
    }
};
