<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = "%{$request->search}%";
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search);
            });
        }

        $users = $query->orderBy('id', 'desc')->paginate(10)->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $request->search
            ],
            'stats' => [
                'total' => User::count(),
                'admin' => User::where('role', 'admin')->count(),
                'user' => User::where('role', 'user')->count(),
            ]
        ]);
    }

    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->with('error', 'Không thể xóa tài khoản của chính bạn.');
        }

        try {
            $user->delete();
            return back()->with('success', 'Đã xóa người dùng thành công.');
        } catch (\Exception $e) {
            // Check if it's a constraint violation
            if ($e->getCode() == 23000) {
                return back()->with('error', 'Không thể xóa người dùng này vì họ đã có lịch sử đặt hàng hoặc dữ liệu liên kết khác.');
            }
            return back()->with('error', 'Có lỗi xảy ra khi xóa người dùng: ' . $e->getMessage());
        }
    }
}
