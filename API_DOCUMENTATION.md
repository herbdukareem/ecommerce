# API Documentation

## Base URL
```
http://localhost:8000/api
```

## Authentication
All authenticated endpoints require a Bearer token in the Authorization header:
```
Authorization: Bearer {token}
```

---

## Authentication Endpoints

### Register
```http
POST /auth/register
```

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response:**
```json
{
  "message": "User registered successfully",
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "roles": [...]
  }
}
```

### Login
```http
POST /auth/login
```

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "message": "Login successful",
  "token": "2|xyz789...",
  "user": {...}
}
```

### Logout
```http
POST /auth/logout
```
*Requires authentication*

**Response:**
```json
{
  "message": "Logged out successfully"
}
```

### Get Current User
```http
GET /me
```
*Requires authentication*

**Response:**
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "roles": [...]
  }
}
```

---

## Product Catalog Endpoints

### List Products
```http
GET /products
```

**Query Parameters:**
- `q` - Search query
- `category_id` - Filter by category
- `price_min` - Minimum price
- `price_max` - Maximum price
- `in_stock` - Filter in-stock items (boolean)
- `vendor_id` - Filter by vendor
- `sort` - Sort by (newest, oldest, price_asc, price_desc, name_asc, name_desc)
- `page` - Page number
- `per_page` - Items per page (max 100)

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Product Name",
      "slug": "product-name-abc123",
      "description": "Product description",
      "base_price": 99.99,
      "status": "active",
      "skus": [...],
      "categories": [...]
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 24,
    "total": 120
  },
  "facets": {
    "categories": [...],
    "price_range": {
      "min": 0,
      "max": 999
    }
  }
}
```

### Get Product Details
```http
GET /products/{slug}
```

**Response:**
```json
{
  "id": 1,
  "title": "Product Name",
  "slug": "product-name-abc123",
  "description": "Detailed description",
  "base_price": 99.99,
  "total_stock": 50,
  "skus": [
    {
      "id": 1,
      "sku_code": "SKU-ABC123",
      "price": 99.99,
      "stocks": [...]
    }
  ],
  "categories": [...],
  "vendor": {...}
}
```

### Search Products
```http
GET /products/search?q=laptop
```

**Response:**
```json
[
  {
    "id": 1,
    "title": "Laptop Pro",
    "slug": "laptop-pro-xyz",
    "base_price": 1299.99
  }
]
```

---

## Shopping Cart Endpoints

### Get Cart
```http
GET /cart
```
*Requires authentication*

**Response:**
```json
{
  "cart_id": 1,
  "items": [
    {
      "id": 1,
      "sku_id": 5,
      "sku_code": "SKU-ABC123",
      "product_id": 2,
      "product_title": "Product Name",
      "product_slug": "product-name",
      "price": 99.99,
      "quantity": 2,
      "subtotal": 199.98,
      "available_stock": 50,
      "in_stock": true
    }
  ],
  "total": 199.98,
  "item_count": 2
}
```

### Add Item to Cart
```http
POST /cart/items
```
*Requires authentication*

**Request Body:**
```json
{
  "sku_id": 5,
  "quantity": 2
}
```

**Response:**
```json
{
  "message": "Item added to cart",
  "cart_item": {...}
}
```

### Update Cart Item
```http
PATCH /cart/items/{id}
```
*Requires authentication*

**Request Body:**
```json
{
  "quantity": 3
}
```

### Remove Cart Item
```http
DELETE /cart/items/{id}
```
*Requires authentication*

### Clear Cart
```http
DELETE /cart
```
*Requires authentication*

---

## Checkout Endpoints

### Get Shipping Quotes
```http
POST /checkout/quote-shipping
```
*Requires authentication*

**Request Body:**
```json
{
  "destination": {
    "country": "US",
    "state": "CA",
    "zip": "90210"
  }
}
```

**Response:**
```json
{
  "quotes": [
    {
      "method": "standard",
      "name": "Standard Shipping (5-7 days)",
      "amount": 1000
    },
    {
      "method": "express",
      "name": "Express Shipping (2-3 days)",
      "amount": 2000
    }
  ]
}
```

### Place Order
```http
POST /checkout/place-order
```
*Requires authentication*

**Request Body:**
```json
{
  "address_id": 1,
  "payment_method": "card",
  "shipping_method": "standard"
}
```

**Response:**
```json
{
  "message": "Order placed successfully",
  "order": {
    "id": 1,
    "user_id": 1,
    "status": "pending",
    "payment_status": "pending",
    "subtotal": 199.98,
    "shipping_cost": 10.00,
    "total": 209.98,
    "items": [...],
    "shippingAddress": {...}
  }
}
```

---

## Order Endpoints

### List Orders
```http
GET /orders
```
*Requires authentication*

**Query Parameters:**
- `status` - Filter by status
- `payment_status` - Filter by payment status
- `page` - Page number

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "status": "pending",
      "payment_status": "pending",
      "total": 209.98,
      "placed_at": "2024-01-15T10:30:00Z",
      "items": [...],
      "shippingAddress": {...}
    }
  ],
  "meta": {...}
}
```

### Get Order Details
```http
GET /orders/{id}
```
*Requires authentication*

### Cancel Order
```http
POST /orders/{id}/cancel
```
*Requires authentication*

---

## Vendor Endpoints

### List Vendor Products
```http
GET /vendor/products
```
*Requires authentication (Vendor role)*

### Create Product
```http
POST /vendor/products
```
*Requires authentication (Vendor role)*

**Request Body:**
```json
{
  "title": "New Product",
  "description": "Product description",
  "base_price": 99.99,
  "status": "active",
  "category_ids": [1, 2]
}
```

### Update Product
```http
PATCH /vendor/products/{id}
```
*Requires authentication (Vendor role)*

### Delete Product
```http
DELETE /vendor/products/{id}
```
*Requires authentication (Vendor role)*

### List Vendor Orders
```http
GET /vendor/orders
```
*Requires authentication (Vendor role)*

### Update Order Status
```http
PATCH /vendor/orders/{id}/status
```
*Requires authentication (Vendor role)*

**Request Body:**
```json
{
  "status": "shipped"
}
```

### Fulfill Order
```http
POST /vendor/orders/{id}/fulfill
```
*Requires authentication (Vendor role)*

**Request Body:**
```json
{
  "warehouse_id": 1,
  "tracking_no": "TRACK123",
  "shipment_provider": "FedEx"
}
```

---

## Error Responses

### Validation Error (422)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

### Unauthorized (401)
```json
{
  "message": "Unauthenticated."
}
```

### Not Found (404)
```json
{
  "message": "Resource not found."
}
```

### Server Error (500)
```json
{
  "message": "Server error occurred.",
  "error": "Error details..."
}
```

