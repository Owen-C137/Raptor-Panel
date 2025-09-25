# 🔄 **Raptor Panel Auto-Update System**

**Version:** 1.3.34+  
**Last Updated:** September 24, 2025  
**Compatibility:** All Raptor Panel installations v1.3.0+

---

## 📋 **Table of Contents**

1. [Dependencies Required](#-dependencies-required)
2. [System Overview](#-system-overview)
3. [User Guide](#-user-guide)
4. [Developer Documentation](#-developer-documentation)
5. [Technical Implementation](#-technical-implementation)
6. [Configuration](#-configuration)
7. [Troubleshooting](#-troubleshooting)
8. [API Integration](#-api-integration)

---

## 🛠️ **Dependencies Required**

### **System Requirements**
- **PHP**: 8.3+ (for panel compatibility)
- **Extensions**: `curl`, `zip`, `json` (usually included in standard PHP installations)
- **Tools**: `rsync` (for high-performance file transfers)
- **Storage**: Minimum 1GB free space for backups and temporary files

### **Optional Enhancements**
- **GitHub Token**: For increased API rate limits (5,000/hour vs 60/hour)
  - Add `GITHUB_TOKEN=your_token_here` to `.env` file
  - Without token: 60 requests/hour limit
  - With token: 5,000 requests/hour limit

### **Web Server Requirements**
- **Server-Sent Events (SSE)**: Must support streaming responses
- **Output Buffering**: Should allow real-time streaming (most modern setups support this)
- **Nginx Users**: Ensure `proxy_buffering off;` or `X-Accel-Buffering: no` is respected

### **No Additional Dependencies**
✅ Uses existing Laravel/Pterodactyl core dependencies  
✅ No additional Composer packages required  
✅ No database schema changes needed  
✅ Works with existing panel installation

---

## 🎯 **System Overview**

The Raptor Panel Auto-Update System provides seamless, real-time updates directly from GitHub releases with comprehensive backup and rollback capabilities.

### **Key Features**

#### 🚀 **Real-Time Streaming Updates** *(v1.3.34+)*
- **Live Terminal Output**: See each update step as it happens
- **Immediate Feedback**: No more waiting for logs to appear at the end
- **Progress Tracking**: File counts, sizes, and transfer rates in real-time
- **Server-Sent Events**: True streaming implementation with proper buffering control

#### 🔒 **Safety & Reliability**
- **Automatic Backups**: Full system backup before any changes
- **Rollback Capability**: Restore previous version if issues occur
- **Ownership Management**: Automatic file permission and ownership fixes
- **Error Recovery**: Comprehensive error handling and recovery procedures

#### ⚡ **High-Performance Features**
- **rsync File Transfer**: 20x faster than standard copy operations
- **Bulk Operations**: Optimized for large file sets (45,000+ files)
- **Authenticated GitHub API**: 83x higher rate limits with token
- **Smart Caching**: Efficient update checking and version management

### **Update Flow Overview**
```
1. Check GitHub API for latest release
2. Compare with current panel version
3. Display update notification to admin
4. User clicks "Update Now"
5. Real-time streaming begins immediately
6. Create full system backup
7. Download update files from GitHub
8. Extract and verify update contents
9. High-speed rsync file transfer
10. Fix file permissions and ownership
11. Update version in database
12. Clear system caches
13. Complete with success notification
```

---

## 👤 **User Guide**

### **Accessing Updates**

1. **Navigate to Admin Panel**
   ```
   https://your-panel.com/admin/updates
   ```

2. **Check for Updates**
   - Updates are checked automatically every page load
   - Manual check available via "Check for Updates" button
   - Green notification appears when updates are available

### **Performing Updates**

#### **Standard Update Process**
1. **Backup Verification**: Ensure you have recent backups
2. **Maintenance Window**: Plan for 2-3 minutes of update time
3. **Click "Update Now"**: Initiates the real-time streaming update
4. **Watch Progress**: View live terminal output as update progresses
5. **Completion**: Automatic page refresh when update completes

#### **What You'll See During Updates** *(v1.3.34+)*
```
🚀 Update process starting...
📁 Initializing update system...
📂 Fixed recursive ownership for /var/www/panel
⬇️ Downloading from: https://api.github.com/repos/...
📦 Download completed: 242.43 MB
📂 ZIP contains 45,127 files to extract
🔄 Starting bulk file transfer with rsync...
📊 Transfer statistics: sent 254,256,008 bytes
⚡ High-speed file transfer completed successfully
🧹 Clearing application caches...
✅ Update completed successfully!
```

### **Update Safety Features**

#### **Automatic Backups**
- **Location**: `storage/app/backups/updates/`
- **Format**: `backup_v{version}_{timestamp}.zip`
- **Contents**: Complete panel backup before changes
- **Retention**: Backups are preserved for manual management

#### **Rollback Process** *(If Needed)*
1. **Locate Backup**: Check `storage/app/backups/updates/`
2. **Extract Backup**: Unzip to temporary location
3. **Manual Restore**: Copy files back to panel directory
4. **Database Restore**: Update version in settings table if needed

### **Version Information**
- **Current Version**: Displayed on updates page
- **Available Updates**: Shown with release notes and changelog
- **Version History**: Available via GitHub releases page

---

## 💻 **Developer Documentation**

### **Architecture Overview**

```
app/Http/Controllers/Admin/SimpleUpdateController.php
├── index()                 # Display update dashboard
├── checkUpdates()         # AJAX endpoint for update checking
├── performUpdate()        # Standard AJAX update (fallback)
└── performUpdateStream()  # Real-time SSE streaming update

app/Services/SimpleUpdateService.php
├── checkForUpdates()      # GitHub API integration
├── performUpdate()        # Main update orchestration
├── downloadFile()         # Download from GitHub
├── createBackup()         # System backup creation
├── extractUpdate()        # ZIP extraction and verification
├── copyUpdateFiles()      # High-performance rsync transfer
└── Stream Callback System # Real-time progress streaming
```

### **Core Components**

#### **1. GitHub API Integration**
```php
// config/updates.php
'github' => [
    'owner' => 'Owen-C137',
    'repo' => 'Raptor-Panel',
    'api_url' => 'https://api.github.com',
    'token' => env('GITHUB_TOKEN'), // Optional but recommended
]
```

#### **2. Real-Time Streaming System** *(v1.3.34+)*
```php
// Server-Sent Events Implementation
public function performUpdateStream(Request $request): StreamedResponse
{
    return response()->stream(function () use ($downloadUrl) {
        // Disable output buffering for real-time streaming
        if (ob_get_level()) ob_end_clean();
        
        // Set up streaming callback
        $this->updateService->setStreamCallback(function($logEntry) {
            $this->sendSSEData([
                'type' => 'log',
                'message' => $logEntry,
                'timestamp' => date('H:i:s')
            ]);
        });
        
        // Perform update with live streaming
        $result = $this->updateService->performUpdate($downloadUrl);
    }, 200, [
        'Content-Type' => 'text/event-stream',
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
        'X-Accel-Buffering' => 'no'
    ]);
}
```

#### **3. High-Performance File Transfer**
```php
// rsync implementation for 20x speed improvement
private function copyUpdateFiles(string $source, string $target): void
{
    $rsyncCommand = "rsync -av --progress --exclude='storage' --exclude='.env*' --stats '{$source}/' '{$target}/' 2>&1";
    
    $process = popen($rsyncCommand, 'r');
    while (($line = fgets($process)) !== false) {
        // Parse progress and stream to frontend
        $this->parseRsyncProgress($line);
    }
}
```

### **Adding Custom Update Logic**

#### **Pre-Update Hooks**
```php
// In SimpleUpdateService.php
public function performUpdate(string $downloadUrl): array
{
    // Add custom pre-update logic here
    $this->runCustomPreUpdateTasks();
    
    // Standard update process continues...
}
```

#### **Post-Update Hooks**
```php
// Add custom post-update logic
private function runPostUpdateTasks(): void
{
    // Custom cache warming
    Artisan::call('config:cache');
    Artisan::call('route:cache');
    
    // Custom notification systems
    $this->notifyAdminsOfUpdate();
}
```

### **Extending the Update System**

#### **Custom Update Sources**
```php
// Support for additional update sources beyond GitHub
interface UpdateSourceInterface
{
    public function checkForUpdates(): array;
    public function downloadUpdate(string $version): string;
}

class GitHubUpdateSource implements UpdateSourceInterface
{
    // Current GitHub implementation
}

class CustomUpdateSource implements UpdateSourceInterface
{
    // Your custom update source
}
```

---

## ⚙️ **Technical Implementation**

### **File Structure**
```
app/Http/Controllers/Admin/SimpleUpdateController.php  # Main controller
app/Services/SimpleUpdateService.php                   # Core update logic
config/updates.php                                     # Configuration
resources/views/admin/simple-updates/                  # Frontend templates
routes/admin.php                                       # Update routes
storage/app/temp/updates/                             # Temporary files
storage/app/backups/updates/                          # Backup storage
```

### **Database Integration**
```sql
-- Version tracking (uses existing settings table)
SELECT value FROM settings WHERE key = 'app:version';

-- Update version after successful update
UPDATE settings SET value = '1.3.34' WHERE key = 'app:version';
```

### **Routes Configuration**
```php
// routes/admin.php
Route::group(['prefix' => 'updates'], function () {
    Route::get('/', [SimpleUpdateController::class, 'index'])->name('admin.simple-updates.index');
    Route::get('/check', [SimpleUpdateController::class, 'checkUpdates'])->name('admin.simple-updates.check');
    Route::post('/perform', [SimpleUpdateController::class, 'performUpdate'])->name('admin.simple-updates.perform');
    Route::post('/stream', [SimpleUpdateController::class, 'performUpdateStream'])->name('admin.simple-updates.stream');
});
```

### **Frontend JavaScript Implementation**
```javascript
// Real-time streaming with Server-Sent Events
function startUpdateStream(version) {
    fetch('/admin/updates/stream', {
        method: 'POST',
        body: formData,
        headers: { 'Accept': 'text/event-stream' }
    })
    .then(response => {
        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        
        function readStream() {
            return reader.read().then(({ done, value }) => {
                if (done) return;
                
                const chunk = decoder.decode(value, { stream: true });
                const lines = chunk.split('\n');
                
                lines.forEach(line => {
                    if (line.trim().startsWith('data: ')) {
                        const data = JSON.parse(line.substring(6));
                        handleStreamMessage(data);
                    }
                });
                
                return readStream();
            });
        }
        
        return readStream();
    });
}
```

---

## 🔧 **Configuration**

### **Environment Variables**
```bash
# Optional GitHub token for higher API rate limits
GITHUB_TOKEN=ghp_your_token_here

# Update system configuration (optional overrides)
UPDATE_BACKUP_RETENTION=30      # Days to keep backups
UPDATE_TIMEOUT=600              # Update timeout in seconds
UPDATE_TEMP_DIR=storage/app/temp/updates
```

### **Config File** (`config/updates.php`)
```php
return [
    'github' => [
        'owner' => 'Owen-C137',
        'repo' => 'Raptor-Panel',
        'api_url' => 'https://api.github.com',
        'token' => env('GITHUB_TOKEN'),
    ],
    
    'backup' => [
        'enabled' => true,
        'path' => storage_path('app/backups/updates'),
        'retention_days' => 30,
    ],
    
    'performance' => [
        'use_rsync' => true,
        'chunk_size' => 1000,
        'timeout' => 600,
    ],
    
    'streaming' => [
        'enabled' => true,
        'buffer_size' => 8192,
        'delay_ms' => 10,
    ]
];
```

### **Web Server Configuration**

#### **Nginx Configuration**
```nginx
# Ensure streaming responses work properly
location /admin/updates/stream {
    proxy_pass http://backend;
    proxy_buffering off;
    proxy_cache off;
    proxy_set_header X-Accel-Buffering no;
    proxy_read_timeout 600s;
}
```

#### **Apache Configuration**
```apache
# Enable Server-Sent Events
<Location "/admin/updates/stream">
    ProxyPass http://backend/admin/updates/stream
    ProxyPassReverse http://backend/admin/updates/stream
    ProxyPreserveHost On
    SetEnv proxy-nokeepalive 1
    SetEnv proxy-initial-not-pooled 1
</Location>
```

---

## 🚨 **Troubleshooting**

### **Common Issues**

#### **1. Update Not Detected**
**Symptoms**: No update notification appears  
**Causes**: GitHub API rate limiting, network issues  
**Solutions**:
```bash
# Check GitHub API status
curl -I https://api.github.com/repos/Owen-C137/Raptor-Panel/releases/latest

# Add GitHub token to .env for higher rate limits
echo "GITHUB_TOKEN=your_token_here" >> .env

# Clear caches and retry
php artisan cache:clear
php artisan config:clear
```

#### **2. Streaming Not Working** *(Real-time logs not appearing)*
**Symptoms**: Logs appear all at once at the end  
**Causes**: Output buffering, proxy configuration  
**Solutions**:
```bash
# Check PHP output buffering
php -i | grep output_buffering

# For Nginx users - add to location block:
proxy_buffering off;
proxy_set_header X-Accel-Buffering no;

# Clear caches
php artisan cache:clear
```

#### **3. Download Failures**
**Symptoms**: "Failed to download update file"  
**Causes**: Network connectivity, GitHub rate limits, disk space  
**Solutions**:
```bash
# Check disk space
df -h

# Test GitHub connectivity
curl -I https://api.github.com/repos/Owen-C137/Raptor-Panel/zipball/v1.3.34

# Check GitHub rate limits
curl -H "Authorization: token YOUR_TOKEN" -I https://api.github.com/rate_limit
```

#### **4. Permission Issues**
**Symptoms**: "Permission denied" errors during update  
**Causes**: Incorrect file ownership, insufficient permissions  
**Solutions**:
```bash
# Fix ownership (adjust user/group as needed)
sudo chown -R www-data:www-data /var/www/panel

# Fix permissions
sudo chmod -R 755 /var/www/panel
sudo chmod -R 644 /var/www/panel/storage
sudo chmod -R 644 /var/www/panel/.env*
```

#### **5. rsync Not Found**
**Symptoms**: "Failed to start rsync process"  
**Causes**: rsync not installed  
**Solutions**:
```bash
# Ubuntu/Debian
sudo apt-get install rsync

# CentOS/RHEL
sudo yum install rsync

# Alpine Linux
sudo apk add rsync
```

### **Debug Mode**

#### **Enable Detailed Logging**
```php
// In SimpleUpdateService.php
private function log(string $message, string $level = 'info'): void
{
    // Enable debug logging
    if (config('app.debug')) {
        Log::debug("[SimpleUpdate] {$message}");
    }
    
    // Existing logging code...
}
```

#### **Check Laravel Logs**
```bash
# Monitor update process in real-time
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep SimpleUpdate

# Search for specific errors
grep -n "SimpleUpdate.*error" storage/logs/laravel-*.log
```

### **Recovery Procedures**

#### **Manual Rollback**
```bash
# 1. Navigate to panel directory
cd /var/www/panel

# 2. Find latest backup
ls -la storage/app/backups/updates/

# 3. Extract backup (replace with actual backup name)
unzip storage/app/backups/updates/backup_v1.3.33.2_2025_09_24_16_46_36.zip -d /tmp/restore

# 4. Copy files back
rsync -av /tmp/restore/ /var/www/panel/

# 5. Fix permissions
sudo chown -R www-data:www-data /var/www/panel
```

#### **Database Version Correction**
```bash
# Reset database version if needed
php artisan tinker
>>> DB::table('settings')->where('key', 'app:version')->update(['value' => '1.3.33.2']);
>>> exit
```

---

## 🔌 **API Integration**

### **GitHub API Endpoints Used**

#### **1. Latest Release Information**
```http
GET https://api.github.com/repos/Owen-C137/Raptor-Panel/releases/latest

Headers:
- User-Agent: Raptor-Panel/1.3.34
- Accept: application/vnd.github.v3+json
- Authorization: token YOUR_TOKEN (optional but recommended)

Response:
{
    "tag_name": "v1.3.34",
    "name": "v1.3.34: Enhanced Real-Time Streaming Update System",
    "body": "Release notes...",
    "zipball_url": "https://api.github.com/repos/Owen-C137/Raptor-Panel/zipball/v1.3.34",
    "published_at": "2025-09-24T17:02:19Z"
}
```

#### **2. Rate Limit Checking**
```http
GET https://api.github.com/rate_limit

Response:
{
    "rate": {
        "limit": 5000,      # With token: 5000/hour, Without: 60/hour
        "remaining": 4999,
        "reset": 1632847200
    }
}
```

### **API Rate Limits**

| Authentication | Limit | Reset Period |
|----------------|-------|--------------|
| No Token | 60 requests | Per hour |
| With GitHub Token | 5,000 requests | Per hour |
| GitHub App | 15,000 requests | Per hour |

### **Error Handling**
```php
// Handle API rate limits gracefully
private function handleGitHubResponse($response): array
{
    $statusCode = $response->getStatusCode();
    
    switch ($statusCode) {
        case 403:
            // Rate limit exceeded
            $resetTime = $response->getHeader('X-RateLimit-Reset')[0] ?? null;
            throw new Exception("GitHub API rate limit exceeded. Resets at: " . date('H:i:s', $resetTime));
            
        case 404:
            // Repository or release not found
            throw new Exception("Update source not found");
            
        case 200:
            // Success
            return json_decode($response->getBody(), true);
            
        default:
            throw new Exception("GitHub API error: HTTP {$statusCode}");
    }
}
```

---

## 📊 **Performance Metrics**

### **Update Speed Improvements**

| Component | Before | After (v1.3.34) | Improvement |
|-----------|---------|------------------|-------------|
| File Transfer | Standard copy | rsync bulk transfer | **20x faster** |
| API Requests | 60/hour limit | 5,000/hour with token | **83x more requests** |
| User Feedback | Batch at end | Real-time streaming | **Immediate visibility** |
| Update Time | 5-10 minutes | 2-3 minutes | **50-70% faster** |

### **Typical Update Statistics**
```
Total Files: ~45,000
Total Size: ~240MB
Transfer Speed: ~500MB/s (with rsync)
API Calls: 1-2 per update check
Backup Size: ~200MB compressed
Update Duration: 2-3 minutes average
```

### **Resource Usage**
```
Memory Peak: ~128MB during extraction
Disk Space: Temporary ~500MB, Backup ~200MB
Network: Single download ~240MB
CPU: Minimal impact, mostly I/O bound
```

---

## 🚀 **Future Enhancements**

### **Planned Features**
- **Scheduled Updates**: Automatic updates during maintenance windows
- **Update Channels**: Stable, Beta, Alpha release channels
- **Multi-Server Updates**: Update multiple panel instances simultaneously
- **Enhanced Rollback**: One-click rollback from admin interface
- **Update Notifications**: Discord/Slack integration for update alerts

### **Performance Optimizations**
- **Delta Updates**: Only download changed files
- **Compression**: Better compression algorithms for smaller downloads
- **CDN Integration**: Distribute updates via CDN for faster downloads
- **Parallel Processing**: Multi-threaded file operations

---

## 📝 **Contributing**

### **Report Issues**
- **GitHub Issues**: [https://github.com/Owen-C137/Raptor-Panel/issues](https://github.com/Owen-C137/Raptor-Panel/issues)
- **Include**: Version numbers, error logs, system information

### **Submit Improvements**
- **Pull Requests**: Welcome for bug fixes and enhancements
- **Testing**: Test updates on non-production environments first
- **Documentation**: Update this guide when making changes

---

## 📄 **License & Support**

**License**: Same as Raptor Panel (MIT)  
**Support**: GitHub Issues and Community Discord  
**Compatibility**: All Raptor Panel installations v1.3.0+  
**Maintenance**: Actively maintained and updated

---

*Last Updated: September 24, 2025 - v1.3.34 Real-Time Streaming Release*