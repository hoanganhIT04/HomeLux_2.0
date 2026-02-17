<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display profile + user orders
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        // Lấy đơn hàng kèm chi tiết (nếu có)
        $orders = $user->orders()
            ->with(['orderItems.product']) // nếu bạn có quan hệ này
            ->latest()
            ->get();

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'orders' => $orders,
        ]);
    }

    public function account(Request $request)
    {
        $user = $request->user();

        $orders = $user->orders()
            ->latest()
            ->get();

        return Inertia::render('Profile/Account', [
            'orders' => $orders,
        ]);
    }


    /**
     * Update profile
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('account')
            ->with('success', 'Profile updated successfully.');
    }

    /**
     * Delete account
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
