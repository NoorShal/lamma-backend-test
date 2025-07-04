# Product Management API (Lamma backend test)


## Database Schema

Our database uses a normalized structure to handle products efficiently:

**products** - Main products table
- Basic info: name, description, SKU, price
- Type: "simple" or "variable"
- Active status

**product_variations** - For variable products only
- Each variation has its own SKU and price
- Linked to main product

**product_attributes** - Attribute types (size, color, etc.)
- Just the attribute names like "size", "color"

**product_variation_attributes** - Links variations to their attributes
- Stores actual values like "Large", "Red"
- Each variation can have multiple attributes

**Why this approach scales well:** Instead of storing attributes as JSON (which can't be indexed), we use separate tables that allow fast database queries, proper indexing, and efficient filtering even with millions of products.

## Business Logic Assumptions

- Simple products have a single price and SKU
- Variable products don't require a main price (variations handle pricing)
- Each product variation must have a unique SKU
- Attribute names are case-insensitive and stored in lowercase
- Deleting a product automatically removes all its variations and attributes

## Setup Instructions

1. **Install dependencies**
   ```bash
   composer install
   ```

2. **Set up environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Configure database**
   Update `.env` with your database info:
   ```
   DB_DATABASE=your_database_name
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

4. **Run migrations and seed data**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

5. **Start the server**
   ```bash
   php artisan serve
   ```

## Testing with Postman

**Note:** A Postman collection file should be attached upon submission for easy testing of all endpoints.

### 1. Get all products
```
GET http://localhost:8000/api/products
```

### 2. Get products with filters
```
GET http://localhost:8000/api/products?type=variable
GET http://localhost:8000/api/products?name=shirt
GET http://localhost:8000/api/products?min_price=20&max_price=50
```

### 3. Create a simple product
```
POST http://localhost:8000/api/products
Content-Type: application/json

{
    "name": "Wireless Mouse",
    "description": "Ergonomic wireless mouse",
    "sku": "WM-001",
    "type": "simple",
    "price": 29.99,
    "is_active": true
}
```

### 4. Create a variable product
```
POST http://localhost:8000/api/products
Content-Type: application/json

{
    "name": "T-Shirt",
    "description": "Cotton t-shirt",
    "sku": "TS-001",
    "type": "variable",
    "is_active": true,
    "variations": [
        {
            "sku": "TS-001-S-RED",
            "price": 25.00,
            "attributes": [
                {"name": "size", "value": "S"},
                {"name": "color", "value": "Red"}
            ]
        },
        {
            "sku": "TS-001-M-RED",
            "price": 25.00,
            "attributes": [
                {"name": "size", "value": "M"},
                {"name": "color", "value": "Red"}
            ]
        }
    ]
}
```

### 5. Update a product
```
PUT http://localhost:8000/api/products/1
Content-Type: application/json

{
    "name": "Updated Product Name",
    "price": 35.99
}
```

### 6. Delete a product
```
DELETE http://localhost:8000/api/products/1
```

## Important Notes

- Always include `Accept: application/json` in your Postman headers
- The seeder creates sample products you can test with
- Variable products don't need a main price - variations have their own prices
- Simple products just need basic info, no variations

## Available Endpoints

- `GET /api/products` - List products with optional filters
- `GET /api/products/{id}` - Get single product
- `POST /api/products` - Create new product
- `PUT /api/products/{id}` - Update product
- `DELETE /api/products/{id}` - Delete product

**Note:** Authentication is not implemented as it was not required in the task specification. All endpoints are publicly accessible.

## Error Handling

The API uses global exception handling to ensure all error responses follow a consistent format. This is configured in `bootstrap/app.php` and automatically handles:

- **404 errors** (product not found): `{"success": false, "message": "Resource not found"}`
- **422 errors** (validation failed): `{"success": false, "message": "Validation failed", "errors": {...}}`
- **500 errors** (server errors): Handled by controllers with consistent format

This approach ensures all API responses follow the same structure without needing to modify individual controllers for centralized exception handling.


