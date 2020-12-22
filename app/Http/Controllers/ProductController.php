<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function store(Request $request)
    {
        $product_id = $request->product_id;
        $quantity = $request->quantity;

        if($this->checkAvailableStocks($product_id, $quantity)) {
            return $this->makeOrder($product_id, $quantity);
        }

        return response()->json(['message' => 'Failed to order this product due to unavailability of the stock'], 400);
    }

    public function checkAvailableStocks($product_id, $quantity = 1)
    {
        $product = Product::findOrFail($product_id);
        if($product->available_stock >= $quantity) {
            return true;
        }
        
        return false;
    }

    public function makeOrder($product_id, $quantity)
    {
        $product = Product::findOrFail($product_id);
        $product->available_stock -= $quantity;
        $product->save();
        return response()->json(['message' => 'You have successfully ordered this product'], 201);
    }
}
