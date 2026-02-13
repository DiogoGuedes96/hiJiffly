# Hijiffly API

A Laravel-based API for managing hotel availability and reservations through the MEWS property management system.

## Architecture Overview

This project follows a clean, layered architecture with clear separation of concerns:

- **Controllers** - Handle HTTP requests and responses
- **Services** - Contain business logic
- **Clients** - Communicate with external APIs (MEWS)
- **Data Objects** - Type-safe request/response structures using Spatie Laravel Data
- **Exceptions** - Custom exception handling for better error management

## API Routes

### Authentication
- `POST /api/register` - Register a new user
- `POST /api/login` - Login and receive authentication token

### Protected Routes (require authentication)
- `POST /api/logout` - Logout and revoke token
- `GET /api/me` - Get authenticated user information

### MEWS Integration (require authentication)
- `GET /api/mews/availability` - Get available rooms from MEWS
- `GET /api/mews/reservations` - Get reservations from MEWS

## Authentication

This API uses **Laravel Sanctum** for authentication. Sanctum was chosen because it's:
- **Easy to implement** - Simple token-based authentication
- **Quick to set up** - No complex OAuth configuration needed
- **Laravel native** - First-party package with excellent support

After login/registration, clients receive a Bearer token to include in the `Authorization` header for protected routes.

## Folder Structure

```
app/
├── Clients/              # External API clients (MEWS)
│   ├── MewsClient.php    # Base client with HTTP logic
│   ├── MewsAvailabilityEndpoint.php
│   ├── MewsReservationsEndpoint.php
│   └── Contracts/        # Client interfaces
├── Data/                 # Spatie Laravel Data objects
│   ├── AuthResponseData.php
│   ├── AvailabilityResponseData.php
│   └── UserData.php
├── Exceptions/           # Custom exceptions
│   ├── MewsApiException.php
├── Http/
│   ├── Controllers/      # HTTP controllers
│   ├── Requests/         # Form request validation (Spatie Data)
│   └── Responses/        # Response builders
├── Interfaces/           # Service interfaces
├── Models/               # Eloquent models
└── Services/             # Business logic layer

```

## Layered System

The application follows a layered architecture:

1. **Controller Layer** - Receives requests, delegates to services, returns responses
2. **Service Layer** - Contains business logic, orchestrates operations
3. **Client Layer** - Handles external API communication
4. **Data Layer** - Models and data transfer objects

This separation ensures:
- Easy testing and mocking
- Clear responsibilities
- Maintainable and scalable code
- Business logic independence from HTTP concerns

## Spatie Laravel Data

This project uses [Spatie Laravel Data](https://spatie.be/docs/laravel-data) for all request and response objects. This package was chosen because:

- **Type Safety** - Full PHP type hints and IDE autocompletion
- **Clean Code** - Eliminates array manipulation and guesswork
- **Validation** - Built-in validation rules on data objects
- **Transformation** - Easy conversion between arrays, JSON, and objects
- **Documentation** - Self-documenting API through typed properties


## MEWS Client & Endpoints

The **MewsClient** is an abstract base class that handles communication with the MEWS API. It provides:

- **Authentication** - Automatically includes client token and access token
- **HTTP Methods** - Centralized POST request handling
- **Error Handling** - Throws `MewsApiException` on failures
- **Retry Logic** - Automatic retries with exponential backoff
- **Configuration** - Reads from `config/mews.php`

### Endpoint Classes

Specific MEWS endpoints extend the base client:

- **MewsAvailabilityEndpoint** - Fetches available rooms and services
- **MewsReservationsEndpoint** - Retrieves reservation information
- **MewsResourceCategoriesEndpoint** - Gets room categories
- **MewsServicesEndpoint** - Retrieves service information

Each endpoint class encapsulates the specific API calls and response handling for that MEWS resource.


//IMPORTANT NOTE
## Timezone Handling

The MEWS API requires timezone-aware date conversions. Currently, the timezone is **hardcoded to `Europe/Budapest`** (configured via `MEWS_TIMEZONE_OVERRIDE` in `.env`).

### Why Budapest?

When calling the MEWS configuration endpoint, the `TimeZoneIdentifier` returns `Europe/Budapest` for the property.

### Current Implementation

For simplicity and to avoid multiple API calls, the timezone is set as a configuration variable. The `MewsTimeZoneService` handles all date conversions:

- Converts local dates to UTC for API requests
- Handles different timezone requirements for availability vs. reservations
- Ensures correct time unit boundaries (midnight in local time)

### Future Improvement

Ideally, the application should:
1. Call the MEWS configuration endpoint during bootstrap/initialization
2. Retrieve the property's timezone dynamically
3. Cache it for the session/request lifecycle

This would make the application more flexible for properties in different timezones, but requires additional API calls and caching strategy.

## Installation

```bash

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Configure MEWS credentials in .env
# MEWS_API_BASE_URL=
# MEWS_CLIENT_TOKEN=
# MEWS_ACCESS_TOKEN=
# MEWS_TIMEZONE_OVERRIDE=Europe/Budapest (default timezone)
```

## Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run specific test file
php artisan test tests/Feature/AuthApiTest.php
```
