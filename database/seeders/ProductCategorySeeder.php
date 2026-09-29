<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Groceries',
            'Clothing & Fashion',
            'Home & Kitchen',
            'Electronics',
            'Beauty & Health',
            'Books & Stationery',
            'Handicrafts',
            'Food & Catering',
            'Jewelry',
            'Other',
        ];

        foreach ($categories as $name) {
            ProductCategory::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name],
            );
        }
    }
}
