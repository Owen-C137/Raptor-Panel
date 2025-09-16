# Shop System Changelog

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