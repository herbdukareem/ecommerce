# E-commerce Platform Skeleton

This repository contains a **Laravel 10** backend with a **Vue 3** single‑page
application (SPA) integrated directly into the framework’s `resources/js`
directory.  There is no separate `backend` or `frontend` folder – the
entire application lives at the top level of the repository.  This scaffold
is a starting point for a scalable, multi‑vendor e‑commerce platform and is
configured for:

- **Database**: MySQL (configured via `.env.example`)
- **Authentication**: Laravel Sanctum (stateless SPA & API)
- **Multi‑vendor checkout**: single order across multiple vendors with split fulfillment support

The backend provides a RESTful API with JWT‑style authentication via Sanctum, SKU‑level inventory tracking, a configurable logistics engine, and a clean domain structure.  
The frontend offers a reactive SPA built with Vue 3 and Pinia, including components for product listing, advanced filtering (the “Reach Filter”), product details with variant selection, a cart and checkout flow, and a basic admin/dashboard shell.

> **Note**: This is a scaffold, not a complete application. The goal is to give you an opinionated structure and enough starter code so you can immediately begin implementing the business logic.  
> Many features are stubbed out with comments indicating where to add code.

## Project Structure

```
ecommerce-platform/
├── app/                 # Laravel application code (controllers, models, services)
├── database/
│   └── migrations/      # Database schema definitions
├── resources/
│   ├── js/              # Vue 3 SPA code (components, stores, router, views)
│   └── views/           # Blade templates (e.g. app.blade.php mounting the SPA)
├── routes/
│   └── api.php          # API route definitions
├── composer.json        # Backend PHP dependencies
├── package.json         # Frontend dependencies and Vite/Tailwind scripts
├── vite.config.js       # Vite configuration (proxies API to Laravel)
├── tailwind.config.js   # Tailwind configuration
├── .env.example         # Environment example
└── README.md
```

## Getting Started

### Backend

1. Install PHP 8.2 and Composer.
2. Copy `.env.example` to `.env` and adjust database credentials.
3. Run `composer install` to install dependencies.
4. Generate an application key: `php artisan key:generate`.
5. Run migrations: `php artisan migrate`.
6. (Optional) Seed sample data: `php artisan db:seed`.
7. Start the local server: `php artisan serve` or use
   [Laravel Herd](https://herd.laravel.com/) for an easy local development
   environment.  (Docker configuration files have been removed in this
   structure.)

### Frontend

1. Install Node.js (v18+) and npm or pnpm.
2. Run `npm install` in the project root. The Vue 3 SPA lives in
   `resources/js` and uses the Vite configuration at the project root.
3. Start the development server: `npm run dev`. Vite proxies API requests to the Laravel backend on port 8000.

## Design Highlights

- **Sanctum** is set up for stateless SPA/API authentication; the frontend uses the provided `/sanctum/csrf-cookie` endpoint before login.
- **SKU‑based inventory**: the backend models `Sku` and `Stock` represent product variants and their quantities per warehouse.  
  The `InventoryService` coordinates reservations during checkout to prevent overselling.
- **Configurable logistics**: the `ShippingRateService` reads rules from the database and returns shipping quotes based on destination and weight/dimensions.  
  Live carrier integrations (e.g., DHL/FedEx) can be implemented via the `shipping_providers` table.
- **Reach Filter**: the frontend includes a reusable sidebar component that binds to the API filters.  
  On the backend the `CatalogController` delegates filtering to a `ReachFilterQueryBuilder` which uses indexed columns and caches facet counts in Redis.

## License

This project is provided as open source under the MIT license. Feel free to adapt it to your needs.