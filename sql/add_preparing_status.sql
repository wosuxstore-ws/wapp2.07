-- Run this ONCE (phpMyAdmin > your database > SQL tab) BEFORE uploading the new PHP files.
--
-- `orders.status` is an ENUM, and 'preparing' is not one of its allowed values yet, so
-- without this the "Move to Preparing" button would fail with a server error.
--
-- Existing orders keep their current status; only the list of allowed values grows.
ALTER TABLE `orders`
  MODIFY `status`
  ENUM('pending','paid','processing','preparing','shipped','delivered','cancelled')
  NOT NULL DEFAULT 'pending';
