# Paystack Integration - Architecture & Diagrams

## System Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                     AYA CRAFTS PAYMENT SYSTEM                        │
└─────────────────────────────────────────────────────────────────────┘

                           FRONTEND (Browser)
                    ┌──────────────────────────────┐
                    │  checkout.php / checkout.js  │
                    │  - Cart Display              │
                    │  - Payment Modal             │
                    │  - Email Prompt              │
                    └──────────────────────────────┘
                                  │
                                  │ AJAX/Redirect
                                  ▼
┌──────────────────────────────────────────────────────────────────┐
│                        BACKEND (PHP)                              │
├──────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │   /actions/paystack_init_transaction.php                │   │
│  │   - Validate email & amount                             │   │
│  │   - Call Paystack initialize API                        │   │
│  │   - Return authorization URL                            │   │
│  └──────────────────────────────────────────────────────────┘   │
│                           │                                       │
│                           ▼                                       │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │        PAYSTACK PAYMENT GATEWAY (External)              │   │
│  │   https://checkout.paystack.com/                        │   │
│  │   - Customer enters card details                        │   │
│  │   - 3D Secure verification                              │   │
│  │   - Payment processing                                  │   │
│  │   - Redirect back with reference                        │   │
│  └──────────────────────────────────────────────────────────┘   │
│                           │                                       │
│                           ▼                                       │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │   /view/paystack_callback.php                           │   │
│  │   - Receive reference from Paystack                     │   │
│  │   - Trigger payment verification                        │   │
│  └──────────────────────────────────────────────────────────┘   │
│                           │                                       │
│                           ▼                                       │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │   /actions/paystack_verify_payment.php                  │   │
│  │   - Call Paystack verify API                            │   │
│  │   - Validate payment status                             │   │
│  │   - Validate amount                                     │   │
│  │   - Create order (if verified)                          │   │
│  │   - Record payment details                              │   │
│  │   - Empty cart                                          │   │
│  └──────────────────────────────────────────────────────────┘   │
│                           │                                       │
│                           ▼                                       │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │        DATABASE (MySQL)                                 │   │
│  │   ├── orders (order created)                            │   │
│  │   ├── orderdetails (items added)                        │   │
│  │   ├── payment (payment recorded)                        │   │
│  │   └── cart (items removed)                              │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                   │
└──────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
                           FRONTEND (Browser)
                    ┌──────────────────────────────┐
                    │  payment_success.php         │
                    │  - Show order confirmation   │
                    │  - Display invoice number    │
                    │  - Show payment reference    │
                    │  - Navigation options        │
                    └──────────────────────────────┘
```

## Payment Flow Sequence Diagram

```
Customer       Frontend         Backend              Paystack         Database
   │              │               │                     │                 │
   │ Load          │               │                     │                 │
   │ Checkout      │               │                     │                 │
   ├─────────────>│               │                     │                 │
   │              │               │                     │                 │
   │ Click Pay    │               │                     │                 │
   │ Now          │               │                     │                 │
   ├─────────────>│               │                     │                 │
   │              │               │                     │                 │
   │ Prompt       │               │                     │                 │
   │ Email        │               │                     │                 │
   ├──────────────│               │                     │                 │
   │              │               │                     │                 │
   │ POST         │               │                     │                 │
   │ Init Payment │               │                     │                 │
   │              ├──────────────>│                     │                 │
   │              │               │                     │                 │
   │              │               │ Validate Email     │                 │
   │              │               │ Generate Ref       │                 │
   │              │               │ Initialize API Call│                 │
   │              │               ├────────────────────>│                 │
   │              │               │                     │                 │
   │              │               │  Auth URL +        │                 │
   │              │               │  Reference         │                 │
   │              │               │<────────────────────┤                 │
   │              │               │                     │                 │
   │ Redirect     │               │                     │                 │
   │ to Paystack  │<──────────────┤                     │                 │
   ├─────────────────────────────>│                     │                 │
   │              │               │                     │                 │
   │ Enter Card   │               │                     │                 │
   │ Details      │               │                     │                 │
   ├─────────────────────────────────────────────────>│                 │
   │              │               │                     │                 │
   │ 3D Secure    │               │                     │                 │
   │ Verify       │               │                     │                 │
   ├─────────────────────────────────────────────────>│                 │
   │              │               │                     │                 │
   │ Complete     │               │                     │                 │
   │ Payment      │               │                     │                 │
   ├─────────────────────────────────────────────────>│                 │
   │              │               │                     │                 │
   │ Redirect     │               │                     │                 │
   │ to Callback  │<───────────────────────────────────┤                 │
   ├─────────────>│               │                     │                 │
   │              │               │                     │                 │
   │              │  POST Verify  │                     │                 │
   │              │  Payment      │                     │                 │
   │              ├──────────────>│                     │                 │
   │              │               │                     │                 │
   │              │               │ Call Verify API    │                 │
   │              │               ├────────────────────>│                 │
   │              │               │                     │                 │
   │              │               │ Transaction Data   │                 │
   │              │               │<────────────────────┤                 │
   │              │               │                     │                 │
   │              │               │ Validate Status    │                 │
   │              │               │ Validate Amount    │                 │
   │              │               │ Create Order       ├────────────────>│
   │              │               │ Add Items          ├────────────────>│
   │              │               │ Record Payment     ├────────────────>│
   │              │               │ Empty Cart         ├────────────────>│
   │              │               │                     │                 │
   │              │ Success JSON  │                     │                 │
   │              │<──────────────┤                     │                 │
   │              │               │                     │                 │
   │ Redirect     │               │                     │                 │
   │ Success Page │<──────────────┤                     │                 │
   ├─────────────>│               │                     │                 │
   │              │               │                     │                 │
   │ View Order   │               │                     │                 │
   │ Details      │               │                     │                 │
   │ Confirmation │               │                     │                 │
   └──────────────┘               │                     │                 │
                                  │                     │                 │
```

## Transaction Reference Flow

```
Customer ID: 5
Current Timestamp: 1699876543

Reference Generation:
┌─────────────────────────────────────────┐
│ AYA-5-1699876543                        │
├─────────────────────────────────────────┤
│ AYA           = Store identifier        │
│ 5             = Customer ID             │
│ 1699876543    = Unix timestamp          │
└─────────────────────────────────────────┘

Storage Path:
Payment Table
├── transaction_ref: "AYA-5-1699876543"
├── authorization_code: "xxxxx..."
├── payment_method: "paystack"
└── payment_channel: "card"
```

## Error Handling Flow

```
                         Error Occurs?
                              │
                    ┌─────────┴─────────┐
                    │                   │
            Network Error        Validation Error
                    │                   │
                    ▼                   ▼
        Connection Attempt         Validate Data
        Fails to Paystack          (Email, Amount, etc)
                    │                   │
                    │                   ├─ Invalid Email
                    │                   │  "Invalid email address"
                    │                   │
                    │                   ├─ Invalid Amount
                    │                   │  "Amount must be > 0"
                    │                   │
                    │                   └─ Other Validation
                    │                      Specific error message
                    │
                    ▼
            Paystack API Error
                    │
        ┌───────────┼────────────┐
        │           │            │
    Invalid      Payment      API Rate
    Reference    Failed       Limited
        │           │            │
        ▼           ▼            ▼
    "No such    "Payment    "API Error
     reference" status"     Retry later"

                    │
                    ▼
        Send Error to Frontend
        JSON Response
        {"status": "error", "message": "..."}
                    │
                    ▼
        Show Toast Notification
        to Customer
                    │
                    ▼
        Allow Retry
        └─> Back to Checkout
```

## Database Schema - Payment Table

```
payment Table (Original + New Columns)
┌──────────────────────────────┐
│ EXISTING COLUMNS             │
├──────────────────────────────┤
│ payment_id (PK)              │
│ amt (decimal)                │
│ customer_id (FK)             │
│ order_id (FK)                │
│ currency (varchar)           │
│ payment_date (date)          │
└──────────────────────────────┘

                │ +
┌──────────────────────────────────┐
│ NEW PAYSTACK COLUMNS             │
├──────────────────────────────────┤
│ payment_method (varchar)         │
│ transaction_ref (varchar)        │
│ authorization_code (varchar)     │
│ payment_channel (varchar)        │
└──────────────────────────────────┘

Examples of Data:
┌─────────────────────────────────────────┐
│ Payment Record After Paystack Payment   │
├─────────────────────────────────────────┤
│ amt: 150.50                             │
│ customer_id: 5                          │
│ order_id: 42                            │
│ currency: GHS                           │
│ payment_date: 2023-11-13                │
│ payment_method: paystack                │
│ transaction_ref: AYA-5-1699876543      │
│ authorization_code: 9g1d3y3e84x...     │
│ payment_channel: card                   │
└─────────────────────────────────────────┘
```

## File Dependencies

```
checkout.php (View)
    │
    ├─→ checkout.js (Client-side logic)
    │    │
    │    ├─→ paystack_init_transaction.php (Initialize)
    │    │
    │    └─→ paystack_callback.php (Callback)
    │         │
    │         └─→ paystack_verify_payment.php (Verify)
    │              │
    │              ├─→ paystack_config.php (Config & API)
    │              │
    │              ├─→ order_controller.php (Controller)
    │              │    │
    │              │    └─→ order_class.php (Class)
    │              │         │
    │              │         └─→ db_class.php (Database)
    │              │
    │              └─→ cart_controller.php (Get cart items)
    │
    └─→ paystack_config.php (Config)

payment_success.php (Final page)
    └─→ No dependencies (displays passed data)
```

## API Call Sequence

```
INITIALIZATION CALL
───────────────────

POST /actions/paystack_init_transaction.php
Content-Type: application/json

Request Body:
{
    "amount": 150.50,
    "email": "customer@example.com"
}

Response:
{
    "status": "success",
    "authorization_url": "https://checkout.paystack.com/...",
    "reference": "AYA-5-1699876543",
    "access_code": "xxxxx",
    "message": "Redirecting to payment gateway..."
}


VERIFICATION CALL
─────────────────

POST /actions/paystack_verify_payment.php
Content-Type: application/json

Request Body:
{
    "reference": "AYA-5-1699876543",
    "cart_items": null,
    "total_amount": 150.50
}

Response (Success):
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
    "payment_reference": "AYA-5-1699876543",
    "payment_method": "Card",
    "customer_email": "customer@example.com"
}

Response (Failure):
{
    "status": "error",
    "verified": false,
    "message": "Payment verification failed: Payment status is pending"
}
```

## File Size Summary

```
New Files:
├── settings/paystack_config.php             
├── actions/paystack_init_transaction.php    
├── actions/paystack_verify_payment.php       
├── view/paystack_callback.php                
└── view/payment_success.php

Modified Files:
├── js/checkout.js        (Updated)
├── view/checkout.php     (Updated)
├── classes/order_class.php (Enhanced)
└── controllers/order_controller.php (Updated)