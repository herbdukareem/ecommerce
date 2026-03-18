# Delivery Architecture Guide

## Overview
This platform uses a practical hybrid delivery model designed for Nigeria-like operations:
- Local manual dispatch for day-to-day fulfillment.
- Local delivery partners managed by admin users.
- Zone-based pricing with optional rule overrides.
- Structured customer addresses for accurate last-mile delivery.
- Logistics abstraction to plug in DHL/GIG/Kwik/Sendbox later.

## Delivery Data Model
- `addresses`: structured customer delivery addresses (`full_name`, `phone`, `state`, `city`, `area_or_district`, `landmark`, geo fields, default flag).
- `shipping_zones`: named coverage groups with `coverage_states`, `coverage_cities`, `coverage_areas`, `default_fee`, and fallback support.
- `shipping_methods`: delivery methods (`standard`, `express`, `pickup`) with base/surcharge config.
- `shipping_zone_rules`: per-zone/per-method pricing rules.
- `delivery_partners`: local dispatch partner records and area coverage.
- `orders`: immutable delivery snapshot fields (`delivery_snapshot`, `delivery_address_snapshot`, `delivery_fee`, partner and status fields).

## Address Management API
- `GET /api/addresses`
- `POST /api/addresses`
- `PUT /api/addresses/{id}`
- `DELETE /api/addresses/{id}`
- `PATCH /api/addresses/{id}/default`

Only authenticated customers can manage their own addresses.

## Zone Pricing Logic
1. Match destination against active zone coverage (`state/city/area`).
2. If no direct match, use fallback zone if configured.
3. Resolve active shipping methods.
4. For each method, apply matching zone rule by priority.
5. If no rule exists, use zone default fee or method base fee.
6. Apply optional surcharges and free-shipping thresholds.

## How To Add States/Cities/Areas
1. Go to admin shipping zones (`/admin/zones`).
2. Create or edit a zone.
3. Enter `coverage_states`, `coverage_cities`, `coverage_areas` as comma-separated values.
4. Set fallback zone for unmatched addresses.

## Delivery Partners Management
1. Go to `/admin/delivery-partners`.
2. Create partner profile with phone, optional email/company, and area coverage.
3. Toggle active/inactive status to control assignment availability.
4. Use status/coverage filters to find available partners.

## Assigning Orders
1. Open admin order detail (`/admin/orders/{id}`).
2. Assign partner and optional tracking code.
3. Update delivery status through lifecycle:
   - `pending_assignment -> assigned -> packed -> shipped -> in_transit -> delivered`
   - failure branches: `delivery_failed`, `returned`, `cancelled`
4. Add dispatch notes as needed.

## Checkout Delivery Flow
1. Customer selects/creates a structured address.
2. Checkout quotes shipping from server-side zone/method engine.
3. Customer chooses delivery method.
4. On order creation, delivery fee and address/method snapshot are stored immutably on order.

## Future DHL/GIG/Kwik Integration
Current abstraction classes:
- `App\Services\Logistics\LogisticsProviderInterface`
- `App\Services\Logistics\ManualLogisticsProvider`
- `App\Services\Logistics\ExternalApiLogisticsProvider`
- `App\Services\Logistics\LogisticsManager`

To add real provider support:
1. Implement provider-specific class using `LogisticsProviderInterface`.
2. Register class in `LogisticsManager`.
3. Map order dispatch payload and response normalization.
4. Keep manual provider as safe fallback.
