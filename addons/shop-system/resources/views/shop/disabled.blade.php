@extends('shop::layout')

@section('title')
    Shop Unavailable
@endsection

@section('content-header')
@endsection

@section('content')
<div class="min-vh-100 d-flex align-items-center justify-content-center position-relative overflow-hidden" style="
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 25%, #334155 50%, #475569 75%, #64748b 100%);
    font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
">
    <!-- Animated Background Particles -->
    <div class="position-absolute top-0 start-0 w-100 h-100" style="overflow: hidden; z-index: 1;">
        <!-- Floating geometric shapes -->
        <div class="position-absolute" style="
            top: 10%;
            left: 15%;
            width: 120px;
            height: 120px;
            background: rgba(59, 130, 246, 0.08);
            border-radius: 50%;
            animation: float1 8s ease-in-out infinite;
            filter: blur(1px);
        "></div>
        
        <div class="position-absolute" style="
            top: 60%;
            right: 20%;
            width: 80px;
            height: 80px;
            background: rgba(139, 92, 246, 0.06);
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            animation: float2 12s ease-in-out infinite;
            filter: blur(1px);
        "></div>
        
        <div class="position-absolute" style="
            bottom: 25%;
            left: 25%;
            width: 100px;
            height: 100px;
            background: rgba(16, 185, 129, 0.05);
            border-radius: 20px;
            animation: float3 10s ease-in-out infinite;
            transform: rotate(45deg);
            filter: blur(1px);
        "></div>
        
        <!-- Additional ambient shapes -->
        <div class="position-absolute" style="
            top: 30%;
            right: 10%;
            width: 60px;
            height: 60px;
            background: rgba(245, 158, 11, 0.04);
            border-radius: 50%;
            animation: float1 15s ease-in-out infinite reverse;
        "></div>
        
        <div class="position-absolute" style="
            bottom: 40%;
            right: 35%;
            width: 40px;
            height: 40px;
            background: rgba(239, 68, 68, 0.03);
            border-radius: 50%;
            animation: float2 20s ease-in-out infinite;
        "></div>
    </div>

    <!-- Main Content Card -->
    <div class="container position-relative" style="z-index: 2;">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5 col-xl-4">
                <!-- Glass Card -->
                <div class="card border-0 shadow-lg position-relative" style="
                    background: rgba(15, 23, 42, 0.85);
                    backdrop-filter: blur(20px);
                    border-radius: 24px;
                    border: 1px solid rgba(71, 85, 105, 0.2);
                    animation: slideInUp 1s ease-out;
                    overflow: hidden;
                ">
                    <!-- Card glow effect -->
                    <div class="position-absolute top-0 start-0 w-100 h-100" style="
                        background: linear-gradient(45deg, 
                            transparent 0%, 
                            rgba(59, 130, 246, 0.05) 30%, 
                            rgba(139, 92, 246, 0.05) 70%, 
                            transparent 100%);
                        border-radius: 24px;
                        pointer-events: none;
                    "></div>
                    
                    <div class="card-body p-5 text-center position-relative">
                        <!-- Icon Container -->
                        <div class="mb-4 position-relative" style="animation: pulse 3s infinite;">
                            <!-- Icon background glow -->
                            <div class="position-absolute top-50 start-50 translate-middle" style="
                                width: 120px;
                                height: 120px;
                                background: radial-gradient(circle, rgba(59, 130, 246, 0.3) 0%, transparent 70%);
                                border-radius: 50%;
                                filter: blur(20px);
                                animation: iconGlow 4s ease-in-out infinite alternate;
                            "></div>
                            
                            <!-- Main Icon -->
                            <i class="fas fa-store-slash position-relative" style="
                                font-size: 4rem;
                                background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 50%, #06b6d4 100%);
                                -webkit-background-clip: text;
                                -webkit-text-fill-color: transparent;
                                background-clip: text;
                                filter: drop-shadow(0 0 20px rgba(59, 130, 246, 0.3));
                            "></i>
                        </div>

                        <!-- Title -->
                        <h1 class="h3 fw-bold mb-3" style="
                            color: #f8fafc;
                            letter-spacing: -0.025em;
                            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
                            -webkit-background-clip: text;
                            -webkit-text-fill-color: transparent;
                            background-clip: text;
                        ">
                            Shop is Currently Disabled
                        </h1>

                        <!-- Subtitle -->
                        <p class="mb-4" style="
                            color: #94a3b8;
                            font-size: 1.1rem;
                            line-height: 1.6;
                            font-weight: 400;
                        ">
                            The shop system is temporarily unavailable.<br>
                            <span style="color: #64748b; font-size: 0.95rem;">Please check back later!</span>
                        </p>

                        <!-- Decorative Divider -->
                        <div class="mb-4 d-flex align-items-center justify-content-center">
                            <div style="
                                height: 2px;
                                width: 80px;
                                background: linear-gradient(90deg, transparent 0%, #3b82f6 50%, transparent 100%);
                                border-radius: 1px;
                                position: relative;
                            ">
                                <!-- Animated dot -->
                                <div style="
                                    position: absolute;
                                    top: -3px;
                                    left: 50%;
                                    transform: translateX(-50%);
                                    width: 8px;
                                    height: 8px;
                                    background: #3b82f6;
                                    border-radius: 50%;
                                    animation: dotPulse 2s ease-in-out infinite;
                                    box-shadow: 0 0 15px rgba(59, 130, 246, 0.5);
                                "></div>
                            </div>
                        </div>

                        <!-- Return Button -->
                        <a href="{{ route('index') }}" class="btn btn-lg px-4 py-3 text-decoration-none" style="
                            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
                            border: none;
                            border-radius: 16px;
                            color: white;
                            font-weight: 600;
                            font-size: 1rem;
                            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                            box-shadow: 
                                0 4px 20px rgba(59, 130, 246, 0.3),
                                0 0 0 1px rgba(59, 130, 246, 0.1),
                                inset 0 1px 0 rgba(255, 255, 255, 0.1);
                            position: relative;
                            overflow: hidden;
                        " 
                        onmouseover="
                            this.style.transform='translateY(-2px) scale(1.02)'; 
                            this.style.boxShadow='0 8px 30px rgba(59, 130, 246, 0.4), 0 0 0 1px rgba(59, 130, 246, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.2)';
                        " 
                        onmouseout="
                            this.style.transform='translateY(0) scale(1)'; 
                            this.style.boxShadow='0 4px 20px rgba(59, 130, 246, 0.3), 0 0 0 1px rgba(59, 130, 246, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.1)';
                        ">
                            <!-- Button shine effect -->
                            <span style="
                                position: absolute;
                                top: 0;
                                left: -100%;
                                width: 100%;
                                height: 100%;
                                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
                                transition: left 0.6s;
                            "></span>
                            
                            <i class="fas fa-arrow-left me-2"></i>
                            Return to Dashboard
                        </a>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="text-center mt-4" style="
                    color: #64748b;
                    font-size: 0.875rem;
                    font-weight: 500;
                ">
                    <strong style="color: #94a3b8;">{{ config('app.name', 'Pterodactyl') }}</strong> 
                    <span style="color: #475569;">&copy; {{ date('Y') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom CSS Animations & Styles -->
<style>
    /* Floating animations */
    @keyframes float1 {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        33% { transform: translateY(-20px) rotate(5deg); }
        66% { transform: translateY(-10px) rotate(-3deg); }
    }
    
    @keyframes float2 {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        25% { transform: translateY(-15px) rotate(-5deg); }
        50% { transform: translateY(-25px) rotate(3deg); }
        75% { transform: translateY(-5px) rotate(-2deg); }
    }
    
    @keyframes float3 {
        0%, 100% { transform: translateY(0px) rotate(45deg); }
        50% { transform: translateY(-30px) rotate(50deg); }
    }
    
    /* Card entrance animation */
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(60px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    
    /* Icon animations */
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    @keyframes iconGlow {
        from { opacity: 0.4; transform: scale(1); }
        to { opacity: 0.8; transform: scale(1.1); }
    }
    
    @keyframes dotPulse {
        0%, 100% { opacity: 1; transform: translateX(-50%) scale(1); }
        50% { opacity: 0.6; transform: translateX(-50%) scale(1.2); }
    }
    
    /* Button hover shine effect */
    .btn:hover span {
        left: 100% !important;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .card-body {
            padding: 2rem !important;
        }
        
        .fas.fa-store-slash {
            font-size: 3rem !important;
        }
        
        .h3 {
            font-size: 1.5rem !important;
        }
        
        .btn {
            font-size: 0.9rem !important;
            padding: 0.75rem 1.5rem !important;
        }
    }
    
    /* Accessibility improvements */
    @media (prefers-reduced-motion: reduce) {
        * {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
    }
    
    /* Focus styles for accessibility */
    .btn:focus {
        outline: 2px solid #3b82f6;
        outline-offset: 2px;
    }
</style>
@endsection
