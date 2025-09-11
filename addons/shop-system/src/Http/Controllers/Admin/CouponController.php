<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use PterodactylAddons\ShopSystem\Http\Controllers\Controller;
use PterodactylAddons\ShopSystem\Models\ShopCoupon;
use PterodactylAddons\ShopSystem\Models\ShopCouponUsage;
use PterodactylAddons\ShopSystem\Models\ShopCategory;
use PterodactylAddons\ShopSystem\Models\ShopPlan;

class CouponController extends Controller
{
    /**
     * Display all coupons
     */
    public function index()
    {
        $coupons = ShopCoupon::withCount(['usages'])
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return view('shop::admin.coupons.index', compact('coupons'));
    }

    /**
     * Show create coupon form
     */
    public function create()
    {
        $categories = ShopCategory::with('plans')->get();
        $plans = ShopPlan::with('category')->where('visible', true)->get();
        
        return view('shop::admin.coupons.create', compact('categories', 'plans'));
    }

    /**
     * Store new coupon
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:shop_coupons,code|max:50',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'minimum_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date|after:now',
            'active' => 'boolean'
        ]);

        $coupon = ShopCoupon::create($request->all());

        return redirect()
            ->route('admin.shop.coupons.index')
            ->with('success', 'Coupon created successfully');
    }

    /**
     * Display a specific coupon
     */
    public function show(ShopCoupon $coupon)
    {
        $coupon->load(['usages.user', 'usages.order']);
        
        return view('shop::admin.coupons.show', compact('coupon'));
    }

    /**
     * Show edit coupon form
     */
    public function edit(ShopCoupon $coupon)
    {
        return view('shop::admin.coupons.edit', compact('coupon'));
    }

    /**
     * Update coupon
     */
    public function update(Request $request, ShopCoupon $coupon)
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:shop_coupons,code,' . $coupon->id,
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'minimum_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date|after:now',
            'active' => 'boolean'
        ]);

        $coupon->update($request->all());

        return redirect()
            ->route('admin.shop.coupons.index')
            ->with('success', 'Coupon updated successfully');
    }

    /**
     * Delete coupon
     */
    public function destroy(ShopCoupon $coupon)
    {
        $coupon->delete();

        return redirect()
            ->route('admin.shop.coupons.index')
            ->with('success', 'Coupon deleted successfully');
    }

    /**
     * Toggle coupon status
     */
    public function toggle(ShopCoupon $coupon)
    {
        $coupon->update(['active' => !$coupon->active]);

        $status = $coupon->active ? 'activated' : 'deactivated';
        
        return redirect()
            ->back()
            ->with('success', "Coupon {$status} successfully");
    }

    /**
     * Duplicate a coupon
     */
    public function duplicate(ShopCoupon $coupon): RedirectResponse
    {
        $duplicated = $coupon->replicate();
        $duplicated->code = $coupon->code . '-COPY-' . strtoupper(substr(md5(time()), 0, 4));
        $duplicated->active = false;
        $duplicated->save();

        return redirect()->route('admin.shop.coupons.edit', $duplicated)
            ->with('success', 'Coupon duplicated successfully.');
    }
}
