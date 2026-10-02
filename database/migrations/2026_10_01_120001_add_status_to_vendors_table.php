<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved'])->default('pending')->after('is_active');
        });

        // Backfill: a vendor that was already active stays visible (approved);
        // anything that had been switched off goes back to pending for review.
        DB::table('vendors')->where('is_active', true)->update(['status' => 'approved']);
        DB::table('vendors')->where('is_active', false)->update(['status' => 'pending']);

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('website');
        });

        DB::table('vendors')->where('status', 'approved')->update(['is_active' => true]);
        DB::table('vendors')->where('status', 'pending')->update(['is_active' => false]);

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
