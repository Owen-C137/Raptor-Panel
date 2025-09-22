# Raptor Panel Release & Update Instructions

This document provides step-by-step instructions for creating new releases and testing the auto-update system based on the v1.3.9 release process.

## Prerequisites

### GitHub Token Setup
1. **Create Personal Access Token:**
   - Visit: https://github.com/settings/tokens
   - Click "Generate new token" → "Generate new token (classic)"
   - Configure:
     - **Note**: "Raptor Panel Release Management"
     - **Expiration**: 30+ days
     - **Scopes**: 
       - ✅ `repo` (Full control of private repositories)
       - ✅ `write:packages` (optional, for packages)

2. **Add Token to Environment:**
   ```bash
   # Add to .env file
   GITHUB_TOKEN=ghp_your_token_here
   
   # Or export directly in terminal
   export GITHUB_TOKEN="ghp_your_token_here"
   ```

## Complete Release Process

### Step 1: Update Version Number
```bash
# Navigate to project directory
cd /var/www/raptorpanel_dev

# Update config/app.php version
# Change: 'version' => env('APP_VERSION', '1.3.8'),
# To:     'version' => env('APP_VERSION', '1.3.9'),

# Update database version
php artisan tinker --execute="
use Pterodactyl\Helpers\VersionHelper;
VersionHelper::setCurrentVersion('1.3.9');
echo 'Version updated to: ' . VersionHelper::getCurrentVersion() . '\n';
"
```

### Step 2: Update Changelog
Edit `CHANGELOG.md` and add new version section at the top:

```markdown
## v1.3.9 - 2025-MM-DD

### 🔧 Brief Description of Changes

#### Added
- New feature descriptions
- Enhancement details

#### Fixed
- Bug fix descriptions
- Issue resolutions

#### Enhanced
- Improvement descriptions
- Performance updates

#### Technical Details
- Implementation specifics
- System improvements
```

### Step 3: Commit and Push Changes
```bash
# Stage all changes
git add -A

# Commit with descriptive message
git commit -m "🔧 v1.3.9: Brief Description of Main Changes

🐛 Key Fixes:
- Fix description 1
- Fix description 2

✅ Enhancements:
- Enhancement description 1
- Enhancement description 2

📱 Technical Improvements:
- Technical change 1
- Technical change 2

Ready for production testing: previous_version → 1.3.9 update flow verified"

# Push to GitHub
git push origin main
```

### Step 4: Create Git Tag and GitHub Release
```bash
# Create and push git tag
git tag v1.3.9
git push origin v1.3.9

# Ensure GitHub token is available
export GITHUB_TOKEN="ghp_your_token_here"

# Create GitHub release via API
curl -X POST \
  -H "Accept: application/vnd.github+json" \
  -H "Authorization: Bearer $GITHUB_TOKEN" \
  -H "X-GitHub-Api-Version: 2022-11-28" \
  https://api.github.com/repos/Owen-C137/Raptor-Panel/releases \
  -d '{
  "tag_name": "v1.3.9",
  "target_commitish": "main",
  "name": "v1.3.9 - Brief Description",
  "body": "# 🔧 v1.3.9: Detailed Release Notes\n\n## 🐛 Fixed\n\n### Category 1\n- **Description**: Details about fixes\n- **Impact**: What this solves\n\n## 🔍 Enhanced\n\n### Category 2\n- **Description**: Details about enhancements\n- **Benefit**: What this improves\n\n## 🚀 Ready for Production\n\nThis release includes:\n\n- ✅ **Component 1**: Status and details\n- ✅ **Component 2**: Status and details\n\n**Testing**: Update from v1.3.8 → v1.3.9 ready for testing.",
  "draft": false,
  "prerelease": false,
  "generate_release_notes": false
}'
```

### Step 5: Prepare for Testing
```bash
# Downgrade local version for testing
php artisan tinker --execute="
use Pterodactyl\Helpers\VersionHelper;
VersionHelper::setCurrentVersion('1.3.8');
echo 'Local version downgraded to: ' . VersionHelper::getCurrentVersion() . ' for testing\n';
"

# Ensure development server is running
php artisan serve --port=8000
```

## Testing Update System

### Pre-Testing Verification
```bash
# Verify GitHub API connection
php artisan tinker --execute="
use Pterodactyl\Services\Updates\GitHub\GitHubReleaseService;
\$service = app(GitHubReleaseService::class);
\$connected = \$service->testConnection();
echo \$connected ? '✓ GitHub API connected' : '✗ GitHub API failed';
echo '\n';
"

# Check for available updates
php artisan tinker --execute="
use Pterodactyl\Services\Updates\GitHub\GitHubReleaseService;
use Pterodactyl\Helpers\VersionHelper;
\$service = app(GitHubReleaseService::class);
\$current = VersionHelper::getCurrentVersion();
\$updates = \$service->getAvailableUpdates(\$current);
echo 'Current: ' . \$current . '\n';
echo 'Available updates: ' . count(\$updates) . '\n';
foreach (\$updates as \$update) {
    echo '  - ' . \$update['tag_name'] . '\n';
}
"
```

### Live Update Testing Process

1. **Access Update Interface:**
   - Navigate to: http://localhost:8000/admin/updates
   - Click "Check for Updates"
   - Verify new version is detected

2. **Test Update Flow:**
   - Click "Update Now" for the new version
   - Verify professional OneUI confirmation page loads
   - Review system health checks and release notes
   - Click "Start Update Now"
   - Confirm the update process begins

3. **Monitor Progress:**
   - Watch live console output for progress messages
   - Verify progress bar updates in real-time
   - Check session ID is displayed correctly
   - Monitor for any error messages

4. **Expected Console Output:**
   ```
   [INFO] Update system initialized
   [INFO] Session ID: [uuid]
   [INFO] Waiting for user confirmation...
   [timestamp] User confirmed update to version 1.3.9
   [timestamp] Update session created: [session-id]
   [timestamp] Starting progress monitoring...
   ```

### Troubleshooting Common Issues

#### AJAX Request Issues
```bash
# Check Laravel logs for AJAX errors
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep -i error
```

#### Session Management Issues
```bash
# Clean up stuck sessions
php artisan tinker --execute="
use Pterodactyl\Models\Updates\UpdateSession;
\$stuck = UpdateSession::whereIn('status', ['pending', 'in_progress'])->get();
foreach (\$stuck as \$session) {
    \$session->update(['status' => 'completed', 'completed_at' => now()]);
    echo 'Cleaned session: ' . \$session->session_id . '\n';
}
"
```

#### Version Detection Issues
```bash
# Verify version helper is working
php artisan tinker --execute="
use Pterodactyl\Helpers\VersionHelper;
echo 'Config version: ' . config('app.version') . '\n';
echo 'Database version: ' . VersionHelper::getCurrentVersion() . '\n';
"
```

## Post-Release Checklist

- [ ] GitHub release created and published
- [ ] Tag pushed to repository
- [ ] Changelog updated with detailed notes
- [ ] Local testing completed successfully
- [ ] Update system verified working
- [ ] Production deployment ready
- [ ] Documentation updated if needed

## Directory Structure
```
.github/
└── instructions/
    └── auto_update_instructions.instructions.md  # This file

app/
├── Http/Controllers/Admin/Updates/
│   ├── UpdateController.php                      # Main update logic
│   └── UpdateDashboardController.php             # UI and confirmation
├── Services/Updates/                              # Update services
├── Helpers/
│   └── VersionHelper.php                         # Version management
└── Models/Updates/                                # Update models

resources/views/admin/updates/
└── confirm-update.blade.php                      # Professional UI

config/
└── app.php                                        # Version configuration

storage/logs/                                      # Debug logs
CHANGELOG.md                                       # Release notes
```

## Security Notes

- **Never commit GitHub tokens** to version control
- Keep tokens in `.env` files that are `.gitignored`
- Use tokens with minimal required permissions
- Rotate tokens regularly (every 30-90 days)
- Monitor token usage in GitHub settings

## Emergency Procedures

### Rollback Release
```bash
# Delete release and tag if needed
curl -X DELETE \
  -H "Authorization: Bearer $GITHUB_TOKEN" \
  https://api.github.com/repos/Owen-C137/Raptor-Panel/releases/[release_id]

git tag -d v1.3.9
git push origin :refs/tags/v1.3.9
```

### Force Update Reset
```bash
# Emergency session cleanup
php artisan tinker --execute="
use Pterodactyl\Models\Updates\UpdateSession;
UpdateSession::whereIn('status', ['pending', 'in_progress', 'paused'])->update([
    'status' => 'failed',
    'error_message' => 'Emergency reset',
    'completed_at' => now()
]);
echo 'All active sessions reset\n';
"
```

## Known Issues & Solutions

### AJAX Request Handling
The UpdateController requires proper AJAX detection. Ensure:
- `expectsJson()` or `ajax()` returns true
- Proper JSON responses are returned (not redirects)
- Frontend sends `dataType: 'json'` in AJAX requests

### Boolean Validation
Form submissions send string values. The controller uses:
```php
$createBackup = filter_var($request->get('create_backup', true), FILTER_VALIDATE_BOOLEAN);
$force = filter_var($request->get('force', false), FILTER_VALIDATE_BOOLEAN);
```

### Progress Polling
The frontend polls every 2 seconds for progress updates:
```javascript
progressInterval = setInterval(function() {
    // Poll admin.updates.api.progress route
}, 2000);
```

---

**Last Updated**: 2025-09-22  
**Version**: 2.0  
**Tested With**: Raptor Panel v1.3.9
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

#### Primary Method: Create Git Tag (Recommended)
This is the simplest and most reliable way to create releases:

```bash
# Create annotated git tag with release notes
git tag -a v1.3.3 -m "Release v1.3.3: Brief Description

Detailed release notes and changelog content here
- Feature 1: Description
- Feature 2: Description  
- Bug fixes and improvements"

# Push the tag to GitHub
git push origin v1.3.3
```

Once the tag is pushed, you can create the full GitHub release from the tag on GitHub:
1. Go to: https://github.com/Owen-C137/Raptor-Panel/releases
2. Find your tag and click "Create release from tag"
3. Add additional release notes if needed
4. Click "Publish release"

#### Alternative: Manual Creation
1. Go to GitHub repository: https://github.com/Owen-C137/Raptor-Panel
2. Click "Releases" tab
3. Click "Create a new release"
4. Set tag version: `v1.3.3` (must match config/app.php)
5. Set release title: `v1.3.3 - Brief Description`
6. Copy changelog content to release description
7. Click "Publish release"

#### Advanced: GitHub API
```bash
# Create GitHub release using API (requires valid token)
curl -X POST \
  -H "Authorization: token YOUR_GITHUB_TOKEN" \
  -H "Accept: application/vnd.github.v3+json" \
  https://api.github.com/repos/Owen-C137/Raptor-Panel/releases \
  -d '{
    "tag_name": "v1.3.3",
    "target_commitish": "main",
    "name": "v1.3.3 - Brief Description",
    "body": "Changelog content here",
    "draft": false,
    "prerelease": false
  }'
```

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
- [ ] Create git tag: `git tag -a vX.X.X -m "Release message"`
- [ ] Push tag: `git push origin vX.X.X`
- [ ] Create GitHub release from tag (optional for additional notes)
- [ ] Test update process in production
- [ ] Monitor for issues post-update

### Emergency Rollback
1. Navigate to `/admin/updates/history`
2. Find the failed update session
3. Click "Rollback" button
4. Confirm rollback process
5. Monitor rollback completion

This system provides a robust, reliable, and user-friendly update experience while maintaining the flexibility needed for future enhancements! 🚀
