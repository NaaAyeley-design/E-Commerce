# Paystack Payment Verification - Diagnostic Guide

## ✅ STEP 1 COMPLETE: Diagnostic Version Created

I've created a comprehensive diagnostic version that logs every step of the payment verification process.

### Files Created/Modified:

1. **`actions/paystack_verify_payment_diagnostic.php`** (NEW)
   - Comprehensive logging at every step
   - Detailed error tracking
   - Returns full diagnostic JSON response

2. **`public_html/assets/js/checkout.js`** (MODIFIED)
   - Temporarily using diagnostic endpoint
   - Enhanced console logging
   - Shows diagnostic information in browser console

---

## 📋 STEP 2: Run a Test Payment

### Instructions:

1. **Make a test payment:**
   - Go to your checkout page
   - Add items to cart
   - Proceed to payment
   - Complete payment on Paystack (use test card: 4084084084084081)
   - Amount: GHS 1 or GHS 5 (small test amount)

2. **After payment completes, check THREE places:**

   #### A. Browser Console (F12 → Console tab)
   - Look for: `=== PAYMENT VERIFICATION START ===`
   - Look for: `=== DIAGNOSTIC INFORMATION ===`
   - Look for: `=== ERRORS DETECTED ===` (if any)
   - **Copy ALL console output** starting from "PAYMENT VERIFICATION START"

   #### B. PHP Error Log: `C:\xampp\php\logs\php_error_log`
   - Open this file in a text editor
   - Search for: `PAYSTACK VERIFICATION DIAGNOSTIC START`
   - **Copy ALL log entries** from that point forward
   - Look for the first `✗` (failure marker) - that's where it fails!

   #### C. Apache Error Log: `C:\xampp\apache\logs\error.log`
   - Check this file as well for any additional errors

3. **Find the failure point:**
   - Look for the first log entry with `✗` (cross mark)
   - That's exactly where the verification is failing
   - The step before that `✗` is the last successful step

---

## 🔍 STEP 3: Common Issues & Fixes

Based on the diagnostic output, the issue will be one of these:

### ISSUE A: Secret Key Problem
**Symptoms:**
- Log shows: `✗ Secret key not configured properly`
- Log shows: `Secret key starts with sk_test: NO`

**Fix:**
```php
// In settings/paystack_config.php
// Make sure your secret key starts with 'sk_test_' (not 'pk_test_')
define('PAYSTACK_SECRET_KEY', 'sk_test_YOUR_ACTUAL_KEY_HERE');
```

---

### ISSUE B: API Call Failing
**Symptoms:**
- Log shows: `✗ No response from Paystack API`
- Log shows: `✗ Paystack API call exception`
- HTTP status code is not 200

**Fix:**
- Check internet connection
- Verify Paystack API is accessible
- Check if cURL is enabled in PHP
- Verify secret key is correct

---

### ISSUE C: Database Connection Failing
**Symptoms:**
- Log shows: `✗ Database connection FAILED`
- Log shows: `✗ Database connection exception`

**Fix:**
- Check database credentials in `settings/db_cred.php`
- Verify database server is running
- Check if database exists

---

### ISSUE D: Payment Status Check Failing
**Symptoms:**
- Log shows: `✗ Payment status is not successful`
- Payment status is not 'success', 'successful', or 'completed'

**Fix:**
- Check Paystack dashboard to confirm payment status
- Verify the payment actually completed
- Check if payment is still pending

---

### ISSUE E: Amount Mismatch
**Symptoms:**
- Log shows: `✗ Amount mismatch`
- Expected amount ≠ Paid amount

**Fix:**
- Verify cart total matches payment amount
- Check currency conversion (pesewas to cedis)
- Allow small rounding differences (0.01 tolerance)

---

### ISSUE F: Order Creation Failing
**Symptoms:**
- Log shows: `✗ Order creation failed`
- `order_id` is 0 or false

**Fix:**
- Check `orders` table exists
- Verify table structure matches code
- Check database permissions
- Review `order_class::create_order()` method

---

### ISSUE G: Payment Recording Failing
**Symptoms:**
- Log shows: `✗ Payment recording failed`
- `payment_id` is 0 or false

**Fix:**
- Check `payment` table exists
- Verify table structure
- Check if `transaction_ref` column exists
- Review `order_class::record_payment()` method

---

### ISSUE H: Order Update Failing
**Symptoms:**
- Log shows: `✗ Order status update FAILED`
- Order status remains 'pending'

**Fix:**
- Check `orders` table structure
- Verify `order_status` and `invoice_no` columns exist
- Review `order_class::update_order_complete()` method

---

## 🛠️ STEP 4: Implement the Fix

Once you identify the issue from the diagnostic:

1. **Fix the specific issue** in the main verification file (`actions/paystack_verify_payment.php`)
2. **Keep all security checks** in place
3. **Maintain proper verification flow**
4. **Test with another payment**

---

## 🔄 STEP 5: Switch Back to Production

After fixing the issue:

1. **Test with diagnostic version** to confirm fix works
2. **Update `checkout.js`** to use production endpoint:
   ```javascript
   // Change this line back:
   const verifyUrl = ... + '/actions/paystack_verify_payment.php';
   // (remove '_diagnostic' from filename)
   ```
3. **Remove or archive** the diagnostic file (optional, but recommended)
4. **Test one more time** with production version

---

## 📊 Diagnostic Response Structure

The diagnostic version returns JSON with this structure:

```json
{
  "success": true/false,
  "verified": true/false,
  "message": "Error or success message",
  "order_id": 123,
  "invoice_no": "KENTE-123-1234567890",
  "diagnostic": {
    "timestamp": "2024-01-01 12:00:00",
    "steps": ["Step 1...", "Step 2..."],
    "errors": ["Error 1", "Error 2"],
    "reference": "KENTE-123-1234567890",
    "paystack_api_called": true,
    "payment_verified": true,
    "database_connected": true,
    "payment_inserted": true,
    "order_created": true,
    "order_items_added": true,
    "order_updated": true
  }
}
```

---

## ⚠️ IMPORTANT NOTES

1. **Never remove verification** - it's critical for security
2. **Always verify on backend** - never trust frontend callbacks
3. **Check all three logs** - browser console, PHP error log, Apache log
4. **Look for the first ✗** - that's your failure point
5. **Test with small amounts** - GHS 1-5 for testing

---

## 📝 Next Steps

1. ✅ Diagnostic version created
2. ⏳ **YOU: Make a test payment**
3. ⏳ **YOU: Share diagnostic output** (browser console + PHP error log)
4. ⏳ **ME: Identify exact failure point**
5. ⏳ **ME: Implement fix**
6. ⏳ **YOU: Test again**
7. ⏳ **YOU: Switch back to production**

---

## 🆘 Need Help?

If you're stuck:
1. Share the diagnostic output (all three logs)
2. Share the exact error message
3. Share which step shows the first `✗`
4. I'll identify and fix the issue immediately

---

**Ready to test? Make a payment and share the diagnostic output!**

