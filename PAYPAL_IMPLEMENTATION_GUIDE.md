# PayPal Integration Implementation Guide

## Complete PayPal Integration for Resort Venue Booking System

This guide provides step-by-step instructions for implementing PayPal payments to replace the manual GCash screenshot upload system.

## 📋 Table of Contents

1. [Setup & Configuration](#setup--configuration)
2. [Database Migration](#database-migration)
3. [File Structure](#file-structure)
4. [Security Measures](#security-measures)
5. [Testing Instructions](#testing-instructions)
6. [API Reference](#api-reference)
7. [Troubleshooting](#troubleshooting)

## 🔧 Setup & Configuration

### 1. Environment Configuration

Add these lines to your `.env` file (create from `.env.example` if needed):

```bash
# PayPal Configuration
PAYPAL_CLIENT_ID=your-actual-paypal-client-id
PAYPAL_SECRET=your-actual-paypal-secret
PAYPAL_MODE=sandbox  # Use 'sandbox' for testing, 'live' for production
```

### 2. PayPal Developer Setup

1. Go to [PayPal Developer Dashboard](https://developer.paypal.com/developer/applications/)
2. Create a new application or use an existing one
3. Get your Client ID and Secret from the application settings
4. For webhook integration (optional but recommended):
   - Add webhook URL: `https://yourdomain.com/api/paypalWebhook.php`
   - Subscribe to events: `PAYMENT.CAPTURE.COMPLETED`, `PAYMENT.CAPTURE.REFUNDED`
   - Note the Webhook ID for `PAYPAL_WEBHOOK_ID` environment variable

### 3. Database Setup

**✅ FIXED: All Database Issues Resolved**

**For Fresh Installations:**
If you're setting up the system for the first time, simply run the setup.php script - it now includes all PayPal fields automatically:

```bash
php setup.php
```

**For Existing Installations:**
If you already have the database set up, run these SQL migrations in order:

```sql
-- First migration (payment fields)
source migration_add_payment_fields.sql;

-- Second migration (enhanced payment tracking)
source migration_add_pending_payment_status.sql;
```

**✅ Database Structure Now Properly Organized:**
- All tables, views, and triggers are correctly within the database structure
- Users table fully accessible to login/registration systems
- Venue images and data properly stored and retrievable
- PayPal payment fields integrated throughout the system

**Testing & Verification:**
Use the provided test scripts to verify everything is working:

```bash
# Test database connection and structure
php debug_database.php

# Test all API endpoints and user management
php test_direct.php

# Fix default user email verification
php fix_users.php
```

## 📁 File Structure

```
api/
├── paypalCreateOrder.php      # Creates PayPal orders
├── paypalCapture.php          # Captures PayPal payments
├── paypalWebhook.php          # Webhook handler (optional)
└── getPayPalClientId.php      # Provides client ID to frontend

assets/js/
├── paypal.js                  # PayPal Smart Buttons integration
└── booking.js                 # Updated with PayPal support

pages/
└── booking.php                # Updated booking workflow

migration_add_payment_fields.sql        # Database schema updates
migration_add_pending_payment_status.sql # Enhanced payment tracking
```

## 🔒 Security Measures

### 1. Server-Side Verification
- All payment amounts are verified against server-side calculations
- Order details are validated before capture
- User authentication required for all payment endpoints

### 2. PayPal API Security
- Uses PayPal REST API with OAuth 2.0 authentication
- Webhook signature verification (when using webhooks)
- All API calls include proper headers and error handling

### 3. Input Validation
- All user inputs are validated and sanitized
- File uploads for GCash (legacy) are restricted to images only
- SQL injection protection via prepared statements

### 4. Session Management
- Payment data stored in session for security
- Session cleanup after successful payment
- CSRF protection through session validation

## 🧪 Testing Instructions

### 1. PayPal Sandbox Testing

1. **Setup PayPal Sandbox Account:**
   - Use `PAYPAL_MODE=sandbox` in your `.env`
   - Create sandbox accounts at [PayPal Developer](https://developer.paypal.com/developer/applications/)

2. **Test Payment Flow:**
   - Navigate to venue selection
   - Complete steps 1-3 of booking
   - On step 4 (payment), use PayPal Smart Buttons
   - Use sandbox test accounts for payment

3. **Expected Results:**
   - Order should be created in PayPal
   - Payment should complete successfully
   - Booking should be created with `payment_status = 'PAID'`

### 2. Database Verification

Check that the following fields are populated correctly:

```sql
SELECT 
    id, 
    payment_method, 
    payment_status, 
    paypal_order_id, 
    paypal_capture_id,
    payment_reference
FROM bookings 
WHERE payment_method = 'PAYPAL';
```

### 3. Error Testing

Test these scenarios:
- Invalid PayPal credentials (should show configuration error)
- Network timeout during payment (should handle gracefully)
- Cancelled payment (should return to booking page)
- Multiple payment attempts (should handle idempotently)

## 📡 API Reference

### `api/paypalCreateOrder.php`

**Method:** POST  
**Purpose:** Create a new PayPal order

**Request Body:**
```json
{
    "venue_id": "venue_id",
    "venue_price": 5000,
    "guest_count": 25,
    "booking_date": "2024-01-15",
    "event_type": "Wedding",
    "special_requests": "Outdoor setup preferred"
}
```

**Response:**
```json
{
    "success": true,
    "orderID": "5O190127TX5849112",
    "approveUrl": "https://www.paypal.com/checkoutnow?token=...",
    "total_amount": 5000,
    "currency": "PHP"
}
```

### `api/paypalCapture.php`

**Method:** POST  
**Purpose:** Capture PayPal payment after approval

**Request Body:**
```json
{
    "orderID": "5O190127TX5849112",
    "booking_data": {
        "total_amount": 5000
    }
}
```

**Response:**
```json
{
    "success": true,
    "captureID": "36S67920N9125101N",
    "orderID": "5O190127TX5849112",
    "amount": "5000.00",
    "currency": "PHP",
    "status": "COMPLETED",
    "payment_method": "PAYPAL",
    "message": "Payment captured successfully"
}
```

### `api/getPayPalClientId.php`

**Method:** GET  
**Purpose:** Get PayPal client configuration for frontend

**Response:**
```json
{
    "success": true,
    "clientId": "AYour_Client_ID",
    "mode": "sandbox",
    "environment": "sandbox",
    "currency": "PHP",
    "intent": "capture"
}
```

## 🔧 Customization

### 1. PayPal Button Styling

Modify the button configuration in `assets/js/paypal.js`:

```javascript
style: {
    layout: 'vertical',        // 'vertical' or 'horizontal'
    color: 'gold',             // 'gold', 'blue', 'silver', 'black'
    shape: 'rect',             // 'rect' or 'pill'
    label: 'paypal'            // 'paypal', 'checkout', 'buynow'
}
```

### 2. Payment Flow Customization

To modify the payment completion flow, edit the `handlePayPalApprove` function in `assets/js/paypal.js`.

### 3. Database Fields

Add custom fields to the booking record by modifying the `processPayPalPayment` function in `pages/booking.php`.

## 🔍 Troubleshooting

### Common Issues

1. **"PayPal SDK not loaded" Error**
   - Check that `api/getPayPalClientId.php` is accessible
   - Verify PayPal credentials in `.env`
   - Ensure network connectivity to PayPal

2. **Payment Amount Mismatch**
   - Verify venue pricing calculations
   - Check guest count and extra guest fees
   - Ensure currency is set to 'PHP'

3. **Booking Not Created After Payment**
   - Check PHP error logs
   - Verify database connectivity
   - Ensure user session is valid

4. **PayPal Buttons Not Appearing**
   - Check browser console for JavaScript errors
   - Verify PayPal Client ID is correct
   - Ensure proper script loading order

### Debug Mode

Enable debug logging by adding to your `.env`:

```bash
PAYPAL_DEBUG=true
```

Then check the PHP error log for detailed PayPal API responses.

### Log Files

PayPal integration logs are written to:
- PHP error log: `error_log()` calls
- Webhook logs: `api/paypalWebhook.php` logs
- JavaScript console: Browser developer tools

## 🚀 Production Deployment

### 1. Environment Changes
```bash
# Switch to live PayPal
PAYPAL_MODE=live

# Use real PayPal credentials
PAYPAL_CLIENT_ID=your-live-client-id
PAYPAL_SECRET=your-live-secret

# Set secure base URL
BASE_URL=https://yourdomain.com
```

### 2. Security Checklist
- [ ] Use HTTPS in production
- [ ] Set proper file permissions (644 for PHP files)
- [ ] Enable PayPal webhooks for additional verification
- [ ] Regular security updates for PayPal SDK
- [ ] Monitor PayPal API response logs

### 3. Backup Strategy
- Regular database backups including payment records
- Test restore procedures with PayPal transaction data
- Keep PayPal transaction logs for reconciliation

## 📞 Support

For issues with this PayPal integration:

1. Check the troubleshooting section above
2. Review PayPal Developer documentation: https://developer.paypal.com/docs/
3. Check PHP error logs for detailed error messages
4. Test with PayPal sandbox credentials first

## 🔄 Migration from GCash

The system maintains backward compatibility:
- GCash upload option still available as alternative
- Existing GCash bookings continue to work
- Admin can view both payment methods in reports
- Users can choose between PayPal and GCash

---

**Implementation Date:** November 2025  
**Version:** 1.0  
**Compatibility:** PHP 7.4+, PayPal REST API v2