# Production Checklist

## Application
- [x] Storefront pages consume live APIs (no mock data on core pages).
- [x] Cart API standardized and coupon endpoints implemented.
- [x] Address management API and checkout integration completed.
- [x] Inventory authority unified on `stocks` with reservation/release/commit lifecycle.
- [x] Shipping zones/rules/methods integrated for checkout quotes.
- [x] Payment lifecycle supports initialize/verify/webhook and order payment status updates.
- [x] Payment gateway behavior is admin-managed via `payment_gateways` with provider readiness checks.
- [x] Public checkout gateway options are API-driven (`/api/payments/gateways`) and filtered by enabled/visible/configured state.

## Security
- [x] Admin/vendor/customer middleware guards active.
- [x] Policies enforced for product/order/address/review ownership.
- [x] Auth route throttling enabled.
- [x] Secure headers middleware enabled.
- [x] Secrets removed from committed docs.

## Operations
- [x] Queue-based jobs for emails, payment verification, inventory adjustments, exports.
- [x] Health endpoint available (`/api/health`).
- [x] Request logging middleware enabled.
- [x] Slow query logging enabled.

## Quality
- [x] PHPUnit config restored.
- [x] Feature tests for Auth, Cart, Checkout, Order, Review, Admin, Address, Payment.
- [x] Frontend linting configured.
- [x] CI workflow for build/lint/migrate/test.

## Validation
- [x] `php artisan test` passing.
- [x] `npm run build` passing.
- [x] API routes validated with `php artisan route:list --path=api`.

## Remaining Hardening (Recommended)
- [ ] Raise backend coverage toward 70%+ with deeper edge-case tests.
- [ ] Enable CDN image optimization pipeline for uploaded product media.
- [ ] Add external APM integration (Sentry/New Relic/Datadog).
