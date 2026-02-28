-- Migration: add numeric status_code to orders and migrate legacy status text
-- Create master lookup table for status codes
CREATE TABLE IF NOT EXISTS status_codes (
  id TINYINT NOT NULL PRIMARY KEY,
  code TINYINT NOT NULL,
  label VARCHAR(64) NOT NULL,
  description TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Seed default status codes (id matches code for simplicity)
INSERT INTO status_codes (id,code,label,description)
VALUES
  (0,0,'Open','Pending / new orders'),
  (1,1,'Confirmed','Supplier confirmed / accepted'),
  (2,2,'Closed','Closed or cancelled orders')
ON DUPLICATE KEY UPDATE label=VALUES(label), description=VALUES(description);

-- Ensure `status_code` column exists on orders
ALTER TABLE orders
  ADD COLUMN IF NOT EXISTS status_code TINYINT DEFAULT 0;

-- Migrate existing string statuses to numeric codes (adjust variants as needed)
UPDATE orders SET status_code =
  CASE
    WHEN LOWER(COALESCE(status,'')) IN ('pending','open') THEN 0
    WHEN LOWER(COALESCE(status,'')) IN ('confirmed','accepted') THEN 1
    WHEN LOWER(COALESCE(status,'')) IN ('closed','cancelled') THEN 2
    ELSE 0
  END
  WHERE status_code IS NULL OR status_code = 0;

-- Add index for faster filtering
CREATE INDEX IF NOT EXISTS idx_orders_status_code ON orders(status_code);

-- Add foreign key from orders.status_code -> status_codes.id (requires InnoDB)
ALTER TABLE orders
  ADD CONSTRAINT fk_orders_status_code FOREIGN KEY (status_code) REFERENCES status_codes(id);
