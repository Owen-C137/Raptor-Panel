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

@section('content')
<script>
    window.modManagerGames = @json($games);
</script>
<div class="row" x-data="modManagerDashboard()">
    
    <!-- Games Selection View -->
    <div x-show="currentView === 'games'" class="col-12">
        <div class="row">
            @foreach($games as $game)
            @php
                $coverImage = $game->cover_url ?: asset('assets/mod-manager/minecraft_background.png');
                $logoImage = $game->logo_url ?: asset('assets/mod-manager/minecraft-icon-1.png');
            @endphp
            <div class="col-xl-4">
                <div class="block block-rounded h-100 mb-0 overflow-hidden game-card" role="button" tabindex="0"
                     @click="selectGameById({{ $game->id }})" @keydown.enter.prevent="selectGameById({{ $game->id }})"
                     style="cursor: pointer;">
                    <div class="block-content bg-image p-0" style="background-image: url('{{ asset('assets/mod-manager/minecraft_background.png') }}');">
                        <div class="block-content block-content-full d-flex align-items-center justify-content-between bg-primary-dark-op">
                            <div class="me-3">
                                <p class="fw-bold text-white mb-0">{{ $game->name }}</p>
                                <p class="fs-sm fw-medium text-white-75 mb-0">
                                    <span x-text="(liveStats.game_{{ $game->id }}_mods ?? {{ $game->mods_count ?? 0 }}) + ' Mods collected'"></span>
                                </p>
                            </div>
                            <img class="img-avatar img-avatar48" src="{{ asset('assets/mod-manager/minecraft-icon-1.png') }}" alt="{{ $game->name }}">
                        </div>
                    </div>
                    <div class="block-content">
                        <div class="list-group push">
                            <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <h5 class="fs-base mb-0">Mods in Database</h5>
                                <small class="fw-semibold text-primary" x-text="liveStats.game_{{ $game->id }}_mods ?? {{ $game->mods_count ?? 0 }}">{{ $game->mods_count ?? 0 }}</small>
                            </div>
                            <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <h5 class="fs-base mb-0">Categories</h5>
                                <small class="fw-semibold text-info" x-text="liveStats.game_{{ $game->id }}_categories ?? {{ $game->categories_count ?? 0 }}">{{ $game->categories_count ?? 0 }}</small>
                            </div>
                            <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <h5 class="fs-base mb-0">Files Indexed</h5>
                                <small class="fw-semibold text-success" x-text="liveStats.game_{{ $game->id }}_files ?? 0">0</small>
                            </div>
                        </div>
                    </div>
                    <div class="block-content border-top">
                        <div class="d-flex justify-content-between push">
                            <span class="btn btn-sm btn-alt-secondary pe-none">
                                <i class="fa fa-fw fa-signal opacity-50 me-1"></i>
                                <span x-text="(liveStats.game_{{ $game->id }}_active_sessions ?? 0) + ' Active'">0 Active</span>
                            </span>
                            <span class="text-primary fw-semibold">Click to manage</span>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Data Collection Management View -->
    <div x-show="currentView === 'harvest'" class="col-12">
        
        <!-- Back Button -->
        <div class="mb-4">
            <button type="button" class="btn btn-secondary" @click="backToGames()">
                <i class="fas fa-arrow-left me-2"></i>Back to Games
            </button>
        </div>

        <!-- Direct Data Collection Control -->
        <div class="col-12 mb-4">
            <div class="block block-rounded harvest-progress" 
                 :class="{'border-success': isHarvesting, 'border-primary': !isHarvesting, 'running': isHarvesting}">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fas fa-download me-2"></i>Direct Data Collection
                        <small class="text-muted ms-2" x-show="isHarvesting">
                            <i class="fas fa-circle text-success live-indicator"></i>
                            Processing - Real-time updates
                        </small>
                    </h3>
                </div>
                <div class="block-content">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p class="text-muted mb-3">
                                Our bulletproof system directly processes CurseForge data without complex job queues. 
                                Get complete mod and file information in seconds, not hours.
                            </p>
                            
                            <div class="btn-group mb-2" role="group" x-show="!isHarvesting">
                                <button type="button" 
                                        class="btn btn-primary" 
                                        @click="startDirectHarvest('complete')"
                                        :disabled="isHarvesting">
                                    <i class="fas fa-rocket me-1"></i>Start Complete Collection
                                </button>
                                <button type="button" 
                                        class="btn btn-success" 
                                        @click="startDirectHarvest('popular')"
                                        :disabled="isHarvesting">
                                    <i class="fas fa-fire me-1"></i>Popular Mods Only
                                </button>
                                <button type="button" 
                                        class="btn btn-info" 
                                        @click="startDirectHarvest('recent')"
                                        :disabled="isHarvesting">
                                    <i class="fas fa-clock me-1"></i>Recent Updates Only
                                </button>
                            </div>
                            
                            <!-- Category-Based Harvesting (bypasses 10K limit) -->
                            <div class="alert alert-info mb-3" x-show="!isHarvesting">
                                <i class="fas fa-rocket me-2"></i>
                                <strong>🚀 Category-Based Collection:</strong> Bypasses the 10,000 item limit!<br>
                                <small class="text-muted">
                                    • Each category gets 10,000 items (Minecraft has ~142 categories)<br>
                                    • Can access all ~76,000 Minecraft mods<br>
                                    • Automatically fetches categories if needed<br>
                                    • Includes complete file collection for every mod
                                </small>
                            </div>
                            
                            <div class="btn-group" role="group" x-show="!isHarvesting">
                                <button type="button" 
                                        class="btn btn-outline-primary" 
                                        @click="startCategoryHarvest()"
                                        :disabled="isHarvesting"
                                        title="Harvest by categories to bypass the 10,000 item API limit">
                                    <i class="fas fa-layer-group me-1"></i>Category-Based Collection
                                </button>
                                <button type="button" 
                                        class="btn btn-outline-secondary" 
                                        @click="showCategoryConfig = !showCategoryConfig"
                                        :disabled="isHarvesting"
                                        title="Configure which categories to harvest">
                                    <i class="fas fa-cog me-1"></i>Configure
                                </button>
                            </div>
                            
                            <div class="btn-group" role="group" x-show="isHarvesting">
                                <button type="button" 
                                        class="btn btn-warning" 
                                        @click="stopDirectHarvest()"
                                        title="Finishes collecting files for mods already processed">
                                    <i class="fas fa-pause me-1"></i>Stop & Fetch Files
                                </button>
                                <button type="button" 
                                        class="btn btn-danger" 
                                        @click="forceStopDirectHarvest()"
                                        title="Immediately stops without processing files">
                                    <i class="fas fa-stop-circle me-1"></i>Force Stop
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <!-- Progress Stats -->
                            <div class="row text-center" x-show="harvestStats.total_mods > 0">
                                <div class="col-4">
                                    <div class="fs-sm text-uppercase text-muted mb-1">Mods</div>
                                    <div class="h5 text-primary mb-0">
                                        <span x-text="harvestStats.processed_mods"></span>/<span x-text="harvestStats.total_mods"></span>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="fs-sm text-uppercase text-muted mb-1">Files</div>
                                    <div class="h5 text-success mb-0" x-text="harvestStats.total_files">0</div>
                                </div>
                                <div class="col-4">
                                    <div class="fs-sm text-uppercase text-muted mb-1">API Calls</div>
                                    <div class="h5 text-info mb-0" x-text="harvestStats.api_calls">0</div>
                                </div>
                            </div>
                            
                            <!-- Progress Bar -->
                            <div x-show="harvestStats.total_mods > 0" class="mt-3">
                                <div class="progress">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                                         :style="'width: ' + harvestStats.progress_percentage + '%'">
                                        <span x-text="harvestStats.progress_percentage + '%'"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Live Progress Display -->
        <div x-show="isHarvesting || progressLog.length > 0" class="col-12 mb-4">
            <div class="block block-rounded bg-dark">
                <div class="block-content bg-dark p-0">
                    <div class="terminal-console" id="progress-output" x-ref="console">
                        <!-- Terminal Header -->
                        <div class="terminal-header">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="terminal-path">~/mod-manager/harvest</span>
                                    <span class="badge bg-success" x-show="isHarvesting">
                                        <i class="fas fa-circle fa-beat text-white"></i> LIVE
                                    </span>
                                    <span class="text-muted" x-text="progressLog.length + ' lines'"></span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" 
                                            class="btn btn-xs"
                                            :class="autoScroll ? 'btn-success' : 'btn-outline-secondary'"
                                            @click="autoScroll = !autoScroll; if(autoScroll) scrollToBottom();"
                                            title="Toggle auto-scroll">
                                        <i class="fas fa-arrow-down fa-xs" :class="{'fa-beat': autoScroll}"></i>
                                        <span class="d-none d-sm-inline ms-1" x-text="autoScroll ? 'Auto' : 'Manual'"></span>
                                    </button>
                                    <button type="button" class="btn btn-xs btn-outline-secondary" @click="clearProgressLog()" title="Clear console">
                                        <i class="fas fa-trash fa-xs"></i>
                                        <span class="d-none d-sm-inline ms-1">Clear</span>
                                    </button>
                                    <button type="button" class="btn btn-xs btn-outline-secondary" @click="copyLogs()" title="Copy logs">
                                        <i class="fas fa-copy fa-xs"></i>
                                        <span class="d-none d-sm-inline ms-1">Copy</span>
                                    </button>
                                    <span class="terminal-time" x-text="new Date().toLocaleTimeString()"></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Console Content -->
                        <div class="terminal-body">
                            <template x-if="progressLog.length === 0">
                                <div class="terminal-line">
                                    <span class="terminal-prompt">$</span>
                                    <span class="terminal-text text-muted">Waiting for data collection to start...</span>
                                    <span class="terminal-cursor"></span>
                                </div>
                            </template>
                            
                            <template x-for="(entry, idx) in progressLog" :key="idx">
                                <div class="terminal-line" :class="'log-' + entry.type">
                                    <span class="terminal-timestamp" x-text="entry.time"></span>
                                    <span class="terminal-icon" x-text="entry.icon"></span>
                                    <span class="terminal-message" x-text="entry.message"></span>
                                </div>
                            </template>
                            
                            <!-- Cursor line when harvesting -->
                            <div x-show="isHarvesting" class="terminal-line">
                                <span class="terminal-prompt">$</span>
                                <span class="terminal-text">Processing...</span>
                                <span class="terminal-cursor terminal-cursor-blink"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Collection History -->
        <div class="col-12 mb-4">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fas fa-history me-2"></i>Collection History
                    </h3>
                    <div class="block-options">
                        <button type="button" class="btn btn-sm btn-primary" @click="refreshHistory()">
                            <i class="fas fa-sync me-1"></i>Refresh
                        </button>
                    </div>
                </div>
                <div class="block-content">
                    <div class="table-responsive">
                        <table class="table table-vcenter">
                            <thead>
                                <tr>
                                    <th>Session Name</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Progress</th>
                                    <th>Started</th>
                                    <th>Duration</th>
                                    <th>Results</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="session in harvestHistory" :key="session.id">
                                    <tr>
                                        <td>
                                            <span x-text="session.session_name"></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info" x-text="session.harvest_type"></span>
                                        </td>
                                        <td>
                                            <span class="badge" 
                                                  :class="'bg-' + session.status_color" 
                                                  x-text="session.status"></span>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar" 
                                                     :class="'bg-' + session.status_color"
                                                     :style="'width: ' + session.progress_percentage + '%'"></div>
                                            </div>
                                            <small class="text-muted" x-text="session.processed_mods + '/' + session.total_mods + ' mods'"></small>
                                        </td>
                                        <td>
                                            <small x-text="session.started_at_human"></small>
                                        </td>
                                        <td>
                                            <small x-text="session.formatted_duration"></small>
                                        </td>
                                        <td>
                                            <small class="text-muted d-block">
                                                <span x-text="session.new_mods"></span> new, 
                                                <span x-text="session.updated_mods"></span> updated mods<br>
                                                <span x-text="session.new_files"></span> new, 
                                                <span x-text="session.updated_files"></span> updated files
                                                <template x-if="session.skipped_items > 0">
                                                    <span class="d-block text-warning">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                                        <span x-text="session.skipped_items"></span> skipped items
                                                    </span>
                                                </template>
                                                <template x-if="session.error_count > 0">
                                                    <span class="d-block text-danger">
                                                        <i class="fas fa-times-circle me-1"></i>
                                                        <span x-text="session.error_count"></span> errors
                                                    </span>
                                                </template>
                                            </small>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="harvestHistory.length === 0">
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="fas fa-info-circle me-2"></i>No collection history yet. Start your first data collection above.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function modManagerDashboard() {
    return {
        // State
        currentView: 'games',
        selectedGame: null,
        isHarvesting: false,
        progressLog: [],
        autoScroll: true,
        harvestStats: {
            total_mods: 0,
            processed_mods: 0,
            total_files: 0,
            api_calls: 0,
            progress_percentage: 0
        },
        harvestHistory: [],
        liveStats: {},
        currentSession: null,
        showCategoryConfig: false,
        
        init() {
            this.refreshHistory();
            this.startLiveStatsPolling();
        },
        
        // Game Selection
        selectGame(game) {
            console.log('selectGame called with:', game);
            this.selectedGame = game;
            this.currentView = 'harvest';
            console.log('currentView set to:', this.currentView);
            console.log('selectedGame set to:', this.selectedGame);
            this.refreshHistory();
        },
        
        selectGameById(gameId) {
            const game = window.modManagerGames.find(g => g.id === gameId);
            if (game) {
                this.selectGame(game);
            } else {
                console.error('Game not found with ID:', gameId);
            }
        },
        
        backToGames() {
            this.currentView = 'games';
            this.selectedGame = null;
        },
        
        // Direct Harvest Control
        async startDirectHarvest(type = 'complete') {
            // Prevent double starts
            if (this.isHarvesting) {
                console.warn('[ModManager] Harvest already in progress – ignoring new start request.');
                return;
            }

            // Ensure a game is selected
            if (!this.selectedGame || !this.selectedGame.id) {
                const msg = '[ModManager] No game selected. Please click a game card first.';
                console.error(msg, this.selectedGame);
                this.addLog('❌ Cannot start: No game selected. Go back and choose a game.');
                return;
            }
            
            this.isHarvesting = true;
            this.progressLog = [];
            this.harvestStats = {
                total_mods: 0,
                processed_mods: 0,
                total_files: 0,
                api_calls: 0,
                progress_percentage: 0
            };

            const sessionName = `${type.charAt(0).toUpperCase() + type.slice(1)} Collection - ${new Date().toLocaleString()}`;

            try {
                const url = `/admin/mod-manager/harvest-complete?type=${type}&game_id=${this.selectedGame.id}&session_name=${encodeURIComponent(sessionName)}`;
                console.info('[ModManager] Starting harvest fetch:', url);

                const response = await fetch(url, { headers: { 'Accept': 'text/plain' } });

                // Show waiting indicator early
                this.addLog('⏳ Connected. Waiting for first data chunk...');

                    if (!response.ok) {
                        let bodyText = '';
                        try { bodyText = await response.text(); } catch (_) {}
                        const errMsg = `[ModManager] HTTP ${response.status} while starting harvest. Body: ${bodyText || '(empty)'}`;
                        console.error(errMsg);
                        this.addLog(`❌ Failed to start collection: HTTP ${response.status}`);
                        if (bodyText) this.addLog(bodyText.substring(0, 500));
                        this.isHarvesting = false;
                        return;
                    }

                    if (!response.body) {
                        console.error('[ModManager] No readable stream on response.');
                        this.addLog('❌ Browser does not support streaming response.');
                        this.isHarvesting = false;
                        return;
                    }

                    const reader = response.body.getReader();
                    const decoder = new TextDecoder();
                    let totalBytes = 0;

                    while (true) {
                        const { done, value } = await reader.read();
                        if (done) break;

                        totalBytes += value?.length || 0;
                        const chunk = decoder.decode(value, { stream: true });
                        const lines = chunk.split('\n');

                        for (const raw of lines) {
                            const line = raw.trim();
                            if (!line) continue;
                            this.addLog(line);
                            this.updateHarvestStats(line);
                        }

                        // Auto-scroll to bottom after each batch
                        this.$nextTick(() => {
                            const output = document.getElementById('progress-output');
                            if (output) output.scrollTop = output.scrollHeight;
                        });
                    }

                    console.info('[ModManager] Harvest stream ended. Total bytes received:', totalBytes);
                    this.addLog('✅ Stream closed by server.');

            } catch (error) {
                console.error('[ModManager] Data collection failed (outer catch):', error);
                this.addLog(`❌ Collection failed: ${error.message}`);
            } finally {
                this.isHarvesting = false;
                this.refreshHistory();
                this.refreshLiveStats();
            }
        },
        
        async stopDirectHarvest() {
            if (!this.isHarvesting) return;
            
            try {
                const token = document.querySelector('meta[name="_token"], meta[name="csrf-token"]').getAttribute('content');
                const res = await fetch('/admin/mod-manager/harvest-stop', { 
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ reason: 'user_request', session_id: this.currentSession })
                });
                if (res.status === 419) {
                    this.addLog('⚠️ Stop failed (419 CSRF). Page reload may be needed.');
                }
                const data = await res.json().catch(()=>({}));
                if (data.error) {
                    this.addLog('❌ Stop error: ' + data.error);
                } else {
                    this.addLog('🛑 Stop signal sent. Waiting for graceful shutdown...');
                }
            } catch (error) {
                console.error('Failed to stop collection:', error);
                this.addLog('❌ Stop request error: ' + error.message);
            } finally {
                // Reset state after a delay to allow processing to complete
                setTimeout(() => {
                    this.isHarvesting = false;
                    this.currentSession = null;
                }, 5000);
            }
        },
        
        async forceStopDirectHarvest() {
            if (!this.isHarvesting) return;
            
            try {
                const token = document.querySelector('meta[name="_token"], meta[name="csrf-token"]').getAttribute('content');
                const res = await fetch('/admin/mod-manager/harvest-stop', { 
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ 
                        reason: 'force_stop', 
                        session_id: this.currentSession,
                        skip_files: true 
                    })
                });
                if (res.status === 419) {
                    this.addLog('⚠️ Force stop failed (419 CSRF). Page reload may be needed.');
                }
                const data = await res.json().catch(()=>({}));
                if (data.error) {
                    this.addLog('❌ Force stop error: ' + data.error);
                } else {
                    this.addLog('🚨 Force stop signal sent. Terminating immediately...');
                }
            } catch (error) {
                console.error('Failed to force stop collection:', error);
                this.addLog('❌ Force stop request error: ' + error.message);
            } finally {
                // For force stop, reset immediately since no processing happens
                setTimeout(() => {
                    this.isHarvesting = false;
                    this.currentSession = null;
                }, 2000);
            }
        },
        
        // Progress Parsing
        updateHarvestStats(line) {
            // Parse progress updates from the stream
            const modsMatch = line.match(/📥 Processed (\d+)\/(\d+) .* mods \((\d+) files\)/);
            if (modsMatch) {
                this.harvestStats.processed_mods = parseInt(modsMatch[1]);
                this.harvestStats.total_mods = parseInt(modsMatch[2]);
                this.harvestStats.total_files = parseInt(modsMatch[3]);
                this.harvestStats.progress_percentage = Math.round((this.harvestStats.processed_mods / this.harvestStats.total_mods) * 100);
            }
            
            // Parse final stats
            const finalMatch = line.match(/Final Stats:.*Total Mods: (\d+).*Total Files: (\d+)/);
            if (finalMatch) {
                this.harvestStats.total_mods = parseInt(finalMatch[1]);
                this.harvestStats.total_files = parseInt(finalMatch[2]);
                this.harvestStats.progress_percentage = 100;
            }
        },
        
        // History Management
        async refreshHistory() {
            if (!this.selectedGame) return;
            
            try {
                const response = await fetch(`/admin/mod-manager/harvest-history?game_id=${this.selectedGame.id}`);
                this.harvestHistory = await response.json();
            } catch (error) {
                console.error('Failed to load history:', error);
            }
        },
        
        clearProgressLog() { this.progressLog = []; },
        scrollToBottom() {
            const consoleEl = this.$refs.console;
            if (consoleEl) {
                const terminalBody = consoleEl.querySelector('.terminal-body');
                if (terminalBody) {
                    terminalBody.scrollTo({ top: terminalBody.scrollHeight, behavior: 'smooth' });
                }
            }
        },
        copyLogs() {
            const text = this.progressLog.map(l => '['+l.time+'] '+l.message).join('\n');
            navigator.clipboard.writeText(text)
                .then(()=>this.addLog('📋 Logs copied to clipboard.'))
                .catch(e=>this.addLog('❌ Copy failed: '+e.message));
        },
        addLog(message) {
            const type = this.classifyLine(message);
            const iconMap = { info:'ℹ️', start:'🚀', progress:'📥', page:'📄', success:'✅', warning:'⚠️', error:'❌', final:'🏁' };
            const now = new Date();
            const ts = now.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit', second:'2-digit'});
            this.progressLog.push({ time: ts, type, icon: iconMap[type] || '•', message });
            
            // Auto-scroll if enabled
            if (this.autoScroll) {
                this.$nextTick(() => {
                    const consoleEl = this.$refs.console;
                    if (consoleEl) {
                        const terminalBody = consoleEl.querySelector('.terminal-body');
                        if (terminalBody) {
                            terminalBody.scrollTop = terminalBody.scrollHeight;
                        }
                    }
                });
            }
            
            if (!this.currentSession && message.includes('Session:')) {
                const sid = message.split('Session:')[1]?.trim();
                if (sid) this.currentSession = sid;
            }
        },
        classifyLine(line) {
            if (line.includes('Harvest Complete') || line.startsWith('🎉')) return 'final';
            if (line.startsWith('🚀') || line.startsWith('⏱️')) return 'start';
            if (line.startsWith('📄')) return 'page';
            if (line.startsWith('📥')) return 'progress';
            if (line.startsWith('✅')) return 'success';
            if (line.startsWith('⚠️')) return 'warning';
            if (line.startsWith('❌')) return 'error';
            return 'info';
        },
        
        // Live Stats
        async refreshLiveStats() {
            try {
                // Get stats for all games (will include game_2_xxx properties)
                const response = await fetch('/admin/mod-manager/api/live-stats');
                const stats = await response.json();
                this.liveStats = stats;
            } catch (error) {
                console.error('Failed to refresh stats:', error);
            }
        },
        
        startLiveStatsPolling() {
            // Load initial stats immediately
            this.refreshLiveStats();
            
            // Poll stats every 3 seconds (faster during harvest for live updates)
            setInterval(() => {
                this.refreshLiveStats();
            }, 3000);
        },
        
        // Category-Based Harvest (bypasses 10K limit)
        async startCategoryHarvest() {
            if (this.isHarvesting) {
                console.warn('[ModManager] Harvest already in progress – ignoring category harvest.');
                return;
            }

            if (!this.selectedGame || !this.selectedGame.id) {
                this.addLog('❌ Cannot start: No game selected. Go back and choose a game.');
                return;
            }
            
            this.addLog('🚀 Starting Category-Based Collection...');
            this.addLog('ℹ️  This method bypasses the 10,000 item API limit by harvesting each category separately.');
            
            // Use the existing direct harvest but with category type
            await this.startDirectHarvest('categories');
        },
        
        toggleCategoryConfig() {
            this.showCategoryConfig = !this.showCategoryConfig;
        }
    }
}
</script>
@endsection

@section('footer-scripts')
    @parent
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endsection