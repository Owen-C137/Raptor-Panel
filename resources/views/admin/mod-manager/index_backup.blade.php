@extends('layouts.admin')

@section('title')
    Mod Manager
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          <i class="fas fa-cubes me-2"></i>Mod Manager
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          <span x-show="!selectedGame && currentView === 'games'">Choose a game to manage mods</span>
          <span x-show="selectedGame && currentView === 'harvest'">
            Manage <span x-text="selectedGame?.name"></span> mods from CurseForge
          </span>
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item">
            <a class="link-fx" href="{{ route('admin.index') }}">Admin</a>
          </li>
          <li class="breadcrumb-item" x-show="!selectedGame" aria-current="page">
            Mod Manager
          </li>
          <li class="breadcrumb-item" x-show="selectedGame" aria-current="page" x-text="selectedGame?.name">
          </li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@push('head-scripts')
<style>
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.3; }
        100% { opacity: 1; }
    }
    
    .live-indicator {
        font-size: 6px;
        animation: pulse 2s infinite;
    }
    
    .updating {
        opacity: 0.7;
        transition: opacity 0.3s ease;
    }
    
    .queue-worker-card {
        transition: all 0.3s ease;
    }
    
    .queue-worker-card.updating {
        box-shadow: 0 0 10px rgba(40, 167, 69, 0.3);
    }
</style>
@endpush

@section('content')
<div class="row" x-data="modManagerDashboard()">
    <!-- Queue Worker Status (Always Visible) -->
    <div class="col-12 mb-4">
        <div class="block block-rounded queue-worker-card" 
             :class="{'border-success': queueWorker.running, 'border-danger': !queueWorker.running, 'updating': isLoadingQueueWorker}">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fas fa-cogs me-2"></i>Queue Worker Status
                    <small class="text-muted ms-2" x-show="queueWorker.running">
                        <span x-show="!connectionError">
                            <i class="fas fa-circle text-success live-indicator"></i>
                            Live monitoring - updates every 2 seconds
                        </span>
                        <span x-show="connectionError" class="text-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Connection issue - retrying...
                        </span>
                        <span x-show="lastQueueWorkerUpdate && !connectionError" class="ms-2">
                            (Last updated: <span x-text="lastQueueWorkerUpdate"></span>)
                        </span>
                    </small>
                </h3>
                <div class="block-options">
                    <button type="button" 
                            class="btn btn-sm btn-success me-2" 
                            @click="startQueueWorker"
                            :disabled="workerButtonState.running || isLoadingQueueWorker"
                            x-show="!workerButtonState.running">
                        <i class="fas fa-play me-1"></i>Start Worker
                    </button>
                    <button type="button" 
                            class="btn btn-sm btn-danger me-2" 
                            @click="stopQueueWorker"
                            :disabled="!workerButtonState.running || isLoadingQueueWorker"
                            x-show="workerButtonState.running">
                        <i class="fas fa-stop me-1"></i>Stop Worker
                    </button>
                    <button type="button" class="btn btn-sm btn-alt-primary" @click="refreshQueueWorkerStatus">
                        <i class="fas fa-sync-alt me-1"></i>Refresh
                    </button>
                </div>
            </div>
            <div class="block-content">
                <div class="row">
                    <div class="col-md-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3">
                                <div :class="queueWorker.running ? 'bg-success-light' : 'bg-danger-light'" class="p-2 rounded">
                                    <i :class="queueWorker.running ? 'fas fa-check-circle text-success' : 'fas fa-times-circle text-danger'" class="fa-lg"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fs-sm fw-semibold text-muted text-uppercase tracking-wider">Status</div>
                                <div class="fs-5 fw-normal" :class="queueWorker.running ? 'text-success' : 'text-danger'" 
                                     x-text="queueWorker.running ? 'Running' : 'Stopped'">Checking...</div>
                                <small class="text-muted" x-show="queueWorker.running && queueWorker.pid">
                                    PID: <span x-text="queueWorker.pid"></span>
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3" x-show="queueWorker.running">
                        <div class="fs-sm fw-semibold text-muted text-uppercase tracking-wider">
                            Uptime
                            <i class="fas fa-circle text-success ms-1" 
                               style="font-size: 6px; animation: pulse 2s infinite;" 
                               title="Live data - updates every 2 seconds"></i>
                        </div>
                        <div class="fs-5 fw-normal" x-text="queueWorker.uptime || 'N/A'">-</div>
                    </div>
                    <div class="col-md-3" x-show="queueWorker.running">
                        <div class="fs-sm fw-semibold text-muted text-uppercase tracking-wider">
                            CPU Usage
                            <i class="fas fa-circle text-success ms-1" 
                               style="font-size: 6px; animation: pulse 2s infinite;" 
                               title="Live data - updates every 2 seconds"></i>
                        </div>
                        <div class="fs-5 fw-normal" x-text="queueWorker.cpu_usage ? queueWorker.cpu_usage + '%' : 'N/A'">-</div>
                    </div>
                    <div class="col-md-3" x-show="queueWorker.running">
                        <div class="fs-sm fw-semibold text-muted text-uppercase tracking-wider">
                            Memory
                            <i class="fas fa-circle text-success ms-1" 
                               style="font-size: 6px; animation: pulse 2s infinite;" 
                               title="Live data - updates every 2 seconds"></i>
                        </div>
                        <div class="fs-5 fw-normal" x-text="queueWorker.memory_usage ? queueWorker.memory_usage + '%' : 'N/A'">-</div>
                    </div>
                </div>
                
                <div x-show="!queueWorker.running" class="alert alert-warning mt-3 mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Queue Worker Required:</strong> You must start the queue worker before you can run harvest jobs.
                </div>
            </div>
        </div>
    </div>

    <!-- Game Selection View -->
    <div x-show="currentView === 'games'" class="col-12">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fas fa-gamepad me-2"></i>Select Game
                </h3>
                <div class="block-options">
                    <button type="button" class="btn btn-sm btn-alt-primary" @click="refreshGames">
                        <i class="fas fa-sync-alt me-1"></i>Refresh
                    </button>
                </div>
            </div>
            <div class="block-content block-content-full">
                <div class="row" x-show="games.length > 0">
                    <template x-for="game in games" :key="game.id">
                        <div class="col-md-6 col-xl-4 mb-4">
                            <div class="block block-rounded h-100 mb-0 overflow-hidden cursor-pointer" 
                                 @click="selectGame(game)"
                                 style="transition: all 0.3s ease;">
                                
                                <!-- Game Header with Background -->
                                <div class="block-content bg-image p-0" 
                                     :style="getGameBackgroundStyle(game)">
                                    <div class="block-content block-content-full d-flex align-items-center justify-content-between bg-primary-dark-op">
                                        <div class="me-3">
                                            <p class="fw-bold text-white mb-0" x-text="game.name">Game Name</p>
                                            <p class="fs-sm fw-medium text-white-75 mb-0">
                                                <span x-text="(game.mods_count || 0)"></span> Mods Available
                                            </p>
                                        </div>
                                        <img class="img-avatar img-avatar48 img-avatar-thumb" 
                                             :src="getGameIconUrl(game)" 
                                             :alt="game.name"
                                             style="border: 2px solid rgba(255,255,255,0.3);">
                                    </div>
                                </div>
                                
                                <!-- Game Stats -->
                                <div class="block-content">
                                    <div class="list-group push">
                                        <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5 class="fs-base mb-1">Total Mods Available</h5>
                                                <small class="text-muted">Downloadable mod packages</small>
                                            </div>
                                            <span class="badge bg-primary fs-sm" x-text="game.mods_count || '0'">0</span>
                                        </div>
                                        <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5 class="fs-base mb-1">Categories</h5>
                                                <small class="text-muted">Organized mod categories</small>
                                            </div>
                                            <span class="badge bg-info fs-sm" x-text="game.categories_count || '0'">0</span>
                                        </div>
                                        <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5 class="fs-base mb-1">Mod Files</h5>
                                                <small class="text-muted">Version files available</small>
                                            </div>
                                            <span class="badge bg-success fs-sm" x-text="game.files_count || '0'">0</span>
                                        </div>
                                        <div x-show="game.last_harvest_at" class="list-group-item list-group-item-action">
                                            <div>
                                                <h5 class="fs-base mb-1">
                                                    <i class="fas fa-clock me-1"></i>Last Harvest
                                                </h5>
                                                <small class="text-muted" x-text="formatDate(game.last_harvest_at)">Never</small>
                                            </div>
                                        </div>
                                        <div x-show="!game.last_harvest_at" class="list-group-item list-group-item-action">
                                            <div>
                                                <h5 class="fs-base mb-1">
                                                    <i class="fas fa-info-circle me-1"></i>No Harvests Yet
                                                </h5>
                                                <small class="text-muted">Start a harvest to collect mod data</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Action Footer -->
                                <div class="block-content border-top">
                                    <div class="d-flex justify-content-between">
                                        <span class="btn btn-sm btn-alt-secondary">
                                            <i class="fas fa-cubes opacity-50 me-1"></i>
                                            <span x-text="(game.categories_count || 0) + ' Categories'"></span>
                                        </span>
                                        <span class="btn btn-sm btn-primary">
                                            Manage <i class="fas fa-arrow-right ms-1"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                
                <!-- Loading State -->
                <div x-show="isLoadingGames" class="text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                    <p class="text-muted mt-2">Loading games...</p>
                </div>
                
                <!-- No Games State -->
                <div x-show="!isLoadingGames && games.length === 0" class="text-center py-5">
                    <i class="fas fa-gamepad fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No games available. Install the addon to add game data.</p>
                </div>
            </div>
        </div>
    </div>
    <!-- Harvest Control View (Game Selected) -->
    <div x-show="currentView === 'harvest'" class="col-12">
        <!-- Back Button -->
        <div class="mb-3">
            <button type="button" class="btn btn-alt-secondary" @click="backToGameSelection">
                <i class="fas fa-arrow-left me-2"></i>Back to Game Selection
            </button>
        </div>

        <!-- Game Info Header -->
        <div class="block block-rounded mb-4">
            <div class="block-content">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 me-3">
                        <img :src="getGameIconUrl(selectedGame)" 
                             :alt="selectedGame?.name"
                             class="img-fluid rounded"
                             style="max-height: 60px;">
                    </div>
                    <div class="flex-grow-1">
                        <h3 class="h4 fw-bold mb-1" x-text="selectedGame?.name">Game Name</h3>
                        <p class="text-muted mb-0">Manage mods, categories, and harvest operations</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Overview for Selected Game -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="block block-rounded" :class="{'updating': isLoadingLiveStats}">
                    <div class="block-content block-content-full">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3">
                                <div class="bg-success-light p-2 rounded">
                                    <i class="fas fa-cube fa-lg text-success"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fs-sm fw-semibold text-muted text-uppercase tracking-wider">
                                    Mods in Database
                                    <i class="fas fa-circle text-success live-indicator ms-1" 
                                       title="Live data - updates every 2 seconds"></i>
                                </div>
                                <div class="fs-2 fw-normal" x-text="liveStats?.mods_count || selectedGame?.mods_count || '0'">0</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="block block-rounded" :class="{'updating': isLoadingLiveStats}">
                    <div class="block-content block-content-full">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3">
                                <div class="bg-info-light p-2 rounded">
                                    <i class="fas fa-tags fa-lg text-info"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fs-sm fw-semibold text-muted text-uppercase tracking-wider">
                                    Categories
                                    <i class="fas fa-circle text-success live-indicator ms-1" 
                                       title="Live data - updates every 2 seconds"></i>
                                </div>
                                <div class="fs-2 fw-normal" x-text="liveStats?.categories_count || selectedGame?.categories_count || '0'">0</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="block block-rounded" :class="{'updating': isLoadingLiveStats}">
                    <div class="block-content block-content-full">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3">
                                <div class="bg-secondary-light p-2 rounded">
                                    <i class="fas fa-file-archive fa-lg text-secondary"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fs-sm fw-semibold text-muted text-uppercase tracking-wider">
                                    Files
                                    <i class="fas fa-circle text-success live-indicator ms-1" 
                                       title="Live data - updates every 2 seconds"></i>
                                </div>
                                <div class="fs-2 fw-normal" x-text="liveStats?.files_count || selectedGame?.files_count || '0'">0</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="block block-rounded" :class="{'updating': isLoadingLiveStats}">
                    <div class="block-content block-content-full">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3">
                                <div class="bg-warning-light p-2 rounded">
                                    <i class="fas fa-tasks fa-lg text-warning"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fs-sm fw-semibold text-muted text-uppercase tracking-wider">
                                    Active Jobs
                                    <i class="fas fa-circle text-success live-indicator ms-1" 
                                       title="Live data - updates every 2 seconds"></i>
                                </div>
                                <div class="fs-2 fw-normal" x-text="liveStats?.active_jobs || stats.active_jobs || '0'">0</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Harvest Control Panel -->
        <div class="row">
            <div class="col-xl-8">
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">
                            <i class="fas fa-play-circle me-2"></i>Harvest Control
                        </h3>
                        <div class="block-options">
                            <button type="button" class="btn btn-sm btn-alt-primary" @click="refreshAll">
                                <i class="fas fa-sync-alt me-1"></i>Refresh
                            </button>
                        </div>
                    </div>
                    <div class="block-content">
                        <!-- Queue Worker Warning -->
                        <div x-show="!queueWorker.running" class="alert alert-warning mb-4">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Queue Worker Required:</strong> Start the queue worker above before running harvest jobs.
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <button type="button" 
                                        class="btn btn-success w-100 mb-3" 
                                        @click="startHarvest('categories')"
                                        :disabled="!queueWorker.running || isLoading">
                                    <i class="fas fa-download me-2"></i>
                                    Start Category Harvest
                                </button>
                                <button type="button" 
                                        class="btn btn-primary w-100 mb-3" 
                                        @click="startHarvest('popular')"
                                        :disabled="!queueWorker.running || isLoading">
                                    <i class="fas fa-fire me-2"></i>
                                    Start Popular Mods
                                </button>
                            </div>
                            <div class="col-md-6">
                                <button type="button" 
                                        class="btn btn-info w-100 mb-3" 
                                        @click="startHarvest('recent')"
                                        :disabled="!queueWorker.running || isLoading">
                                    <i class="fas fa-clock me-2"></i>
                                    Start Recent Mods
                                </button>
                                <button type="button" 
                                        class="btn btn-danger w-100 mb-3" 
                                        @click="stopAllJobs()"
                                        :disabled="isLoading">
                                    <i class="fas fa-stop me-2"></i>
                                    Stop All Jobs
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Jobs -->
            <div class="col-xl-4">
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">
                            <i class="fas fa-tasks me-2"></i>Active Harvest Jobs
                            <small class="text-muted ms-2" x-show="jobs.length > 0">
                                <i class="fas fa-circle text-success live-indicator"></i>
                                <span x-text="jobs.length"></span> running
                            </small>
                        </h3>
                        <div class="block-options" x-show="jobs.length > 0">
                            <button type="button" class="btn btn-sm btn-alt-secondary" @click="refreshJobs">
                                <i class="fas fa-sync-alt me-1"></i>Refresh
                            </button>
                        </div>
                    </div>
                    <div class="block-content">
                        <!-- No Active Jobs State -->
                        <div x-show="jobs.length === 0" class="text-center text-muted py-4">
                            <div class="push">
                                <div class="bg-body-light rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                                    <i class="fas fa-tasks fa-2x text-muted"></i>
                                </div>
                            </div>
                            <h4 class="h5 fw-medium text-muted mb-1">No Active Jobs</h4>
                            <p class="fs-sm text-muted mb-0">Start a harvest to see job progress here</p>
                        </div>
                        
                        <!-- Active Jobs List -->
                        <div x-show="jobs.length > 0" class="space-y-2">
                            <template x-for="job in jobs" :key="job.id">
                                <div class="bg-body-light rounded p-3 mb-3">
                                    <!-- Job Header -->
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div class="d-flex align-items-center">
                                            <!-- Job Type Icon -->
                                            <div class="flex-shrink-0 me-2">
                                                <div class="bg-primary-light p-2 rounded" x-show="job.job_type === 'category'">
                                                    <i class="fas fa-tags fa-sm text-primary"></i>
                                                </div>
                                                <div class="bg-success-light p-2 rounded" x-show="job.job_type === 'popular'">
                                                    <i class="fas fa-fire fa-sm text-success"></i>
                                                </div>
                                                <div class="bg-info-light p-2 rounded" x-show="job.job_type === 'recent'">
                                                    <i class="fas fa-clock fa-sm text-info"></i>
                                                </div>
                                                <div class="bg-warning-light p-2 rounded" x-show="job.job_type === 'files'">
                                                    <i class="fas fa-file-archive fa-sm text-warning"></i>
                                                </div>
                                            </div>
                                            
                                            <!-- Job Info -->
                                            <div>
                                                <div class="fw-bold fs-sm" x-text="job.job_name"></div>
                                                <div class="fs-xs text-muted d-flex align-items-center">
                                                    <span x-text="job.job_type.charAt(0).toUpperCase() + job.job_type.slice(1)"></span>
                                                    <span x-show="job.status === 'running'" class="ms-2 text-primary">
                                                        <i class="fas fa-circle text-success" style="font-size: 4px; animation: pulse 2s infinite;"></i>
                                                        Live
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Status Badge -->
                                        <span class="badge fs-xs" :class="getStatusBadgeClass(job.status)" x-text="job.status.toUpperCase()"></span>
                                    </div>
                                    
                                    <!-- Progress Section -->
                                    <div class="mb-2">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fs-xs fw-medium text-muted">Progress</span>
                                            <div class="fs-xs">
                                                <span class="badge bg-primary" x-text="getJobProgressDisplay(job)"></span>
                                                <span class="text-muted ms-1" x-text="`(${getJobProgress(job)}%)`"></span>
                                            </div>
                                        </div>
                                        
                                        <!-- Enhanced Progress Bar -->
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar progress-bar-striped" 
                                                 :class="job.status === 'running' ? 'progress-bar-animated bg-primary' : 'bg-success'" 
                                                 :style="`width: ${getJobProgress(job)}%`"
                                                 x-show="job.total_items > 0">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Stats Row -->
                                    <div class="row g-1 mb-2">
                                        <!-- Always show processing stats for category jobs -->
                                        <div class="col-3" x-show="job.job_type === 'category'">
                                            <div class="bg-white rounded p-1 text-center">
                                                <div class="fs-xs text-muted">Categories</div>
                                                <div class="fw-bold text-primary fs-sm" x-text="getCategoryProgress(job)"></div>
                                            </div>
                                        </div>
                                        <div class="col-3" x-show="job.job_type === 'category'">
                                            <div class="bg-white rounded p-1 text-center">
                                                <div class="fs-xs text-muted">API Responses</div>
                                                <div class="fw-bold text-success fs-sm" x-text="job.processed_items || 0"></div>
                                            </div>
                                        </div>
                                        <div class="col-3" x-show="job.items_created > 0 || job.job_type === 'category'">
                                            <div class="bg-white rounded p-1 text-center">
                                                <div class="fs-xs text-muted">Created</div>
                                                <div class="fw-bold text-info fs-sm" x-text="job.items_created || 0"></div>
                                            </div>
                                        </div>
                                        <div class="col-3" x-show="job.items_updated > 0 || job.job_type === 'category'">
                                            <div class="bg-white rounded p-1 text-center">
                                                <div class="fs-xs text-muted">Updated</div>
                                                <div class="fw-bold text-warning fs-sm" x-text="job.items_updated || 0"></div>
                                            </div>
                                        </div>
                                        
                                        <!-- For non-category jobs, show the original layout -->
                                        <div class="col-4" x-show="job.job_type !== 'category' && (job.items_created > 0 || job.items_updated > 0)">
                                            <div class="bg-white rounded p-1 text-center">
                                                <div class="fs-xs text-muted">Created</div>
                                                <div class="fw-bold text-success fs-sm" x-text="job.items_created || 0"></div>
                                            </div>
                                        </div>
                                        <div class="col-4" x-show="job.job_type !== 'category' && (job.items_created > 0 || job.items_updated > 0)">
                                            <div class="bg-white rounded p-1 text-center">
                                                <div class="fs-xs text-muted">Updated</div>
                                                <div class="fw-bold text-warning fs-sm" x-text="job.items_updated || 0"></div>
                                            </div>
                                        </div>
                                        <div class="col-4" x-show="job.job_type !== 'category' && job.api_calls_made > 0">
                                            <div class="bg-white rounded p-1 text-center">
                                                <div class="fs-xs text-muted">API Calls</div>
                                                <div class="fw-bold text-info fs-sm" x-text="job.api_calls_made || 0"></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Runtime Info -->
                                    <div class="pt-2 border-top d-flex justify-content-between align-items-center fs-xs text-muted">
                                        <div>
                                            <i class="fas fa-clock me-1"></i>
                                            <span x-text="formatDuration(job.started_at, job.completed_at)"></span>
                                        </div>
                                        <div x-show="job.failed_items > 0" class="text-warning">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            <span x-text="job.failed_items"></span> failed
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="col-12 mt-4">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fas fa-history me-2"></i>Job History
                    </h3>
                </div>
                <div class="block-content block-content-full">
                    <div class="table-responsive">
                        <table class="table table-striped table-vcenter">
                            <thead>
                                <tr>
                                    <th>Job Name</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Progress</th>
                                    <th>Started</th>
                                    <th>Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="job in jobHistory" :key="job.id">
                                    <tr>
                                        <td x-text="job.job_name"></td>
                                        <td>
                                            <span class="badge bg-secondary" x-text="job.job_type"></span>
                                        </td>
                                        <td>
                                            <span class="badge" 
                                                  :class="{
                                                      'bg-success': job.status === 'completed',
                                                      'bg-danger': job.status === 'failed',
                                                      'bg-warning': job.status === 'running',
                                                      'bg-secondary': job.status === 'pending'
                                                  }" 
                                                  x-text="job.status"></span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                                    <div class="progress-bar" 
                                                         :style="`width: ${getJobProgress(job)}%`"></div>
                                                </div>
                                                <span class="fs-sm text-muted" x-text="`${job.processed_items || 0}/${job.total_items || 0}`"></span>
                                            </div>
                                        </td>
                                        <td class="fs-sm" x-text="formatDate(job.started_at)"></td>
                                        <td class="fs-sm" x-text="formatDuration(job.started_at, job.completed_at)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<style>
/* Game Card Hover Effects */
.block.cursor-pointer:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}

.bg-primary-dark-op {
    background-color: rgba(46, 125, 50, 0.8) !important;
}

/* Smooth transitions for hover effects */
.block.cursor-pointer {
    transition: all 0.3s ease;
}

/* Icon styling for better visibility */
.img-avatar-thumb {
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

/* Stat number highlighting */
.fw-semibold.text-primary,
.fw-semibold.text-info,
.fw-semibold.text-success {
    font-size: 1.1em;
}
</style>
<script>
function modManagerDashboard() {
    return {
        // Views and navigation
        currentView: 'games', // 'games' or 'harvest'
        selectedGame: null,
        
        // Data
        games: [],
        stats: {
            active_jobs: 0,
            completed_jobs_today: 0
        },
        liveStats: {
            mods_count: 0,
            categories_count: 0,
            files_count: 0,
            active_jobs: 0
        },
        jobs: [],
        jobHistory: [],
        
        // Queue Worker - Separate button state from display stats to prevent flashing
        queueWorker: {
            running: false,
            pid: null,
            uptime: null,
            cpu_usage: null,
            memory_usage: null
        },
        
        // Separate button state that only changes when worker actually starts/stops
        workerButtonState: {
            running: false,
            pid: null
        },
        
        // Loading states
        isLoading: false,
        isLoadingGames: true,
        isLoadingQueueWorker: false,
        isLoadingLiveStats: false,
        lastQueueWorkerUpdate: null,
        lastLiveStatsUpdate: null,
        connectionError: false,
        
        // SSE
        eventSource: null,

        init() {
            // Initialize with server-side data to prevent initial state mismatch
            @if(isset($queueWorkerStatus) && $queueWorkerStatus)
            const initialWorkerState = @json($queueWorkerStatus);
            
            // Set both button state and display data from server
            this.workerButtonState.running = initialWorkerState.running || false;
            this.workerButtonState.pid = initialWorkerState.pid || null;
            
            this.queueWorker.running = initialWorkerState.running || false;
            this.queueWorker.pid = initialWorkerState.pid || null;
            this.queueWorker.uptime = initialWorkerState.uptime || null;
            this.queueWorker.cpu_usage = initialWorkerState.cpu_usage || null;
            this.queueWorker.memory_usage = initialWorkerState.memory_usage || null;
            
            console.log('🚀 Initialized with server queue worker state:', initialWorkerState);
            @endif
            
            this.refreshGames();
            this.refreshQueueWorkerStatus();
            this.refreshStats();
            this.refreshJobs();
            this.startProgressStream();
            
            // Refresh data periodically
            setInterval(() => {
                this.refreshStats();
                this.refreshJobs();
            }, 30000); // General data every 30 seconds
            
            // Queue worker status more frequently for real-time monitoring
            setInterval(() => {
                this.refreshQueueWorkerStatus();
            }, 2000); // Queue worker status every 2 seconds for real-time updates
            
            // Live stats for harvest view - update every 2 seconds when game is selected
            setInterval(() => {
                if (this.selectedGame && this.currentView === 'harvest') {
                    this.refreshLiveStats();
                }
            }, 2000); // Live stats every 2 seconds when in harvest view
        },

        // Navigation
        selectGame(game) {
            this.selectedGame = game;
            this.currentView = 'harvest';
            console.log('Selected game:', game);
            
            // Immediately refresh live stats for the selected game
            this.refreshLiveStats();
        },

        backToGameSelection() {
            this.currentView = 'games';
            this.selectedGame = null;
        },

        // Data fetching
        async refreshGames() {
            this.isLoadingGames = true;
            try {
                const response = await fetch('/admin/mod-manager/api/games');
                if (response.ok) {
                    const data = await response.json();
                    this.games = data.games || [];
                    console.log('Games loaded:', this.games.length);
                }
            } catch (error) {
                console.error('Error fetching games:', error);
            } finally {
                this.isLoadingGames = false;
            }
        },

        async refreshStats() {
            try {
                const response = await fetch('/admin/mod-manager/api/stats');
                if (response.ok) {
                    const data = await response.json();
                    this.stats = data.stats || data;
                }
            } catch (error) {
                console.error('Error fetching stats:', error);
            }
        },

        async refreshLiveStats() {
            if (this.isLoadingLiveStats || !this.selectedGame) return;
            
            this.isLoadingLiveStats = true;
            try {
                const response = await fetch(`/admin/mod-manager/api/live-stats?game_id=${this.selectedGame.id}`);
                if (response.ok) {
                    const data = await response.json();
                    this.liveStats = data.stats || {};
                    this.lastLiveStatsUpdate = new Date().toLocaleTimeString();
                    
                    // Debug log to see what we're getting
                    console.log('📊 Live stats updated:', this.liveStats);
                    console.log('📊 Stats card will show:', this.liveStats.mods_count, 'mods');
                } else {
                    console.warn('Failed to fetch live stats');
                }
            } catch (error) {
                console.error('Error fetching live stats:', error);
            } finally {
                this.isLoadingLiveStats = false;
            }
        },

        async refreshJobs() {
            try {
                const response = await fetch('/admin/mod-manager/api/jobs');
                if (response.ok) {
                    const data = await response.json();
                    this.jobs = data.active || [];
                    this.jobHistory = data.history || [];
                }
            } catch (error) {
                console.error('Error fetching jobs:', error);
            }
        },

        async refreshQueueWorkerStatus() {
            // Don't update if already loading to prevent overlapping requests
            if (this.isLoadingQueueWorker) return;
            
            this.isLoadingQueueWorker = true;
            try {
                const response = await fetch('/admin/mod-manager/api/queue-worker/status');
                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.queue_worker) {
                        // Format the data for better display
                        const newStatus = {
                            ...data.queue_worker,
                            cpu_usage: data.queue_worker.cpu_usage ? parseFloat(data.queue_worker.cpu_usage).toFixed(1) : '0.0',
                            memory_usage: data.queue_worker.memory_usage ? parseFloat(data.queue_worker.memory_usage).toFixed(1) : '0.0'
                        };
                        
                        // Handle critical state changes separately from stats to prevent button flashing
                        let criticalStateChanged = false;
                        let statsChanged = false;
                        
                        // Critical states that affect buttons (update button state only when actually different)
                        if (this.workerButtonState.running !== newStatus.running) {
                            this.workerButtonState.running = newStatus.running;
                            this.workerButtonState.pid = newStatus.pid;
                            criticalStateChanged = true;
                            console.log('🔄 Worker button state changed - Running:', newStatus.running, 'PID:', newStatus.pid);
                        }
                        
                        // Always update display data (this doesn't affect buttons)
                        if (this.queueWorker.running !== newStatus.running) {
                            this.queueWorker.running = newStatus.running;
                            statsChanged = true;
                        }
                        if (this.queueWorker.pid !== newStatus.pid) {
                            this.queueWorker.pid = newStatus.pid;
                            statsChanged = true;
                        }
                        if (this.queueWorker.uptime !== newStatus.uptime) {
                            this.queueWorker.uptime = newStatus.uptime;
                            statsChanged = true;
                        }
                        if (this.queueWorker.cpu_usage !== newStatus.cpu_usage) {
                            this.queueWorker.cpu_usage = newStatus.cpu_usage;
                            statsChanged = true;
                        }
                        if (this.queueWorker.memory_usage !== newStatus.memory_usage) {
                            this.queueWorker.memory_usage = newStatus.memory_usage;
                            statsChanged = true;
                        }
                        
                        if (criticalStateChanged || statsChanged) {
                            this.lastQueueWorkerUpdate = new Date().toLocaleTimeString();
                            this.connectionError = false;
                            
                            if (criticalStateChanged) {
                                console.log('🔄 Worker critical state updated - buttons will change');
                            } else if (statsChanged) {
                                console.log('📊 Worker stats updated (CPU: ' + this.queueWorker.cpu_usage + '%, Memory: ' + this.queueWorker.memory_usage + '%) - buttons unchanged');
                            }
                        }
                    } else {
                        // Fallback if response structure is different
                        // Update both button state and display data
                        const fallbackData = data.queue_worker || {
                            running: false,
                            pid: null,
                            uptime: null,
                            cpu_usage: '0.0',
                            memory_usage: '0.0'
                        };
                        
                        // Update button state
                        this.workerButtonState.running = fallbackData.running;
                        this.workerButtonState.pid = fallbackData.pid;
                        
                        // Update display data
                        this.queueWorker.running = fallbackData.running;
                        this.queueWorker.pid = fallbackData.pid;
                        this.queueWorker.uptime = fallbackData.uptime;
                        this.queueWorker.cpu_usage = fallbackData.cpu_usage;
                        this.queueWorker.memory_usage = fallbackData.memory_usage;
                    }
                } else {
                    console.error('Failed to fetch queue worker status:', response.status);
                    this.connectionError = true;
                }
            } catch (error) {
                console.error('Error fetching queue worker status:', error);
                this.connectionError = true;
                // Don't reset the status on error, keep showing last known status
            } finally {
                // Add a small delay to show the updating effect
                setTimeout(() => {
                    this.isLoadingQueueWorker = false;
                }, 200);
            }
        },

        async refreshAll() {
            await Promise.all([
                this.refreshStats(),
                this.refreshJobs(),
                this.refreshQueueWorkerStatus()
            ]);
        },

        // Queue Worker Management
        async startQueueWorker() {
            this.isLoadingQueueWorker = true;
            try {
                const response = await fetch('/admin/mod-manager/api/queue-worker/start', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="_token"]').getAttribute('content')
                    }
                });
                
                if (response.ok) {
                    const result = await response.json();
                    this.showAlert('success', result.message);
                    await this.refreshQueueWorkerStatus();
                } else {
                    const error = await response.json();
                    throw new Error(error.message || 'Failed to start queue worker');
                }
            } catch (error) {
                console.error('Error starting queue worker:', error);
                this.showAlert('danger', error.message || 'Failed to start queue worker');
            } finally {
                this.isLoadingQueueWorker = false;
            }
        },

        async stopQueueWorker() {
            this.isLoadingQueueWorker = true;
            try {
                const response = await fetch('/admin/mod-manager/api/queue-worker/stop', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="_token"]').getAttribute('content')
                    }
                });
                
                if (response.ok) {
                    const result = await response.json();
                    this.showAlert('success', result.message);
                    await this.refreshQueueWorkerStatus();
                } else {
                    const error = await response.json();
                    throw new Error(error.message || 'Failed to stop queue worker');
                }
            } catch (error) {
                console.error('Error stopping queue worker:', error);
                this.showAlert('danger', error.message || 'Failed to stop queue worker');
            } finally {
                this.isLoadingQueueWorker = false;
            }
        },

        // Harvest Management
        async startHarvest(type) {
            if (!this.queueWorker.running) {
                this.showAlert('warning', 'Queue worker must be running to start harvest jobs');
                return;
            }

            if (!this.selectedGame) {
                this.showAlert('warning', 'Please select a game first');
                return;
            }

            this.isLoading = true;
            try {
                const response = await fetch(`/admin/mod-manager/api/harvest/${type}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="_token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        game_id: this.selectedGame.id
                    })
                });
                
                if (response.ok) {
                    const result = await response.json();
                    this.showAlert('success', `Started ${type} harvest: ${result.message}`);
                    this.refreshJobs();
                    
                    // Ensure progress stream is running
                    if (!this.eventSource || this.eventSource.readyState === EventSource.CLOSED) {
                        this.startProgressStream();
                    }
                } else {
                    const error = await response.json();
                    throw new Error(error.message || 'Failed to start harvest');
                }
            } catch (error) {
                console.error('Error starting harvest:', error);
                this.showAlert('danger', error.message || 'Failed to start harvest job');
            } finally {
                this.isLoading = false;
            }
        },

        async stopAllJobs() {
            this.isLoading = true;
            try {
                const response = await fetch('/admin/mod-manager/api/harvest/stop-all', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="_token"]').getAttribute('content')
                    }
                });
                
                if (response.ok) {
                    const result = await response.json();
                    this.showAlert('success', result.message);
                    this.refreshJobs();
                } else {
                    const error = await response.json();
                    throw new Error(error.message || 'Failed to stop jobs');
                }
            } catch (error) {
                console.error('Error stopping jobs:', error);
                this.showAlert('danger', error.message || 'Failed to stop jobs');
            } finally {
                this.isLoading = false;
            }
        },

        // Progress Stream
        startProgressStream() {
            if (this.eventSource) {
                this.eventSource.close();
            }

            this.eventSource = new EventSource('/admin/mod-manager/api/progress/stream');
            
            this.eventSource.onopen = () => {
                console.log('SSE connection opened');
            };
            
            this.eventSource.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    this.updateProgress(data);
                } catch (error) {
                    console.error('Error parsing SSE data:', error);
                }
            };

            this.eventSource.onerror = (error) => {
                console.error('SSE Error:', error);
                setTimeout(() => {
                    if (this.jobs.length > 0) {
                        this.startProgressStream();
                    }
                }, 5000);
            };
        },

        updateProgress(data) {
            // Update jobs
            if (data.jobs !== undefined) {
                this.jobs = data.jobs;
            }
            
            // Update stats
            if (data.stats) {
                this.stats = { ...this.stats, ...data.stats };
            }
            
            // Refresh job history when no active jobs
            if (data.active_jobs === 0 && this.jobs.length === 0) {
                this.refreshJobs();
            }
        },

        // Utility functions
        getJobProgress(job) {
            if (!job.total_items || job.total_items === 0) {
                return 0;
            }
            
            // For category jobs, try different approaches based on available data
            if (job.job_type === 'category') {
                // If we have page data, use that
                if (job.total_pages > 0 && job.current_page > 0) {
                    return Math.min(100, Math.round((job.current_page / job.total_pages) * 100));
                }
                
                // If we don't have page data, estimate progress based on categories
                // Assuming average of ~100 mods per category for estimation
                const avgModsPerCategory = 100;
                const estimatedCategoriesProcessed = Math.min(job.processed_items / avgModsPerCategory, job.total_items);
                const progress = Math.round((estimatedCategoriesProcessed / job.total_items) * 100);
                return Math.min(100, Math.max(1, progress)); // At least 1% if processing
            }
            
            // For other job types, use standard calculation but cap at 100%
            const progress = Math.round((job.processed_items / job.total_items) * 100);
            return Math.min(100, progress);
        },

        getJobProgressDisplay(job) {
            // For category jobs, show different metrics based on what data we have
            if (job.job_type === 'category') {
                if (job.total_pages > 0 && job.current_page > 0) {
                    // If we have page data, show page progress
                    return `Page ${job.current_page} / ${job.total_pages}`;
                } else if (job.total_items > 0) {
                    // If we have category count, show category progress
                    const categoriesProcessed = Math.min(job.processed_items / 50, job.total_items); // Estimate based on ~50 mods per category
                    const categoriesProcessedRounded = Math.floor(categoriesProcessed);
                    return `${categoriesProcessedRounded} / ${job.total_items} categories`;
                } else {
                    // Fallback to mods processed
                    return `${job.processed_items} mods processed`;
                }
            }
            
            // For other job types, show normal item count
            return `${job.processed_items || 0} / ${job.total_items || 0}`;
        },

        getCategoryProgress(job) {
            if (job.job_type === 'category' && job.total_items > 0) {
                // If we have page data, use that
                if (job.current_page > 0 && job.total_pages > 0) {
                    return `${job.current_page}/${job.total_pages}`;
                }
                
                // Otherwise estimate based on mods processed (rough estimate: ~100 mods per category)
                const avgModsPerCategory = 100;
                const estimatedCategoriesProcessed = Math.min(Math.floor(job.processed_items / avgModsPerCategory), job.total_items);
                return `~${estimatedCategoriesProcessed}/${job.total_items}`;
            }
            
            return `${job.current_page || 0}/${job.total_pages || job.total_items}`;
        },

        getStatusBadgeClass(status) {
            const classes = {
                'pending': 'bg-info',
                'running': 'bg-warning',
                'completed': 'bg-success',
                'failed': 'bg-danger',
                'cancelled': 'bg-secondary'
            };
            return classes[status] || 'bg-secondary';
        },

        getGameBackgroundStyle(game) {
            // Use custom background for specific games, fallback for others
            let backgroundUrl = '/assets/mod-manager/minecraft_background.png'; // Default fallback
            
            if (game.name && game.name.toLowerCase().includes('minecraft')) {
                backgroundUrl = '/assets/mod-manager/minecraft_background.png';
            }
            
            return `background-image: url('${backgroundUrl}'); background-size: cover; background-position: center;`;
        },

        getGameIconUrl(game) {
            // Safety check for null game
            if (!game) {
                return '/assets/mod-manager/default-game.png';
            }
            
            // Use custom icons for specific games, fallback for others
            if (game.name && game.name.toLowerCase().includes('minecraft')) {
                return '/assets/mod-manager/minecraft-icon-1.png';
            }
            
            // Fallback to CurseForge logo if available
            if (game.logo_url) {
                return game.logo_url;
            }
            
            // Ultimate fallback to a default game icon
            return '/assets/mod-manager/minecraft-icon-1.png';
        },

        formatDate(dateString) {
            if (!dateString) return '-';
            return new Date(dateString).toLocaleString();
        },

        formatDuration(start, end) {
            if (!start) return '-';
            const startTime = new Date(start);
            const endTime = end ? new Date(end) : new Date();
            const diff = Math.floor((endTime - startTime) / 1000);
            
            if (diff < 60) return `${diff}s`;
            if (diff < 3600) return `${Math.floor(diff / 60)}m`;
            return `${Math.floor(diff / 3600)}h ${Math.floor((diff % 3600) / 60)}m`;
        },

        showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 5000);
        },

        // Cleanup
        destroy() {
            if (this.eventSource) {
                this.eventSource.close();
            }
        }
    }
}
</script>
@endsection