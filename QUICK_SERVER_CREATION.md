# 🚀 Quick Server Creation Feature

## Overview

The **Quick Server Creation** feature provides a streamlined way to rapidly create test servers for egg validation and development purposes. It's designed to eliminate the tedious process of manually configuring every server setting, making it perfect for developers and administrators who need to quickly test different eggs and configurations.

## Features

### ⚡ Smart Defaults
- **Auto Node Selection**: Automatically selects the first available public node not in maintenance
- **Auto Allocation**: Selects the first available allocation on the chosen node
- **Smart Environment Variables**: Provides intelligent defaults based on common egg variable patterns
- **Docker Image Selection**: Prefers Java 17 or latest images when available
- **Random Naming**: Generates descriptive, unique server names based on the egg type

### 🎛️ Resource Presets
- **Low (Testing)**: 512MB RAM, 1GB Disk, 100% CPU - Perfect for basic functionality testing
- **Medium (Development)**: 2GB RAM, 4GB Disk, 200% CPU - Ideal for development and debugging
- **High (Production)**: 4GB RAM, 8GB Disk, 300% CPU - Suitable for production-like testing

### 🔧 User Options
- **Auto-start**: Automatically start the server after creation
- **Custom Naming**: Override random naming with custom server names
- **Nest/Egg Selection**: Choose from all available nests and eggs

## How to Use

### 1. Access Quick Create
1. Navigate to **Admin Panel > Servers**
2. Click the green **"Quick Create"** button next to "Create New"
3. The Quick Server Creation modal will open

### 2. Configure Your Server
1. **Select Nest**: Choose the game/service type (e.g., Minecraft, Source Engine)
2. **Select Egg**: Pick the specific server type from the chosen nest
3. **Choose Preset**: Select resource allocation based on your testing needs
4. **Server Options**:
   - ✅ **Auto-start**: Server will start immediately after creation
   - ✅ **Random Name**: Use generated names like "Agile Minecraft Server 342"
   - ❌ **Custom Name**: Uncheck to specify your own server name

### 3. Create and Go
1. Click **"Create Quick Server"**
2. Wait for creation confirmation (usually 5-10 seconds)
3. Automatically redirected to the new server management page

## Smart Environment Variables

The system automatically provides sensible defaults for common variables:

| Variable Type | Smart Default | Examples |
|---------------|---------------|----------|
| Passwords | `quicktest123` | ADMIN_PASSWORD, RCON_PASS |
| Server Names | `Quick Test Server 456` | SERVER_NAME, SESSION_NAME |
| World/Map Names | `world789` | WORLD, MAP, LEVEL |
| Player Limits | `10` | MAX_PLAYERS, SLOTS |
| Ports | `20000-30000` | QUERY_PORT, RCON_PORT |
| Boolean Settings | `1` (enabled) | AUTO_UPDATE, ENABLE_* |

## System Requirements

### Prerequisites
- At least one **public node** not in maintenance mode
- Available **allocations** on at least one node
- At least one **nest with eggs** configured

### Warnings System
The system will warn you about:
- ⚠️ No available nodes
- ⚠️ Low allocation count (< 5 available)
- ⚠️ No nests/eggs configured
- ℹ️ Single node availability

## Use Cases

### 🧪 Egg Testing
Perfect for testing new eggs or egg configurations:
```
1. Select your nest/egg
2. Choose "Low" preset for basic testing
3. Enable auto-start
4. Create and test immediately
```

### 🏗️ Development Servers
Ideal for development work requiring multiple servers:
```
1. Use "Medium" preset for adequate resources
2. Create multiple servers with different configurations
3. Test server interactions and compatibility
```

### 📊 Load Testing Preparation
Set up multiple servers for load testing:
```
1. Use "High" preset for production-like resources
2. Create several identical servers
3. Test under realistic resource constraints
```

### 🔍 Quick Debugging
Rapid server creation for troubleshooting:
```
1. Replicate user-reported issues quickly
2. Test fixes without complex setup
3. Clean slate testing environment
```

## Technical Details

### Created Server Properties
Each quick server is created with:
- **Owner**: Current admin user
- **Description**: "Quick test server for [EggName] - Created via Quick Create"
- **Security**: OOM killer disabled for stability
- **Scripts**: Installation scripts enabled
- **Startup**: Uses egg's default startup command

### Error Handling
Common issues are handled gracefully:
- **No nodes available**: Clear error message with guidance
- **No allocations**: Specific node information provided
- **Resource constraints**: Suggests trying lower preset
- **Validation errors**: Detailed error explanations

### Logging
All creation attempts are logged for debugging:
- Creation attempts with parameters
- Success/failure status
- Error details for troubleshooting

## Tips & Best Practices

### 🎯 For Egg Testing
- Start with "Low" preset to conserve resources
- Use auto-start to immediately verify functionality
- Check logs if server fails to start properly

### 🛠️ For Development
- "Medium" preset provides good balance of resources
- Custom names help organize multiple test servers
- Disable auto-start if you need to modify settings first

### 🚦 Resource Management
- Monitor node resource usage with high presets
- Clean up test servers regularly
- Use "Low" preset for basic functionality verification

### 🔍 Troubleshooting
- Check system warnings before creation
- Verify node has sufficient resources
- Ensure allocations exist and are not all assigned

## Integration with Existing Systems

### Pterodactyl Compatibility
- Uses standard `ServerCreationService`
- Follows all existing validation rules
- Compatible with all standard eggs
- Integrates with existing permission system

### Admin Interface
- OneUI theme integration
- Responsive modal design
- Real-time progress feedback
- Bootstrap 5 compatible

## Future Enhancements

Planned improvements include:
- **Template Servers**: Save configurations as reusable templates
- **Bulk Creation**: Create multiple servers simultaneously
- **Custom Presets**: Define organization-specific resource presets
- **Integration Testing**: Automated server functionality validation
- **Clone Existing**: Quick clone of existing server configurations

---

## Support

For issues or questions about Quick Server Creation:
1. Check the system warnings in the modal
2. Verify node and allocation availability
3. Review server logs for creation failures
4. Consult the main Raptor Panel documentation

**Happy Quick Creating!** 🚀