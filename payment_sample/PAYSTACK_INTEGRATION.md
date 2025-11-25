# Paystack Payment Integration - Aya Crafts

## Files Modified/Created

### New Files Created
1. **`settings/paystack_config.php`** - Paystack configuration and API helper functions
2. **`actions/paystack_init_transaction.php`** - Initialize Paystack transactions
3. **`actions/paystack_verify_payment.php`** - Verify payments after customer returns from Paystack
4. **`view/paystack_callback.php`** - Callback page after Paystack payment process
5. **`view/payment_success.php`** - Success page displayed after verified payment

### Modified Files
1. **`js/checkout.js`** - Updated to use Paystack instead of dummy payment
2. **`view/checkout.php`** - Updated modal to show Paystack payment
3. **`classes/order_class.php`** - Enhanced `record_payment()` to store payment method details
4. **`controllers/order_controller.php`** - Updated function signature for extended payment tracking

## Configuration

### API Keys
The following Paystack API keys are configured in `settings/paystack_config.php`:
- **Secret Key (sk_test_...)**: Used for server-side API calls
- **Public Key (pk_test_...)**: Would be used for client-side Paystack JS (optional)

### Base Configuration
```php
define('APP_ENVIRONMENT', 'test'); 
define('APP_BASE_URL', 'http://localhost/aya_crafts');
define('PAYSTACK_CALLBACK_URL', APP_BASE_URL . '/view/paystack_callback.php');
```

## Payment Flow

### 1. Customer Checkout Process
```
Customer Reviews Cart → Clicks "Proceed to Payment" 
    ↓
Enter Email in Prompt → "Pay Now" Button
    ↓
paystack_init_transaction.php (Initialize)
    ↓
Redirect to Paystack Payment Gateway
    ↓
Customer Enters Payment Details & Confirms
    ↓
Paystack Redirects to paystack_callback.php with Reference
```

### 2. Payment Verification & Order Creation
```
paystack_callback.php (Receives Reference)
    ↓
Calls paystack_verify_payment.php
    ↓
Verifies Transaction with Paystack API
    ↓
Creates Order in Database (if verified)
    ↓
Records Payment Details
    ↓
Empties Customer Cart
    ↓
Redirects to payment_success.php
```

## API Endpoints

### 1. Initialize Transaction
**File**: `actions/paystack_init_transaction.php`
**Method**: POST
**Request**:
```json
{
    "amount": 150.50,
    "email": "customer@example.com"
}
```
**Response**:
```json
{
    "status": "success",
    "authorization_url": "https://checkout.paystack.com/...",
    "reference": "AYA-123-1699876543",
    "access_code": "...",
    "message": "Redirecting to payment gateway..."
}
```

### 2. Verify Payment
**File**: `actions/paystack_verify_payment.php`
**Method**: POST
**Request**:
```json
{
    "reference": "AYA-123-1699876543",
    "cart_items": null,
    "total_amount": 150.50
}
```
**Response** (Success):
```json
{
    "status": "success",
    "verified": true,
    "message": "Payment successful! Order confirmed.",
    "order_id": 42,
    "invoice_no": "INV-20231113-ABC123",
    "total_amount": "150.50",
    "currency": "GHS",
    "order_date": "November 13, 2023",
    "customer_name": "John Doe",
    "item_count": 3,
    "payment_reference": "AYA-123-1699876543",
    "payment_method": "Card",
    "customer_email": "customer@example.com"
}
```

## Database Schema Updates

### Payment Table Additions
The `payment` table now supports additional fields for Paystack:

```sql
ALTER TABLE payment ADD COLUMN (
    payment_method VARCHAR(50),
    transaction_ref VARCHAR(100),
    authorization_code VARCHAR(100),
    payment_channel VARCHAR(50)
);
```

**Fields**:
- `payment_method`: 'paystack', 'cash', 'bank_transfer', etc.
- `transaction_ref`: Paystack transaction reference
- `authorization_code`: Authorization code from payment gateway
- `payment_channel`: 'card', 'mobile_money', etc.

## Security Features

### 1. Amount Verification
- Server verifies that the amount paid matches the order total (with 1 pesewa tolerance)
- Prevents payment tampering

### 2. Payment Status Validation
- Only 'success' status transactions are accepted
- Failed, pending, or cancelled payments are rejected

### 3. Reference Tracking
- Unique references generated for each transaction
- Prevents duplicate orders
- Stored for audit trails

### 4. Secure API Communication
- Uses HTTPS for all Paystack API calls
- Secret key sent in Authorization header
- Error logging for debugging

### 5. Transaction Atomicity
- Database transactions ensure order is created only when payment is verified
- Automatic rollback if any step fails

## Error Handling

### Common Error Scenarios

1. **Invalid Amount**
   - Error: "Amount must be greater than 0"
   - Handle: Validate amount on client and server

2. **Email Validation**
   - Error: "Invalid email address"
   - Handle: Prompt user for valid email

3. **Payment Verification Failed**
   - Error: Various based on Paystack response
   - Handle: Redirect to checkout with error message, user can retry

4. **Cart Empty**
   - Error: "Cart is empty"
   - Handle: Redirect to cart page

5. **Database Transaction Failure**
   - Error: Specific database error message
   - Handle: Payment marked as failed, user can retry

## Testing

### Test Card Details
Use these Paystack test cards for testing:

**Successful Payment**:
- Card Number: 4111111111111111
- Expiry: Any future date (MM/YY)
- CVV: Any 3-digit number

**Declined Payment**:
- Card Number: 5425233010103010

For OTP testing, use: **123456**

### Test Transactions
1. Go to checkout page
2. Click "Proceed to Payment"
3. Enter test email (any valid format)
4. Use test card details above
5. System will verify and create order