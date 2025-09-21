# Raptor Panel Auto-Update System Instructions

## 🚀 Overview

The Raptor Panel auto-update system has been completely redesigned as of v1.3.0 to provide a simple, reliable, and maintainable update process. The system uses GitHub Releases API for 100% reliability and downloads complete release archives for perfect file integrity.

## 📋 System Architecture

### Key Components
- **GitHub Releases Integration** - Direct API integration with GitHub's official releases
- **Archive Download System** - Downloads complete release archives (.zip files)
- **Automatic Backup Creation** - Creates comprehensive backups before updates
- **Session Management** - Tracks update progress and allows rollback
- **Bootstrap 5/OneUI Interface** - Modern, responsive admin interface

### Core Services
- `GitHubReleaseService` - Handles GitHub API integration and release management
- `SessionService` - Manages update session lifecycle and state tracking
- `VersionService` - Handles version detection and comparison
- `SystemHealthService` - Monitors system health and update prerequisites

## 🔄 Release Process Workflow

### 1. Prepare New Release

#### Update Version Number
```php
// File: config/app.php
'version' => '1.3.3', // Increment version
```

#### Update Changelog
```markdown
// File: CHANGELOG.md
## v1.3.3 - 2025-09-21

### 🎯 New Features
- **Feature Name** - Description of what was added

### 🔧 Enhancements
- **Component** - What was improved

### 🐛 Bug Fixes
- **Issue** - What was fixed

### 📋 Technical Changes
- **System** - Technical improvements made
```

#### Files to Always Update for New Release
1. **config/app.php** - Version number
2. **CHANGELOG.md** - Release notes and changelog
3. **Any feature files** - New functionality or bug fixes

### 2. Commit and Push Changes
```bash
git add .
git commit -m "Release v1.3.3: [Brief description of changes]"
git push origin main
```

### 3. Create GitHub Release
1. Go to GitHub repository: https://github.com/Owen-C137/Raptor-Panel
2. Click "Releases" tab
3. Click "Create a new release"
4. Set tag version: `v1.3.3` (must match config/app.php)
5. Set release title: `v1.3.3 - Brief Description`
6. Copy changelog content to release description
7. Click "Publish release"

**⚠️ CRITICAL: The GitHub release tag MUST exactly match the version in config/app.php**

## 🎯 Update System Usage

### Production Deployment Process

#### Step 1: Deploy Current Stable Version to Production
```bash
# On production server (raptorpanel_main)
cd /var/www/raptorpanel_main
git pull origin main
```

#### Step 2: Create Test Update in Development
```bash
# In development (raptorpanel_dev)
# 1. Add test features/changes
# 2. Update version number
# 3. Update changelog
# 4. Commit and push
# 5. Create GitHub release
```

#### Step 3: Test Update Process
1. Navigate to production admin panel: `/admin/updates`
2. Click "Check for Updates"
3. Review available update information
4. Click "Download & Install Update"
5. Monitor progress through the interface
6. Verify successful update completion

### Admin Interface Navigation
- **Dashboard**: `/admin/updates` - Main update status and controls
- **Management**: `/admin/updates/manage` - Update execution and control
- **History**: `/admin/updates/history` - Past update sessions and logs
- **Health**: `/admin/updates/health` - System health monitoring

## ⚙️ Technical Details

### GitHub API Integration
The system uses these GitHub API endpoints:
- `GET /repos/Owen-C137/Raptor-Panel/releases/latest` - Get latest release info
- `GET /repos/Owen-C137/Raptor-Panel/zipball/{tag}` - Download release archive

### Update Process Flow
1. **Check** - Query GitHub API for latest release
2. **Compare** - Compare remote version with local version
3. **Download** - Download complete release archive (.zip)
4. **Backup** - Create automatic backup of current installation
5. **Extract** - Extract new files to temporary directory
6. **Apply** - Replace old files with new files
7. **Cleanup** - Clear caches, remove temporary files
8. **Verify** - Confirm successful update

### File Exclusions
The system automatically excludes these patterns during updates:
- `.git/` - Git repository data
- `.env` - Environment configuration
- `storage/` - User data and logs
- `bootstrap/cache/` - Laravel cache files
- `node_modules/` - Node.js dependencies
- `vendor/` - Composer dependencies (managed separately)

### Configuration Files
```php
// config/app.php - Update system configuration
'update_source' => [
    'github_owner' => 'Owen-C137',
    'github_repo' => 'Raptor-Panel',
    'branch' => 'main',
    'api_base' => 'https://api.github.com/repos/Owen-C137/Raptor-Panel',
    'raw_base' => 'https://raw.githubusercontent.com/Owen-C137/Raptor-Panel/main',
],

'update_settings' => [
    'check_interval' => 24, // hours between automatic checks
    'auto_backup' => true,
    'require_confirmation' => true,
    'show_changelog' => true,
],
```

## 🛡️ Safety Features

### Automatic Backups
- Created before every update
- Stored in `storage/app/backups/`
- Include complete application files
- Enable rollback if update fails

### Session Management
- Each update creates a tracked session
- Progress monitoring throughout process
- Complete logs for debugging
- Rollback capability for failed updates

### Health Checks
- Verify system prerequisites before updates
- Check file permissions
- Validate configuration
- Monitor system resources

## 🔧 Troubleshooting

### Common Issues

#### Update Not Detected
- Verify GitHub release tag matches config/app.php version exactly
- Check internet connectivity to GitHub API
- Clear application cache: `php artisan cache:clear`

#### Update Fails During Download
- Check file permissions on storage directories
- Verify sufficient disk space
- Check GitHub API rate limits

#### Update Fails During Application
- Review session logs in admin interface
- Check file permissions on application directories
- Verify backup was created successfully

### Debug Information
- Update logs: `/admin/updates/history`
- System health: `/admin/updates/health`
- Session details: Available in history interface
- Laravel logs: `storage/logs/laravel.log`

## 📊 Monitoring and Maintenance

### Regular Tasks
1. **Monitor Updates** - Check for available updates weekly
2. **Review Logs** - Check update history and logs monthly
3. **Test Backups** - Verify backup creation and restoration quarterly
4. **System Health** - Monitor system health indicators regularly

### Best Practices
- ✅ Always test updates in development first
- ✅ Create manual backups before major updates
- ✅ Monitor system after updates for issues
- ✅ Keep changelog updated with all changes
- ✅ Use semantic versioning (MAJOR.MINOR.PATCH)
- ✅ Create detailed GitHub release notes

### Version Numbering Guidelines
- **MAJOR** (1.x.x) - Breaking changes, major new features
- **MINOR** (x.3.x) - New features, significant enhancements
- **PATCH** (x.x.2) - Bug fixes, small improvements

## 🎯 Quick Reference

### Release Checklist
- [ ] Update version in `config/app.php`
- [ ] Update `CHANGELOG.md` with release notes
- [ ] Commit and push changes
- [ ] Create GitHub release with matching tag
- [ ] Test update process in production
- [ ] Monitor for issues post-update

### Emergency Rollback
1. Navigate to `/admin/updates/history`
2. Find the failed update session
3. Click "Rollback" button
4. Confirm rollback process
5. Monitor rollback completion

This system provides a robust, reliable, and user-friendly update experience while maintaining the flexibility needed for future enhancements! 🚀
