# 🛠️ **PTERODACTYL ADDON DEVELOPMENT GUIDELINES**

Main rules to follow:
- ALWAYS keep everything in `addons/{addon-name}/` directory
- NEVER create files in main `app/`, `config/`, `resources/` directories
- ALWAYS use proper PSR-4 namespace: `PterodactylAddons\{AddonName}`
- NEVER use `Pterodactyl` namespace for addon code
- ALWAYS create `install` and `uninstall` commands for every addon
- ALWAYS make uninstall commands clean up ALL traces
- ALWAYS provide clear installation instructions
- ALWAYS use descriptive command output with progress indicators
- ALWAYS include helpful error messages and troubleshooting
- ALWAYS validate prerequisites before installation
- ALWAYS update plan files after implementing features
- ALWAYS mark completed items as ✅ in roadmap files
- ALWAYS add newly discovered requirements to plans
- ALWAYS keep implementation status accurate and current
- NEVER leave plan files outdated or incorrect

- NEVER Assume anything about the users environment, Always validate everything and provide clear error messages if something is missing or wrong.
- NEVER Modify any core Pterodactyl files directly, Only change `config/app.php` to register your service provider.
- If your unsure about anything ASK ME FIRST.
- ALWAYS document your code and provide clear comments.


IMPORTANT: we have set up a repo you can use this to push changes when i ask to:

## **📦 VERSION CONTROL & CHANGELOG WORKFLOW**

**CRITICAL: Before every commit, you MUST:**

### **1. Update Version**
```bash
# For minor changes (bug fixes, small features):
echo "1.2.1" > addons/shop-system/VERSION

# For major changes (new features, breaking changes):
echo "1.3.0" > addons/shop-system/VERSION
```

### **2. Update Changelog**
```bash
# Always update addons/shop-system/CHANGELOG.md with:
# - Version number
# - Date
# - What changed (features, fixes, improvements)
# - Any breaking changes
```

### **3. Commit with Version**
```bash
# 1. Stage all changes
git add .

# 2. Commit with version and description
git commit -m "v1.2.1: Describe what you changed here

- Feature/fix details
- Any breaking changes
- Updated changelog and version"

# 3. Push to GitHub
git push origin main
```

**✅ ALWAYS**: Include version number in commit message  
**✅ ALWAYS**: Update CHANGELOG.md before committing  
**✅ ALWAYS**: Update VERSION file before committing  
**❌ NEVER**: Commit without updating version and changelog

---

*Comprehensive instructions for creating and maintaining self-contained Pterodactyl Panel addons*

THIS IS OUR PLAN / ROADMAP UPDATED_SHOP_ROADMAP_2025.md ALWAYS KEEP THIS UPDATED AND NEVER CREATE A FRESH ONE.

---

## 🎯 **CORE PRINCIPLES**

### **🏗️ ALWAYS SELF-CONTAINED**
- ✅ **DO**: Keep ALL addon code in `addons/{addon-name}/` directory
- ❌ **DON'T**: Create files in main `app/`, `config/`, `resources/` directories
- ✅ **DO**: Use proper PSR-4 namespace: `PterodactylAddons\{AddonName}`
- ❌ **DON'T**: Use `Pterodactyl` namespace for addon code

### **🔧 INSTALL/UNINSTALL CAPABLE**
- ✅ **DO**: Create `install` and `uninstall` commands for every addon
- ✅ **DO**: Make uninstall commands clean up ALL traces
- ✅ **DO**: Handle existing installations gracefully
- ❌ **DON'T**: Leave orphaned files after uninstall

### **👤 USER-FRIENDLY**
- ✅ **DO**: Provide clear installation instructions
- ✅ **DO**: Use descriptive command output with progress indicators
- ✅ **DO**: Include helpful error messages and troubleshooting
- ✅ **DO**: Validate prerequisites before installation

### **📋 PLAN MAINTENANCE (CRITICAL)**
- ✅ **ALWAYS**: Update plan files after implementing features
- ✅ **ALWAYS**: Mark completed items as ✅ in roadmap files
- ✅ **ALWAYS**: Add newly discovered requirements to plans
- ✅ **ALWAYS**: Keep implementation status accurate and current
- ❌ **NEVER**: Leave plan files outdated or incorrect

---

## 📁 **REQUIRED ADDON STRUCTURE**

### **Standard Directory Layout**
```
addons/{addon-name}/
├── src/                           # All source code (PSR-4)
│   ├── Commands/                  # Artisan commands
│   │   ├── {Addon}InstallCommand.php     # ✅ REQUIRED
│   │   ├── {Addon}UninstallCommand.php   # ✅ REQUIRED
│   │   └── ...                    # Additional commands
│   ├── Http/Controllers/          # Controllers (if needed)
│   │   ├── Admin/                 # Admin interface
│   │   └── Client/                # Client interface
│   ├── Models/                    # Eloquent models
│   ├── Services/                  # Business logic services
│   ├── Providers/                 # Service providers
│   │   └── {Addon}ServiceProvider.php    # ✅ REQUIRED
│   ├── Middleware/                # Custom middleware (if needed)
│   ├── Requests/                  # Form request validation
│   └── ...                        # Other classes as needed
├── database/                      # Database files
│   ├── migrations/                # Database migrations
│   └── seeders/                   # Database seeders (optional)
├── resources/                     # Frontend resources
│   ├── views/                     # Blade templates
│   ├── js/                        # JavaScript files
│   └── css/                       # Stylesheets
├── config/                        # Configuration files
│   └── {addon}.php                # Main config file
├── routes/                        # Route definitions (if separate files)
│   ├── admin.php                  # Admin routes
│   └── client.php                 # Client routes
├── composer.json                  # ✅ REQUIRED - PSR-4 autoloading
└── README.md                      # Installation/usage docs
```

---

## 🔨 **IMPLEMENTATION REQUIREMENTS**

### **1. Service Provider (REQUIRED)**
**Location**: `src/Providers/{Addon}ServiceProvider.php`

```php
<?php

namespace PterodactylAddons\{AddonName}\Providers;

use IlluminateupporterviceProvider;

class {Addon}ServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register commands if running in console
        if ($this->app->runningInConsole()) {
            $this->commands([
                \PterodactylAddons\{AddonName}\Commands\{Addon}InstallCommand::class,
                \PterodactylAddons\{AddonName}\Commands\{Addon}UninstallCommand::class,
            ]);
        }
    }

    public function boot()
    {
        // Load migrations from addon directory
        $this->loadMigrationsFrom(base_path('addons/{addon-name}/database/migrations'));
        
        // Load views from addon directory  
        $this->loadViewsFrom(base_path('addons/{addon-name}/resources/views'), '{addon}');
        
        // Register routes (if using separate route files)
        $this->loadRoutesFrom(base_path('addons/{addon-name}/routes/admin.php'));
        $this->loadRoutesFrom(base_path('addons/{addon-name}/routes/client.php'));
    }
}
```

### **2. Install Command (REQUIRED)**
**Location**: `src/Commands/{Addon}InstallCommand.php`

**Must Include:**
- ✅ Verification of addon structure
- ✅ Service provider registration in `config/app.php`
- ✅ Database migrations
- ✅ Configuration publishing
- ✅ Default data seeding
- ✅ Cache clearing
- ✅ Success/failure feedback
- ✅ Force option for reinstallation
- ✅ Prerequisites validation

### **3. Uninstall Command (REQUIRED)**
**Location**: `src/Commands/{Addon}UninstallCommand.php`

**Must Include:**
- ✅ Remove service provider from `config/app.php`
- ✅ Rollback database migrations (with confirmation)
- ✅ Remove published configuration files
- ✅ Clear caches
- ✅ Confirmation prompts for destructive actions
- ✅ Option to preserve user data
- ✅ Complete cleanup verification

### **4. Composer Configuration (REQUIRED)**
**Location**: `composer.json`

```json
{
    "name": "pterodactyl-addons/{addon-name}",
    "description": "Description of your addon",
    "type": "library",
    "license": "MIT",
    "autoload": {
        "psr-4": {
            "PterodactylAddons\{AddonName}": "src/"
        }
    },
    "extra": {
        "pterodactyl": {
            "minimum_version": "1.11.0",
            "maximum_version": "1.99.99"
        }
    }
}
```

---

## ⚠️ **CRITICAL DO's AND DON'Ts**

### **🚫 NEVER DO THESE:**

#### **File Placement**
- ❌ **NEVER** create files in `app/Http/Controllers/` - Use `addons/{addon}/src/Http/Controllers/`
- ❌ **NEVER** create files in `app/Models/` - Use `addons/{addon}/src/Models/`
- ❌ **NEVER** create files in `app/Services/` - Use `addons/{addon}/src/Services/`
- ❌ **NEVER** modify core Pterodactyl files directly
- ❌ **NEVER** create files in `database/migrations/` - Use `addons/{addon}/database/migrations/`

#### **Namespace Usage**
- ❌ **NEVER** use `Pterodactyl` namespace for addon classes
- ❌ **NEVER** extend core Pterodactyl classes unless absolutely necessary
- ❌ **NEVER** modify core database tables directly

#### **Dependencies**
- ❌ **NEVER** assume other addons are installed
- ❌ **NEVER** create hard dependencies on specific Pterodactyl versions without testing
- ❌ **NEVER** override core functionality without clear documentation

### **✅ ALWAYS DO THESE:**

#### **File Organization**
- ✅ **ALWAYS** keep everything in `addons/{addon-name}/` directory
- ✅ **ALWAYS** use proper PSR-4 autoloading with `PterodactylAddons\{AddonName}` namespace
- ✅ **ALWAYS** register service provider in main composer autoload

#### **User Experience**
- ✅ **ALWAYS** provide install/uninstall commands
- ✅ **ALWAYS** include progress feedback during installation
- ✅ **ALWAYS** validate prerequisites before installation
- ✅ **ALWAYS** provide clear error messages
- ✅ **ALWAYS** include comprehensive README documentation

#### **Data Safety**
- ✅ **ALWAYS** ask for confirmation before destructive actions
- ✅ **ALWAYS** provide data backup options
- ✅ **ALWAYS** test install/uninstall cycles thoroughly
- ✅ **ALWAYS** handle existing installations gracefully

---

## 🔧 **INTEGRATION GUIDELINES**

### **Service Provider Registration**
**Location**: `config/app.php` (only this minimal change to core)

```php
// Add to providers array
PterodactylAddons\{AddonName}\Providers\{Addon}ServiceProvider::class,
```

### **Route Integration**
- ✅ **DO**: Define routes in addon service provider or separate route files
- ✅ **DO**: Use proper middleware for admin/client separation
- ❌ **DON'T**: Modify main route files

### **Database Integration**
- ✅ **DO**: Use migrations in addon directory
- ✅ **DO**: Follow Laravel migration conventions
- ✅ **DO**: Create foreign key relationships properly
- ❌ **DON'T**: Modify existing core tables without extreme caution

---

## 🧪 **TESTING REQUIREMENTS**

### **Installation Testing**
- ✅ Test fresh installation
- ✅ Test reinstallation with `--force`
- ✅ Test installation failure scenarios
- ✅ Verify all files are created correctly
- ✅ Confirm all routes are accessible

### **Functionality Testing**
- ✅ Test all addon features work independently
- ✅ Verify integration with Pterodactyl auth system
- ✅ Test admin and client interfaces separately
- ✅ Validate database operations

### **Uninstallation Testing**  
- ✅ Test complete uninstallation
- ✅ Verify no orphaned files remain
- ✅ Confirm database is properly cleaned
- ✅ Test Pterodactyl still works after uninstall

---

## 📋 **DEVELOPMENT CHECKLIST**

### **Pre-Development**
- [ ] Plan addon structure following standard layout
- [ ] Define clear namespace: `PterodactylAddons\{AddonName}`
- [ ] Create `addons/{addon-name}/` directory
- [ ] Set up `composer.json` with proper PSR-4 autoloading

### **Core Implementation**
- [ ] Create service provider with command registration
- [ ] Implement install command with all required features
- [ ] Implement uninstall command with cleanup
- [ ] Create models with proper namespace
- [ ] Implement controllers (if needed)
- [ ] Create database migrations
- [ ] Design frontend templates (if needed)

### **Integration**
- [ ] Register service provider in `config/app.php`
- [ ] Test autoloading works correctly
- [ ] Verify routes are accessible
- [ ] Confirm database migrations run properly

### **Quality Assurance**
- [ ] Test complete install/uninstall cycle
- [ ] Verify no core files are modified
- [ ] Test addon functionality thoroughly
- [ ] Create comprehensive documentation
- [ ] Test on clean Pterodactyl installation

### **Documentation**
- [ ] Create clear README with installation steps
- [ ] Document all configuration options
- [ ] Provide troubleshooting guide
- [ ] Include usage examples

---

## 🚀 **DEPLOYMENT BEST PRACTICES**

### **Distribution**
- ✅ Package as complete `addons/{addon-name}/` directory
- ✅ Include installation instructions
- ✅ Provide version compatibility information
- ✅ Include changelog for updates

### **Updates**
- ✅ Provide update commands if needed
- ✅ Handle database schema changes gracefully
- ✅ Maintain backward compatibility when possible
- ✅ Test upgrade paths thoroughly

### **Support**
- ✅ Provide clear troubleshooting documentation
- ✅ Include common error solutions
- ✅ Maintain compatibility matrix
- ✅ Offer rollback procedures

---

## 🎯 **SUCCESS CRITERIA**

An addon is considered properly implemented when:

1. **✅ Self-Contained**: Everything in `addons/` directory with proper namespace
2. **✅ Install/Uninstall**: Commands work flawlessly and clean up properly  
3. **✅ User-Friendly**: Clear output, error handling, and documentation
4. **✅ Tested**: Installation, functionality, and uninstallation all verified
5. **✅ Compatible**: Works with target Pterodactyl versions
6. **✅ Clean**: No modifications to core files or contamination
7. **✅ Documented**: Comprehensive README and usage instructions

---

## 🔍 **EXAMPLE: SHOP SYSTEM ADDON**

Reference implementation: `addons/shop-system/`
- ✅ Fully self-contained structure
- ✅ Working install/uninstall commands  
- ✅ Proper PSR-4 namespace: `PterodactylAddons\ShopSystem`
- ✅ 40+ routes, 10+ controllers, 8+ models
- ✅ Complete MVC architecture
- ✅ Database migrations and seeders
- ✅ Admin and client interfaces

Use this as a reference for proper addon development practices.

---

*Follow these guidelines to create maintainable, user-friendly, and professional Pterodactyl Panel addons that integrate seamlessly while remaining completely self-contained.*