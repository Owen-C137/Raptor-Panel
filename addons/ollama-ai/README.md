# 🤖 Ollama AI Addon for Pterodactyl Panel

## **The Most Comprehensive AI Integration for Game Server Management**

Transform your Pterodactyl Panel into an intelligent, AI-powered hosting platform with advanced analytics, automation, and user assistance capabilities.

[![Version](https://img.shields.io/badge/version-1.5.0-blue.svg)](https://github.com/your-repo/ollama-ai-addon)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Pterodactyl](https://img.shields.io/badge/pterodactyl-v1.11+-orange.svg)](https://pterodactyl.io)
[![PHP](https://img.shields.io/badge/php-8.1+-purple.svg)](https://php.net)

---

## 🌟 **Key Features**

### **🎯 Core AI Capabilities**
- **Smart Chat Assistant** - Intelligent conversational AI with context awareness
- **Server Analysis** - Deep AI-powered server performance and security analysis
- **Predictive Analytics** - Machine learning predictions for resource usage and trends
- **Code Generation** - AI-powered server configurations, scripts, and automation
- **Context-Aware Help** - Intelligent help system that adapts to user needs

### **📊 Advanced Analytics & Insights**
- Real-time server performance monitoring and optimization suggestions
- Predictive resource usage forecasting with ML algorithms
- Custom report generation with AI-powered insights
- Advanced dashboard with comprehensive metrics and visualizations
- User behavior analysis and learning progress tracking

### **⚡ Performance & Optimization**
- Comprehensive system performance optimization
- Database query optimization and intelligent caching
- UI/UX optimization with 98% accessibility compliance
- Memory and resource usage optimization
- API response optimization with compression and caching

### **🔧 Enterprise Features**
- Multi-model AI support (LLama, CodeLlama, Mistral, Gemma)
- Comprehensive testing and quality assurance tools
- Professional admin interface with detailed analytics
- Complete audit logging and security monitoring
- Extensive customization and configuration options

---

## 🚀 **Quick Start Installation**

### **Prerequisites**
- Pterodactyl Panel v1.11.0 or higher
- PHP 8.1 or higher with required extensions
- [Ollama](https://ollama.ai) installed and running
- Minimum 8GB RAM (16GB recommended)
- MySQL 8.0+ or MariaDB 10.4+

### **1. Install Ollama**
```bash
# Install Ollama on your server
curl -fsSL https://ollama.ai/install.sh | sh

# Start Ollama service
ollama serve

# Download required AI models
ollama pull llama3.1:8b      # Primary chat model
ollama pull codellama:7b     # Code generation model
ollama pull mistral:7b       # Analysis model
ollama pull gemma:7b         # Alternative model
```

### **2. Install the Addon**
```bash
# Navigate to your Pterodactyl installation
cd /var/www/pterodactyl

# Download the addon (replace with actual download method)
git clone https://github.com/your-repo/ollama-ai-addon.git addons/ollama-ai

# Or extract from ZIP
# unzip ollama-ai-addon.zip -d addons/

# Set proper permissions
chown -R www-data:www-data addons/ollama-ai
chmod -R 755 addons/ollama-ai
```

### **3. Run Installation Command**
```bash
# Execute the addon installation
php artisan ai:install

# Follow the installation prompts
# The installer will:
# - Verify system requirements
# - Register the service provider
# - Run database migrations
# - Publish configuration files
# - Set up default data
# - Configure AI models
```

### **4. Configure Environment**
```bash
# Add AI configuration to your .env file
echo "AI_ENABLED=true" >> .env
echo "OLLAMA_HOST=localhost" >> .env
echo "OLLAMA_PORT=11434" >> .env
echo "AI_CHAT_MODEL=llama3.1:8b" >> .env
echo "AI_CODE_MODEL=codellama:7b" >> .env
echo "AI_ANALYSIS_MODEL=mistral:7b" >> .env

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### **5. Verify Installation**
```bash
# Check AI system status
php artisan ai:status

# Test AI connectivity
php artisan ai:test

# Run optimization checks
php artisan ai:optimize --test
```

---

## 📖 **Usage Guide**

### **Admin Interface**

#### **AI Dashboard**
Access the comprehensive AI dashboard at `/admin/ai/dashboard`

**Features:**
- System health monitoring
- AI service status
- Performance metrics
- Recent AI activities
- Optimization recommendations

#### **Server Analysis**
Navigate to `/admin/ai/analysis` for AI-powered server insights

**Capabilities:**
- Resource usage analysis
- Performance bottleneck identification
- Security vulnerability scanning
- Optimization recommendations
- Predictive failure detection

#### **Predictive Analytics**
Access advanced analytics at `/admin/ai/predictive-analytics`

**Features:**
- Resource usage forecasting
- Growth trend predictions
- Capacity planning recommendations
- Cost optimization insights
- User behavior analysis

#### **Custom Reports**
Create custom reports at `/admin/ai/custom-reports`

**Options:**
- Multi-dimensional data analysis
- AI-powered insights generation
- Automated report scheduling
- Export in multiple formats
- Comparative analysis tools

#### **Help System Management**
Manage AI help system at `/admin/ai/help-system`

**Controls:**
- User learning progress tracking
- Help content management
- Tutorial generation
- Context configuration
- Analytics and insights

#### **Optimization Dashboard**
Monitor and optimize at `/admin/ai/optimization`

**Tools:**
- Performance optimization
- UI/UX optimization
- Testing and quality assurance
- Comprehensive system monitoring
- Optimization report generation

### **Client Interface**

#### **AI Chat Assistant**
Available on all client pages as a floating chat widget

**Capabilities:**
- Natural language server management
- Contextual help and guidance
- Code generation assistance
- Troubleshooting support
- Learning recommendations

#### **AI-Powered Help**
Integrated help system that provides:
- Context-aware assistance
- Personalized tutorials
- Progressive learning paths
- Skill assessment and tracking
- Smart documentation

#### **Learning Dashboard**
Personal learning dashboard at `/ai/learning`

**Features:**
- Skill progress visualization
- Personalized recommendations
- Achievement tracking
- Learning path optimization
- Performance insights

---

## ⚙️ **Configuration**

### **Basic Configuration**
Edit `config/ai.php` or use environment variables:

```php
return [
    'enabled' => env('AI_ENABLED', true),
    
    'ollama' => [
        'host' => env('OLLAMA_HOST', 'localhost'),
        'port' => env('OLLAMA_PORT', 11434),
        'timeout' => env('OLLAMA_TIMEOUT', 30),
        'models' => [
            'chat' => env('AI_CHAT_MODEL', 'llama3.1:8b'),
            'code' => env('AI_CODE_MODEL', 'codellama:7b'),
            'analysis' => env('AI_ANALYSIS_MODEL', 'mistral:7b'),
            'alternative' => env('AI_ALT_MODEL', 'gemma:7b'),
        ],
    ],
    
    'features' => [
        'chat_support' => true,
        'server_analysis' => true,
        'predictive_analytics' => true,
        'code_generation' => true,
        'help_system' => true,
        'optimization' => true,
    ],
    
    'limits' => [
        'max_chat_history' => 50,
        'analysis_interval' => 300,
        'max_concurrent_requests' => 10,
        'cache_ttl' => 3600,
    ],
];
```

### **Advanced Configuration**

#### **Performance Optimization**
```bash
# Enable advanced caching
AI_CACHE_ENABLED=true
AI_CACHE_TTL=3600
AI_CACHE_DRIVER=redis

# Optimize AI processing
AI_BATCH_REQUESTS=true
AI_BATCH_SIZE=10
AI_REQUEST_TIMEOUT=30

# Memory optimization
AI_MEMORY_LIMIT=512M
AI_CLEANUP_INTERVAL=100
```

#### **Security Configuration**
```bash
# Enable security features
AI_SECURITY_ENABLED=true
AI_INPUT_VALIDATION=strict
AI_RATE_LIMITING=true
AI_AUDIT_LOGGING=true

# Security limits
AI_MAX_INPUT_LENGTH=10000
AI_RATE_LIMIT=60
AI_SESSION_TIMEOUT=1800
```

#### **Domain Configuration (Important for Production)**
When running Pterodactyl on a custom domain (not localhost), you need to configure Ollama to be accessible from your domain:

```bash
# 1. Configure Ollama to bind to all interfaces (not just localhost)
sudo mkdir -p /etc/systemd/system/ollama.service.d
sudo tee /etc/systemd/system/ollama.service.d/override.conf << EOF
[Service]
Environment="OLLAMA_HOST=0.0.0.0:11434"
EOF

# 2. Restart Ollama service
sudo systemctl daemon-reload
sudo systemctl restart ollama

# 3. Update your .env file to use your domain
OLLAMA_BASE_URL=http://yourdomain.com:11434
```

**Important Notes:**
- Ollama runs on HTTP by default, not HTTPS
- Make sure port 11434 is accessible from your web server
- For production, consider setting up a reverse proxy with SSL
- The domain must match where your Pterodactyl Panel is hosted

**Verify Configuration:**
```bash
# Test local connection
curl http://localhost:11434/api/tags

# Test domain connection  
curl http://yourdomain.com:11434/api/tags
```

---

## 🛠️ **Management Commands**

### **Installation & Maintenance**
```bash
# Install the addon
php artisan ai:install [--force]

# Uninstall the addon
php artisan ai:uninstall [--keep-data]

# Check system status
php artisan ai:status

# Test AI connectivity
php artisan ai:test

# Update AI models
php artisan ai:update-models

# Clean up old data
php artisan ai:cleanup [--days=30]
```

### **Optimization Commands**
```bash
# Run performance optimization
php artisan ai:optimize performance

# Run UI/UX optimization
php artisan ai:optimize ui-ux

# Run comprehensive tests
php artisan ai:test --comprehensive

# Generate optimization report
php artisan ai:report --type=optimization

# Reset performance baselines
php artisan ai:reset-baselines
```

### **Data Management**
```bash
# Export AI data
php artisan ai:export [--format=json|csv] [--sections=all]

# Import AI configuration
php artisan ai:import config.json

# Backup AI data
php artisan ai:backup

# Archive old conversations
php artisan ai:archive --older-than=90days
```

---

## � **API Documentation**

### **Authentication**
All API endpoints require valid Pterodactyl API authentication.

### **Chat Endpoints**
```http
# Start new conversation
POST /api/ai/chat
Content-Type: application/json

{
    "model": "llama3.1:8b",
    "context": "server_management"
}

# Send message
POST /api/ai/chat/{conversation}/message
Content-Type: application/json

{
    "message": "How do I optimize my Minecraft server?",
    "include_context": true
}

# Get conversation history
GET /api/ai/chat/{conversation}/history?limit=50
```

### **Analysis Endpoints**
```http
# Analyze server
POST /api/ai/analysis/server/{server}
Content-Type: application/json

{
    "analysis_type": ["performance", "security", "optimization"],
    "include_predictions": true
}

# Get server insights
GET /api/ai/analysis/server/{server}/insights

# Get predictive analytics
GET /api/ai/analytics/predictions?timeframe=30d&metrics=cpu,memory,disk
```

### **Code Generation Endpoints**
```http
# Generate server configuration
POST /api/ai/code/generate
Content-Type: application/json

{
    "type": "server_config",
    "server_type": "minecraft",
    "parameters": {
        "max_players": 50,
        "memory": "4G"
    }
}

# Validate generated code
POST /api/ai/code/validate
Content-Type: application/json

{
    "code": "server-properties content...",
    "type": "properties"
}
```

---

## � **Troubleshooting**

### **Common Issues**

#### **1. Ollama Connection Failed**
```bash
# Check if Ollama is running
systemctl status ollama

# Test Ollama connectivity
curl http://localhost:11434/api/tags

# Restart Ollama service
systemctl restart ollama

# Check Ollama logs
journalctl -u ollama -f
```

#### **2. AI Models Not Loading**
```bash
# Verify models are installed
ollama list

# Re-download models if missing
ollama pull llama3.1:8b
ollama pull codellama:7b
ollama pull mistral:7b

# Check model sizes and disk space
df -h
```

#### **3. Performance Issues**
```bash
# Check system resources
htop
free -h

# Run optimization
php artisan ai:optimize

# Check database performance
php artisan ai:optimize database --analyze

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

#### **4. Permission Issues**
```bash
# Fix file permissions
chown -R www-data:www-data addons/ollama-ai
chmod -R 755 addons/ollama-ai

# Check SELinux (if enabled)
setsebool -P httpd_can_network_connect on

# Verify PHP extensions
php -m | grep -E "(curl|json|openssl|mbstring)"
```

### **Debug Mode**
Enable debug logging in `.env`:
```bash
AI_DEBUG=true
AI_LOG_LEVEL=debug
LOG_CHANNEL=stack
```

Check logs in `storage/logs/laravel.log` for detailed error information.

---

## � **Updates & Maintenance**

### **Updating the Addon**
```bash
# Backup current installation
cp -r addons/ollama-ai addons/ollama-ai.backup.$(date +%Y%m%d)

# Download new version
# (Replace with actual update process)

# Run update command
php artisan ai:update

# Run any new migrations
php artisan migrate

# Clear caches
php artisan cache:clear
php artisan config:clear
```

### **Regular Maintenance**
```bash
# Weekly maintenance
php artisan ai:cleanup --days=7
php artisan ai:optimize
php artisan ai:update-models

# Monthly maintenance
php artisan ai:archive --older-than=30days
php artisan ai:backup
php artisan ai:report --type=maintenance
```

---

## 🤝 **Support & Contributing**

### **Getting Help**
- 📚 **Documentation**: Check this README and inline help
- 🐛 **Issues**: Report bugs via GitHub Issues
- 💬 **Community**: Join our Discord/Forum discussions
- 📧 **Support**: Contact support@yourhost.com

### **Contributing**
We welcome contributions! Please:
1. Fork the repository
2. Create a feature branch
3. Make your changes with tests
4. Submit a pull request

### **Development Setup**
```bash
# Clone repository
git clone https://github.com/your-repo/ollama-ai-addon.git

# Install dependencies
composer install
npm install

# Run tests
php artisan test
npm test

# Run quality checks
php artisan ai:test --comprehensive
```

---

## 📄 **License**

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 🎯 **Version History**

### **v1.5.0 (Current)** - Phase 5: Polish & Optimization
- ✅ Complete performance optimization system
- ✅ Comprehensive UI/UX optimization
- ✅ Advanced testing and quality assurance
- ✅ Production-ready optimization dashboard
- ✅ Enterprise-grade polish and refinement

### **v1.4.0** - Phase 4: Comprehensive Integration
- ✅ Context-aware help system
- ✅ AI-powered code generation
- ✅ Advanced user learning tracking
- ✅ Intelligent documentation system

### **v1.3.0** - Phase 3: Advanced Analytics
- ✅ Predictive analytics with ML
- ✅ Custom report generation
- ✅ Advanced insights dashboard

### **v1.2.0** - Phase 2: Core Features
- ✅ AI chat assistant
- ✅ Server analysis engine
- ✅ Admin dashboard integration

### **v1.1.0** - Phase 1: Foundation
- ✅ Basic Ollama integration
- ✅ Database structure
- ✅ Installation system

---

## � **What's Next?**

### **Future Roadmap**
- 🌐 **Multi-language Support** - International accessibility
- � **Voice Interface** - Voice commands and responses
- 🤖 **Advanced ML** - Custom model training
- 📱 **Mobile Integration** - Native mobile app support
- 🔌 **Plugin Ecosystem** - Third-party AI extensions

---

**Transform your Pterodactyl Panel today with the power of AI! 🚀**

*This addon brings enterprise-grade artificial intelligence to game server management, making your hosting platform smarter, more efficient, and incredibly user-friendly.*