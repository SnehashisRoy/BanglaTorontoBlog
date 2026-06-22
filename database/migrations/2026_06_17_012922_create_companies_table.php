<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->text('company');
            $table->text('contact');
            $table->text('service');
            $table->text('description');
            $table->string('slug', 191)->index();          // service category slug
            $table->string('company_slug', 191)->index();  // unique company identifier
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
