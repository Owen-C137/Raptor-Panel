---
applyTo: '**'
---

# 🛠️ **MOD MANAGER ADDON - IMPLEMENTATION PLAN**

**Version:** 1.0.0  
**Date:** September 23, 2025  
**Compatible with:** Raptor Panel v1.3.13+  
**License:** MIT

---

## ⚠️ **CRITICAL: SELF-CONTAINED ADDON REQUIREMENTS**

### **🔒 ADDON ISOLATION PRINCIPLES**
Following the [Pterodactyl Addon Development Guidelines](/.github/instructions/addon_creations.instructions.md):

- ✅ **ALWAYS**: Keep everything in `addons/mod-manager/` directory
- ✅ **ALWAYS**: Use proper PSR-4 namespace: `PterodactylAddons\ModManager`
- ✅ **ALWAYS**: Create install and uninstall commands for complete lifecycle management
- ✅ **ALWAYS**: Make uninstall command clean up ALL traces
- ✅ **ALWAYS**: Provide clear installation instructions
- ✅ **ALWAYS**: Use descriptive command output with progress indicators
- ❌ **NEVER**: Create files in main `app/`, `config/`, `resources/` directories
- ❌ **NEVER**: Use `Pterodactyl` namespace for addon code
- ❌ **NEVER**: Modify core Pterodactyl files directly

### **📦 REQUIRED CORE FILE MODIFICATIONS**
**ONLY modification allowed to core files:**

**File**: `config/app.php`
**Location**: Line ~267 (providers array)
**Addition**: 
```php
/*
 * Mod Manager Service Provider - Self-contained addon
 */
PterodactylAddons\ModManager\Providers\ModManagerServiceProvider::class,
```

**File**: `composer.json` 
**Location**: autoload.psr-4 section
**Addition**:
```json
"PterodactylAddons\\ModManager\\": "addons/mod-manager/src/"
```

### **🔧 INSTALL/UNINSTALL COMMANDS**

#### **Installation Command**
```bash
php artisan mod-manager:install [--force]
```
**Features:**
- ✅ Complete addon structure verification
- ✅ Prerequisites validation (PHP 8.3+, database, Redis, CurseForge API key)
- ✅ Database migration execution with rollback on failure
- ✅ Service provider registration in `config/app.php`
- ✅ PSR-4 autoloader configuration in `composer.json`
- ✅ Queue configuration and worker setup
- ✅ CurseForge API connectivity testing
- ✅ Initial game/category seeding (Minecraft data)
- ✅ Cache clearing and optimization
- ✅ Comprehensive success/failure feedback
- ✅ Force option for reinstallation over existing installation

#### **Uninstall Command**
```bash
php artisan mod-manager:uninstall [--keep-data] [--force]
```
**Features:**
- ✅ Complete service provider removal from `config/app.php`
- ✅ PSR-4 autoloader cleanup from `composer.json`
- ✅ Database migration rollback (with confirmation prompts)
- ✅ Queue job cleanup and cancellation
- ✅ Cache clearing and file cleanup
- ✅ Option to preserve user data (`--keep-data`)
- ✅ Complete addon directory removal verification
- ✅ Confirmation prompts for destructive actions
- ✅ Verification that Pterodactyl Panel still functions after removal

### **🎯 PUBLIC RELEASE PREPARATION**
- ✅ Complete documentation with installation guide
- ✅ Version control with semantic versioning
- ✅ Changelog maintenance with detailed release notes
- ✅ Error handling with user-friendly messages
- ✅ Comprehensive testing on clean Pterodactyl installations
- ✅ Performance benchmarks and resource requirements
- ✅ Troubleshooting guide with common issues and solutions

---

## 🎯 **PROJECT OVERVIEW**

The Mod Manager Addon is a comprehensive, self-contained system that integrates with CurseForge API to provide seamless mod browsing, installation, and management for game servers. This system will harvest mod data through intelligent parallel processing and provide both admin and user interfaces for mod management.

### **Core Features**
- 🔄 **Smart Simultaneous Harvesting**: Multi-category parallel jobs (max 20 concurrent) to bypass API pagination limits
- 🎮 **Multi-Game Support**: Extensible architecture starting with Minecraft
- 📊 **Real-time Progress Tracking**: Server-Sent Events for live progress with immediate start/stop controls
- 🛡️ **Admin Dashboard**: Complete control over harvesting and mod management with full mod/version display
- 🚀 **One-Click Installation**: Direct mod installation to server directories
- 📱 **Responsive Interface**: OneUI-based admin and client interfaces
- 📦 **Future Modpack Support**: Architecture designed to support CurseForge modpacks (Phase 6+)

---

## 📋 **DEVELOPMENT PHASES**

### **🎯 PHASE 1: Foundation & Database (Week 1-2) ✅ COMPLETED**
**Goal**: Establish completely self-contained addon architecture and data models

#### **Phase 1.1: Self-Contained Project Structure Setup ✅ COMPLETED**
- ✅ **DONE**: Create complete `addons/mod-manager/` directory structure following guidelines
- ✅ **DONE**: Set up PSR-4 autoloading with `PterodactylAddons\ModManager` namespace
- ✅ **DONE**: Create service provider with proper Laravel integration
- ✅ **DONE**: Set up configuration files within addon directory
- ✅ **DONE**: Create `composer.json` for addon with dependencies and metadata
- ✅ **DONE**: Implement comprehensive install/uninstall commands with validation

#### **Phase 1.2: Install/Uninstall Command Implementation ✅ COMPLETED**
- ✅ **DONE**: **ModManagerInstallCommand**: Complete one-click installation system
  - ✅ Prerequisites validation (PHP version, extensions, API key)
  - ✅ Database connectivity testing
  - ✅ Service provider registration automation
  - ✅ PSR-4 autoloader configuration
  - ✅ Initial data seeding with error recovery
  - ✅ Comprehensive progress reporting and error handling
- ✅ **DONE**: **ModManagerUninstallCommand**: Complete cleanup system
  - ✅ Safe removal with confirmation prompts
  - ✅ Database rollback with data preservation options
  - ✅ Service provider deregistration
  - ✅ File cleanup verification
  - ✅ Post-uninstall system integrity check

#### **Phase 1.3: Database Schema & Models ✅ COMPLETED**
- ✅ **DONE**: Create all 8 migration files with complete data storage capabilities
- ✅ **DONE**: Build Eloquent models with proper relationships and validation (5 core models)
- ✅ **DONE**: Set up database indexes for optimal performance
- ✅ **DONE**: Create seeders for initial game data (Minecraft, categories)
- ✅ **DONE**: Implement data integrity checks and foreign key constraints
- ✅ **DONE**: Fix foreign key compatibility with existing Pterodactyl schema

#### **Phase 1.4: API Integration Foundation ✅ COMPLETED**
- ✅ **DONE**: CurseForge API service wrapper with rate limiting
- ✅ **DONE**: Configuration management within addon directory
- ✅ **DONE**: Error handling and retry logic for API failures
- ✅ **DONE**: API connectivity testing and validation
- ✅ **DONE**: Response caching and optimization strategies

**🎉 Phase 1 Successfully Completed on January 22, 2025!**
**Installation Status**: ✅ Fully functional and tested
**Database Tables**: ✅ 8 tables created successfully
**Service Registration**: ✅ Auto-loaded and working
**Commands Available**: ✅ install, uninstall, status, verify

### **🎯 PHASE 2: Parallel Harvesting System (Week 2-3) ✅ COMPLETED**
**Goal**: Build intelligent mod data collection system

#### **Phase 2.1: Modular Job System ✅ COMPLETED**
- ✅ **DONE**: Modular job architecture with HarvestCategoriesJob, HarvestPopularJob, HarvestRecentJob
- ✅ **DONE**: Background job processing with Laravel queues ('mod-harvest' queue)
- ✅ **DONE**: Job progress tracking and status management (HarvestJob model)
- ✅ **DONE**: Real-time progress updates via Server-Sent Events (ProgressController)
- ✅ **DONE**: Harvest management commands (harvest-categories, harvest-status, harvest-stop)

#### **Phase 2.2: Harvesting Logic ✅ COMPLETED**
- ✅ **DONE**: Category-based parallel harvesting with concurrent job processing
- ✅ **DONE**: Popular mods harvesting with Downloads/Rating sort strategies
- ✅ **DONE**: Recent mods harvesting with LastUpdated sort strategy
- ✅ **DONE**: Enhanced CurseForge API service with category/popular/recent methods
- ✅ **DONE**: Duplicate detection and data merging via updateOrCreate
- ✅ **DONE**: Category-mod relationship synchronization (pivot table)

#### **Phase 2.3: Admin Control Interface ✅ COMPLETED**
- ✅ **DONE**: Harvest dashboard with live progress tracking
- ✅ **DONE**: Start/stop job controls with immediate response (HarvestController API)
- ✅ **DONE**: Job queue management and monitoring via admin interface
- ✅ **DONE**: Error handling and retry mechanisms with comprehensive logging

#### **Phase 2.4: Real-time Progress Tracking ✅ COMPLETED**
- ✅ **DONE**: Server-Sent Events streaming endpoint (/api/progress/stream)
- ✅ **DONE**: Real-time job progress updates with ETA calculation
- ✅ **DONE**: Live dashboard updates without page refresh
- ✅ **DONE**: Connection management and auto-reconnection logic

#### **Phase 2.5: Error Handling & Recovery ✅ COMPLETED**
- ✅ **DONE**: Comprehensive job failure handling with retry logic
- ✅ **DONE**: Error logging and debugging information
- ✅ **DONE**: Rate limiting enforcement for API calls
- ✅ **DONE**: Graceful degradation and recovery mechanisms

**🎉 Phase 2 Successfully Completed on January 22, 2025!**
**Features Delivered**: Complete parallel harvesting system with real-time progress tracking
**Admin Integration**: Fully integrated with existing admin interface and navigation
**Job System**: 3 job types (categories, popular, recent) with comprehensive error handling
**Real-time Updates**: Server-Sent Events for live progress monitoring
**Commands Available**: harvest-categories, harvest-status, harvest-stop all functional

### **🎯 PHASE 3: Admin Interface & Management (Week 3-4)**
**Goal**: Create comprehensive admin tools

#### **Phase 3.1: Admin Dashboard**
- ✅ OneUI-based interface matching existing admin styles
- ✅ Real-time statistics and progress indicators
- ✅ Job management controls
- ✅ System health monitoring

#### **Phase 3.2: Mod Browser & Search**
- ✅ Advanced search and filtering system
- ✅ Category browsing with pagination
- ✅ **Complete Mod Display**: Show all harvested mods, versions, files, and metadata in admin
- ✅ **Version Management**: Display all available versions per mod with compatibility info
- ✅ Mod detail views with screenshots and metadata
- ✅ Bulk operations and management tools

#### **Phase 3.3: Server Integration**
- ✅ Server-aware mod compatibility checking
- ✅ Direct file system integration
- ✅ Backup creation before mod installation
- ✅ Installation history and rollback capabilities

### **🎯 PHASE 4: User Interface & Installation (Week 4-5)**
**Goal**: Build user-facing mod installation system

#### **Phase 4.1: Client Mod Browser**
- ✅ User-friendly mod browsing interface
- ✅ Server-specific mod recommendations
- ✅ One-click installation system
- ✅ Installation status tracking

#### **Phase 4.2: Mod Management**
- ✅ Installed mod listing and management
- ✅ Update notifications and auto-updates
- ✅ Mod enable/disable functionality
- ✅ Dependency resolution and conflict detection

### **🎯 PHASE 5: Testing & Optimization (Week 5-6)**
**Goal**: Ensure reliability and performance

#### **Phase 5.1: Comprehensive Testing**
- ✅ Unit tests for all core functionality
- ✅ Integration tests for API interactions
- ✅ Performance testing for large mod sets
- ✅ Error handling and edge case testing

#### **Phase 5.2: Performance Optimization**
- ✅ Database query optimization
- ✅ Caching strategy implementation
- ✅ Memory usage optimization for large harvests
- ✅ API rate limiting fine-tuning

### **🎯 PHASE 6: Future Modpack Support (Post-Launch)**
**Goal**: Extend system to support CurseForge modpacks

#### **Phase 6.1: Modpack Foundation**
- ✅ Extend database schema for modpack support
- ✅ CurseForge modpack API integration
- ✅ Modpack harvesting system (similar to mod harvesting)
- ✅ Modpack-to-mod relationship mapping

#### **Phase 6.2: Modpack Management**
- ✅ Admin modpack browser and management
- ✅ Modpack installation system (bulk mod installation)
- ✅ Modpack version management and updates
- ✅ Custom modpack creation tools

#### **Phase 6.3: Advanced Features**
- ✅ Server template creation from modpacks
- ✅ Modpack sharing and export/import
- ✅ Automated server setup with modpack deployment
- ✅ Modpack compatibility checking and conflict resolution

**Note**: Modpack support is designed into the core architecture but will be implemented after the mod system is complete and stable.

---

## 🗄️ **DATABASE SCHEMA**

### **Migration Files Required**

#### **001_create_mod_games_table.php**
```sql
CREATE TABLE mod_games (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    curse_game_id INT UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    logo_url VARCHAR(500),
    tile_url VARCHAR(500),
    cover_url VARCHAR(500),
    status TINYINT DEFAULT 1,
    api_status TINYINT DEFAULT 1,
    date_modified TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_curse_game_id (curse_game_id),
    INDEX idx_slug (slug),
    INDEX idx_is_active (is_active)
);
```

#### **002_create_mod_categories_table.php**
```sql
CREATE TABLE mod_categories (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    curse_category_id INT UNIQUE NOT NULL,
    game_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    url VARCHAR(500),
    icon_url VARCHAR(500),
    is_class BOOLEAN DEFAULT false,
    class_id INT NULL,
    parent_category_id INT NULL,
    display_index INT DEFAULT 0,
    date_modified TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (game_id) REFERENCES mod_games(id) ON DELETE CASCADE,
    INDEX idx_curse_category_id (curse_category_id),
    INDEX idx_game_id (game_id),
    INDEX idx_parent_category_id (parent_category_id),
    INDEX idx_is_class (is_class)
);
```

#### **003_create_mod_mods_table.php**
```sql
CREATE TABLE mod_mods (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    curse_mod_id INT UNIQUE NOT NULL,
    game_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    summary TEXT,
    description LONGTEXT,
    
    -- Statistics
    download_count BIGINT DEFAULT 0,
    popularity_rank INT NULL,
    thumbs_up_count INT DEFAULT 0,
    rating DECIMAL(3,2) NULL,
    
    -- Media
    logo_url VARCHAR(500),
    screenshots JSON,
    
    -- Authors (JSON array)
    authors JSON,
    
    -- Categories (JSON array of category IDs)
    categories JSON,
    
    -- Links
    website_url VARCHAR(500),
    wiki_url VARCHAR(500),
    issues_url VARCHAR(500),
    source_url VARCHAR(500),
    
    -- Dates
    date_created TIMESTAMP NULL,
    date_modified TIMESTAMP NULL,
    date_released TIMESTAMP NULL,
    
    -- Status
    allow_mod_distribution BOOLEAN DEFAULT true,
    is_available BOOLEAN DEFAULT true,
    game_popularity_rank INT NULL,
    
    -- Sync tracking
    last_sync_at TIMESTAMP NULL,
    sync_status ENUM('pending', 'syncing', 'completed', 'failed') DEFAULT 'pending',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (game_id) REFERENCES mod_games(id) ON DELETE CASCADE,
    INDEX idx_curse_mod_id (curse_mod_id),
    INDEX idx_game_id (game_id),
    INDEX idx_name (name),
    INDEX idx_slug (slug),
    INDEX idx_download_count (download_count DESC),
    INDEX idx_popularity_rank (popularity_rank ASC),
    INDEX idx_date_released (date_released DESC),
    INDEX idx_sync_status (sync_status),
    FULLTEXT idx_search (name, summary, description)
);
```

#### **004_create_mod_files_table.php**
```sql
CREATE TABLE mod_files (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    curse_file_id INT UNIQUE NOT NULL,
    mod_id BIGINT NOT NULL,
    display_name VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    
    -- File details
    release_type TINYINT NOT NULL, -- 1=Release, 2=Beta, 3=Alpha
    file_status TINYINT NOT NULL,
    is_available BOOLEAN DEFAULT true,
    
    -- Download info
    download_url VARCHAR(500),
    file_length BIGINT DEFAULT 0,
    download_count BIGINT DEFAULT 0,
    file_size_on_disk BIGINT DEFAULT 0,
    
    -- Compatibility
    game_versions JSON, -- Array of game version strings
    sortable_game_versions JSON, -- Structured version data
    mod_loader_types JSON, -- Forge, Fabric, NeoForge, etc.
    game_version_type_id INT NULL,
    
    -- Dependencies
    dependencies JSON, -- Array of dependency objects
    
    -- Security
    hashes JSON, -- File hashes for integrity
    file_fingerprint BIGINT NULL,
    modules JSON, -- Mod modules information
    
    -- Dates
    file_date TIMESTAMP NULL,
    upload_date TIMESTAMP NULL,
    
    -- Server pack info
    is_server_pack BOOLEAN DEFAULT false,
    server_pack_file_id INT NULL,
    
    -- Early access
    is_early_access_content BOOLEAN DEFAULT false,
    early_access_end_date TIMESTAMP NULL,
    
    -- Alternative files
    expose_as_alternative BOOLEAN DEFAULT false,
    parent_project_file_id INT NULL,
    alternate_file_id INT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (mod_id) REFERENCES mod_mods(id) ON DELETE CASCADE,
    INDEX idx_curse_file_id (curse_file_id),
    INDEX idx_mod_id (mod_id),
    INDEX idx_file_name (file_name),
    INDEX idx_release_type (release_type),
    INDEX idx_file_date (file_date DESC),
    INDEX idx_download_count (download_count DESC),
    INDEX idx_is_available (is_available)
);
```

#### **005_create_mod_installations_table.php**
```sql
CREATE TABLE mod_installations (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    server_id BIGINT NOT NULL,
    mod_id BIGINT NOT NULL,
    file_id BIGINT NOT NULL,
    
    -- Installation details
    installation_path VARCHAR(500) NOT NULL,
    status ENUM('pending', 'installing', 'installed', 'failed', 'removed') DEFAULT 'pending',
    
    -- Version management
    installed_version VARCHAR(100),
    target_version VARCHAR(100),
    
    -- Settings
    is_enabled BOOLEAN DEFAULT true,
    auto_update BOOLEAN DEFAULT false,
    
    -- Installation tracking
    installed_at TIMESTAMP NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    removed_at TIMESTAMP NULL,
    last_check_at TIMESTAMP NULL,
    
    -- Error handling
    error_message TEXT NULL,
    retry_count INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE,
    FOREIGN KEY (mod_id) REFERENCES mod_mods(id) ON DELETE CASCADE,
    FOREIGN KEY (file_id) REFERENCES mod_files(id) ON DELETE CASCADE,
    INDEX idx_user_server (user_id, server_id),
    INDEX idx_server_id (server_id),
    INDEX idx_mod_id (mod_id),
    INDEX idx_status (status),
    INDEX idx_installed_at (installed_at DESC),
    UNIQUE KEY unique_server_mod (server_id, mod_id)
);
```

#### **006_create_mod_harvest_jobs_table.php**
```sql
CREATE TABLE mod_harvest_jobs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    job_type ENUM('category', 'popular', 'recent', 'files', 'full') NOT NULL,
    job_name VARCHAR(255) NOT NULL,
    
    -- Job configuration
    parameters JSON, -- Job-specific parameters (category_id, sort_field, etc.)
    
    -- Progress tracking
    status ENUM('pending', 'running', 'completed', 'failed', 'cancelled', 'paused') DEFAULT 'pending',
    total_items INT DEFAULT 0,
    processed_items INT DEFAULT 0,
    failed_items INT DEFAULT 0,
    current_page INT DEFAULT 0,
    total_pages INT DEFAULT 0,
    
    -- Timing
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    estimated_completion TIMESTAMP NULL,
    
    -- Error handling
    error_message TEXT NULL,
    retry_count INT DEFAULT 0,
    max_retries INT DEFAULT 3,
    
    -- Rate limiting
    api_calls_made INT DEFAULT 0,
    last_api_call TIMESTAMP NULL,
    
    -- Results
    items_created INT DEFAULT 0,
    items_updated INT DEFAULT 0,
    items_skipped INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_job_type (job_type),
    INDEX idx_status (status),
    INDEX idx_started_at (started_at DESC),
    INDEX idx_completed_at (completed_at DESC)
);
```

#### **007_create_mod_collections_table.php**
```sql
CREATE TABLE mod_collections (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    user_id BIGINT NOT NULL,
    
    -- Collection settings
    is_public BOOLEAN DEFAULT false,
    is_featured BOOLEAN DEFAULT false,
    
    -- Metadata
    game_id BIGINT NOT NULL,
    target_version VARCHAR(100),
    mod_loader VARCHAR(50),
    
    -- Collection data
    mods JSON, -- Array of mod IDs with specific versions
    total_mods INT DEFAULT 0,
    total_downloads BIGINT DEFAULT 0,
    
    -- Sharing
    share_code VARCHAR(20) UNIQUE,
    download_count INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES mod_games(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_game_id (game_id),
    INDEX idx_is_public (is_public),
    INDEX idx_share_code (share_code)
);
```

#### **008_create_mod_compatibility_table.php**
```sql
CREATE TABLE mod_compatibility (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    mod_a_id BIGINT NOT NULL,
    mod_b_id BIGINT NOT NULL,
    
    -- Compatibility status
    compatibility_status ENUM('compatible', 'incompatible', 'unknown', 'requires_patch') DEFAULT 'unknown',
    
    -- Version specific compatibility
    version_a VARCHAR(100),
    version_b VARCHAR(100),
    game_version VARCHAR(100),
    mod_loader VARCHAR(50),
    
    -- Details
    notes TEXT,
    reported_by BIGINT NULL, -- User ID who reported this
    verified BOOLEAN DEFAULT false,
    verified_by BIGINT NULL, -- Admin who verified
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (mod_a_id) REFERENCES mod_mods(id) ON DELETE CASCADE,
    FOREIGN KEY (mod_b_id) REFERENCES mod_mods(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_mod_pair (mod_a_id, mod_b_id),
    INDEX idx_compatibility_status (compatibility_status),
    UNIQUE KEY unique_mod_pair (mod_a_id, mod_b_id, version_a, version_b, game_version)
);
```

#### **009_create_mod_modpacks_table.php** *(Future - Phase 6)*
```sql
CREATE TABLE mod_modpacks (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    curse_modpack_id INT UNIQUE NOT NULL,
    game_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    summary TEXT,
    description LONGTEXT,
    
    -- Statistics
    download_count BIGINT DEFAULT 0,
    popularity_rank INT NULL,
    
    -- Media
    logo_url VARCHAR(500),
    screenshots JSON,
    
    -- Authors
    authors JSON,
    
    -- Modpack details
    minecraft_version VARCHAR(50),
    mod_loader VARCHAR(50), -- Forge, Fabric, etc.
    total_mods INT DEFAULT 0,
    
    -- Included mods (JSON array of mod objects with versions)
    included_mods JSON,
    
    -- Links
    website_url VARCHAR(500),
    source_url VARCHAR(500),
    
    -- Dates
    date_created TIMESTAMP NULL,
    date_modified TIMESTAMP NULL,
    date_released TIMESTAMP NULL,
    
    -- Status
    is_available BOOLEAN DEFAULT true,
    
    -- Sync tracking  
    last_sync_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (game_id) REFERENCES mod_games(id) ON DELETE CASCADE,
    INDEX idx_curse_modpack_id (curse_modpack_id),
    INDEX idx_game_id (game_id),
    INDEX idx_name (name),
    INDEX idx_minecraft_version (minecraft_version),
    INDEX idx_mod_loader (mod_loader),
    FULLTEXT idx_search (name, summary, description)
);
```

#### **010_create_mod_modpack_installations_table.php** *(Future - Phase 6)*
```sql
CREATE TABLE mod_modpack_installations (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    server_id BIGINT NOT NULL,
    modpack_id BIGINT NOT NULL,
    
    -- Installation details
    status ENUM('pending', 'installing', 'installed', 'failed', 'removed') DEFAULT 'pending',
    installed_version VARCHAR(100),
    
    -- Progress tracking
    total_mods INT DEFAULT 0,
    installed_mods INT DEFAULT 0,
    failed_mods INT DEFAULT 0,
    
    installed_at TIMESTAMP NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE,
    FOREIGN KEY (modpack_id) REFERENCES mod_modpacks(id) ON DELETE CASCADE,
    INDEX idx_user_server (user_id, server_id),
    UNIQUE KEY unique_server_modpack (server_id, modpack_id)
);
```
```sql
CREATE TABLE mod_compatibility (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    mod_a_id BIGINT NOT NULL,
    mod_b_id BIGINT NOT NULL,
    
    -- Compatibility status
    compatibility_status ENUM('compatible', 'incompatible', 'unknown', 'requires_patch') DEFAULT 'unknown',
    
    -- Version specific compatibility
    version_a VARCHAR(100),
    version_b VARCHAR(100),
    game_version VARCHAR(100),
    mod_loader VARCHAR(50),
    
    -- Details
    notes TEXT,
    reported_by BIGINT NULL, -- User ID who reported this
    verified BOOLEAN DEFAULT false,
    verified_by BIGINT NULL, -- Admin who verified
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (mod_a_id) REFERENCES mod_mods(id) ON DELETE CASCADE,
    FOREIGN KEY (mod_b_id) REFERENCES mod_mods(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_mod_pair (mod_a_id, mod_b_id),
    INDEX idx_compatibility_status (compatibility_status),
    UNIQUE KEY unique_mod_pair (mod_a_id, mod_b_id, version_a, version_b, game_version)
);
```

### **⚡ PERFORMANCE ESTIMATES**

**Smart Simultaneous Harvesting Performance:**
- **Total Minecraft Mods**: ~232,000 mods available
- **API Calls Required**: ~46,400 calls (232K ÷ 50 per page)  
- **With 20 Concurrent Jobs**: ~2,320 calls per job
- **At 1 call/second rate**: ~39 minutes per job
- **Total Harvest Time**: **~25-30 minutes** for complete Minecraft harvest!

**Comparison to Sequential Approach:**
- **Sequential**: 46,400 calls × 1 second = ~13 hours
- **Our Smart Approach**: 46,400 ÷ 20 jobs = **~39 minutes**
- **Speed Improvement**: **20x faster than sequential!**

**Resource Usage:**
- **Memory**: ~2GB for full harvest (configurable)
- **Database Storage**: ~500MB for all mod data
- **API Rate**: Respects CurseForge limits (1 call/second per job)
- **Concurrent Jobs**: 20 maximum (configurable down to prevent overload)

### **Job Directory Structure**
```
addons/mod-manager/src/Jobs/
├── HarvestJobBase.php                      # Base job class with common functionality
├── CategoryHarvest/                        # Category-based harvesting jobs
│   ├── HarvestCategoryJob.php              # Single category harvest
│   └── HarvestAllCategoriesJob.php         # Dispatch all category jobs
├── PopularHarvest/                         # Popular mod harvesting
│   ├── HarvestPopularModsJob.php           # Popular mods by downloads
│   ├── HarvestRecentModsJob.php            # Recent mod releases
│   └── HarvestTrendingModsJob.php          # Trending mods
├── FileHarvest/                            # File/version harvesting
│   ├── SyncModFilesJob.php                 # Sync files for specific mod
│   └── BulkFileSyncJob.php                 # Bulk file synchronization
├── MaintenanceJobs/                        # System maintenance
│   ├── CleanupFailedJobsJob.php            # Clean failed job records
│   └── UpdateModStatisticsJob.php          # Update cached statistics
└── Monitors/
    ├── JobProgressMonitor.php              # Real-time progress tracking
    └── JobHealthChecker.php                # Monitor job system health
```

### **Job Processing Strategy: Option A+ (Smart Simultaneous)**

**Maximum Speed Configuration:**
```php
// config/mod-manager.php
'harvesting' => [
    'strategy' => 'smart_simultaneous',
    'max_concurrent_jobs' => 20,           // Max jobs running simultaneously
    'category_batch_size' => 5,            // Smaller batches for faster processing
    'rate_limit_delay' => 1,               // Minimum delay (1 second) for maximum speed
    'queue_name' => 'mod-harvest',
    'retry_attempts' => 3,
    'timeout_minutes' => 90,               // Longer timeout for large harvests
    
    // Aggressive job priorities for speed
    'job_priorities' => [
        'popular' => 10,      // Highest priority - gets popular mods first
        'category' => 8,      // High priority - bulk category harvesting  
        'files' => 6,         // Medium priority - version details
        'maintenance' => 2,   // Lower priority - cleanup tasks
    ],
    
    // Smart resource management
    'memory_limit' => '2G',               // Increase memory for large datasets
    'chunk_size' => 100,                  // Process 100 mods at a time
    'api_burst_limit' => 5,               // Allow 5 rapid calls, then throttle
]
```

**Intelligent Harvesting Strategy:**
```php
class SmartHarvestDispatcher
{
    public function dispatchFullHarvest()
    {
        // Phase 1: Rapid popular mods (gets most important mods first)
        $this->dispatchPopularHarvest();
        
        // Phase 2: Parallel category harvest (bypass 10K limit completely)
        $this->dispatchAllCategoriesSimultaneously();
        
        // Phase 3: File sync for discovered mods (versions/compatibility)
        $this->dispatchBulkFileSyncing();
    }
    
    private function dispatchAllCategoriesSimultaneously()
    {
        $categories = Category::where('game_id', 432)->get();
        
        // Launch ALL categories at once (20 max concurrent)
        foreach ($categories as $category) {
            HarvestCategoryJob::dispatch($category)
                ->onQueue('mod-harvest-high')           // High priority queue
                ->delay(now()->addSeconds(rand(1, 10))); // Stagger start times
        }
        
        echo "🚀 Launched {$categories->count()} category harvest jobs simultaneously!\n";
    }
    
    private function estimateCompletionTime()
    {
        // With 142 categories × 10K limit × 50 mods per page = ~28,400 API calls
        // At 1 call per second = ~8 hours for complete harvest
        // With 20 concurrent jobs = ~24 minutes for complete harvest!
        return "Estimated completion: ~25-30 minutes for full Minecraft harvest";
    }
}
```

---

## 🎨 **USER INTERFACE DESIGN**

### **Admin Dashboard Interface**

**Using OneUI Components:**
- **Content Header**: Standard Raptor Panel admin header
- **Progress Cards**: OneUI card components with progress bars
- **Control Buttons**: OneUI button groups
- **Statistics Widgets**: OneUI info blocks
- **Live Updates**: Real-time progress via Server-Sent Events

**Required OneUI Elements:**
```html
<!-- From addons/one-ui/html-templates/ -->
- blocks.html (for progress cards)
- buttons.html (for control buttons) 
- forms.html (for job configuration)
- tables.html (for mod listings)
- progress.html (for harvest progress)
- modals.html (for job details)
```

**CSS Classes (from public/themes/one-ui/css/oneui.css):**
- `.block`: Main content blocks
- `.block-header`: Block titles and controls
- `.progress`: Progress bars
- `.btn-group`: Button groupings
- `.table-responsive`: Data tables
- `.alert`: Status messages
- `.badge`: Status indicators

### **Real-Time Progress System**

**Live Progress Updates:**
```php
// JobProgressMonitor.php
class JobProgressMonitor 
{
    public function broadcastProgress($jobId)
    {
        $job = HarvestJob::find($jobId);
        $progress = $this->calculateProgress($job);
        
        // Broadcast via Server-Sent Events
        event(new JobProgressUpdated($job->id, $progress));
    }
    
    private function calculateProgress($job)
    {
        return [
            'percentage' => $job->total_items > 0 ? 
                round(($job->processed_items / $job->total_items) * 100, 2) : 0,
            'processed' => $job->processed_items,
            'total' => $job->total_items,
            'failed' => $job->failed_items,
            'eta' => $this->calculateETA($job),
            'status' => $job->status
        ];
    }
}
```

**Frontend Live Updates (JavaScript):**
```javascript
// Using Server-Sent Events for real-time updates
const eventSource = new EventSource('/admin/mod-manager/progress-stream');

eventSource.onmessage = function(event) {
    const progress = JSON.parse(event.data);
    updateProgressBar(progress.job_id, progress.percentage);
    updateStatus(progress.job_id, progress.status);
    updateETA(progress.job_id, progress.eta);
};

function updateProgressBar(jobId, percentage) {
    const progressBar = document.getElementById(`progress-${jobId}`);
    progressBar.style.width = `${percentage}%`;
    progressBar.setAttribute('aria-valuenow', percentage);
    progressBar.textContent = `${percentage}%`;
}
```

---

## 🔧 **IMPLEMENTATION COMMANDS**

### **Self-Contained Addon Commands**

#### **Primary Installation Command**
```bash
php artisan mod-manager:install [--force] [--skip-api-test]
```
**Complete Installation Process:**
1. **Prerequisites Check**:
   - PHP 8.3+ verification
   - Required extensions (curl, json, zip)
   - Database connectivity test
   - Redis availability check
   - CurseForge API key validation
   
2. **Addon Structure Setup**:
   - Verify `addons/mod-manager/` structure integrity
   - Validate PSR-4 namespace compliance
   - Check file permissions and ownership
   
3. **Core Integration** (ONLY allowed core modifications):
   - Register service provider in `config/app.php`
   - Add PSR-4 autoloading to `composer.json`
   - Refresh composer autoloader
   
4. **Database Setup**:
   - Execute all 10 migration files in sequence
   - Create indexes and foreign key constraints
   - Seed initial game data (Minecraft + categories)
   - Verify data integrity
   
5. **System Configuration**:
   - Configure queue settings for mod harvesting
   - Set up rate limiting for CurseForge API
   - Initialize cache structures
   - Test API connectivity with sample requests
   
6. **Final Validation**:
   - Verify all components are functioning
   - Run basic system health checks
   - Display installation summary and next steps

**Options:**
- `--force`: Reinstall over existing installation
- `--skip-api-test`: Skip CurseForge API connectivity test

#### **Complete Uninstall Command**
```bash
php artisan mod-manager:uninstall [--keep-data] [--force] [--no-confirm]
```
**Complete Removal Process:**
1. **Pre-Uninstall Safety**:
   - Display what will be removed
   - Check for active harvesting jobs
   - Verify backup options if `--keep-data` not used
   
2. **Job Cleanup**:
   - Cancel all running mod harvesting jobs
   - Clear mod-manager queue entries
   - Stop any active background processes
   
3. **Database Cleanup**:
   - Rollback all migrations (unless `--keep-data`)
   - Remove mod-manager specific indexes
   - Clean foreign key references
   
4. **Core File Restoration**:
   - Remove service provider from `config/app.php`
   - Remove PSR-4 entry from `composer.json`
   - Refresh composer autoloader
   
5. **File System Cleanup**:
   - Remove `addons/mod-manager/` directory
   - Clear related cache entries
   - Remove temporary files
   
6. **System Verification**:
   - Verify Pterodactyl Panel still functions
   - Check for any orphaned references
   - Display cleanup summary

**Options:**
- `--keep-data`: Preserve database tables and user data
- `--force`: Skip safety checks and confirmations
- `--no-confirm`: Skip interactive confirmation prompts

### **Addon Management Commands**

```bash
# Check addon status and health
php artisan mod-manager:status

# Verify addon integrity
php artisan mod-manager:verify

# Update addon version and migrate
php artisan mod-manager:update

# Repair addon installation
php artisan mod-manager:repair [--fix-permissions] [--rebuild-cache]

# Display addon information
php artisan mod-manager:info

# Test CurseForge API connectivity
php artisan mod-manager:test-api [--detailed]
```

### **Harvest Management Commands**

```bash
# Start full harvest (all categories + popular)
php artisan mod-manager:harvest-full

# Harvest specific categories
php artisan mod-manager:harvest-categories --categories=5,12,25

# Harvest popular mods only
php artisan mod-manager:harvest-popular --limit=10000

# Sync files for existing mods
php artisan mod-manager:sync-files [--mod-id=12345]

# Monitor harvest progress
php artisan mod-manager:harvest-status

# Stop all running harvests
php artisan mod-manager:harvest-stop

# Clean up failed jobs
php artisan mod-manager:cleanup-jobs
```

---

## 📊 **TESTING STRATEGY**

### **Phase Testing Requirements**

#### **Phase 1 Testing (Foundation)**
- ✅ **Unit Tests**: All model relationships and validations
- ✅ **Migration Tests**: All database schema creation and rollback
- ✅ **API Tests**: CurseForge connectivity and error handling
- ✅ **Install/Uninstall Tests**: Complete addon lifecycle

#### **Phase 2 Testing (Harvesting)**
- ✅ **Job Tests**: All harvest job types with mock data
- ✅ **Queue Tests**: Job dispatching and processing
- ✅ **Rate Limiting Tests**: API call throttling
- ✅ **Progress Tests**: Real-time progress updates

#### **Phase 3 Testing (Admin Interface)**
- ✅ **Feature Tests**: All admin functionality end-to-end
- ✅ **UI Tests**: Interface rendering and responsiveness
- ✅ **Permission Tests**: Admin access controls
- ✅ **Performance Tests**: Large dataset handling

#### **Phase 4 Testing (User Interface)**
- ✅ **Integration Tests**: Mod installation to actual servers
- ✅ **File System Tests**: Safe file operations and rollback
- ✅ **Security Tests**: File upload validation and sandbox
- ✅ **Compatibility Tests**: Multiple game versions and mod loaders

#### **Phase 5 Testing (System Integration)**
- ✅ **Load Tests**: Concurrent harvesting performance
- ✅ **Stress Tests**: Large-scale mod operations
- ✅ **Memory Tests**: Long-running harvest job memory usage
- ✅ **Error Recovery Tests**: System resilience and recovery

---

## 🚀 **DEPLOYMENT & LAUNCH**

### **Pre-Launch Checklist**
- [ ] All migrations tested and verified
- [ ] Full harvest test completed successfully
- [ ] Admin interface fully functional
- [ ] User permissions properly configured
- [ ] Error handling and logging implemented
- [ ] Performance benchmarks met
- [ ] Documentation completed
- [ ] Version control and changelog updated

### **Launch Strategy**
1. **Soft Launch**: Admin-only access for initial testing
2. **Beta Release**: Selected users for feedback
3. **Full Launch**: Public availability
4. **Monitoring**: Continuous system health monitoring

---

**🎯 Ready to begin Phase 1 implementation?**