<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        Category::firstOrCreate(['slug' => 'technology'], ['name' => 'Technology']);
        Category::firstOrCreate(['slug' => 'general'],    ['name' => 'General']);
    }
}
