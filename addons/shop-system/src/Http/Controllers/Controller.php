<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Get the authenticated user.
     */
    protected function user()
    {
        return auth()->user();
    }

    /**
     * Check if shop is enabled and not in maintenance mode.
     */
    protected function checkShopAvailability(): void
    {
        if (!config('shop.enabled')) {
            abort(503, config('shop.maintenance_message', 'Shop is temporarily unavailable.'));
        }
    }

    /**
     * Format currency amount.
     */
    protected function formatCurrency(float $amount): string
    {
        $symbol = config('shop.currency.symbol', '$');
        $precision = config('shop.currency.precision', 2);
        $position = config('shop.currency.position', 'before');

        $formatted = number_format($amount, $precision);

        return $position === 'before' ? $symbol . $formatted : $formatted . $symbol;
    }

    /**
     * Generate success response for API endpoints.
     */
    protected function successResponse($data = null, string $message = 'Success', int $status = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Generate error response for API endpoints.
     */
    protected function errorResponse(string $message = 'An error occurred', int $status = 400, $errors = null): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    /**
     * Log user activity for shop actions.
     */
    protected function logActivity(string $description, $subject = null, array $properties = []): void
    {
        if (function_exists('activity') && $this->user()) {
            activity()
                ->causedBy($this->user())
                ->performedOn($subject)
                ->withProperties($properties)
                ->log($description);
        }
    }
}
