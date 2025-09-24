# Raptor Panel

[![Build Status](https://img.shields.io/github/actions/workflow/status/Owen-C137/Raptor-Panel/release.yml?branch=main)](https://github.com/Owen-C137/Raptor-Panel/actions)
[![License](https://img.shields.io/github/license/Owen-C137/Raptor-Panel)](https://opensource.org/licenses/MIT)
[![Version](https://img.shields.io/github/v/release/Owen-C137/Raptor-Panel)](https://github.com/Owen-C137/Raptor-Panel/releases)

Raptor Panel is an enhanced, feature-rich fork of Pterodactyl Panel with modern UI improvements and advanced functionality for managing game servers.

## ✨ Key Features

- 🎨 **Modern OneUI Interface** - Beautiful, responsive admin dashboard
- 🔄 **Auto-Update System** - Seamless updates directly from the admin panel
- ✨ **Enhanced Node Configuration** - Syntax highlighting and copy-to-clipboard functionality
- 💾 **Backup & Restore** - Comprehensive backup system with rollback capabilities
- 🛡️ **Security First** - Safe updates with automatic backup creation
- ⚙️ **Improved Settings** - Better organized configuration management

## 🚀 Quick Start

### Requirements

- PHP 8.1 or higher
- Composer
- MariaDB 10.4+ / MySQL 8.0+
- Redis
- Node.js (for theme compilation)

### Installation

1. Clone the repository:
```bash
git clone https://github.com/Owen-C137/Raptor-Panel.git
cd Raptor-Panel
```

2. Install dependencies:
```bash
composer install --no-dev --optimize-autoloader
```

3. Copy configuration and set up environment:
```bash
cp .env.example .env
php artisan key:generate --force
```

4. Configure your database in `.env` and run migrations:
```bash
php artisan migrate --force
php artisan db:seed --force
```

5. Create your first admin user:
```bash
php artisan p:user:make
```

## 🔄 Auto-Update System

Raptor Panel includes a built-in auto-update system that allows seamless updates:

### Web Interface
- Navigate to Admin Dashboard
- Updates are automatically detected and shown
- Click "View Update" to see changelog
- Click "Update Now" for one-click installation

### Command Line
```bash
# Check for updates
php artisan update:check

# Apply updates with backup
php artisan update:apply --backup

# Rollback to previous version
php artisan update:rollback --latest
```

## 🛠️ For Developers

### Release Workflow

1. **Update version** in `config/app.php` 
2. **Update CHANGELOG.md** with new features/fixes
3. **Commit and push** changes
4. Users automatically get update notifications!

### Development Setup

```bash
# Install dev dependencies
composer install
npm install

# Compile assets
npm run build

# Run development server
php artisan serve
```

## 📖 Documentation

- [Installation Guide](https://github.com/Owen-C137/Raptor-Panel/wiki/Installation)
- [Configuration](https://github.com/Owen-C137/Raptor-Panel/wiki/Configuration)
- [Auto-Update System](https://github.com/Owen-C137/Raptor-Panel/wiki/Auto-Updates)
- [API Documentation](https://github.com/Owen-C137/Raptor-Panel/wiki/API)

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- Built upon the excellent foundation of [Pterodactyl Panel](https://github.com/pterodactyl/panel)
- OneUI theme integration for modern interface
- Community feedback and contributions

## 💖 Support the Project

If you find Raptor Panel useful, please consider:
- ⭐ Starring the repository
- 🐛 Reporting bugs and issues
- 💝 [Sponsoring the project](https://github.com/sponsors/Owen-C137)
- 📢 Sharing with others

---

**Raptor Panel** - Enhanced server management for the modern era.

* [Panel Documentation](https://pterodactyl.io/panel/1.0/getting_started.html)
* [Wings Documentation](https://pterodactyl.io/wings/1.0/installing.html)
* [Community Guides](https://pterodactyl.io/community/about.html)
* Or, get additional help [via Discord](https://discord.gg/pterodactyl)

## Sponsors

I would like to extend my sincere thanks to the following sponsors for helping fund Pterodactyl's development.
[Interested in becoming a sponsor?](https://github.com/sponsors/matthewpi)

| Company                                                      | About                                                                                                                                                                                                                                           |
|--------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| [**Aussie Server Hosts**](https://aussieserverhosts.com/)    | No frills Australian Owned and operated High Performance Server hosting for some of the most demanding games serving Australia and New Zealand.                                                                                                 |
| [**BisectHosting**](https://www.bisecthosting.com/)          | BisectHosting provides Minecraft, Valheim and other server hosting services with the highest reliability and lightning fast support since 2012.                                                                                                 |
| [**MineStrator**](https://minestrator.com/)                  | Looking for the most highend French hosting company for your minecraft server? More than 24,000 members on our discord trust us. Give us a try!                                                                                                 |
| [**HostEZ**](https://hostez.io)                              | US & EU Rust & Minecraft Hosting. DDoS Protected bare metal, VPS and colocation with low latency, high uptime and maximum availability. EZ!                                                                                                     |
| [**Blueprint**](https://blueprint.zip/?pterodactyl=true)     | Create and install Pterodactyl addons and themes with the growing Blueprint framework - the package-manager for Pterodactyl. Use multiple modifications at once without worrying about conflicts and make use of the large extension ecosystem. |
| [**indifferent broccoli**](https://indifferentbroccoli.com/) | indifferent broccoli is a game server hosting and rental company. With us, you get top-notch computer power for your gaming sessions. We destroy lag, latency, and complexity--letting you focus on the fun stuff.                              |

### Supported Games

Pterodactyl supports a wide variety of games by utilizing Docker containers to isolate each instance. This gives
you the power to run game servers without bloating machines with a host of additional dependencies.

Some of our core supported games include:

* Minecraft — including Paper, Sponge, Bungeecord, Waterfall, and more
* Rust
* Terraria
* Teamspeak
* Mumble
* Team Fortress 2
* Counter Strike: Global Offensive
* Garry's Mod
* ARK: Survival Evolved

In addition to our standard nest of supported games, our community is constantly pushing the limits of this software
and there are plenty more games available provided by the community. Some of these games include:

* Factorio
* San Andreas: MP
* Pocketmine MP
* Squad
* Xonotic
* Starmade
* Discord ATLBot, and most other Node.js/Python discord bots
* [and many more...](https://github.com/parkervcp/eggs)

## License

Pterodactyl® Copyright © 2015 - 2022 Dane Everitt and contributors.

Code released under the [MIT License](./LICENSE.md).

# pt-addons-overhaul
This is a repo for me to backup my work on my pt addons / overhaul stuff
