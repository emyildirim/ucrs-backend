# UCRS Backend API

Laravel REST API for the University Course Registration System. Designed for use with a separate React frontend.

## Prerequisites

- PHP 8.2+
- Composer
- SQLite or MySQL/PostgreSQL

## Installation

```bash
cd ucrs-backend/project
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan serve
```

API available at: http://localhost:8000

## API Documentation

Swagger UI: http://localhost:8000/api/documentation


## Endpoints

### Public
- GET `/api/hello` - Health check
- POST `/api/auth/register` - Register user
- POST `/api/auth/login` - Login

### Protected (requires Bearer token)
- GET `/api/auth/me` - Get current user
- POST `/api/auth/logout` - Logout
- POST `/api/auth/logout-all` - Logout all devices

## Authentication

Uses Laravel Sanctum for token-based authentication.

Include token in requests:
```
Authorization: Bearer YOUR_TOKEN
```

## Tech Stack

- Laravel 12
- Laravel Sanctum (authentication)
- L5-Swagger (API docs)
- SQLite/MySQL/PostgreSQL
- Pest PHP (testing)

## CORS

Configured for React development:
- http://localhost:3000
- http://localhost:5173

Update via `.env`:
```env
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:5173
```

## Commands

```bash
# Development
php artisan serve
php artisan test
php artisan route:list

# Database
php artisan migrate
php artisan migrate:rollback

# Code generation
php artisan make:controller Api/YourController
php artisan make:model YourModel -m

# Cache
php artisan cache:clear
php artisan config:clear
```

## Production

Update `.env`:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.example.com
DB_CONNECTION=mysql
CORS_ALLOWED_ORIGINS=https://frontend.example.com
SANCTUM_STATEFUL_DOMAINS=frontend.example.com
```

## Frontend Integration

```javascript
// Axios setup
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  headers: { 'Accept': 'application/json' }
});

// Add token to requests
api.interceptors.request.use(config => {
  const token = localStorage.getItem('token');
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});
```