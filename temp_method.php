    public function initiateUpdate(Request $request): JsonResponse|RedirectResponse
    {
        \Log::info("=== SIMPLE REDIRECT TEST ===");
        
        // Create a fake session ID for testing
        $sessionId = 'test-' . uniqid();
        \Log::info("Redirecting to progress page with session: " . $sessionId);
        
        return redirect()->route('admin.updates.progress-page', $sessionId);
    }
