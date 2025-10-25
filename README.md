# Apache Superset PHP Client

[![PHP Version](https://img.shields.io/packagist/php-v/mihaikelemen/superset-php-client)](https://packagist.org/packages/mihaikelemen/superset-php-client)
[![Latest Version](https://img.shields.io/packagist/v/mihaikelemen/superset-php-client)](https://packagist.org/packages/mihaikelemen/superset-php-client)
[![License](https://img.shields.io/packagist/l/mihaikelemen/superset-php-client)](https://github.com/mihaikelemen/superset-php-client/blob/main/LICENSE)

A modern, fully-typed PHP client library for interacting with the [Apache Superset](https://superset.apache.org/) API. This library provides a clean, object-oriented interface for managing dashboards, authentication, and API requests.

## Features

- 🚀 **Modern PHP**: Requires PHP 8.4+ with full type safety
- 🔐 **Authentication Support**: Username/password, bearer tokens, guest tokens, and CSRF protection
- 📊 **Dashboard Management**: Retrieve dashboards, embedded UUIDs, and filter by tags
- 🎯 **Fully Typed**: Complete type hints and PHPStan level max compliance
- 🧪 **Well Tested**: Comprehensive unit test coverage
- 🔧 **Flexible**: Custom HTTP client support and configuration options
- 📦 **PSR-4 Autoloading**: Standard Composer autoloading

## Installation

Install the library using Composer:

```bash
composer require mihaikelemen/superset-php-client
```

## Requirements

- PHP 8.4 or higher
- ext-curl
- ext-json
- GuzzleHTTP 7.10+
- Symfony Serializer Pack 1.3+

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
    echo "Dashboard: {$dashboard->title} (ID: {$dashboard->id})\n";
    echo "URL: {$dashboard->url}\n";
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

echo "Guest Token: {$guestToken}\n";
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

### Working with Dashboard Data

```php
$dashboard = $superset->getDashboard('123');

// Access dashboard properties
echo "ID: {$dashboard->id}\n";
echo "Title: {$dashboard->title}\n";
echo "Slug: {$dashboard->slug}\n";
echo "URL: {$dashboard->url}\n";
echo "Published: " . ($dashboard->isPublished ? 'Yes' : 'No') . "\n";

// Access owners
foreach ($dashboard->owners as $owner) {
    echo "Owner: {$owner['first_name']} {$owner['last_name']}\n";
}

// Access tags
foreach ($dashboard->tags as $tag) {
    echo "Tag: {$tag['name']}\n";
}

// Access metadata
if ($dashboard->updatedAt) {
    echo "Last updated: {$dashboard->updatedAt->format('Y-m-d H:i:s')}\n";
}

if ($dashboard->updatedBy) {
    echo "Updated by: {$dashboard->updatedBy['first_name']} {$dashboard->updatedBy['last_name']}\n";
}
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

## Error Handling

The library throws specific exceptions for different error scenarios:

```php
use Superset\Exception\AuthenticationException;
use Superset\Exception\HttpResponseException;
use Superset\Exception\JsonDecodeException;
use Superset\Exception\SerializationException;
use Superset\Exception\UnexpectedRuntimeException;

try {
    $superset = SupersetFactory::createAuthenticated(
        'https://your-superset-instance.com',
        'username',
        'wrong-password'
    );
} catch (AuthenticationException $e) {
    echo "Authentication failed: {$e->getMessage()}\n";
} catch (HttpResponseException $e) {
    echo "HTTP error: {$e->getMessage()}\n";
    echo "Status code: {$e->getCode()}\n";
} catch (JsonDecodeException $e) {
    echo "JSON decode error: {$e->getMessage()}\n";
} catch (SerializationException $e) {
    echo "Serialization error: {$e->getMessage()}\n";
} catch (UnexpectedRuntimeException $e) {
    echo "Unexpected error: {$e->getMessage()}\n";
}
```

## Development

### Running Tests

```bash
# Run unit tests
composer test

# Run all tests (including integration)
composer test:all

# Run with coverage
composer test:coverage
```

### Code Quality

```bash
# Run PHP CS Fixer
composer cs-fix

# Check code style
composer cs-check

# Run PHPStan analysis
composer phpstan

# Run all quality checks
composer quality
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

## Support

- **Issues**: [GitHub Issues](https://github.com/mihaikelemen/superset-php-client/issues)
- **Source**: [GitHub Repository](https://github.com/mihaikelemen/superset-php-client)

## Credits

- **Author**: Mihai KELEMEN
- **Email**: mihai@webmanage.ro

## Acknowledgments

- Built for [Apache Superset](https://superset.apache.org/)
- Uses [GuzzleHTTP](https://github.com/guzzle/guzzle) for HTTP client
- Uses [Symfony Serializer](https://symfony.com/doc/current/components/serializer.html) for data transformation
