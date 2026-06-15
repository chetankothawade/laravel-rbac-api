<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name' => 'Wireless Mouse',
                'sku' => 'PRD-WM-001',
                'description' => 'Ergonomic wireless mouse with adjustable DPI.',
                'price' => 799.00,
                'stock' => 50,
            ],
            [
                'name' => 'Mechanical Keyboard',
                'sku' => 'PRD-MK-002',
                'description' => 'Compact mechanical keyboard with tactile switches.',
                'price' => 2499.00,
                'stock' => 30,
            ],
            [
                'name' => 'USB-C Hub',
                'sku' => 'PRD-UH-003',
                'description' => 'Multi-port USB-C hub with HDMI and card reader.',
                'price' => 1499.00,
                'stock' => 40,
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['sku' => $product['sku']],
                $product + ['status' => 'active']
            );
        }
    }
}
