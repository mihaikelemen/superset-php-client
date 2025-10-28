# Changelog

All notable changes to this project will be documented in this file.

## v1.0.1

### Changed
- **Breaking**: Renamed `SupersetFactory::createWithHttpClient()` to `SupersetFactory::createWithHttpClientConfig()`
  - Method now accepts `HttpClientConfig` instead of `HttpClientInterface` and base URL
  - Simplifies factory usage and improves API consistency
- Refactored test suite to use centralized `BASE_URL` constant in `BaseTestCase`
- Added `buildUrl()` helper method to `BaseTestCase` for consistent URL construction in tests
- Updated all test files to use centralized base URL and helper methods
- Updated documentation (README.md and CONTRIBUTING.md) to reflect factory changes

### Fixed
- Improved test maintainability by eliminating hardcoded URLs across test files

## v1.0.0

### Added
- Initial release of Apache Superset PHP Client
- Authentication support (username/password, bearer token)
- Guest token generation
- CSRF token handling
- Dashboard retrieval (single, multiple, filtered by tag)
- Dashboard embedded UUID retrieval
- Generic HTTP methods (GET, POST, PUT, PATCH, DELETE)

### Features
- `SupersetFactory` for easy client instantiation
- `Dashboard` DTO with full type support
- Flexible HTTP client configuration
- Custom header support
