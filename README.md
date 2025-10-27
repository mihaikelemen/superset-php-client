# Apache Superset PHP Client

[![PHP Version](https://img.shields.io/packagist/php-v/mihaikelemen/superset-php-client)](https://packagist.org/packages/mihaikelemen/superset-php-client)
[![Latest Version](https://img.shields.io/packagist/v/mihaikelemen/superset-php-client)](https://packagist.org/packages/mihaikelemen/superset-php-client)
[![License](https://img.shields.io/packagist/l/mihaikelemen/superset-php-client)](https://github.com/mihaikelemen/superset-php-client/blob/main/LICENSE)

A PHP client library for interacting with the [Apache Superset API](https://superset.apache.org/docs/api/).

## Installation

Install the library using Composer:

```bash
composer require mihaikelemen/superset-php-client
```

## Requirements

- PHP 8.4 or higher
- ext-curl
- ext-json
- GuzzleHTTP
- Symfony Serializer

## Quick Start

### Basic Usage

```php
<?php

require 'vendor/autoload.php';

use Superset\SupersetFactory;

// Create a client with authentication
$superset = SupersetFactory::createAuthenticated(
    baseUrl: 'https://your-superset-instance.com',
    username: 'your-username',
    password: 'your-password'
);

// Get all dashboards
$dashboards = $superset->getDashboards();

foreach ($dashboards as $dashboard) {
    echo sprintf("Dashboard: %s (ID %d)", $dashboard->title, $dashboard->id);
}
```

### Manual Authentication

```php
use Superset\SupersetFactory;

// Create client without authentication
$superset = SupersetFactory::create('https://your-superset-instance.com');

// Authenticate manually
$superset->auth()->authenticate('username', 'password');

// Now you can make authenticated requests
$dashboard = $superset->getDashboard('my-dashboard-slug');
```

### Using Bearer Token

```php
use Superset\SupersetFactory;

$superset = SupersetFactory::create('https://your-superset-instance.com');

// Set access token directly
$superset->auth()->setAccessToken('your-bearer-token');

// Make requests
$dashboards = $superset->getDashboards();
```

## Usage Examples

### Retrieving Dashboards

#### Get a Single Dashboard

```php
// By ID
$dashboard = $superset->getDashboard('123');

// By slug
$dashboard = $superset->getDashboard('sales-dashboard');

echo $dashboard->title;
echo $dashboard->url;
echo $dashboard->isPublished ? 'Published' : 'Draft';
```

#### Get All Dashboards

```php
// Get all published dashboards
$dashboards = $superset->getDashboards();

// Get dashboards by tag
$salesDashboards = $superset->getDashboards(tag: 'sales');

// Include unpublished dashboards
$allDashboards = $superset->getDashboards(onlyPublished: false);
```

#### Get Dashboard Embedded UUID

```php
// Get UUID for embedding dashboard in an iframe
$uuid = $superset->getDashboardUuid('my-dashboard');
```

### Working with Guest Tokens

```php
// Create a guest token for embedded dashboards
$guestToken = $superset->auth()->createGuestToken(
    userAttributes: [
        'username' => 'guest_user',
        'first_name' => 'Guest',
        'last_name' => 'User',
    ],
    resources: [
        'dashboard' => 'abc-def-123',
    ],
    rls: []
);
```

### CSRF Token Handling

```php
// Request CSRF token for POST/PUT/PATCH/DELETE operations
$csrfToken = $superset->auth()->requestCsrfToken();

// The token is automatically added to subsequent requests
$result = $superset->post('some/endpoint', ['data' => 'value']);
```

### Direct API Calls

```php
// GET request
$result = $superset->get('chart', ['q' => 'some-filter']);

// POST request
$result = $superset->post('dataset', [
    'database' => 1,
    'table_name' => 'my_table',
]);

// PUT request
$result = $superset->put('dashboard/123', [
    'dashboard_title' => 'Updated Title',
]);

// PATCH request
$result = $superset->patch('chart/456', [
    'viz_type' => 'bar',
]);

// DELETE request
$result = $superset->delete('dashboard/123');
```

## Advanced Configuration

### Custom HTTP Client

```php
use Superset\Config\HttpClientConfig;
use Superset\Http\HttpClient;
use Superset\SupersetFactory;

// Create custom HTTP configuration
$httpConfig = new HttpClientConfig(
    baseUrl: 'https://your-superset-instance.com',
    timeout: 60.0,
    verifySsl: true,
    maxRedirects: 5,
    userAgent: 'MyApp/1.0'
);

// Create custom HTTP client
$httpClient = new HttpClient($httpConfig);

// Use with factory
$superset = SupersetFactory::createWithHttpClient(
    'https://your-superset-instance.com',
    $httpClient
);
```

### Custom Headers

```php
// Add custom headers to all requests
$superset->auth()->setAccessToken('token');

// Or use the HTTP client directly
$httpClient = new HttpClient($httpConfig);
$httpClient->addDefaultHeader('X-Custom-Header', 'value');
```

## API Coverage

### Currently Implemented

- ✅ Authentication (username/password, bearer token)
- ✅ Guest token generation
- ✅ CSRF token handling
- ✅ Dashboard retrieval (single, multiple, filtered)
- ✅ Dashboard embedded UUID
- ✅ Generic HTTP methods (GET, POST, PUT, PATCH, DELETE)

### Planned Features

- 🔄 Chart management
- 🔄 Dataset operations
- 🔄 Database connections
- 🔄 User management
- 🔄 SQL Lab queries
- 🔄 Complete Dashboard CRUD operations

## Contributing

Contributions are welcome! Please refer to the [CONTRIBUTING.md](CONTRIBUTING.md) file for guidelines.

## License

This library is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Acknowledgments

- Built for [Apache Superset](https://superset.apache.org/)
- Uses [GuzzleHTTP](https://github.com/guzzle/guzzle) for HTTP client
- Uses [Symfony Serializer](https://symfony.com/doc/current/components/serializer.html) for data transformation
