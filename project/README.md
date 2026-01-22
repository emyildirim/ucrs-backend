# University Course Registration System - Backend API

A Laravel based REST API backend for the University Course Registration System (UCRS). This API-only backend is designed to work with a separate React frontend.

## Quick Start

### Prerequisites
- PHP 8.2 or higher
- Composer
- SQLite (default) or MySQL/PostgreSQL

### Installation

```bash
# Clone the repo
cd ucrs-backend/project

# Install dependencies
composer install

# Set up environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Create database (SQLite)
touch database/database.sqlite

# Run migrations
php artisan migrate

# Start development server
php artisan serve
```

The API will be available at `http://localhost:8000`

## API Documentation

Interactive API documentation is available via Swagger UI:

**URL:** http://localhost:8000/api/documentation


### Public Endpoints

#### Hello World
```http
GET /api/hello
```

**Response:**
```json
{
  "message": "Hello World from University Course Registration System API",
  "version": "1.0.0",
  "timestamp": "2026-01-20T16:10:55+00:00"
}
```

#### Get Authenticated User
```http
GET /api/user
Authorization: Bearer {token}
```

**Response:**
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "email_verified_at": null,
  "created_at": "2026-01-20T16:00:00.000000Z",
  "updated_at": "2026-01-20T16:00:00.000000Z"
}
```

## Authentication

This API uses **Laravel Sanctum** for token-based authentication.

### How to Authenticate

1. **Login/Register** (endpoints to be implemented)
2. **Receive token** from authentication endpoint
3. **Include token** in requests:
   ```
   Authorization: Bearer YOUR_TOKEN_HERE
   ```

## Tech Stack

- **Framework:** Laravel 12
- **Authentication:** Laravel Sanctum
- **Documentation:** Swagger/OpenAPI (L5-Swagger)
- **Database:** SQLite (development) / MySQL or PostgreSQL (production)
- **Testing:** Pest PHP

## Project Structure

```
ucrs-backend/project/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── Api/           # API controllers
│   ├── Models/                # Eloquent models
│   └── Providers/
├── config/
│   ├── sanctum.php            # Sanctum configuration
│   ├── cors.php               # CORS settings
│   └── l5-swagger.php         # Swagger configuration
├── database/
│   ├── migrations/            # Database migrations
│   └── seeders/               # Database seeders
├── routes/
│   ├── api.php                # API routes (main routing file)
│   └── console.php            # Console commands
├── storage/
│   └── api-docs/              # Generated Swagger docs
└── tests/                     # Pest tests
```

## CORS Configuration

CORS is pre-configured to work with React frontends. Default allowed origins:
- `http://localhost:3000` (Create React App default)
- `http://localhost:5173` (Vite default)

To add more origins, update the `.env` file:

```env
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:5173
```

## Testing

Run tests using Pest:

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/ApiTest.php

# Run with coverage
php artisan test --coverage
```

## Development Commands

```bash
# Start development server
php artisan serve

# Create a new controller
php artisan make:controller Api/YourController

# Create a new model with migration
php artisan make:model YourModel -m

# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# List all routes
php artisan route:list

# Generate Swagger documentation
php artisan l5-swagger:generate
```

## Deployment

### Environment Variables for Production

Update `.env` for production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# CORS
CORS_ALLOWED_ORIGINS=https://your-frontend.com

# Sanctum
SANCTUM_STATEFUL_DOMAINS=your-frontend.com
SESSION_DOMAIN=.your-domain.com
```

### Production Checklist

- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Use strong `APP_KEY`
- [ ] Configure production database
- [ ] Set correct CORS origins
- [ ] Configure Sanctum domains
- [ ] Enable HTTPS
- [ ] Set up proper logging
- [ ] Configure queue workers
- [ ] Set up scheduled tasks (if any)

## Frontend Integration

1. **Configure API Base URL**
   ```javascript
   const API_BASE_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';
   ```

2. **Handle Authentication**
   ```javascript
   // Store token
   localStorage.setItem('token', response.token);
   
   // Include in requests
   headers: {
     'Authorization': `Bearer ${localStorage.getItem('token')}`,
     'Accept': 'application/json',
     'Content-Type': 'application/json'
   }
   ```

3. **Axios or Fetch**
   ```javascript
   import axios from 'axios';
   
   const api = axios.create({
     baseURL: API_BASE_URL,
     headers: {
       'Accept': 'application/json'
     }
   });
   ```