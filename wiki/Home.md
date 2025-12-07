# PayFast Payment Gateway Package

<p align="center">
  <img src="../logo.png" alt="PayFast Payment Gateway" width="300"/><br/>
</p>

[![Latest Version on Packagist](https://img.shields.io/packagist/v/zfhassaan/Payfast.svg?style=flat-square)](https://packagist.org/packages/zfhassaan/payfast)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](../LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/zfhassaan/Payfast.svg?style=flat-square)](https://packagist.org/packages/zfhassaan/payfast)

## Overview

The PayFast Payment Gateway Package is a comprehensive Laravel package that provides seamless integration with PayFast payment gateway APIs. This package supports both **Direct Checkout** and **Hosted Checkout** processes, making it easy to process payments through various payment methods including credit/debit cards and mobile wallets (EasyPaisa, UPaisa, JazzCash).

## Features

- ✅ **Direct Checkout** - PCI DSS compliant payment processing
- ✅ **Hosted Checkout** - Redirect-based payment processing
- ✅ **Card Payments** - Credit/Debit card processing with 3DS authentication
- ✅ **Mobile Wallets** - EasyPaisa, UPaisa, and JazzCash support
- ✅ **OTP Verification** - Secure OTP-based customer validation
- ✅ **3DS Authentication** - 3D Secure payment authentication
- ✅ **IPN Support** - Instant Payment Notification webhook handling
- ✅ **Event System** - Event-driven architecture for extensibility
- ✅ **Activity Logging** - Comprehensive payment activity tracking
- ✅ **Email Notifications** - Automated email notifications for payment events
- ✅ **Transaction Management** - Query, verify, and refund transactions
- ✅ **Console Commands** - CLI tools for payment management
- ✅ **Comprehensive Testing** - Full test suite included

## Quick Start

### Installation

```bash
composer require zfhassaan/payfast
```

### Basic Usage

```php
use zfhassaan\Payfast\Facades\PayFast;

// Get authentication token
$response = PayFast::getToken();

// Process card payment
$paymentData = [
    'orderNumber' => 'ORD-12345',
    'transactionAmount' => 1000.00,
    'customerMobileNo' => '03001234567',
    'customer_email' => 'customer@example.com',
    'cardNumber' => '4111111111111111',
    'expiry_month' => '12',
    'expiry_year' => '2025',
    'cvv' => '123',
];

$response = PayFast::getOTPScreen($paymentData);
```

## Documentation

### Getting Started
- [Installation Guide](Installation-Guide.md) - Complete installation instructions
- [Configuration Guide](Configuration-Guide.md) - Environment and configuration setup
- [Getting Started](Getting-Started.md) - Quick start tutorial

### Core Features
- [Payment Flows](Payment-Flows.md) - Detailed payment flow documentation
- [API Reference](API-Reference.md) - Complete API method documentation
- [IPN Handling](IPN-Handling.md) - Instant Payment Notification setup and usage

### Advanced Topics
- [Events and Listeners](Events-and-Listeners.md) - Event system documentation
- [Models and Database](Models-and-Database.md) - Database schema and models
- [Console Commands](Console-Commands.md) - CLI commands reference

### Development
- [Testing Guide](Testing-Guide.md) - Testing documentation
- [Troubleshooting](Troubleshooting.md) - Common issues and solutions
- [Security Best Practices](Security-Best-Practices.md) - Security guidelines
- [Contributing](Contributing.md) - Contribution guidelines

## Architecture

This package follows a **Service-Based Architecture** with **Event-Driven components**:

- **Service Layer**: Handles business logic (Authentication, Payment, Transaction, OTP)
- **Repository Pattern**: Abstracts data access layer
- **Event System**: Handles side effects (logging, notifications, analytics)
- **DTO Pattern**: Type-safe data transfer objects
- **Dependency Injection**: All dependencies injected via constructor

For detailed architecture information, see [ARCHITECTURE.md](../ARCHITECTURE.md).

## Payment Methods Supported

1. **Credit/Debit Cards** - Direct card payments with 3DS authentication
2. **EasyPaisa** - Mobile wallet payments
3. **UPaisa** - Mobile wallet payments
4. **JazzCash** - Mobile wallet payments (via bank code)

## Payment Status Flow

```
pending → validated → otp_verified → completed
                ↓
            failed/cancelled
```

## Requirements

- PHP 7.4 or higher (8.0+ recommended)
- Laravel 8.0, 9.0, 10.0, 11.0, or 12.0
- cURL extension
- PayFast merchant account with Merchant ID and Secured Key

## Disclaimer

This is an **unofficial** PayFast API Payment Gateway package. This repository is created to help developers streamline the integration process. You can review the official PayFast documentation [here](https://gopayfast.com/docs/#preface).

**Note**: This package currently covers Direct Checkout and Hosted Checkout processes. Subscription functionality will be added in future releases.

## Support

- **Issues**: [GitHub Issues](https://github.com/zfhassaan/payfast/issues)
- **Email**: zfhassaan@gmail.com
- **Documentation**: See wiki pages for detailed guides

## License

The MIT License (MIT). Please see [LICENSE.md](../LICENSE.md) for more information.

## Changelog

Please see [changelog.md](../changelog.md) for a list of changes.

## Contributing

Contributions are welcome! Please see [Contributing.md](../CONTRIBUTING.md) and [Contributing Guide](Contributing.md) for details.

---

**Version**: 1.0.0  
**Last Updated**: 2025

