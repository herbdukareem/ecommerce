# System Architecture

## Overview
The platform is a Laravel API + Vue 3 SPA monolith with clear domain modules and role-based access controls.

## Architecture Diagram
```mermaid
flowchart TD
    Browser[Vue SPA] -->|HTTPS JSON| API[Laravel API]
    API --> Auth[Sanctum + RBAC Middleware]
    API --> Catalog[Catalog + Reach Filters]
    API --> Cart[Cart + Coupon Engine]
    API --> Checkout[Checkout + Shipping Quote]
    API --> Payments[PaymentService + Gateway Interface]
    API --> Admin[Admin Modules]

    Checkout --> Inventory[InventoryService]
    Payments --> Queue[Queue Jobs]
    Admin --> Queue
    Checkout --> Queue

    Queue --> Worker[Queue Worker / Supervisor]
    API --> Redis[(Redis Cache + Queue)]
    API --> DB[(MySQL)]
    Worker --> DB

    API --> Logs[Request + Slow Query Logs]
    API --> Health[/api/health]
```

## Runtime Components
- Frontend: Vue 3, Pinia, Vue Router, Vite build with route-based lazy loading.
- Backend: Laravel 12, Sanctum auth, Spatie roles/permissions, Eloquent domain models.
- Async: Laravel queues for payment verification, inventory adjustment, email delivery, order exports.
- Data: MySQL (transactional), Redis (cache/queue), Storage for export files.

## Payment Gateway Governance
- Credentials and secrets are environment-driven (`config/services.php`), not stored in database.
- Runtime behavior is database-driven in `payment_gateways` (enabled, visible, default, sort order, mode, fees, currencies, extra_config).
- Checkout gateway discovery uses `GET /api/payments/gateways` and returns only enabled, visible, configured providers.
- Webhooks remain provider-scoped with `POST /api/payments/webhook/{provider}` to preserve historical transaction compatibility.
- `PaymentGatewayManager` enforces single default gateway and activation readiness checks.

## Security Layers
- Authentication: Sanctum bearer tokens.
- Authorization: `admin`, `vendor`, `customer` middleware + model policies.
- API protection: auth throttling, secure response headers, request logging.
