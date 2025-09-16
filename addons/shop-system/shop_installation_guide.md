# 🛒 Pterodactyl Shop System Addon - Installation Guide

**Version:** 1.2.4  
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

### **Step 2: Install Dependencies**

```bash
# Navigate to your Pterodactyl root directory
cd /var/www/pterodactyl

# Install required dependencies
composer require stripe/stripe-php:^10.0
composer require paypal/paypal-checkout-sdk:^1.0
composer require ramsey/uuid:^4.0

# Verify PDF support (should already exist)
composer show barryvdh/laravel-dompdf

# If PDF library is missing, install it:
# composer require barryvdh/laravel-dompdf:^3.1

# Update autoloader
composer dump-autoload -o
```

### **Step 3: Run Installation Command**

```bash
# Run the automated shop system installer
php artisan shop:install

# If you need to reinstall or force install:
# php artisan shop:install --force
```

**Expected Output:**
```
🚀 Installing Pterodactyl Shop System...

🔍 Verifying addon structure...
   ✅ Addon structure verified
🔧 Registering service provider...
   ✅ Service provider registered
📊 Running database migrations...
   ✅ Database migrations completed (15 new tables)
📁 Publishing configuration...
   ✅ Configuration published
🌱 Creating default shop settings...
   ✅ Default settings created
🧹 Clearing application caches...
   ✅ Caches cleared

✅ Shop System installed successfully!

🎉 Your Pterodactyl Shop System is ready!
📊 Admin Dashboard: https://yourpanel.com/admin/shop
🛒 Customer Shop: https://yourpanel.com/shop
```

### **Step 4: Verify Installation**

```bash
# Check if new database tables were created
php artisan tinker --execute "
echo 'Shop System Tables:' . PHP_EOL;
\$tables = ['shop_categories', 'shop_plans', 'user_wallets', 'wallet_transactions', 'shop_orders', 'shop_payments'];
foreach(\$tables as \$table) {
    \$exists = Schema::hasTable(\$table) ? '✅ Created' : '❌ Missing';
    echo '  ' . \$table . ': ' . \$exists . PHP_EOL;
}
"

# Check if shop routes are available
php artisan route:list | grep shop

# Verify you can access the admin dashboard
# Visit: https://yourpanel.com/admin/shop
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

### **Common Issues**

#### **1. "Shop routes not found" / 404 errors**
```bash
# Clear all caches
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Verify service provider is registered
grep -r "ShopServiceProvider" config/app.php

# Retry installation
php artisan shop:install --force
```

#### **2. Database migration errors**
```bash
# Check migration status
php artisan migrate:status

# Run shop migrations manually
php artisan migrate --path=addons/shop-system/database/migrations --force
```

#### **3. Payment gateway not working**
```bash
# Test Stripe connection
php artisan tinker --execute "
\Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
try {
    \$account = \Stripe\Account::retrieve();
    echo 'Stripe connected: ' . \$account->email;
} catch(\Exception \$e) {
    echo 'Stripe error: ' . \$e->getMessage();
}
"
```

#### **4. "Class not found" errors**
```bash
# Update composer autoloader
composer dump-autoload -o

# Clear application cache
php artisan cache:clear
```

### **Enable Debug Mode**
Add to your `.env` for detailed error logging:
```bash
LOG_LEVEL=debug
APP_DEBUG=true
```

### **Check Logs**
```bash
# View recent shop-related logs
tail -f storage/logs/laravel.log | grep -i shop
```

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

**Next Steps:**
1. 🔐 Login to admin dashboard
2. 🛒 Configure your first products  
3. 💳 Test the payment process
4. 🚀 Start selling hosting services!

**Quick Links:**
- **Admin:** `https://yourpanel.com/admin/shop`
- **Shop:** `https://yourpanel.com/shop`
- **Orders:** `https://yourpanel.com/shop/orders`

---

**Version:** 1.2.4 | **Last Updated:** September 16, 2025