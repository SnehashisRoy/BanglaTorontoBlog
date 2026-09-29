<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug', 191);
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamps();

            $table->unique(['vendor_id', 'slug']);
            $table->index(['status', 'created_at']);
            $table->index(['vendor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
