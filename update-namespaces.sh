#!/bin/bash

# Update Admin Controllers
find /var/www/pterodactyl/addons/shop-system/src/Http/Controllers/Admin/ -name "*.php" -exec sed -i 's|namespace Pterodactyl\\Http\\Controllers\\Admin\\Shop;|namespace PterodactylAddons\\ShopSystem\\Http\\Controllers\\Admin;|g' {} \;
find /var/www/pterodactyl/addons/shop-system/src/Http/Controllers/Admin/ -name "*.php" -exec sed -i 's|Pterodactyl\\Models\\Shop\\|PterodactylAddons\\ShopSystem\\Models\\|g' {} \;
find /var/www/pterodactyl/addons/shop-system/src/Http/Controllers/Admin/ -name "*.php" -exec sed -i 's|Pterodactyl\\Models\\UserWallet|PterodactylAddons\\ShopSystem\\Models\\UserWallet|g' {} \;

# Update Client Controllers  
find /var/www/pterodactyl/addons/shop-system/src/Http/Controllers/Client/ -name "*.php" -exec sed -i 's|namespace Pterodactyl\\Http\\Controllers\\Client\\Shop;|namespace PterodactylAddons\\ShopSystem\\Http\\Controllers\\Client;|g' {} \;
find /var/www/pterodactyl/addons/shop-system/src/Http/Controllers/Client/ -name "*.php" -exec sed -i 's|Pterodactyl\\Models\\Shop\\|PterodactylAddons\\ShopSystem\\Models\\|g' {} \;
find /var/www/pterodactyl/addons/shop-system/src/Http/Controllers/Client/ -name "*.php" -exec sed -i 's|Pterodactyl\\Models\\UserWallet|PterodactylAddons\\ShopSystem\\Models\\UserWallet|g' {} \;

# Update Services
find /var/www/pterodactyl/addons/shop-system/src/Services/ -name "*.php" -exec sed -i 's|namespace Pterodactyl\\Services\\Shop;|namespace PterodactylAddons\\ShopSystem\\Services;|g' {} \;
find /var/www/pterodactyl/addons/shop-system/src/Services/ -name "*.php" -exec sed -i 's|Pterodactyl\\Models\\Shop\\|PterodactylAddons\\ShopSystem\\Models\\|g' {} \;
find /var/www/pterodactyl/addons/shop-system/src/Services/ -name "*.php" -exec sed -i 's|Pterodactyl\\Models\\UserWallet|PterodactylAddons\\ShopSystem\\Models\\UserWallet|g' {} \;

echo "Namespace updates completed!"
