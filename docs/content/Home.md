# PayFast Payment Gateway Package

Welcome to the PayFast Payment Gateway Package documentation! This comprehensive Laravel package provides seamless integration with PayFast payment gateway APIs.

## Overview

The PayFast Payment Gateway Package is a Laravel package that simplifies integration with PayFast payment gateway. This package supports both **Direct Checkout** and **Hosted Checkout** processes, making it easy to process payments through various payment methods including credit/debit cards and mobile wallets (EasyPaisa, UPaisa, JazzCash).

## Quick Navigation

### Payment Processes

- **[Understanding the Direct Checkout Process](Understanding-the-Direct-Checkout-Process)** - Complete guide for Direct Checkout integration
- **[Understanding the Hosted Checkout Process for Payfast](Understanding-the-Hosted-Checkout-Process-for-Payfast)** - Complete guide for Hosted Checkout integration

### Getting Started

- [Installation Guide](Installation-Guide) - Step-by-step installation instructions
- [Configuration Guide](Configuration-Guide) - Environment and configuration setup
- [Getting Started](Getting-Started) - Quick start tutorial with code examples

### Core Features

- [Payment Flows](Payment-Flows) - Detailed payment flow documentation
- [API Reference](API-Reference) - Complete API method documentation
- [IPN Handling](IPN-Handling) - Instant Payment Notification setup and usage

### Advanced Topics

- [Events and Listeners](Events-and-Listeners) - Event system documentation
- [Models and Database](Models-and-Database) - Database schema and models
- [Console Commands](Console-Commands) - CLI commands reference

### Development & Support

- [Testing Guide](Testing-Guide) - Testing documentation
- [Troubleshooting](Troubleshooting) - Common issues and solutions
- [Security Best Practices](Security-Best-Practices) - Security guidelines
- [Contributing](Contributing) - Contribution guidelines

## Features

- **Direct Checkout** - PCI DSS compliant payment processing
- **Hosted Checkout** - Redirect-based payment processing
- **Card Payments** - Credit/Debit card processing with 3DS authentication
- **Mobile Wallets** - EasyPaisa, UPaisa, and JazzCash support
- **OTP Verification** - Secure OTP-based customer validation
- **3DS Authentication** - 3D Secure payment authentication
- **IPN Support** - Instant Payment Notification webhook handling
- **Event System** - Event-driven architecture for extensibility
- **Activity Logging** - Comprehensive payment activity tracking
- **Email Notifications** - Automated email notifications for payment events

## Installation

```bash
composer require zfhassaan/payfast
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

## License

The MIT License (MIT). Please see [LICENSE.md](../LICENSE.md) for more information.

---

Thank you for using the PayFast Payment Gateway Package! If you have any questions or need assistance, please refer to the documentation pages above or contact support.
