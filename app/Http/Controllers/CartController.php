<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CartController extends Controller
{
    public function index()
    {
        $cartItems = CartItem::with('product.primaryImage')
            ->where('user_id', auth()->id())
            ->get();

        return Inertia::render('Cart', [
            'cartItems' => $cartItems
        ]);
    }
    /**
     * Thêm sản phẩm vào giỏ hàng
     * Dùng chung cho Home / Shop / Detail
     */
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'nullable|integer|min:1',
        ]);

        $userId    = Auth::id();
        $productId = $request->product_id;
        $qty       = $request->quantity ?? 1;

        $product = Product::findOrFail($productId);

        // ❌ Chặn nếu hết hàng
        if ($product->quantity <= 0) {
            return response()->json([
                'message' => 'Sản phẩm đã hết hàng'
            ], 400);
        }

        // Nếu sản phẩm đã tồn tại trong giỏ → tăng số lượng
        $cartItem = CartItem::where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        if ($cartItem) {

            $newQty = $cartItem->quantity + $qty;

            if ($newQty > $product->quantity) {
                return response()->json([
                    'message' => 'Số lượng vượt quá tồn kho'
                ], 400);
            }

            $cartItem->increment('quantity', $qty);
        } else {
            if ($qty > $product->quantity) {
                return response()->json([
                    'message' => 'Số lượng vượt quá tồn kho'
                ], 400);
            }

            CartItem::create([
                'user_id'    => $userId,
                'product_id' => $productId,
                'quantity'   => $qty,
                'price'      => $product->price, // snapshot giá tại thời điểm thêm
            ]);
        }

        return response()->json([
            'message'    => 'Đã thêm sản phẩm vào giỏ hàng',
            'cart_count' => CartItem::where('user_id', $userId)->sum('quantity'),
        ]);
    }

    /**
     * Cập nhật số lượng sản phẩm trong giỏ
     */
    public function update(Request $request)
    {
        $request->validate([
            'cart_item_id' => 'required|exists:cart_items,id',
            'quantity'     => 'required|integer|min:1',
        ]);

        $cartItem = CartItem::where('id', $request->cart_item_id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $cartItem->update([
            'quantity' => $request->quantity,
        ]);

        return response()->json([
            'message' => 'Cập nhật giỏ hàng thành công',
        ]);
    }

    /**
     * Xóa sản phẩm khỏi giỏ
     */
    public function remove($id)
    {
        CartItem::where('id', $id)
            ->where('user_id', Auth::id())
            ->delete();

        return response()->json([
            'message' => 'Đã xóa sản phẩm khỏi giỏ hàng',
        ]);
    }

    public function count()
    {
        $count = CartItem::where('user_id', auth()->id())
            ->distinct('product_id')
            ->count();

        return response()->json([
            'count' => $count
        ]);
    }

    public function checkout()
    {
        $cartItems = CartItem::with('product.primaryImage')
            ->where('user_id', auth()->id())
            ->get();

        return Inertia::render('Checkout', [
            'cartItems' => $cartItems
        ]);
    }
}
