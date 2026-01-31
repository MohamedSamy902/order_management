<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Create 30 active products
        Product::factory()->count(30)->active()->create();

        // Create 5 out of stock products
        Product::factory()->count(5)->outOfStock()->create();

        // Create 5 random status products
        Product::factory()->count(5)->create();
    }
}
