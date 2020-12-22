<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Product;
use App\Models\User;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function unauthorized_user_cannot_order_products()
    {
        $product = Product::create([
            'name' => 'Product1',
            'available_stock' => 3
        ]);
        
        $this->post('api/order', ['product_id' => $product->id, 'quantity' => 1])->assertStatus(401);
    }

    /** @test */
    public function user_can_order_products_with_available_stock()
    {
        $user = User::factory()->create();

        $product = Product::create([
            'name' => 'Product1',
            'available_stock' => 3
        ]);
        
        $this->actingAs($user, 'api')->post('api/order', ['product_id' => $product->id, 'quantity' => 1])->assertStatus(201);
    }

    /** @test */
    public function user_cannot_order_products_with_insufficient_available_stock()
    {
        $user = User::factory()->create();

        $product = Product::create([
            'name' => 'Product1',
            'available_stock' => 3
        ]);
        
        $this->actingAs($user, 'api')->post('api/order', ['product_id' => $product->id, 'quantity' => 999])->assertStatus(400);
    }
}
