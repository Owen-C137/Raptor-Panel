# 🚀 **RAPTOR PANEL AUTO UPDATE SYSTEM - RELEASE MANAGEMENT**

**Version:** 1.0.0  
**Date:** September 24, 2025  
**For:** Raptor Panel v1.3.16+  
**License:** MIT

---

## 📋 **OVERVIEW**

This guide covers the complete process for creating new Raptor Panel releases using the enhanced auto update system with database-driven version management, automatic permission handling, and GitHub integration.

---

## 🛠️ **PREREQUISITES**

### **Required Environment Setup**
- ✅ **Database Access**: Working Raptor Panel installation with database access
- ✅ **GitHub Token**: Personal access token in `.env` file as `GITHUB_TOKEN=your_token_here`
- ✅ **Git Configuration**: Properly configured git with push access to repository
- ✅ **PHP Artisan Access**: Command line access to Laravel artisan commands
- ✅ **File Permissions**: Write access to project directory

### **Required Token Permissions**
Your GitHub token needs these permissions:
- `repo` (Full repository access)
- `write:packages` (For release creation)
- `read:org` (Organization read access)

---

## 🔄 **RELEASE PROCESS WORKFLOW**

### **Phase 1: Version Update & Database Management**

#### **Step 1.1: Update Database Version**
```bash
# Update version in database (primary source of truth)
cd /var/www/raptorpanel_dev && php artisan tinker --execute="
\$versionService = app('Pterodactyl\Services\VersionService');
echo 'Current version: ' . \$versionService->getCurrentVersion() . PHP_EOL;
\$versionService->updateVersion('1.3.19');  # Replace with your version
echo 'Updated version: ' . \$versionService->getCurrentVersion() . PHP_EOL;
"
```

**What this does:**
- Updates the `settings` table with new version
- Clears version cache automatically
- Makes version immediately available to admin panels

#### **Step 1.2: Verify Version Update**
```bash
# Confirm version is properly set
php artisan config:clear
php artisan tinker --execute="
echo 'Database Version: ' . app('Pterodactyl\Services\VersionService')->getCurrentVersion() . PHP_EOL;
echo 'Config Version: ' . config('app.version') . PHP_EOL;
"
```

### **Phase 2: Changelog & Documentation**

#### **Step 2.1: Update CHANGELOG.md**
Add comprehensive changelog entry at the top of `CHANGELOG.md`:

```markdown
## [vX.X.X] - YYYY-MM-DD

### 🔧 **Feature Category**
- **Feature Name**: Detailed description of what was added/changed
- **Enhancement**: Description of improvements made
- **Bug Fix**: Description of issues resolved

### 🛡️ **System Improvements**
- **Reliability**: System stability improvements
- **Security**: Security enhancements (if applicable)
- **Performance**: Performance optimizations

### 🎨 **User Experience**
- **UI/UX**: Interface improvements
- **Usability**: User experience enhancements
- **Accessibility**: Accessibility improvements
```

#### **Step 2.2: Document Breaking Changes**
If there are breaking changes, clearly document:
- What changed
- Migration instructions
- Compatibility notes
- Rollback procedures (if applicable)

### **Phase 3: Repository Management**

#### **Step 3.1: Stage and Commit Changes**
```bash
# Add all relevant changes (exclude sensitive files)
git add app/ resources/ database/ config/ CHANGELOG.md

# Create descriptive commit message
git commit -m "vX.X.X: Brief Description of Main Changes

🔧 Main Feature Category
- Key feature or improvement 1
- Key feature or improvement 2
- Key feature or improvement 3

🛡️ System Enhancements
- System improvement 1
- System improvement 2

🎨 User Experience
- UI/UX improvement 1
- User experience enhancement

Detailed technical notes about the release and any
important information for developers or users."
```

#### **Step 3.2: Push to Repository**
```bash
# Push changes to main branch
git push origin main
```

### **Phase 4: Release Creation**

#### **Step 4.1: Create Git Tag**
```bash
# Create annotated tag with comprehensive message
git tag -a vX.X.X -m "vX.X.X: Brief Description

🔧 Main Features
- Feature description 1
- Feature description 2

🛡️ System Improvements  
- System enhancement 1
- System enhancement 2

🎨 User Experience
- UI improvement 1
- UX enhancement 2

This release provides enhanced functionality for [describe main purpose].
All changes are backward compatible and include automatic migration support."
```

#### **Step 4.2: Push Tag to GitHub**
```bash
# Push the tag to trigger release preparation
git push origin vX.X.X
```

#### **Step 4.3: Create Public GitHub Release**
```bash
# Create release using GitHub API with comprehensive release notes
curl -X POST \
  -H "Authorization: token $GITHUB_TOKEN" \
  -H "Accept: application/vnd.github.v3+json" \
  -H "User-Agent: Raptor-Panel-Release-Creator" \
  -d '{
    "tag_name": "vX.X.X",
    "target_commitish": "main",
    "name": "vX.X.X: Brief Description of Main Changes",
    "body": "## 🔧 **Main Feature Category**\n\n- **Feature Name**: Detailed description of what was added or improved\n- **Enhancement**: Description of system improvements\n- **Bug Fix**: Description of issues resolved\n\n## 🛡️ **System Reliability**\n\n- **Stability**: System stability improvements\n- **Performance**: Performance optimizations\n- **Security**: Security enhancements\n\n## 🎨 **User Experience Improvements**\n\n- **Interface**: UI/UX enhancements\n- **Usability**: User experience improvements\n- **Accessibility**: Accessibility enhancements\n\n## 📥 **Installation & Upgrade**\n\n### Fresh Installation\n```bash\ngit clone https://github.com/Owen-C137/Raptor-Panel.git\ncd Raptor-Panel\n# Follow standard Pterodactyl installation process\n```\n\n### Upgrading from Previous Versions\n- Use the built-in update system in Admin Panel\n- Automatic permission management handles all file operations\n- Cache clearing is automatic - no manual intervention required\n- Database migrations run automatically\n\n## ⚠️ **Important Notes**\n\n- **Automatic Updates**: System handles permissions and cache management\n- **Database Migrations**: All migrations run automatically during update\n- **Backup Recommended**: System creates automatic backups before updates\n- **Rollback Support**: Previous version backup available if needed\n\n## 🔗 **What'\''s Next**\n\nUpcoming features and improvements planned for future releases.",
    "draft": false,
    "prerelease": false
  }' \
  https://api.github.com/repos/Owen-C137/Raptor-Panel/releases
```

### **Phase 5: Testing & Verification**

#### **Step 5.1: Test Update Detection**
For testing, temporarily downgrade version and test update detection:

```bash
# Downgrade version for testing (TESTING ONLY)
php artisan tinker --execute="
app('Pterodactyl\Services\VersionService')->updateVersion('1.3.16');
"

# Clear cache and test update detection
php artisan config:clear

# Test update system
php artisan tinker --execute="
\$updateService = app('Pterodactyl\Services\SimpleUpdateService');
\$result = \$updateService->checkForUpdates();
echo 'Current: ' . \$result['current_version'] . PHP_EOL;
echo 'Latest: ' . \$result['latest_version'] . PHP_EOL;
echo 'Available: ' . (\$result['available'] ? 'YES' : 'NO') . PHP_EOL;
"
```

#### **Step 5.2: Verify Release Creation**
- ✅ Check GitHub releases page for new release
- ✅ Verify release notes are properly formatted
- ✅ Confirm download links are working
- ✅ Test update detection in admin panel

---

## 🎯 **QUICK REFERENCE COMMANDS**

### **Complete Release Process (One-Shot)**
```bash
# 1. Update version
php artisan tinker --execute="app('Pterodactyl\Services\VersionService')->updateVersion('X.X.X');"

# 2. Update changelog (manual edit of CHANGELOG.md)

# 3. Commit and push
git add app/ resources/ database/ config/ CHANGELOG.md
git commit -m "vX.X.X: Description"
git push origin main

# 4. Create and push tag  
git tag -a vX.X.X -m "vX.X.X: Description"
git push origin vX.X.X

# 5. Create GitHub release
curl -X POST -H "Authorization: token $GITHUB_TOKEN" \
     -H "Accept: application/vnd.github.v3+json" \
     -d '{"tag_name":"vX.X.X","name":"vX.X.X: Description","body":"Release notes"}' \
     https://api.github.com/repos/Owen-C137/Raptor-Panel/releases
```

### **Version Management Commands**
```bash
# Get current version
php artisan tinker --execute="echo app('Pterodactyl\Services\VersionService')->getCurrentVersion();"

# Update version  
php artisan tinker --execute="app('Pterodactyl\Services\VersionService')->updateVersion('X.X.X');"

# Force refresh version cache
php artisan tinker --execute="app('Pterodactyl\Services\VersionService')->forceRefresh();"

# Test update detection
php artisan tinker --execute="print_r(app('Pterodactyl\Services\SimpleUpdateService')->checkForUpdates());"
```

---

## ⚠️ **IMPORTANT CONSIDERATIONS**

### **Version Management**
- ✅ **Database Primary**: Version stored in database `settings` table as primary source
- ✅ **Auto-Initialize**: New installations automatically get version setting
- ✅ **Cache Management**: Version cache automatically cleared during updates
- ✅ **Config Integration**: AppServiceProvider synchronizes config with database

### **Permission Management**
- ✅ **Automatic Fixing**: Update system automatically fixes file permissions
- ✅ **New File Support**: Handles creation of new files with proper permissions
- ✅ **Error Recovery**: Multiple retry strategies for permission issues
- ✅ **Cross-Platform**: Works on various server configurations

### **Release Best Practices**
- ✅ **Semantic Versioning**: Use proper version numbering (MAJOR.MINOR.PATCH)
- ✅ **Comprehensive Changelogs**: Document all changes clearly
- ✅ **Testing**: Test update process before public release
- ✅ **Backup Strategy**: Ensure automatic backups are working
- ✅ **Rollback Plan**: Have rollback procedures documented

### **Security Considerations**
- ✅ **Token Security**: Keep GitHub tokens secure and rotated regularly  
- ✅ **Permission Validation**: Verify file permissions are secure after updates
- ✅ **Access Control**: Ensure only authorized users can trigger updates
- ✅ **Audit Logging**: All update operations are logged for auditing

---

## 🔧 **TROUBLESHOOTING**

### **Common Issues & Solutions**

#### **Version Not Updating**
```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear

# Force version refresh
php artisan tinker --execute="app('Pterodactyl\Services\VersionService')->forceRefresh();"
```

#### **Permission Errors During Update**
- ✅ Update system automatically handles most permission issues
- ✅ Check web server user (usually www-data) has write access
- ✅ Verify directory permissions are 755 and files are 644

#### **GitHub API Rate Limits**
- ✅ Ensure token has proper permissions
- ✅ Check rate limit status: `curl -H "Authorization: token $GITHUB_TOKEN" https://api.github.com/rate_limit`
- ✅ Wait for rate limit reset if exceeded

#### **Release Creation Failures**
- ✅ Verify GitHub token permissions
- ✅ Check tag exists and is pushed to repository
- ✅ Validate JSON format in API request
- ✅ Review GitHub API response for error details

---

## 📊 **MONITORING & ANALYTICS**

### **Update System Health**
- ✅ Monitor update success/failure rates
- ✅ Track permission issue resolution
- ✅ Review update performance metrics
- ✅ Analyze user adoption of new versions

### **Release Metrics**
- ✅ Download statistics from GitHub releases
- ✅ Update adoption rates across installations
- ✅ Error reporting and resolution tracking
- ✅ User feedback and issue reports

---

*This document is maintained as part of the Raptor Panel release management process. Keep it updated with any changes to the release workflow.*