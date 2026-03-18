# API Documentation (Production)

## Auth
- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `GET /api/me`
- `POST /api/auth/forgot-password`
- `POST /api/auth/reset-password`

## Health / Observability
- `GET /api/health`

## Catalog
- `GET /api/products`
- `GET /api/products/{slug}`
- `GET /api/products/search`
- `GET /api/categories`
- `GET /api/attributes`

## Cart and Coupon
- `GET /api/cart`
- `POST /api/cart/items`
- `PUT /api/cart/items/{id}`
- `PATCH /api/cart/items/{id}`
- `DELETE /api/cart/items/{id}`
- `POST /api/cart/apply-coupon`
- `DELETE /api/cart/coupon`
- `DELETE /api/cart`
- `POST /api/cart/merge`

## Addresses
- `GET /api/addresses`
- `POST /api/addresses`
- `PUT /api/addresses/{id}`
- `DELETE /api/addresses/{id}`

## Checkout and Orders
- `POST /api/checkout/quote-shipping`
- `POST /api/checkout/place-order`
- `GET /api/orders`
- `GET /api/orders/{id}`
- `POST /api/orders/{id}/cancel`

## Payments
- `GET /api/payments/gateways`
- `POST /api/payments/orders/{orderId}/initialize`
- `POST /api/payments/{paymentId}/verify`
- `POST /api/payments/webhook/{provider}`

## Reviews
- `GET /api/products/{productId}/reviews`
- `POST /api/products/{productId}/reviews`
- `PATCH /api/reviews/{reviewId}`
- `DELETE /api/reviews/{reviewId}`
- `POST /api/reviews/{reviewId}/vote`

## Vendor
- `GET /api/vendor/products`
- `POST /api/vendor/products`
- `GET /api/vendor/products/{id}`
- `PATCH /api/vendor/products/{id}`
- `DELETE /api/vendor/products/{id}`
- `GET /api/vendor/orders`
- `PATCH /api/vendor/orders/{id}/status`
- `POST /api/vendor/orders/{id}/fulfill`

## Admin
- Dashboard/analytics, users, products, vendors, categories, shipping, settings, orders, reviews.
- Payment gateway behavior management:
	- `GET /api/admin/payment-gateways`
	- `PUT /api/admin/payment-gateways/{provider}`
	- `POST /api/admin/payment-gateways/reorder`
	- `POST /api/admin/payment-gateways/{provider}/set-default`
- All admin endpoints are under `/api/admin/*` and protected by admin middleware.
