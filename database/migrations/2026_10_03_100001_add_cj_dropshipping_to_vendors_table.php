<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->boolean('cj_dropshipping_enabled')->default(false)->after('status');
            $table->decimal('cj_markup_percent', 5, 2)->nullable()->after('cj_dropshipping_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['cj_dropshipping_enabled', 'cj_markup_percent']);
        });
    }
};
