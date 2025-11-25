# Paystack Public Key Configuration Guide

## Current Issue

You're seeing this warning:
```
⚠ Paystack public key not configured. Using Standard (redirect) method only.
```

This means your Paystack public key is still set to the placeholder value and needs to be configured.

## Quick Fix (2 Steps)

### Step 1: Get Your Paystack Public Key

1. **Log in to Paystack Dashboard:**
   - Go to: https://dashboard.paystack.com
   - Sign in with your Paystack account

2. **Navigate to API Keys:**
   - Click **Settings** (gear icon) in the left sidebar
   - Click **API Keys & Webhooks**

3. **Copy Your Public Key:**
   - Find the **Public Key** section
   - Copy the key that starts with `pk_test_` (for testing) or `pk_live_` (for production)
   - Example: `pk_test_1234567890abcdefghijklmnopqrstuvwxyz`

### Step 2: Add Your Key to Configuration

1. **Open the config file:**
   - File: `settings/paystack_config.php`
   - Location: `C:\xampp\htdocs\ecommerce-authent\settings\paystack_config.php`

2. **Find this line (around line 14):**
   ```php
   define('PAYSTACK_PUBLIC_KEY', 'pk_test_YOUR_PUBLIC_KEY_HERE');
   ```

3. **Replace the placeholder:**
   ```php
   define('PAYSTACK_PUBLIC_KEY', 'pk_test_YOUR_ACTUAL_KEY_HERE');
   ```
   
   Replace `pk_test_YOUR_ACTUAL_KEY_HERE` with the key you copied from Paystack dashboard.

4. **Also update the Secret Key (for server-side operations):**
   ```php
   define('PAYSTACK_SECRET_KEY', 'sk_test_YOUR_ACTUAL_SECRET_KEY_HERE');
   ```

5. **Save the file**

## Example Configuration

**Before (Placeholder):**
```php
define('PAYSTACK_SECRET_KEY', 'sk_test_YOUR_SECRET_KEY_HERE');
define('PAYSTACK_PUBLIC_KEY', 'pk_test_YOUR_PUBLIC_KEY_HERE');
```

**After (With Real Keys):**
```php
define('PAYSTACK_SECRET_KEY', 'sk_test_1234567890abcdefghijklmnopqrstuvwxyz');
define('PAYSTACK_PUBLIC_KEY', 'pk_test_abcdefghijklmnopqrstuvwxyz1234567890');
```

## Verify It's Working

1. **Clear your browser cache** (or do a hard refresh: Ctrl+F5)

2. **Go to checkout page**

3. **Open browser console** (F12)

4. **Look for this message:**
   - ✅ `✓ Paystack public key set successfully` = Working!
   - ❌ `⚠ Paystack public key not configured` = Still using placeholder

5. **Test payment:**
   - Click "Pay Now"
   - If using Inline method: Popup should appear
   - If using Standard method: You'll be redirected to Paystack

## Test vs Live Keys

### Test Keys (Development)
- **Public Key:** Starts with `pk_test_`
- **Secret Key:** Starts with `sk_test_`
- **Use for:** Testing and development
- **Safe to:** Commit to version control (but not recommended)

### Live Keys (Production)
- **Public Key:** Starts with `pk_live_`
- **Secret Key:** Starts with `sk_live_`
- **Use for:** Real payments on live site
- **NEVER:** Commit to version control!

## How the Key is Used

The public key is automatically:
1. Loaded from `settings/paystack_config.php`
2. Passed to JavaScript in `checkout.php`
3. Set using `PaystackPop.setPublicKey()` when the page loads
4. Used to initialize Paystack Inline (popup) payments

## Troubleshooting

### Still seeing the warning?

1. **Check the config file:**
   - Make sure you saved the file
   - Verify the key doesn't have extra spaces
   - Ensure the key is wrapped in quotes: `'pk_test_...'`

2. **Check browser console:**
   - Look for: `window.PAYSTACK_PUBLIC_KEY`
   - Should show your actual key (not the placeholder)

3. **Clear cache:**
   - Hard refresh: Ctrl+F5
   - Or clear browser cache

4. **Check file path:**
   - Make sure you edited: `settings/paystack_config.php`
   - Not: `payment_sample/settings/paystack_config.php`

### Key format issues?

- ✅ Correct: `'pk_test_1234567890abcdef'`
- ❌ Wrong: `pk_test_1234567890abcdef` (missing quotes)
- ❌ Wrong: `'pk_test_1234567890abcdef '` (extra space)
- ❌ Wrong: `'pk_test_YOUR_PUBLIC_KEY_HERE'` (still placeholder)

## Security Notes

- ✅ **Public Key is safe to expose** - It's designed to be used in JavaScript
- ❌ **Secret Key must be kept private** - Never expose in JavaScript or frontend code
- ✅ **Public Key can be in version control** - But it's better to use environment variables
- ❌ **Secret Key should NOT be in version control** - Use environment variables or secure config

## Need Help?

If you're still having issues:

1. **Verify your Paystack account:**
   - Make sure you're logged into the correct Paystack account
   - Check that API keys are enabled in your dashboard

2. **Check for typos:**
   - Copy-paste the key directly (don't type it)
   - Make sure there are no extra characters

3. **Test with a simple script:**
   ```javascript
   console.log('Public Key:', window.PAYSTACK_PUBLIC_KEY);
   ```
   Should show your actual key, not the placeholder.

4. **Check PHP error logs:**
   - Look for any errors related to Paystack config
   - File: `C:\xampp\php\logs\php_error_log`

## Summary

**The fix is simple:**
1. Get your public key from Paystack dashboard
2. Replace the placeholder in `settings/paystack_config.php`
3. Save and refresh

That's it! Once configured, the warning will disappear and you'll be able to use Paystack Inline (popup) payments.

