<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{

    public function makeOrder(Request $request)
    {
        if(!auth()->check()) {
            return response()->json(['message' => 'Unauthorized user'], 401);
        }

        $product_id = $request->product_id;
        $quantity = $request->quantity;

        if($this->checkAvailableStocks($product_id, $quantity)) {
            return $this->processOrder($product_id, $quantity);
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

    public function processOrder($product_id, $quantity)
    {
        $product = Product::where('id', $product_id)->lockForUpdate()->first();
        $product->available_stock -= $quantity;
        $product->save();
        return response()->json(['message' => 'You have successfully ordered this product'], 201);
    }
}
