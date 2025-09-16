# Shop System Changelog

# Changelog

## [1.2.4] - 2025-09-16

### 🚨 Critical Payment Bug Fix
- **FIXED**: PayPal payment discrepancy where checkout showed £53.99 but PayPal charged £97.99
- **FIXED**: OrderService incorrectly storing combined total (£53.99) as recurring amount instead of separating recurring (£9.99) and setup fee (£44.00)
- **FIXED**: PayPal receiving duplicated amounts causing double charging (Item 1: £53.99 + Item 2: £44.00 = £97.99 instead of £53.99)

### 🔧 Technical Improvements
- Updated ShopOrderService::createOrder() to use `$totals['subtotal']` for amount field instead of `$totals['total']`
- PayPal payment gateway now receives correct item breakdown: recurring £9.99 + setup £44.00 = total £53.99
- Improved order amount storage to properly separate recurring charges from one-time setup fees

## [1.2.3] - 2025-01-13

### 🛒 Checkout & Pricing Fixes
- **FIXED**: Checkout pricing display bug where total (£53.99) didn't match PayPal amount
- **FIXED**: Cart service incorrectly storing combined price as unit_price instead of separating recurring and setup fees
- **FIXED**: Cart summary showing "Unknown" plan name and £0.00 prices on checkout page
- **IMPROVED**: Cart items now properly separate unit price (£9.99) and setup fee (£44.00) for accurate display
- **IMPROVED**: Checkout breakdown now correctly shows "Subtotal: £9.99 + Setup Fees: £44.00 = Total: £53.99"

### 🔧 Technical Improvements
- Updated CartService::addItem() to store setup fee in plan_options instead of combining with unit price
- Updated CartService::getCartSummary() to extract pricing from stored cart data instead of recalculating from plans
- Fixed cart item pricing persistence and display accuracy

## [1.2.2] - 2025-01-13

### 🚨 Critical Fixes
- **FIXED**: Critical wallet balance corruption where stored balance didn't match transaction history
- **FIXED**: Transaction amount storage bug causing double-negative values (-£-53.99)
- **FIXED**: Incorrect transaction types for deposits showing as debits instead of credits
- **FIXED**: UserWallet::deductFunds() method now stores positive amounts with correct type indication
- **ADDED**: Missing `paid_at` column to shop_orders table for proper payment tracking
- **VERIFIED**: Analytics queries correctly use `gateway` column (not `method`)

### 🛠️ Data Integrity Improvements
- Recalculated and corrected all existing wallet balances
- Fixed transactions with empty or missing type fields
- Implemented comprehensive wallet balance validation
- Added wallet balance repair command for future maintenance

### 🧪 Testing & Validation
- Comprehensive 6-point testing suite for all critical fixes
- Verified wallet balance integrity matches transaction calculations
- Confirmed new transactions store amounts correctly
- Validated database schema additions and column references

## [1.2.1] - 2025-01-12
### 🔧 Technical Improvements
- Added proper versioning system with VERSION file
- Implemented VersionService for dynamic version tracking
- Updated addon creation instructions with version control workflow
- Enhanced documentation with changelog requirements
- Improved commit message standards with version prefixes

### 📋 Documentation Updates
- Updated addon creation guidelines with mandatory version control
- Added version control workflow to development process
- Established changelog maintenance requirements
- Defined semantic versioning standards for addon development

---

## v1.2.0 (2025-09-16)
### 🎉 Major Features
- **Complete Server Plan Renewal System**
  - Modal renewal buttons on cancelled servers
  - Dedicated renewal checkout interface
  - Billing cycle selection (monthly, quarterly, annually)
  - Multi-payment method support (wallet, PayPal, Stripe)

### ✨ New Features
- Renewal checkout page with server details and pricing
- Dynamic billing cycle pricing calculations
- Payment processing for renewals (excludes setup fees)
- Order reactivation logic with proper status updates
- Session-based renewal data management for external payments
- Enhanced payment completion handling for all gateways

### 🐛 Bug Fixes
- Fixed WalletService method calls (deductCredits → deductFunds)
- Fixed payment amount calculation to exclude setup fees for renewals
- Fixed payment status completion with improved markAsPaid method
- Fixed view syntax issues (array access → object properties)
- Fixed renewal detection and routing logic

### 🔧 Technical Improvements
- Added comprehensive logging and error handling
- Improved payment record management
- Enhanced AJAX form submission with user feedback
- Better session data handling for payment callbacks
- Proper order lifecycle management

### 📝 API Changes
- Added renewal-specific payment processing methods
- Enhanced CheckoutController with renewal detection
- New renewal checkout view and form handling

---

## v1.1.0 (Previous)
### Features
- Basic shop system functionality
- Payment gateway integration
- Order management
- Server provisioning
- Admin panel interface

---

## v1.0.0 (Initial)
### Features
- Initial shop system implementation
- Core payment processing
- Basic order lifecycle