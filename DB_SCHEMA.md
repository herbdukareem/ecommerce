# Database Schema (Production-Oriented)

## Core Commerce
- `users`
- `roles`, `permissions`, `model_has_roles`, `role_has_permissions`, `model_has_permissions`
- `products`, `product_images`
- `categories`, `category_product`
- `attributes`, `attribute_values`, `attribute_product`, `sku_attribute_value`
- `skus`
- `warehouses`, `stocks`

## Cart and Checkout
- `carts`, `cart_items`
- `coupons`
- `addresses`

## Orders and Payments
- `orders`, `order_items`, `order_fulfillments`
- `payments`

## Logistics
- `shipping_zones`, `shipping_zone_rules`, `shipping_methods`, `shipping_provider_accounts`

## Reviews
- `reviews`, `review_images`, `review_votes`

## Settings and Platform
- `settings`
- `personal_access_tokens`
- `sessions`
- `password_reset_tokens`

## Inventory Authority
Authoritative stock model is `stocks`:
- `available = on_hand - reserved`
- reservation at checkout
- release on cancellation
- commit (deduct on_hand) on delivery completion
- stock rows auto-provisioned on SKU creation
