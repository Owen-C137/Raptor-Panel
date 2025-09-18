---
applyTo: '**'
---

# Auto-Update System Instructions

## System Overview
Raptor Panel includes a cu### 2. GitHub API Endpoints Used
```php
// Check version
'https://raw.githubusercontent.com/Owen-C137/Raptor-Panel/main/config/app.php'

// Download files  
'https://raw.githubusercontent.com/Owen-C137/Raptor-Panel/main/{file_path}'

// Get changelog
'https://raw.githubusercontent.com/Owen-C137/Raptor-Panel/main/CHANGELOG.md'

// Get commit info (for "what's new")
'https://api.github.com/repos/Owen-C137/Raptor-Panel/commits?since={last_update_date}'
```te system that allows users to update their panel directly from the admin interface by pulling updates from our GitHub repository (Owen-C137/Raptor-Panel).

## How The Update System Works

### 1. Version Detection
- **Local Version**: Stored in `config/app.php` as `'version' => 'x.x.x'`
- **Remote Version**: Fetched directly from GitHub repo's `config/app.php`
- **Comparison**: System compares versions and shows update notifications

### 2. File Management System
- **Manifest File**: Generated automatically by comparing repo files with local files
- **Direct Download**: Files downloaded directly from GitHub's raw file API
- **Selective Updates**: Excludes sensitive files (configs, uploads, logs)  
- **Backup System**: Creates backup before applying updates

### 3. Update Process Flow
```
1. Check repo's config/app.php for latest version
2. If newer version found, scan repo files vs local files
3. Generate list of changed/new files to download
4. Create backup of current files that will be updated
5. Download changed files from GitHub raw API
6. Update local version number
7. Run any post-update scripts (cache clear, etc.)
8. Show success notification with changelog
```

## 🚨 CRITICAL RELEASE WORKFLOW 🚨

### For You (Developer):

#### 1. 🔴 IMPORTANT: Update Version Number (MUST DO!)
```php
// In config/app.php - THIS IS MANDATORY AND TRIGGERS THE UPDATE CHECK
'version' => '1.2.3', // ⚠️ UPDATE THIS FIRST - REQUIRED FOR EVERY RELEASE!
```
**⚠️ WARNING: If you don't update this, users won't see the update notification!**

#### 2. 🟡 IMPORTANT: Create Changelog Entry (HIGHLY RECOMMENDED!)
Create or update `CHANGELOG.md` in your repo root:
```markdown
## v1.2.3 - 2025-09-18
### Added
- New syntax highlighting on node configuration page
- Copy-to-clipboard functionality

### Fixed  
- Layout issues in settings page
- JavaScript loading errors

### Changed
- Moved General Configuration block position
```
**📝 Note: If no CHANGELOG.md exists, the system will auto-generate from commit messages**

#### 3. Commit and Push
```bash
git add .
git commit -m "Release v1.2.3 - Enhanced node configuration UI"
git push origin main
```

#### 4. That's It!
No zip files, no uploads, no manual release creation needed!

### For Users:

1. **See update notification** in admin dashboard
2. **Click "View Update"** to see changelog and details
3. **Click "Update Now"** for one-click installation
4. **Automatic backup creation** and progress tracking
5. **Enjoy the new features!**

### 4. How Users See Updates

**Dashboard Notification:**
```
🔄 Update Available!
Version 1.2.3 is now available (you're running 1.2.2)

New Features:
• Enhanced node configuration page
• Improved copy-to-clipboard functionality
• Better layout organization

[View Full Changelog] [Update Now]
```

## System Components

### 1. Services
- **CustomUpdateService**: Checks GitHub repo directly via API
- **GitHubFileService**: Downloads files from raw.githubusercontent.com
- **ChangelogService**: Fetches and parses CHANGELOG.md
- **BackupService**: Creates and manages file backups

### 2. GitHub API Endpoints Used
```php
// Check version
'https://raw.githubusercontent.com/Owen-C137/pt-addons-overhaul/main/config/app.php'

// Download files  
'https://raw.githubusercontent.com/Owen-C137/pt-addons-overhaul/main/{file_path}'

// Get changelog
'https://raw.githubusercontent.com/Owen-C137/pt-addons-overhaul/main/CHANGELOG.md'

// Get commit info (for "what's new")
'https://api.github.com/repos/Owen-C137/pt-addons-overhaul/commits?since={last_update_date}'
```

### 3. File Comparison Logic
```php
// Compare local file hash with remote file
$localHash = hash_file('sha256', $localFile);
$remoteContent = file_get_contents($githubRawUrl);
$remoteHash = hash('sha256', $remoteContent);

if ($localHash !== $remoteHash) {
    // File needs updating
    $filesToUpdate[] = $filePath;
}
```

### 2. Commands
- **update:check**: Check for available updates
- **update:generate-manifest**: Generate manifest.json for releases
- **update:apply**: Apply pending updates

### 3. Controllers
- **UpdateController**: Handles admin interface interactions
- **API endpoints**: For AJAX update progress

### 4. Views
- **Admin dashboard integration**: Update notifications
- **Update modal**: Progress indicators and controls

## Configuration

### GitHub Repository Settings
```php
// In config/app.php - Add new update configuration
'update_source' => [
    'github_owner' => 'Owen-C137',
    'github_repo' => 'Raptor-Panel',
    'branch' => 'main', // or 'release' branch if you want
    'api_base' => 'https://api.github.com/repos/Owen-C137/Raptor-Panel',
    'raw_base' => 'https://raw.githubusercontent.com/Owen-C137/Raptor-Panel/main',
],
```

### Update Behavior
```php
'update_settings' => [
    'check_interval' => 24, // hours between automatic checks
    'auto_backup' => true,
    'require_confirmation' => true,
    'show_changelog' => true,
    'excluded_paths' => [
        'storage/',
        '.env*',
        'config/database.php',
        'bootstrap/cache/',
        '*.log'
    ]
],
```

### Files That Will Be Updated
```php
'updatable_paths' => [
    'app/',                    // All application code
    'resources/',             // Views, assets, etc.
    'routes/',                // Routes
    'config/app.php',         // Version updates only
    'public/themes/',         // Theme files  
    'addons/',                // Addon files
    'database/migrations/',   // New migrations only
],
```

## Developer Workflow (Super Simple!)

### Your Release Process:
1. **Code your changes** normally
2. **Update version** in `config/app.php`: `'version' => '1.2.3'`
3. **Update CHANGELOG.md** (optional but recommended)
4. **Commit and push**: 
   ```bash
   git add .
   git commit -m "v1.2.3 - Enhanced UI and bug fixes"
   git push origin main
   ```
5. **Done!** Users will get update notifications automatically

### What Users See:
- **Automatic notification** in admin dashboard
- **Changelog preview** with your CHANGELOG.md content  
- **"What's New" summary** generated from commits
- **One-click update** with progress bar
- **Automatic backup** before update
- **Rollback option** if something goes wrong

## Security Considerations

1. **Checksum Verification**: All downloaded files verified against SHA256 checksums
2. **HTTPS Only**: All API calls and downloads use HTTPS
3. **Backup Before Update**: Always create backup before applying changes
4. **Admin Permission**: Only root admin users can trigger updates
5. **File Exclusions**: Never update sensitive configuration files

## Troubleshooting

### Common Issues:
1. **GitHub API Rate Limit**: Cache responses for 1 hour
2. **File Permission Errors**: Ensure web server has write permissions
3. **Network Failures**: Implement retry logic with exponential backoff
4. **Partial Updates**: Transaction-like behavior - rollback on failure

### Recovery:
- **Rollback Command**: `php artisan update:rollback`
- **Manual Recovery**: Restore from backup directory
- **Safe Mode**: Continue running on old version if update fails

## Development Workflow

### Before Each Commit:
1. Test all changes thoroughly
2. Update version in config/app.php if releasing
3. Document changes in CHANGELOG.md

### Before Each Release:
1. Generate new manifest.json
2. Test update process on development server
3. Create GitHub release with proper versioning
4. Verify manifest.json is included in release assets

### Testing Updates:
1. Set up test environment
2. Install previous version
3. Trigger update to new version
4. Verify all files updated correctly
5. Test rollback functionality

## AI Assistant Instructions

**When working on this project, always:**
1. Check this file first for update system context
2. Follow the versioning and release workflow
3. Never modify excluded files during updates
4. Ensure new features integrate with the update system
5. Update this documentation if changing the update system
6. Test any changes that might affect the update process

**Remember:**
- Users depend on this system for easy updates
- Failed updates can break installations
- Always prioritize backward compatibility
- Include proper error handling and recovery options