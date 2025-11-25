ALTER TABLE payment 
ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) COMMENT 'Payment method: paystack, cash, bank_transfer, etc.',
ADD COLUMN IF NOT EXISTS transaction_ref VARCHAR(100) COMMENT 'Paystack transaction reference',
ADD COLUMN IF NOT EXISTS authorization_code VARCHAR(100) COMMENT 'Authorization code from payment gateway',
ADD COLUMN IF NOT EXISTS payment_channel VARCHAR(50) COMMENT 'Payment channel: card, mobile_money, etc.';

ALTER TABLE payment ADD INDEX idx_transaction_ref (transaction_ref);
ALTER TABLE payment ADD INDEX idx_payment_method (payment_method);