# 🛒 Pterodactyl Shop System Addon - Installation Guide

**Version:** 1.3.0  
**Compatible with:** Pterodactyl Panel v1.11.0+  
**License:** MIT

> **⚠️ Important:** This is an addon for existing Pterodactyl Panel installations. You must have a working Pterodactyl Panel before installing this shop system.

---

## 📋 **What You'll Get**

This addon adds a complete shop and billing system to your existing Pterodactyl Panel:
- 🛒 **Product catalog** with categories and plans
- 💳 **Payment processing** (Stripe, PayPal)
- 👤 **Customer management** and orders
- 🧾 **Invoice generation** (PDF)
- 💰 **Wallet system** with balance tracking
- 📊 **Admin dashboard** with analytics

---

## 🔧 **Prerequisites**

### **Required: Working Pterodactyl Panel**
- ✅ **Pterodactyl Panel v1.11.0+** already installed and working
- ✅ **Admin access** to your panel
- ✅ **SSH/Terminal access** to your server
- ✅ **File upload capability** (FTP/SFTP/direct access)

### **Verify Your Current Setup**
```bash
# Navigate to your Pterodactyl directory
cd /var/www/pterodactyl  # (or wherever your panel is installed)

# Check Pterodactyl version
php artisan --version

# Check PHP version (must be 8.1+)
php -v

# Verify composer works
composer --version

# Check database connection
php artisan migrate:status
```

### **What We'll Install**
The shop addon will add these dependencies to your panel:
- `stripe/stripe-php: ^10.0` - Stripe payment processing
- `paypal/paypal-checkout-sdk: ^1.0` - PayPal payment processing
- `ramsey/uuid: ^4.0` - Unique identifier generation

---

## 🚀 **Installation Steps**

### **Step 1: Upload Addon Files**

Extract the shop-system addon to your Pterodactyl installation:

```bash
# Navigate to your Pterodactyl directory
cd /var/www/pterodactyl

# Create addons directory if it doesn't exist
mkdir -p addons

# Upload the shop-system folder to: /var/www/pterodactyl/addons/shop-system/
# Your file structure should look like:
# /var/www/pterodactyl/
# ├── addons/
# │   └── shop-system/
# │       ├── src/
# │       ├── resources/
# │       ├── config/
# │       ├── database/
# │       ├── routes/
# │       ├── composer.json
# │       └── VERSION
# ├── app/
# ├── config/
# └── ... (your existing panel files)
```

### **Step 2: Run One-Click Installer**

**That's it! One command does everything:**

```bash
# Navigate to your Pterodactyl root directory
cd /var/www/pterodactyl

# Run the one-click installer (handles everything automatically)
php artisan shop:install

# If you need to reinstall or force install:
php artisan shop:install --force
```

**What the installer does automatically:**
- ✅ **Verifies prerequisites** (PHP version, database connection)
- ✅ **Installs dependencies** (Stripe, PayPal, UUID packages)
- ✅ **Configures autoloader** (adds PSR-4 mapping to composer.json)
- ✅ **Registers service provider** (adds to config/app.php)
- ✅ **Resolves route conflicts** (fixes base route patterns)
- ✅ **Runs database migrations** (creates 15+ shop tables)
- ✅ **Publishes configuration** (copies config files)
- ✅ **Seeds default data** (creates initial settings)
- ✅ **Clears all caches** (ensures changes take effect)
- ✅ **Verifies installation** (confirms everything works)

**Expected Output:**
```
🚀 Starting Pterodactyl Shop System One-Click Installation...

🔍 Verifying prerequisites...
   ✅ Prerequisites verified
🔍 Verifying addon structure...
   ✅ Addon structure verified
� Installing required dependencies...
   Installing stripe/stripe-php:^10.0...
   Installing paypal/paypal-checkout-sdk:^1.0...
   Installing ramsey/uuid:^4.0...
   ✅ Dependencies installed
�🔧 Configuring autoloader...
   ✅ Autoloader configured
🔧 Registering service provider...
   ✅ Service provider registered
�️ Resolving route conflicts...
   ✅ Route conflicts resolved
🔄 Refreshing autoloader...
   ✅ Autoloader refreshed
�📊 Running database migrations...
   ✅ Database migrations completed
📁 Publishing configuration...
   ✅ Configuration published
🌱 Creating default shop settings...
   ✅ Default settings created
🧹 Clearing application caches...
   ✅ Caches cleared
✅ Verifying installation...
   ✅ Installation verified

🎉 Shop System installed successfully!

📊 Admin Dashboard: https://yourpanel.com/admin/shop
🛒 Customer Shop: https://yourpanel.com/shop

💡 Next steps:
   1. Visit admin dashboard to configure shop settings
   2. Set up payment gateways (Stripe/PayPal)  
   3. Create product categories and plans
   4. Test the checkout process
```

---

## ⚙️ **Configuration**

### **Step 1: Basic Shop Settings**

Add these environment variables to your `.env` file:

```bash
# Enable the shop system
SHOP_ENABLED=true
SHOP_MAINTENANCE=false

# Shop branding
SHOP_NAME="Your Server Shop"
SHOP_CURRENCY=USD
SHOP_CURRENCY_SYMBOL=$

# Optional: Custom styling
SHOP_PRIMARY_COLOR=#0ea5e9
```

### **Step 2: Payment Gateway Setup**

**Choose and configure at least one payment method:**

#### **Option A: Stripe (Recommended)**
```bash
# Add to your .env file:
STRIPE_ENABLED=true
STRIPE_PUBLISHABLE_KEY=pk_test_your_key_here
STRIPE_SECRET_KEY=sk_test_your_key_here
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret
```

1. Create account at https://stripe.com
2. Get API keys from Dashboard → Developers → API keys
3. Create webhook: `https://yourpanel.com/shop/webhooks/stripe`
4. Add events: `payment_intent.succeeded`, `payment_intent.payment_failed`

#### **Option B: PayPal**
```bash
# Add to your .env file:
PAYPAL_ENABLED=true
PAYPAL_MODE=sandbox  # or 'live' for production
PAYPAL_CLIENT_ID=your_paypal_client_id
PAYPAL_CLIENT_SECRET=your_paypal_client_secret
```

1. Create developer account at https://developer.paypal.com
2. Create new app (sandbox or live)
3. Get Client ID and Client Secret
4. Return URL: `https://yourpanel.com/shop/payment/paypal/return`
5. Cancel URL: `https://yourpanel.com/shop/payment/paypal/cancel`

### **Step 3: Shop Configuration**

1. **Access Admin Dashboard:** `https://yourpanel.com/admin/shop`

2. **Configure General Settings:**
   - Shop name and description
   - Currency and formatting
   - Terms of service URL

3. **Set Up Payment Methods:**
   - Enable your chosen payment gateways
   - Set processing fees if needed
   - Test payment connections

4. **Create Product Categories:**
   - Examples: "Game Servers", "VPS Hosting", "Add-ons"
   - Set descriptions and display order

5. **Add Your First Products:**
   - Create hosting plans with resource limits
   - Set pricing and billing cycles
   - Configure server creation settings

---

## 🎯 **Post-Installation**

### **Set Up Admin Access**
Ensure your admin user can access the shop dashboard:

```bash
# Make sure your user is a root admin
php artisan tinker --execute "
\$user = \Pterodactyl\Models\User::where('email', 'your@email.com')->first();
\$user->root_admin = 1;
\$user->save();
echo 'Admin access granted to: ' . \$user->email;
"
```

### **Optional: Background Processing**
For better performance with large order volumes:

```bash
# Add to your crontab
crontab -e

# Add this line:
* * * * * cd /var/www/pterodactyl && php artisan schedule:run >> /dev/null 2>&1

# For production, run queue worker:
php artisan queue:work --daemon
```

### **SSL Certificate (Required for Payments)**
Ensure HTTPS is properly configured:

```bash
# Example with Certbot
certbot --nginx -d yourpanel.com
```

---

## 🛠️ **Troubleshooting**

### **If Installation Fails**

The one-click installer handles most issues automatically, but if something goes wrong:

```bash
# Try force reinstall (fixes most issues)
php artisan shop:install --force

# Check the error output for specific issues
# Most common problems are resolved automatically
```

### **Common Issues**

#### **1. "Prerequisites verification failed"**
```bash
# Check PHP version (must be 8.1+)
php -v

# Check database connection
php artisan migrate:status

# Ensure you're in the Pterodactyl root directory
ls artisan  # Should exist
```

#### **2. "Addon structure not found"** 
```bash
# Verify addon files are in the right place
ls -la addons/shop-system/

# Should show: src/, resources/, config/, database/, etc.
```

#### **3. "Dependency installation failed"**
```bash
# Check composer is working
composer --version

# Check internet connection for package downloads
ping packagist.org

# Try manual dependency installation
composer require stripe/stripe-php:^10.0 --no-interaction
```

#### **4. "Permission denied" errors**
```bash
# Fix file permissions
sudo chown -R www-data:www-data /var/www/pterodactyl
sudo chmod -R 755 /var/www/pterodactyl

# Or for your user:
sudo chown -R $USER:$USER /var/www/pterodactyl
```

### **Manual Verification**

If you want to check everything manually:

```bash
# Check if shop commands exist
php artisan list | grep shop

# Check if database tables were created  
php artisan tinker --execute "
echo 'Tables: ';
echo Schema::hasTable('shop_settings') ? '✅ shop_settings' : '❌ shop_settings';
echo Schema::hasTable('shop_orders') ? ' ✅ shop_orders' : ' ❌ shop_orders';
echo Schema::hasTable('user_wallets') ? ' ✅ user_wallets' : ' ❌ user_wallets';
"

# Check if routes work
curl -I http://localhost/shop  # Should return 200, not 404

# Check if config is published
ls -la config/shop.php  # Should exist
```

### **Get Help**

If you're still having issues:

1. **Check logs:** `tail -f storage/logs/laravel.log`
2. **Enable debug:** Add `APP_DEBUG=true` to `.env`
3. **Try fresh install:** Remove addon files and reinstall
4. **Check permissions:** Ensure web server can read/write files

The one-click installer is designed to handle 99% of installation scenarios automatically!

---

## 🗑️ **Uninstallation**

If you need to remove the shop system:

```bash
# Remove addon but keep database data
php artisan shop:uninstall --keep-data

# Complete removal (⚠️ DESTRUCTIVE - removes all shop data)
php artisan shop:uninstall --force

# Remove addon files
rm -rf addons/shop-system/

# Clean up configuration
rm -f config/shop.php

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
composer dump-autoload -o
```

---

## 📞 **Support & Resources**

### **Getting Started**
1. 📊 **Admin Dashboard:** `https://yourpanel.com/admin/shop`
2. 🛒 **Shop Frontend:** `https://yourpanel.com/shop`
3. 📖 **Built-in Documentation:** Available in admin dashboard

### **Useful Commands**
```bash
# Check system status
php artisan shop:status

# Clean up old orders
php artisan shop:cleanup-cancelled

# Fix wallet balance issues
php artisan shop:fix-wallet-balances

# View all shop routes
php artisan route:list | grep shop
```

### **File Structure (After Installation)**
```
/var/www/pterodactyl/
├── addons/
│   └── shop-system/          # Your addon files
├── config/
│   └── shop.php             # Published configuration
├── storage/logs/
│   └── laravel.log          # Check for errors here
└── ... (existing panel files)
```

---

## 🎉 **You're All Set!**

Your Pterodactyl Panel now has a complete shop system! 

### **What Just Happened:**
✅ **One Command Installation** - Everything configured automatically  
✅ **Zero Manual Configuration** - No file editing required  
✅ **All Dependencies Installed** - Stripe, PayPal, and PDF support  
✅ **Database Ready** - 15+ tables created and configured  
✅ **Routes Working** - Shop accessible at `/shop`  
✅ **Admin Dashboard** - Full management interface at `/admin/shop`

### **Installation Summary:**
```bash
# That's literally all you needed to do:
cd /var/www/pterodactyl
# (upload addon files to addons/shop-system/)
php artisan shop:install
```

**Next Steps:**
1. 🔐 **Login to admin dashboard** → `https://yourpanel.com/admin/shop`
2. ⚙️ **Configure payment gateways** → Set up Stripe or PayPal
3. 🛒 **Create your first products** → Add categories and hosting plans
4. 💳 **Test checkout process** → Make sure payments work
5. 🚀 **Start selling!** → Your shop is ready for customers

**Quick Access Links:**
- **🎛️ Admin Dashboard:** `https://yourpanel.com/admin/shop`
- **🛒 Customer Shop:** `https://yourpanel.com/shop`  
- **📋 Order Management:** `https://yourpanel.com/admin/shop/orders`
- **💰 Financial Reports:** `https://yourpanel.com/admin/shop/analytics`

---

**Version:** 1.3.0 | **Last Updated:** September 17, 2025