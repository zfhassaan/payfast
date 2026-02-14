# Contributing

Thank you for considering contributing to the PayFast package! This document provides guidelines for contributing.

## Code of Conduct

Please read and follow our [Code of Conduct](../CODE_OF_CONDUCT.md).

## How to Contribute

### Reporting Bugs

1. **Check existing issues** - Search for similar issues
2. **Create detailed issue** - Include:
   - Description of the bug
   - Steps to reproduce
   - Expected behavior
   - Actual behavior
   - Environment details (PHP version, Laravel version, etc.)
   - Error messages/logs

### Suggesting Features

1. **Check existing issues** - Search for similar suggestions
2. **Create feature request** - Include:
   - Use case description
   - Proposed solution
   - Benefits
   - Possible implementation approach

### Pull Requests

1. **Fork the repository**
2. **Create a feature branch**: `git checkout -b feature/amazing-feature`
3. **Make your changes**
4. **Write/update tests**
5. **Update documentation**
6. **Follow coding standards** (PSR-12)
7. **Commit your changes**: `git commit -m 'Add amazing feature'`
8. **Push to branch**: `git push origin feature/amazing-feature`
9. **Open a Pull Request**

## Development Setup

### Clone Repository

```bash
git clone https://github.com/zfhassaan/payfast.git
cd payfast
```

### Install Dependencies

```bash
composer install
```

### Run Tests

```bash
php artisan test
```

### Code Style

The package follows PSR-12 coding standards. Use PHP CS Fixer or Laravel Pint:

```bash
# Using Laravel Pint
./vendor/bin/pint

# Or using PHP CS Fixer
./vendor/bin/php-cs-fixer fix
```

## Coding Standards

### PSR-12 Compliance

- Proper spacing and indentation
- Type declarations on all methods
- Strict types enabled (`declare(strict_types=1)`)
- Consistent naming conventions
- Proper visibility modifiers
- DocBlocks for all classes and methods

### Code Style Example

```php
<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Services;

/**
 * Service description.
 */
class MyService
{
    /**
     * Method description.
     *
     * @param string $param Parameter description
     * @return array<string, mixed>
     */
    public function myMethod(string $param): array
    {
        // Implementation
    }
}
```

## Testing

### Write Tests

All new features must include tests:

1. **Unit tests** - For individual methods/classes
2. **Feature tests** - For complete flows
3. **Edge cases** - Test error conditions

### Test Structure

```php
<?php

namespace Tests\Unit\PayFast\Services;

use Tests\Unit\PayFast\TestCase;

class MyServiceTest extends TestCase
{
    public function test_my_method_returns_expected_result(): void
    {
        // Arrange
        $service = new MyService();

        // Act
        $result = $service->myMethod('input');

        // Assert
        $this->assertNotNull($result);
    }
}
```

### Run Tests

```bash
# All tests
php artisan test

# Specific test
php artisan test tests/Unit/PayFast/Services/MyServiceTest.php

# With coverage
php artisan test --coverage
```

## Documentation

### Update Documentation

When adding features:

1. **Update README.md** - If needed
2. **Update documentation** - Add/update relevant documentation pages in `docs/content/`
3. **Add code comments** - Document complex logic
4. **Update changelog** - Add entry to changelog.md

### Documentation Pages

Documentation pages are in `docs/content/` directory:

- [Home.md](Home.md) - Main landing page
- [Installation-Guide.md](Installation-Guide.md) - Installation
- [Configuration-Guide.md](Configuration-Guide.md) - Configuration
- [Getting-Started.md](Getting-Started.md) - Quick start
- [Payment-Flows.md](Payment-Flows.md) - Payment flows
- [API-Reference.md](API-Reference.md) - API documentation
- [IPN-Handling.md](IPN-Handling.md) - IPN setup
- [Events-and-Listeners.md](Events-and-Listeners.md) - Events
- [Models-and-Database.md](Models-and-Database.md) - Database
- [Console-Commands.md](Console-Commands.md) - Commands
- [Testing-Guide.md](Testing-Guide.md) - Testing
- [Troubleshooting.md](Troubleshooting.md) - Troubleshooting
- [Security-Best-Practices.md](Security-Best-Practices.md) - Security
- [Contributing.md](Contributing.md) - This file

## Architecture Guidelines

### Service-Based Architecture

Follow the existing service-based architecture:

1. **Create service interfaces** - In `Services/Contracts/`
2. **Implement services** - In `Services/`
3. **Use dependency injection** - Inject via constructor
4. **Follow single responsibility** - One service, one purpose

### Repository Pattern

Use repository pattern for data access:

1. **Create repository interfaces** - In `Repositories/Contracts/`
2. **Implement repositories** - In `Repositories/`
3. **Use dependency injection** - Inject via constructor

### Event-Driven Components

Use events for side effects:

1. **Create events** - In `Events/`
2. **Create listeners** - In `Listeners/`
3. **Register in service provider** - Auto-registered

## Commit Messages

Follow conventional commits:

```
feat: Add new payment method
fix: Fix token refresh issue
docs: Update installation guide
test: Add tests for new feature
refactor: Refactor payment service
chore: Update dependencies
```

## Pull Request Process

### Before Submitting

1.  **Tests pass** - All tests must pass
2.  **Code style** - Follows PSR-12
3.  **Documentation** - Updated if needed
4.  **No breaking changes** - Or document them
5.  **Changelog updated** - Add entry

### PR Description

Include:

1. **Description** - What and why
2. **Changes** - List of changes
3. **Testing** - How to test
4. **Screenshots** - If UI changes
5. **Breaking changes** - If any

### Review Process

1. **Automated checks** - CI/CD runs tests
2. **Code review** - Maintainers review code
3. **Feedback** - Address any feedback
4. **Merge** - Once approved

## Versioning

Follow [Semantic Versioning](https://semver.org/):

- **MAJOR** - Breaking changes
- **MINOR** - New features (backward compatible)
- **PATCH** - Bug fixes

## Questions?

If you have questions:

1. **Check documentation** - Review wiki pages
2. **Search issues** - Look for similar questions
3. **Create issue** - Ask in GitHub issues
4. **Email** - zfhassaan@gmail.com

## License

By contributing, you agree that your contributions will be licensed under the MIT License.

## Thank You!

Thank you for contributing to the PayFast package! Your contributions help make this package better for everyone.









