<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    /**
     * Make an order
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function makeOrder(Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthorized user'], 401);
        }

        $productId = $request->product_id;
        $quantity = $request->quantity;

        if ($this->isProductAvailable($productId, $quantity)) {
            return $this->processOrder($productId, $quantity);
        }

        return response()->json(['message' => 'Failed to order this product due to unavailability of the stock'], 400);
    }

    /**
     * Check if the product is available
     *
     * @param $productId
     * @param int $quantity
     * @return bool
     */
    public function isProductAvailable($productId, $quantity = 1)
    {
        $product = Product::findOrFail($productId);

        if ($product->available_stock >= $quantity) {
            return true;
        }

        return false;
    }

    /**
     * Process the order
     *
     * -- this will update the available_stock
     *
     * @param $productId
     * @param $quantity
     * @return JsonResponse
     */
    public function processOrder($productId, $quantity)
    {
        $product = Product::where('id', $productId)->lockForUpdate()->first();
        $product->available_stock -= $quantity;
        $product->save();
        return response()->json(['message' => 'You have successfully ordered this product'], 201);
    }
}
