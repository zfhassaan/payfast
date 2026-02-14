const { createApp } = Vue;

createApp({
  data() {
    return {
      currentPage: "home",
      activeTab: "install",
      searchQuery: "",
      isDark: false,
      mobileMenuOpen: false,
      documentation: {},
      searchOpen: false,
      searchResults: [],
      selectedResultIndex: 0,
      contributors: [],
      loadingContributors: true,
    };
  },
  computed: {
    currentContent() {
      let page = this.currentPage;

      // Map page names to file names
      const pageMap = {
        'home': 'Home',
        'guide': 'Getting-Started', // Guide shows getting started page
        'getting-started': 'Getting-Started',
        'installation': 'Installation-Guide',
        'configuration': 'Configuration-Guide',
        'Understanding-the-Direct-Checkout-Process': 'Understanding-the-Direct-Checkout-Process',
        'Understanding-the-Hosted-Checkout-Process-for-Payfast': 'Understanding-the-Hosted-Checkout-Process-for-Payfast',
        'Payment-Flows': 'Payment-Flows',
        'API-Reference': 'API-Reference',
        'IPN-Handling': 'IPN-Handling',
        'Events-and-Listeners': 'Events-and-Listeners',
        'Models-and-Database': 'Models-and-Database',
        'Console-Commands': 'Console-Commands',
        'Testing-Guide': 'Testing-Guide',
        'Troubleshooting': 'Troubleshooting',
        'Security-Best-Practices': 'Security-Best-Practices',
        'Contributing': 'Contributing',
        'Getting-Started': 'Getting-Started',
        'Installation-Guide': 'Installation-Guide',
        'Configuration-Guide': 'Configuration-Guide',
      };

      // Map page name if needed
      if (pageMap[page]) {
        page = pageMap[page];
      } else if (page === 'home' && this.documentation['Home']) {
        page = 'Home';
      }

      if (this.documentation[page]) {
        return marked.parse(this.documentation[page]);
      }

      return "<h1>Page Not Found</h1><p>The requested page could not be found.</p>";
    },
    filteredSearchResults() {
      if (!this.searchQuery || !this.searchQuery.trim()) {
        return [];
      }

      // Ensure documentation is loaded
      if (!this.documentation || Object.keys(this.documentation).length === 0) {
        console.warn('Search: Documentation not loaded yet');
        return [];
      }

      const query = this.searchQuery.toLowerCase().trim();
      if (!query) {
        return [];
      }

      const results = [];

      // Search through all documentation
      Object.keys(this.documentation).forEach((pageKey) => {
        const content = this.documentation[pageKey];
        if (!content || typeof content !== 'string') {
          return;
        }

        const lines = content.split("\n");

        // Search in headings and content
        lines.forEach((line, index) => {
          const lineLower = line.toLowerCase();
          if (lineLower.includes(query)) {
            // Extract heading or context
            let title = pageKey.replace(/-/g, " ");
            // Better title formatting
            title = title.split(' ').map(word =>
              word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
            ).join(' ');

            let snippet = line.trim();
            // If it's a heading, use it as title
            if (line.startsWith("#")) {
              snippet = line.replace(/^#+\s*/, "").trim();
              title = snippet; // Use heading as title
            }

            // Limit snippet length
            if (snippet.length > 100) {
              snippet = snippet.substring(0, 100) + "...";
            }

            // Avoid duplicates
            const existing = results.find(r => r.page === pageKey && r.snippet === snippet);
            if (!existing) {
              results.push({
                page: pageKey,
                title: title,
                snippet: snippet,
                line: index,
              });
            }
          }
        });
      });

      console.log('Search results for "' + query + '":', results.length);
      return results.slice(0, 10); // Limit to 10 results
    },
  },
  watch: {
    currentPage() {
      // Scroll to top when page changes
      window.scrollTo({ top: 0, behavior: "smooth" });

      // Re-highlight code blocks
      this.$nextTick(() => {
        Prism.highlightAll();
      });
    },
    activeTab() {
      this.$nextTick(() => {
        Prism.highlightAll();
      });
    },
    searchQuery() {
      this.selectedResultIndex = 0;
    },
  },
  async mounted() {
    try {
      // Load embedded documentation immediately (synchronous)
      this.loadEmbeddedDocumentation();

      // Then try to load from server (async, will override if successful)
      this.loadDocumentation();

      // Check for dark mode preference
      const prefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
      this.isDark =
        localStorage.getItem("theme") === "dark" ||
        (prefersDark && !localStorage.getItem("theme"));
      this.applyTheme();

      // Highlight code blocks
      if (typeof Prism !== "undefined") {
        Prism.highlightAll();
      }

      // Handle hash navigation
      if (window.location.hash) {
        const page = window.location.hash.substring(1);
        if (page && page !== "home") {
          // Map common page names
          const pageMap = {
            'guide': 'getting-started',
            'getting-started': 'getting-started',
            'installation': 'installation',
            'configuration': 'configuration',
          };
          this.currentPage = pageMap[page] || page;
        }
      }

      // Add keyboard shortcuts
      document.addEventListener("keydown", this.handleKeyboard);

      // Fetch contributors
      this.fetchContributors();
    } catch (error) {
      console.error("Error in mounted hook:", error);
      // Ensure we still try to fetch contributors even if something else fails
      this.fetchContributors();
    }
  },
  beforeUnmount() {
    document.removeEventListener("keydown", this.handleKeyboard);
  },
  methods: {
    handleKeyboard(e) {
      // Ctrl+K or Cmd+K to open search
      if ((e.ctrlKey || e.metaKey) && e.key === "k") {
        e.preventDefault();
        this.toggleSearch();
        return;
      }

      // Escape to close search
      if (e.key === "Escape" && this.searchOpen) {
        e.preventDefault();
        this.closeSearch();
        return;
      }

      // Arrow keys to navigate results
      if (this.searchOpen && this.filteredSearchResults.length > 0) {
        if (e.key === "ArrowDown") {
          e.preventDefault();
          this.selectedResultIndex = Math.min(
            this.selectedResultIndex + 1,
            this.filteredSearchResults.length - 1
          );
        } else if (e.key === "ArrowUp") {
          e.preventDefault();
          this.selectedResultIndex = Math.max(this.selectedResultIndex - 1, 0);
        } else if (e.key === "Enter") {
          e.preventDefault();
          if (this.filteredSearchResults[this.selectedResultIndex]) {
            this.selectResult(this.filteredSearchResults[this.selectedResultIndex]);
          }
        }
      }
    },
    toggleSearch() {
      this.searchOpen = !this.searchOpen;
      if (this.searchOpen) {
        this.$nextTick(() => {
          const input = document.querySelector(".command-palette-input");
          if (input) input.focus();
        });
      } else {
        this.searchQuery = "";
      }
    },
    closeSearch() {
      this.searchOpen = false;
      this.searchQuery = "";
      this.selectedResultIndex = 0;
    },
    selectResult(result) {
      this.currentPage = result.page;
      this.closeSearch();
    },
    toggleTheme() {
      this.isDark = !this.isDark;
      this.applyTheme();
      localStorage.setItem("theme", this.isDark ? "dark" : "light");
    },
    applyTheme() {
      if (this.isDark) {
        document.documentElement.setAttribute("data-theme", "dark");
      } else {
        document.documentElement.removeAttribute("data-theme");
      }
    },
    async fetchContributors() {
      try {
        const response = await fetch(
          "https://api.github.com/repos/zfhassaan/payfast/contributors?per_page=3"
        );
        if (response.ok) {
          const contributorsList = await response.json();

          // Fetch detailed information for each user concurrently
          const detailedContributors = await Promise.all(
            contributorsList.map(async (c) => {
              try {
                const userResponse = await fetch(`https://api.github.com/users/${c.login}`);
                if (userResponse.ok) {
                  const userData = await userResponse.json();
                  return {
                    login: c.login,
                    avatar_url: c.avatar_url,
                    html_url: c.html_url,
                    contributions: c.contributions,
                    name: userData.name || c.login,
                    bio: userData.bio,
                    location: userData.location,
                    blog: userData.blog,
                    twitter: userData.twitter_username,
                    followers: userData.followers,
                    public_repos: userData.public_repos
                  };
                }
              } catch (e) {
                console.warn(`Failed to fetch details for ${c.login}:`, e);
              }
              // Return original data if detail fetch fails
              return {
                login: c.login,
                avatar_url: c.avatar_url,
                html_url: c.html_url,
                contributions: c.contributions,
              };
            })
          );

          this.contributors = detailedContributors;
        } else {
          throw new Error("Failed to fetch contributors list");
        }
      } catch (error) {
        console.error("Failed to fetch contributors:", error);
        // Fallback to static data
        this.contributors = [
          {
            login: "bschmitt",
            name: "Bernd Schmitt",
            avatar_url: "https://avatars.githubusercontent.com/u/239644?v=4",
            html_url: "https://github.com/bschmitt",
            location: 'Berlin',
            contributions: 55,
          },
          {
            login: "zfhassaan",
            name: "Hassaan",
            avatar_url: "https://avatars.githubusercontent.com/u/17079656?v=4",
            html_url: "https://github.com/zfhassaan",
            location: 'Pakistan',
            contributions: 53,
          },
          {
            login: "petekelly",
            name: "Pete Kelly",
            avatar_url: "https://avatars.githubusercontent.com/u/1177933?v=4",
            html_url: "https://github.com/petekelly",
            location: 'UK',
            contributions: 6,
          },
        ];
      } finally {
        this.loadingContributors = false;
      }
    },
    async loadDocumentation() {
      // Try to load documentation from content folder (for web server)
      // This will override embedded content if fetch succeeds
      // Note: Embedded content is already loaded in mounted() for immediate search
      const docFiles = [
        'Home',
        'Getting-Started',
        'Installation-Guide',
        'Configuration-Guide',
        'API-Reference',
        'Payment-Flows',
        'IPN-Handling',
        'Events-and-Listeners',
        'Models-and-Database',
        'Console-Commands',
        'Testing-Guide',
        'Troubleshooting',
        'Security-Best-Practices',
        'Understanding-the-Direct-Checkout-Process',
        'Understanding-the-Hosted-Checkout-Process-for-Payfast',
        'Contributing'
      ];

      for (const file of docFiles) {
        try {
          const response = await fetch(`content/${file}.md`);
          if (response.ok) {
            this.documentation[file] = await response.text();
          }
        } catch (error) {
          // Fetch failed, embedded content already loaded
        }
      }

      // Set home page - map 'home' to 'Home' for compatibility
      if (this.documentation['Home']) {
        this.documentation['home'] = this.documentation['Home'];
      }

      // Also create aliases for common page names
      if (this.documentation['Getting-Started']) {
        this.documentation['getting-started'] = this.documentation['Getting-Started'];
      }
      if (this.documentation['Installation-Guide']) {
        this.documentation['installation'] = this.documentation['Installation-Guide'];
      }
      if (this.documentation['Configuration-Guide']) {
        this.documentation['configuration'] = this.documentation['Configuration-Guide'];
      }

      console.log('Final documentation pages:', Object.keys(this.documentation).length);
    },
    loadEmbeddedDocumentation() {
      // Embedded documentation content for offline viewing
      // This ensures search works even when opening file directly (file:// protocol)

      // Home page
      this.documentation['Home'] = `# PayFast Payment Gateway Package

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

\`\`\`bash
composer require zfhassaan/payfast
\`\`\`

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

Thank you for using the PayFast Payment Gateway Package! If you have any questions or need assistance, please refer to the documentation pages above or contact support.`;

      // Getting Started
      if (!this.documentation['Getting-Started']) {
        this.documentation['Getting-Started'] = `# Getting Started with PayFast

## Quick Start

### 1. Installation

\`\`\`bash
composer require zfhassaan/payfast
\`\`\`

### 2. Publish Configuration

\`\`\`bash
php artisan vendor:publish --tag=payfast-config
php artisan vendor:publish --tag=payfast-migrations
php artisan migrate
\`\`\`

### 3. Configure Environment

Add to your \`.env\`:

\`\`\`env
PAYFAST_API_URL=https://api.payfast.com
PAYFAST_SANDBOX_URL=https://sandbox.payfast.com
PAYFAST_MERCHANT_ID=your_merchant_id
PAYFAST_SECURED_KEY=your_secured_key
PAYFAST_MODE=sandbox
PAYFAST_RETURN_URL=https://yourdomain.com/payment/callback
\`\`\`

### 4. Basic Usage

\`\`\`php
use zfhassaan\\Payfast\\Facades\\PayFast;

// Get authentication token
$response = PayFast::getToken();

// Process payment
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
\`\`\`

That's it! You're ready to use PayFast Payment Gateway.

## Next Steps

- [Installation Guide](Installation-Guide.md) - Detailed installation
- [Configuration Guide](Configuration-Guide.md) - Configuration setup
- [Payment Flows](Payment-Flows.md) - Understand payment processing`;
      }

      // Installation Guide
      if (!this.documentation['Installation-Guide']) {
        this.documentation['Installation-Guide'] = `# Installation Guide

This guide will walk you through installing and setting up the PayFast Payment Gateway package in your Laravel application.

## Prerequisites

Before installing the package, ensure you have:

- PHP 7.4 or higher (PHP 8.0+ recommended)
- Laravel 8.0, 9.0, 10.0, 11.0, or 12.0
- Composer installed
- cURL extension enabled
- A PayFast merchant account with:
  - Merchant ID
  - Secured Key
  - API URLs (Production and Sandbox)

## Step 1: Install via Composer

Install the package using Composer:

\`\`\`bash
composer require zfhassaan/payfast
\`\`\`

## Step 2: Publish Configuration

Publish the configuration file to your \`config\` directory:

\`\`\`bash
php artisan vendor:publish --tag=payfast-config
\`\`\`

This will create \`config/payfast.php\` in your application.

## Step 3: Publish Migrations

Publish the database migrations:

\`\`\`bash
php artisan vendor:publish --tag=payfast-migrations
\`\`\`

This will copy the following migrations to your \`database/migrations\` directory:

- \`2023_08_14_071018_payfast_create_process_payments_table_in_payfast.php\`
- \`2024_02_02_194203_payfast_create_activity_logs_table.php\`
- \`2024_02_02_195511_payfast_create_ipn_table.php\`
- \`2025_01_15_000001_add_status_and_pares_to_process_payments.php\`

## Step 4: Run Migrations

Run the migrations to create the necessary database tables:

\`\`\`bash
php artisan migrate
\`\`\`

This will create the following tables:

- \`payfast_process_payments_table\` - Stores payment records
- \`payfast_activity_logs_table\` - Stores payment activity logs
- \`payfast_ipn_table\` - Stores IPN (Instant Payment Notification) logs

## Step 5: Configure Environment Variables

Add the following environment variables to your \`.env\` file:

\`\`\`env
# PayFast Configuration
PAYFAST_API_URL=https://api.payfast.com
PAYFAST_SANDBOX_URL=https://sandbox.payfast.com
PAYFAST_GRANT_TYPE=client_credentials
PAYFAST_MERCHANT_ID=your_merchant_id
PAYFAST_SECURED_KEY=your_secured_key
PAYFAST_STORE_ID=your_store_id
PAYFAST_RETURN_URL=https://yourdomain.com/payment/callback
PAYFAST_MODE=sandbox
PAYFAST_VERIFY_TRANSACTION=https://api.payfast.com/transaction/view

# Email Configuration (Optional)
PAYFAST_ADMIN_EMAILS=admin@example.com,admin2@example.com
PAYFAST_EMAIL_SUBJECT_COMPLETION=Payment Completed Successfully
PAYFAST_EMAIL_SUBJECT_ADMIN=New Payment Completed
PAYFAST_EMAIL_SUBJECT_FAILURE=Payment Failed
\`\`\`

## Next Steps

After installation, proceed to:

1. [Configuration Guide](Configuration-Guide.md) - Configure the package
2. [Getting Started](Getting-Started.md) - Learn basic usage
3. [Payment Flows](Payment-Flows.md) - Understand payment processing`;
      }

      // Configuration Guide
      if (!this.documentation['Configuration-Guide']) {
        this.documentation['Configuration-Guide'] = `# Configuration Guide

This guide covers all configuration options available in the PayFast package.

## Configuration File

The configuration file is located at \`config/payfast.php\` after publishing. You can publish it using:

\`\`\`bash
php artisan vendor:publish --tag=payfast-config
\`\`\`

## Environment Variables

All configuration values are loaded from your \`.env\` file. Here's a complete list:

### Required Configuration

\`\`\`env
# API URLs
PAYFAST_API_URL=https://api.payfast.com
PAYFAST_SANDBOX_URL=https://sandbox.payfast.com

# Authentication
PAYFAST_GRANT_TYPE=client_credentials
PAYFAST_MERCHANT_ID=your_merchant_id
PAYFAST_SECURED_KEY=your_secured_key

# Application Settings
PAYFAST_RETURN_URL=https://yourdomain.com/payment/callback
PAYFAST_MODE=sandbox
\`\`\`

### Optional Configuration

\`\`\`env
# Store Configuration
PAYFAST_STORE_ID=your_store_id

# Transaction Verification
PAYFAST_VERIFY_TRANSACTION=https://api.payfast.com/transaction/view

# Email Notifications
PAYFAST_ADMIN_EMAILS=admin@example.com,admin2@example.com
PAYFAST_EMAIL_SUBJECT_COMPLETION=Payment Completed Successfully
PAYFAST_EMAIL_SUBJECT_ADMIN=New Payment Completed
PAYFAST_EMAIL_SUBJECT_FAILURE=Payment Failed
\`\`\`

## Mode Configuration

The package supports two modes:

### Sandbox Mode

\`\`\`env
PAYFAST_MODE=sandbox
\`\`\`

- Uses \`PAYFAST_SANDBOX_URL\` for API calls
- Safe for testing
- Uses test credentials
- No real transactions processed

### Production Mode

\`\`\`env
PAYFAST_MODE=production
\`\`\`

- Uses \`PAYFAST_API_URL\` for API calls
- Real transactions processed
- Requires production credentials
- **Use with caution**

## Next Steps

- [Getting Started](Getting-Started.md) - Start using the package
- [Payment Flows](Payment-Flows.md) - Understand payment processing
- [API Reference](API-Reference.md) - Explore available methods`;
      }

      // API Reference
      if (!this.documentation['API-Reference']) {
        this.documentation['API-Reference'] = `# API Reference

Complete reference documentation for all PayFast package methods and classes.

## Facade Access

All methods are accessible via the \`PayFast\` facade:

\`\`\`php
use zfhassaan\\Payfast\\Facades\\PayFast;
\`\`\`

## Authentication Methods

### getToken()

Get authentication token from PayFast.

\`\`\`php
$response = PayFast::getToken();
\`\`\`

**Returns**: \`JsonResponse\`

**Response Format**:
\`\`\`json
{
    "status": true,
    "data": {
        "token": "abc123...",
        "expires_in": 3600
    },
    "message": "Token retrieved successfully",
    "code": "00"
}
\`\`\`

### refreshToken()

Refresh an existing authentication token.

\`\`\`php
$response = PayFast::refreshToken($token, $refreshToken);
\`\`\`

**Parameters**:
- \`$token\` (string) - Current authentication token
- \`$refreshToken\` (string) - Refresh token

**Returns**: \`JsonResponse|null\`

## Payment Methods

### getOTPScreen()

Validate customer and get OTP screen for card payments.

\`\`\`php
$response = PayFast::getOTPScreen($paymentData);
\`\`\`

**Parameters**:
\`\`\`php
$paymentData = [
    'orderNumber' => 'ORD-12345',        // Required
    'transactionAmount' => 1000.00,      // Required
    'customerMobileNo' => '03001234567', // Required
    'customer_email' => 'customer@example.com', // Required
    'cardNumber' => '4111111111111111',  // Required
    'expiry_month' => '12',              // Required
    'expiry_year' => '2025',             // Required
    'cvv' => '123',                      // Required
];
\`\`\`

**Returns**: \`JsonResponse\`

**Response Format**:
\`\`\`json
{
    "status": true,
    "data": {
        "token": "abc123...",
        "customer_validate": {...},
        "transaction_id": "TXN123456",
        "payment_id": 1,
        "redirect_url": "https://..."
    },
    "message": "OTP screen retrieved successfully",
    "code": "00"
}
\`\`\`

### verifyOTPAndStorePares()

Verify OTP and store 3DS pares.

\`\`\`php
$response = PayFast::verifyOTPAndStorePares($transactionId, $otp, $pares);
\`\`\`

**Parameters**:
\`\`\`php
- \`$transactionId\` (string) - Transaction ID from getOTPScreen
- \`$otp\` (string) - OTP entered by customer
- \`$pares\` (string) - 3DS pares from PayFast
\`\`\`

**Returns**: \`JsonResponse\`

### completeTransactionFromPares()

Complete transaction using stored pares from callback.

\`\`\`php
$response = PayFast::completeTransactionFromPares($pares);
\`\`\`

**Parameters**:
\`\`\`php
- \`$pares\` (string) - 3DS pares from PayFast callback
\`\`\`

**Returns**: \`JsonResponse\`

### initiateTransaction()

Initiate a transaction directly (without OTP flow).

\`\`\`php
$response = PayFast::initiateTransaction($data);
\`\`\`

**Parameters**:
\`\`\`php
$data = [
    'orderNumber' => 'ORD-12345',
    'transactionAmount' => 1000.00,
    // ... other payment data
];
\`\`\`

**Returns**: \`string|bool\` (JSON string)

**Response Format**:
\`\`\`json
{
    "code": "00",
    "message": "Transaction initiated successfully",
    "transaction_id": "TXN123456",
    "redirect_url": "https://...",
    "data_3ds_secureid": "..."
}
\`\`\`

## Mobile Wallet Methods

### payWithEasyPaisa()

Process payment with EasyPaisa wallet.

\`\`\`php
$response = PayFast::payWithEasyPaisa($paymentData);
\`\`\`

**Returns**: \`mixed\` (JSON string)

**Response Format**:
\`\`\`json
{
    "status": true,
    "code": "00",
    "data": {
        "transaction_id": "TXN123456",
        "payment_id": 1,
        "redirect_url": "https://..."
    }
}
\`\`\`

### payWithUPaisa()

Process payment with UPaisa wallet.

\`\`\`php
$response = PayFast::payWithUPaisa($paymentData);
\`\`\`

**Returns**: \`mixed\` (JSON string)

## Transaction Query Methods

### getTransactionDetails()

Get transaction details by transaction ID.

\`\`\`php
$response = PayFast::getTransactionDetails($transactionId);
\`\`\`

**Parameters**:
\`\`\`php
- \`$transactionId\` (string) - PayFast transaction ID
\`\`\`

**Returns**: \`JsonResponse\`

**Response Format**:
\`\`\`json
{
    "status": true,
    "data": {
        "transaction_id": "TXN123456",
        "status": "completed",
        "amount": 1000.00
    },
    "message": "Transaction details retrieved successfully",
    "code": "00"
}
\`\`\`

### getTransactionDetailsByBasketId()

Get transaction details by basket/order ID.

\`\`\`php
$response = PayFast::getTransactionDetailsByBasketId($basketId);
\`\`\`

**Returns**: \`JsonResponse\`

### refundTransactionRequest()

Request a refund for a transaction.

\`\`\`php
$response = PayFast::refundTransactionRequest($data);
\`\`\`

**Returns**: \`string|bool\` (JSON string)

**Response Format**:
\`\`\`json
{
    "code": "00",
    "message": "Refund processed successfully",
    "refund_id": "REF123456"
}
\`\`\`

## Bank and Instrument Methods

### listBanks()

List available banks.

\`\`\`php
$response = PayFast::listBanks();
\`\`\`

**Returns**: \`JsonResponse\`

### listInstrumentsWithBank()

List payment instruments for a specific bank.

\`\`\`php
$response = PayFast::listInstrumentsWithBank($bankCode);
\`\`\`

**Returns**: \`JsonResponse|bool\`

## IPN Methods

### handleIPN()

Handle Instant Payment Notification webhook from PayFast.

\`\`\`php
$response = PayFast::handleIPN($ipnData);
\`\`\`

**Returns**: \`JsonResponse\`

**Response Format**:
\`\`\`json
{
    "status": true,
    "data": {
        "ipn_log_id": 123,
        "transaction_id": "TXN123456",
        "order_no": "ORD-12345",
        "payment_updated": true
    },
    "message": "IPN processed successfully",
    "code": "00"
}
\`\`\``;
      }

      // Payment Flows
      if (!this.documentation['Payment-Flows']) {
        this.documentation['Payment-Flows'] = `# Payment Flows

This document explains the complete payment flows supported by the PayFast package, including card payments, mobile wallet payments, and the OTP verification process.

## Overview

The PayFast package supports multiple payment methods:

1. **Direct Checkout** - PCI DSS compliant card payments
2. **Hosted Checkout** - Redirect-based payment processing
3. **Mobile Wallets** - EasyPaisa, UPaisa, JazzCash

## Card Payment Flow

### Complete Flow Diagram

\`\`\`php
1. Customer Initiates Payment
   ↓
2. Validate Customer (getOTPScreen)
   ↓
3. Payment Stored in DB (status: validated)
   ↓
4. Redirect to OTP Screen
   ↓
5. Customer Enters OTP
   ↓
6. Verify OTP & Store Pares (verifyOTPAndStorePares)
   ↓
7. Payment Updated (status: otp_verified, pares stored)
   ↓
8. PayFast Callback with Pares
   ↓
9. Complete Transaction (completeTransactionFromPares)
   ↓
10. Payment Completed (status: completed)
\`\`\`

### Step-by-Step Implementation

#### Step 1: Customer Validation

\`\`\`php
use zfhassaan\\Payfast\\Facades\\PayFast;

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
$result = json_decode($response->getContent(), true);

if ($result['status']) {
    $transactionId = $result['data']['transaction_id'];
    $paymentId = $result['data']['payment_id'];
    
    return redirect('/otp-screen')->with([
        'transaction_id' => $transactionId,
        'payment_id' => $paymentId,
    ]);
}
\`\`\`

## Payment Status Tracking

The \`ProcessPayment\` model tracks payment status through the following states:

### Status Constants

\`\`\`php
ProcessPayment::STATUS_PENDING      // Initial state
ProcessPayment::STATUS_VALIDATED    // Customer validated, waiting for OTP
ProcessPayment::STATUS_OTP_VERIFIED // OTP verified, pares stored, waiting for callback
ProcessPayment::STATUS_COMPLETED   // Transaction completed successfully
ProcessPayment::STATUS_FAILED      // Transaction failed
ProcessPayment::STATUS_CANCELLED    // Payment cancelled
\`\`\`

## Next Steps

- [API Reference](API-Reference.md) - Explore all available methods
- [IPN Handling](IPN-Handling.md) - Set up webhook notifications
- [Events and Listeners](Events-and-Listeners.md) - Understand event system`;
      }

      // IPN Handling
      if (!this.documentation['IPN-Handling']) {
        this.documentation['IPN-Handling'] = `# IPN (Instant Payment Notification) Handling

The IPN service handles webhook notifications from PayFast to update payment statuses automatically.

## Overview

IPN (Instant Payment Notification) is a webhook system that PayFast uses to notify your application about payment status changes. The IPN service:

- Logs all IPN notifications
- Updates payment status based on IPN data
- Dispatches events for completed/failed payments
- Prevents duplicate processing (idempotency)

## Setup

The IPN service is already registered in the service provider and ready to use. You just need to create a controller method and route to handle incoming IPN requests.

## Controller Implementation

Add this method to your controller to handle IPN webhooks:

\`\`\`php
<?php

namespace App\\Http\\Controllers;

use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\Log;
use zfhassaan\\Payfast\\Facades\\Payfast;

class PaymentController extends Controller
{
    public function handleIPN(Request $request)
    {
        $ipnData = $request->all();

        Log::channel('payfast')->info('IPN Received', [
            'ip' => $request->ip(),
            'data' => $ipnData,
        ]);

        $response = Payfast::handleIPN($ipnData);
        
        return $response;
    }
}
\`\`\`

## Route Setup

Add this route to your \`routes/web.php\` or \`routes/api.php\`:

\`\`\`php
Route::post('/payment/ipn', [PaymentController::class, 'handleIPN']);
\`\`\`

**Important**: Disable CSRF protection for the IPN endpoint since PayFast will be calling it from their servers.

### Disable CSRF for IPN Endpoint

In \`app/Http/Middleware/VerifyCsrfToken.php\`:

\`\`\`php
protected $except = [
    'payment/ipn',
    'api/payment/ipn', // If using API route
];
\`\`\`

## PayFast Configuration

Configure your IPN URL in PayFast dashboard:

- **Production**: \`https://yourdomain.com/payment/ipn\`
- **Sandbox**: \`https://yourdomain.com/payment/ipn\`

## What Happens When IPN is Received

1. **Validation**: IPN data is validated (checks for required fields)
2. **Idempotency Check**: Checks if IPN was already processed
3. **IPN Logging**: Creates an entry in \`payfast_ipn_table\`
4. **Payment Update**: Finds and updates the payment record
5. **Event Dispatch**: Dispatches \`PaymentCompleted\` or \`PaymentFailed\` events
6. **Email Notifications**: Email notifications are sent automatically (via listeners)

## Next Steps

- [Events and Listeners](Events-and-Listeners.md) - Understand event system
- [Models and Database](Models-and-Database.md) - Database schema
- [Troubleshooting](Troubleshooting.md) - Common issues and solutions`;
      }

      // Events and Listeners
      if (!this.documentation['Events-and-Listeners']) {
        this.documentation['Events-and-Listeners'] = `# Events and Listeners

The PayFast package uses Laravel's event system to handle side effects and provide extensibility without modifying core code.

## Overview

Events are dispatched at key points in the payment flow, allowing you to:
- Log payment activities
- Send notifications
- Update related records
- Perform analytics
- Integrate with third-party services

## Available Events

### PaymentInitiated

Dispatched when a payment is initiated.

**Event Class**: \`zfhassaan\\Payfast\\Events\\PaymentInitiated\`

### PaymentValidated

Dispatched when customer validation is successful.

**Event Class**: \`zfhassaan\\Payfast\\Events\\PaymentValidated\`

### PaymentCompleted

Dispatched when a payment is completed successfully.

**Event Class**: \`zfhassaan\\Payfast\\Events\\PaymentCompleted\`

### PaymentFailed

Dispatched when a payment fails.

**Event Class**: \`zfhassaan\\Payfast\\Events\\PaymentFailed\`

## Built-in Listeners

The package includes several built-in listeners that are automatically registered:

### LogPaymentActivity

Logs all payment activities to the activity log table.

**Listener Class**: \`zfhassaan\\Payfast\\Listeners\\LogPaymentActivity\`

### StorePaymentRecord

Stores payment records in the database.

**Listener Class**: \`zfhassaan\\Payfast\\Listeners\\StorePaymentRecord\`

### SendPaymentEmailNotifications

Sends email notifications for payment events.

**Listener Class**: \`zfhassaan\\Payfast\\Listeners\\SendPaymentEmailNotifications\`

## Creating Custom Listeners

### Method 1: Using Event Listeners

Create a listener class:

\`\`\`php
<?php

namespace App\\Listeners;

use zfhassaan\\Payfast\\Events\\PaymentCompleted;
use Illuminate\\Support\\Facades\\Log;

class UpdateOrderStatus
{
    public function handle(PaymentCompleted $event): void
    {
        $paymentData = $event->paymentData;
        
        // Update order status
        $order = Order::where('order_number', $paymentData['orderNumber'])->first();
        if ($order) {
            $order->update(['status' => 'paid']);
        }
    }
}
\`\`\`

Register the listener in \`app/Providers/EventServiceProvider.php\`:

\`\`\`php
use App\\Listeners\\UpdateOrderStatus;
use zfhassaan\\Payfast\\Events\\PaymentCompleted;

protected $listen = [
    PaymentCompleted::class => [
        UpdateOrderStatus::class,
    ],
];
\`\`\`

## Next Steps

- [Models and Database](Models-and-Database.md) - Database schema and models
- [IPN Handling](IPN-Handling.md) - Webhook notifications
- [Troubleshooting](Troubleshooting.md) - Common issues`;
      }

      // Models and Database
      if (!this.documentation['Models-and-Database']) {
        this.documentation['Models-and-Database'] = `# Models and Database

This document describes the database schema, models, and how to work with them.

## Database Tables

The package creates three main database tables:

1. \`payfast_process_payments_table\` - Stores payment records
2. \`payfast_activity_logs_table\` - Stores payment activity logs
3. \`payfast_ipn_table\` - Stores IPN (Instant Payment Notification) logs

## ProcessPayment Model

### Table: \`payfast_process_payments_table\`

Stores all payment records and their status.

### Model Usage

\`\`\`php
use zfhassaan\\Payfast\\Models\\ProcessPayment;

// Create a payment
$payment = ProcessPayment::create([
    'uid' => \\Str::uuid(),
    'token' => 'auth_token',
    'orderNo' => 'ORD-12345',
    'transaction_id' => 'TXN123456',
    'status' => ProcessPayment::STATUS_VALIDATED,
    'payment_method' => ProcessPayment::METHOD_CARD,
]);

// Find by transaction ID
$payment = ProcessPayment::where('transaction_id', 'TXN123456')->first();

// Find by order number
$payment = ProcessPayment::where('orderNo', 'ORD-12345')->first();
\`\`\`

### Status Constants

\`\`\`php
ProcessPayment::STATUS_PENDING      // Initial state
ProcessPayment::STATUS_VALIDATED    // Customer validated
ProcessPayment::STATUS_OTP_VERIFIED // OTP verified
ProcessPayment::STATUS_COMPLETED     // Payment completed
ProcessPayment::STATUS_FAILED       // Payment failed
ProcessPayment::STATUS_CANCELLED     // Payment cancelled
\`\`\`

### Payment Method Constants

\`\`\`php
ProcessPayment::METHOD_CARD       // Card payment
ProcessPayment::METHOD_EASYPAISA // EasyPaisa wallet
ProcessPayment::METHOD_JAZZCASH  // JazzCash wallet
ProcessPayment::METHOD_UPAISA   // UPaisa wallet
\`\`\`

### Helper Methods

\`\`\`php
$payment->isPending();      // Check if pending
$payment->isValidated();    // Check if validated
$payment->isOtpVerified();  // Check if OTP verified
$payment->isCompleted();    // Check if completed
$payment->isFailed();       // Check if failed
\`\`\`

## ActivityLog Model

### Table: \`payfast_activity_logs_table\`

Stores payment activity logs for audit purposes.

## IPNLog Model

### Table: \`payfast_ipn_table\`

Stores IPN (Instant Payment Notification) logs.

## Next Steps

- [Payment Flows](Payment-Flows.md) - Understand payment processing
- [Events and Listeners](Events-and-Listeners.md) - Event system
- [Console Commands](Console-Commands.md) - CLI commands`;
      }

      // Console Commands
      if (!this.documentation['Console-Commands']) {
        this.documentation['Console-Commands'] = `# Console Commands

The PayFast package includes a console command for managing payments.

## Available Commands

### payfast:check-pending-payments

Checks for pending payments, verifies their status with PayFast, updates payment status, logs activity, and sends email notifications.

#### Usage

\`\`\`bash
php artisan payfast:check-pending-payments
\`\`\`

#### Options

- \`--status\` - Filter by payment status (pending, validated, otp_verified, completed, failed)
- \`--limit\` - Limit the number of records to process (default: 50)
- \`--no-email\` - Skip sending email notifications

#### Examples

\`\`\`bash
# Check all pending and validated payments
php artisan payfast:check-pending-payments

# Check specific status
php artisan payfast:check-pending-payments --status=otp_verified

# Limit results
php artisan payfast:check-pending-payments --limit=10

# Skip email notifications
php artisan payfast:check-pending-payments --no-email
\`\`\`

## Scheduling

You can schedule the command to run automatically using Laravel's task scheduler.

In \`app/Console/Kernel.php\`:

\`\`\`php
use Illuminate\\Console\\Scheduling\\Schedule;

protected function schedule(Schedule $schedule): void
{
    // Run every 5 minutes
    $schedule->command('payfast:check-pending-payments')
        ->everyFiveMinutes()
        ->withoutOverlapping();
}
\`\`\`

## Next Steps

- [Models and Database](Models-and-Database.md) - Database schema
- [Payment Flows](Payment-Flows.md) - Payment processing
- [Troubleshooting](Troubleshooting.md) - Common issues`;
      }

      // Testing Guide
      if (!this.documentation['Testing-Guide']) {
        this.documentation['Testing-Guide'] = `# Testing Guide

This guide covers testing the PayFast package, including unit tests, feature tests, and best practices.

## Overview

The PayFast package includes a comprehensive test suite covering:

- Service layer (Authentication, Payment, Transaction, OTP)
- Repository pattern implementation
- DTO creation and conversion
- Model methods and status transitions
- Event dispatching and handling
- Console commands
- Complete payment flows
- Error handling
- Edge cases

## Running Tests

### Run All Tests

\`\`\`bash
php artisan test
\`\`\`

### Run Only PayFast Tests

\`\`\`bash
php artisan test --filter PayFast
\`\`\`

### Run Specific Test Suite

\`\`\`bash
# Unit tests only
php artisan test tests/Unit/PayFast

# Feature tests only
php artisan test tests/Feature/PayFast
\`\`\`

## Test Categories

### Unit Tests

#### Service Tests

- **AuthenticationServiceTest**: Tests token retrieval and refresh
- **PaymentServiceTest**: Tests payment validation and transaction initiation
- **OTPVerificationServiceTest**: Tests OTP verification and pares handling
- **TransactionServiceTest**: Tests transaction queries and refunds

#### Repository Tests

- **ProcessPaymentRepositoryTest**: Tests CRUD operations and queries

#### Model Tests

- **ProcessPaymentTest**: Tests model methods and status transitions

### Feature Tests

#### Payment Flow Tests

- **PaymentFlowTest**: Tests complete payment flows including:
  - Card payment with OTP verification
  - Payment validation failures
  - Wallet payment flows

## Next Steps

- [Payment Flows](Payment-Flows.md) - Understand payment processing
- [API Reference](API-Reference.md) - Explore available methods
- [Troubleshooting](Troubleshooting.md) - Common issues`;
      }

      // Troubleshooting
      if (!this.documentation['Troubleshooting']) {
        this.documentation['Troubleshooting'] = `# Troubleshooting

Common issues and solutions when using the PayFast package.

## Installation Issues

### Service Provider Not Found

**Error**: \`Class 'zfhassaan\\Payfast\\Provider\\PayFastServiceProvider' not found\`

**Solution**:
1. Ensure package is installed: \`composer require zfhassaan/payfast\`
2. Run \`composer dump-autoload\`
3. For Laravel < 11, register service provider in \`config/app.php\`

### Facade Not Found

**Error**: \`Class 'PayFast' not found\`

**Solution**:
1. Ensure facade is registered in \`config/app.php\` (Laravel < 11)
2. Check namespace: \`use zfhassaan\\Payfast\\Facades\\PayFast;\`
3. Run \`php artisan config:clear\`

## Configuration Issues

### Configuration Not Loading

**Error**: Configuration values are null or empty

**Solution**:
1. Clear config cache: \`php artisan config:clear\`
2. Verify \`.env\` file has all required variables
3. Check \`config/payfast.php\` exists
4. Run \`php artisan config:cache\` after changes

### Wrong API URL Being Used

**Error**: Requests going to wrong endpoint

**Solution**:
1. Check \`PAYFAST_MODE\` in \`.env\` (sandbox vs production)
2. Verify \`PAYFAST_API_URL\` and \`PAYFAST_SANDBOX_URL\` are correct
3. Clear config cache: \`php artisan config:clear\`

## Payment Issues

### Token Not Generated

**Error**: \`Failed to get authentication token\`

**Solution**:
1. Check API URL is correct
2. Verify merchant ID and secured key
3. Check network connectivity
4. Verify PayFast service is available
5. Check logs for detailed error messages

### Customer Validation Fails

**Error**: \`Validation failed\` or error code from PayFast

**Solution**:
1. Verify all required fields are provided
2. Check card number format
3. Verify expiry date is valid (not expired)
4. Check CVV format
5. Review PayFast error codes documentation

### Payment Not Completing

**Error**: Payment stuck in \`otp_verified\` status

**Solution**:
1. Check if callback was received
2. Verify callback URL is accessible
3. Check PayFast logs for callback attempts
4. Manually trigger completion if needed
5. Use console command to check status

## IPN Issues

### IPN Not Received

**Error**: IPN webhook not being called

**Solution**:
1. Check IPN URL is configured in PayFast dashboard
2. Verify URL is publicly accessible
3. Check server logs for incoming requests
4. Verify HTTPS is working correctly
5. Check firewall isn't blocking PayFast IPs

## Getting Help

If you're still experiencing issues:

1. **Check Documentation**: Review all wiki pages
2. **Check Issues**: Search GitHub issues
3. **Review Logs**: Check application and PayFast logs
4. **Test in Sandbox**: Verify in sandbox mode first
5. **Contact Support**: Email zfhassaan@gmail.com

## Next Steps

- [Security Best Practices](Security-Best-Practices.md) - Security guidelines
- [API Reference](API-Reference.md) - Method documentation
- [Payment Flows](Payment-Flows.md) - Payment processing`;
      }

      // Security Best Practices
      if (!this.documentation['Security-Best-Practices']) {
        this.documentation['Security-Best-Practices'] = `# Security Best Practices

Security guidelines for using the PayFast package safely and securely.

## Environment Variables

### Never Commit Credentials

**❌ Bad**:

\`\`\`php
// Don't hardcode credentials
$merchantId = '12345';
$securedKey = 'secret_key';
\`\`\`

**✅ Good**:

\`\`\`php
// Use environment variables
$merchantId = config('payfast.merchant_id');
$securedKey = config('payfast.secured_key');
\`\`\`

### Secure .env File

1. **Never commit \`.env\`** to version control
2. **Use \`.env.example\`** for documentation
3. **Restrict file permissions**: \`chmod 600 .env\`
4. **Use different credentials** for sandbox and production
5. **Rotate credentials** regularly

## API Security

### Use HTTPS

Always use HTTPS for:

- API calls to PayFast
- Payment callbacks
- IPN endpoints
- Customer redirects

### Validate All Inputs

Always validate user input:

\`\`\`php
$request->validate([
    'orderNumber' => 'required|string|max:255',
    'transactionAmount' => 'required|numeric|min:0.01',
    'customerMobileNo' => 'required|string|regex:/^[0-9]{11}$/',
    'customer_email' => 'required|email',
    'cardNumber' => 'required|string|regex:/^[0-9]{13,19}$/',
    'expiry_month' => 'required|string|regex:/^(0[1-9]|1[0-2])$/',
    'expiry_year' => 'required|string|regex:/^[0-9]{4}$/',
    'cvv' => 'required|string|regex:/^[0-9]{3,4}$/',
]);
\`\`\`

## IPN Security

### IP Whitelisting

Whitelist PayFast IP addresses:

\`\`\`php
public function handleIPN(Request $request)
{
    $allowedIPs = [
        '203.0.113.0', // PayFast IP 1
        '203.0.113.1', // PayFast IP 2
        // Get actual IPs from PayFast support
    ];

    if (!in_array($request->ip(), $allowedIPs)) {
        Log::warning('IPN from unauthorized IP', ['ip' => $request->ip()]);
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    return Payfast::handleIPN($request->all());
}
\`\`\`

### Disable CSRF for IPN

Add IPN route to CSRF exceptions:

\`\`\`php
// app/Http/Middleware/VerifyCsrfToken.php
protected $except = [
    'payment/ipn',
    'api/payment/ipn',
];
\`\`\`

## Data Protection

### Don't Store Card Data

**❌ Bad**:

\`\`\`php
// Never store full card numbers
ProcessPayment::create([
    'card_number' => $request->cardNumber, // DON'T DO THIS
]);
\`\`\`

**✅ Good**:

\`\`\`php
// Store only last 4 digits if needed
ProcessPayment::create([
    'card_last_four' => substr($request->cardNumber, -4),
]);
\`\`\`

## Best Practices Summary

1. ✅ **Never commit credentials** - Use environment variables
2. ✅ **Use HTTPS** - Always encrypt data in transit
3. ✅ **Validate inputs** - Never trust user input
4. ✅ **Don't store card data** - Use tokenization
5. ✅ **Implement rate limiting** - Prevent abuse
6. ✅ **Log security events** - Monitor for threats
7. ✅ **Keep updated** - Apply security patches
8. ✅ **Use IP whitelisting** - For IPN endpoints
9. ✅ **Verify signatures** - For webhooks
10. ✅ **Handle errors securely** - Don't expose sensitive info

## Next Steps

- [Troubleshooting](Troubleshooting.md) - Common issues
- [Configuration Guide](Configuration-Guide.md) - Secure configuration
- [IPN Handling](IPN-Handling.md) - Secure webhook handling`;
      }

      // Understanding the Direct Checkout Process
      if (!this.documentation['Understanding-the-Direct-Checkout-Process']) {
        this.documentation['Understanding-the-Direct-Checkout-Process'] = `# Understanding the Direct Checkout Process

## Introduction

The Direct Checkout process for Payfast provides a secure and convenient method for merchants to accept online payments. By following a few simple steps, merchants can integrate Payfast into their websites and offer their customers a seamless payment experience. This guide uses the PayFast Laravel package for implementation.

## Overview

Direct Checkout is a PCI DSS compliant payment method that allows you to process card payments directly on your website. The process involves:

1. Getting an authentication token
2. Validating customer information
3. Getting OTP screen for 3DS authentication
4. Verifying OTP and storing pares
5. Completing the transaction

## Installation

First, install the package:

\`\`\`bash
composer require zfhassaan/payfast
\`\`\`

Publish the configuration and migrations:

\`\`\`bash
php artisan vendor:publish --tag=payfast-config
php artisan vendor:publish --tag=payfast-migrations
php artisan migrate
\`\`\`

## Configuration

Add your PayFast credentials to \`.env\`:

\`\`\`env
PAYFAST_API_URL=https://api.payfast.com
PAYFAST_SANDBOX_URL=https://sandbox.payfast.com
PAYFAST_GRANT_TYPE=client_credentials
PAYFAST_MERCHANT_ID=your_merchant_id
PAYFAST_SECURED_KEY=your_secured_key
PAYFAST_RETURN_URL=https://yourdomain.com/payment/callback
PAYFAST_MODE=sandbox
\`\`\`

## Step 1: Collecting Customer Data

Create a form request to validate customer data:

\`\`\`php
<?php

namespace App\\Http\\Requests;

use Illuminate\\Foundation\\Http\\FormRequest;

class PayfastValidateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'orderNumber' => 'required|string',
            'transactionAmount' => 'required|numeric|min:0.01',
            'customerMobileNo' => 'required|string',
            'customer_email' => 'required|email',
            'cardNumber' => 'required|string',
            'expiry_month' => 'required|string',
            'expiry_year' => 'required|string',
            'cvv' => 'required|string',
        ];
    }
}
\`\`\`

## Step 2: Validate Customer and Get OTP Screen

In your controller, implement the checkout method:

\`\`\`php
use zfhassaan\\Payfast\\Facades\\PayFast;
use zfhassaan\\Payfast\\Models\\ProcessPayment;

public function checkout(PayfastValidateRequest $request)
{
    // Get authentication token
    $payfast = app('payfast');
    $tokenResponse = $payfast->getToken();
    $tokenData = json_decode($tokenResponse->getContent(), true);

    if ($tokenData['status'] && $tokenData['code'] === '00') {
        $payfast->setAuthToken($tokenData['data']['token']);
    } else {
        abort(403, 'Error: Auth Token Not Generated.');
    }

    // Validate customer and get OTP screen
    $paymentData = [
        'orderNumber' => $request->orderNumber,
        'transactionAmount' => $request->transactionAmount,
        'customerMobileNo' => $request->customerMobileNo,
        'customer_email' => $request->customer_email,
        'cardNumber' => $request->cardNumber,
        'expiry_month' => $request->expiry_month,
        'expiry_year' => $request->expiry_year,
        'cvv' => $request->cvv,
    ];

    $show_otp = $payfast->getOTPScreen($paymentData);
    $otpData = json_decode($show_otp->getContent(), true);

    if ($otpData['status'] && $otpData['code'] === '00') {
        // Store payment data
        $payment = ProcessPayment::create([
            'uid' => \\Str::uuid(),
            'token' => $tokenData['data']['token'],
            'orderNo' => $request->orderNumber,
            'data_3ds_secureid' => $otpData['data']['customer_validate']['data_3ds_secureid'] ?? null,
            'transaction_id' => $otpData['data']['transaction_id'] ?? null,
            'status' => ProcessPayment::STATUS_VALIDATED,
            'payment_method' => ProcessPayment::METHOD_CARD,
            'payload' => json_encode($otpData['data']),
            'requestData' => json_encode($paymentData),
        ]);

        return redirect('/otp-screen')->with([
            'transaction_id' => $payment->transaction_id,
            'payment_id' => $payment->id,
        ]);
    }

    return response()->json([
        'message' => $otpData['message'] ?? 'Error processing payment',
    ], 400);
}
\`\`\`

## Additional Resources

- [Payment Flows](Payment-Flows) - Detailed payment flow documentation
- [API Reference](API-Reference) - Complete API method documentation
- [IPN Handling](IPN-Handling) - Webhook notifications
- [Troubleshooting](Troubleshooting) - Common issues and solutions`;
      }

      // Understanding the Hosted Checkout Process for Payfast
      if (!this.documentation['Understanding-the-Hosted-Checkout-Process-for-Payfast']) {
        this.documentation['Understanding-the-Hosted-Checkout-Process-for-Payfast'] = `# Understanding the Hosted Checkout Process for Payfast

## Introduction

The Hosted Checkout process for Payfast provides a secure and convenient method for merchants to accept online payments. By following a few simple steps, merchants can integrate Payfast into their websites and offer their customers a seamless payment experience. This guide uses the PayFast Laravel package for implementation.

## Overview

Hosted Checkout redirects customers to PayFast's secure payment page where they complete the payment. This method is ideal for merchants who want to avoid PCI DSS compliance requirements as card data is never handled on their servers.

## Installation

First, install the package:

\`\`\`bash
composer require zfhassaan/payfast
\`\`\`

Publish the configuration:

\`\`\`bash
php artisan vendor:publish --tag=payfast-config
\`\`\`

## Configuration

Add your PayFast credentials to \`.env\`:

\`\`\`env
PAYFAST_API_URL=https://api.payfast.com
PAYFAST_SANDBOX_URL=https://sandbox.payfast.com
PAYFAST_GRANT_TYPE=client_credentials
PAYFAST_MERCHANT_ID=your_merchant_id
PAYFAST_SECURED_KEY=your_secured_key
PAYFAST_RETURN_URL=https://yourdomain.com/payment/callback
PAYFAST_MODE=sandbox
\`\`\`

## Step 1: Setting Up Merchant Data

Get your merchant credentials from PayFast dashboard:

\`\`\`php
$merchant_id = config('payfast.merchant_id');
$secured_key = config('payfast.secured_key');
$merchant_name = 'Your Merchant Name'; // Your registered merchant name
\`\`\`

## Step 2: Collecting Customer Data

Gather customer and order information:

\`\`\`php
$order_id = 'ORD-' . time(); // Or use your order ID generation logic
$amount = 1000.00; // Transaction amount
$mobile = "03001234567"; // Customer mobile number
$email = 'customer@example.com'; // Customer email
\`\`\`

## Step 3: Generating the Payment Token

Get an access token from PayFast:

\`\`\`php
use zfhassaan\\Payfast\\Facades\\PayFast;

// Get authentication token
$tokenResponse = PayFast::getToken();
$tokenData = json_decode($tokenResponse->getContent(), true);

if ($tokenData['status'] && $tokenData['code'] === '00') {
    $ACCESS_TOKEN = $tokenData['data']['token'];
} else {
    // Handle error
    abort(403, 'Error: Auth Token Not Generated.');
}
\`\`\`

## Step 4: Creating the Signature

Generate a signature using MD5 hash:

\`\`\`php
$signature = md5($merchant_id . ":" . $merchant_name . ":" . $amount . ":" . $order_id);
$backend_callback = "signature=" . $signature . "&order_id=" . $order_id;
\`\`\`

## Step 5: Constructing the Payload

Build the payload array with all required parameters:

\`\`\`php
$successUrl = route('payment.success'); // Your success URL
$failUrl = route('payment.failure'); // Your failure URL
$payment_url = config('payfast.mode') === 'production' 
    ? config('payfast.api_url') . '/checkout' 
    : config('payfast.sandbox_api_url') . '/checkout';

$payload = [
    'MERCHANT_ID' => $merchant_id,
    'MERCHANT_NAME' => $merchant_name,
    'TOKEN' => $ACCESS_TOKEN,
    'PROCCODE' => '00',
    'TXNAMT' => $amount,
    'CUSTOMER_MOBILE_NO' => $mobile,
    'CUSTOMER_EMAIL_ADDRESS' => $email,
    'SIGNATURE' => $signature,
    'VERSION' => 'WOOCOM-APPS-PAYMENT-0.9',
    'TXNDESC' => 'Products purchased from ' . $merchant_name,
    'SUCCESS_URL' => urlencode($successUrl),
    'FAILURE_URL' => urlencode($failUrl),
    'BASKET_ID' => $order_id,
    'ORDER_DATE' => date('Y-m-d H:i:s', time()),
    'CHECKOUT_URL' => urlencode($backend_callback),
];
\`\`\`

## Additional Resources

- [Understanding the Direct Checkout Process](Understanding-the-Direct-Checkout-Process) - Direct checkout guide
- [Payment Flows](Payment-Flows) - Detailed payment flow documentation
- [IPN Handling](IPN-Handling) - Webhook notifications
- [Troubleshooting](Troubleshooting) - Common issues and solutions`;
      }

      // Contributing
      if (!this.documentation['Contributing']) {
        this.documentation['Contributing'] = `# Contributing

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
2. **Create a feature branch**: \`git checkout -b feature/amazing-feature\`
3. **Make your changes**
4. **Write/update tests**
5. **Update documentation**
6. **Follow coding standards** (PSR-12)
7. **Commit your changes**: \`git commit -m 'Add amazing feature'\`
8. **Push to branch**: \`git push origin feature/amazing-feature\`
9. **Open a Pull Request**

## Development Setup

### Clone Repository

\`\`\`bash
git clone https://github.com/zfhassaan/payfast.git
cd payfast
\`\`\`

### Install Dependencies

\`\`\`bash
composer install
\`\`\`

### Run Tests

\`\`\`bash
php artisan test
\`\`\`

## Coding Standards

The package follows PSR-12 coding standards. Use PHP CS Fixer or Laravel Pint:

\`\`\`bash
# Using Laravel Pint
./vendor/bin/pint

# Or using PHP CS Fixer
./vendor/bin/php-cs-fixer fix
\`\`\`

## Testing

All new features must include tests:

1. **Unit tests** - For individual methods/classes
2. **Feature tests** - For complete flows
3. **Edge cases** - Test error conditions

## Documentation

When adding features:

1. **Update README.md** - If needed
2. **Update documentation** - Add/update relevant documentation pages in \`docs/content/\`
3. **Add code comments** - Document complex logic
4. **Update changelog** - Add entry to changelog.md

## Commit Messages

Follow conventional commits:

\`\`\`
feat: Add new payment method
fix: Fix token refresh issue
docs: Update installation guide
test: Add tests for new feature
refactor: Refactor payment service
chore: Update dependencies
\`\`\`

## Questions?

If you have questions:

1. **Check documentation** - Review wiki pages
2. **Search issues** - Look for similar questions
3. **Create issue** - Ask in GitHub issues
4. **Email** - zfhassaan@gmail.com

## License

By contributing, you agree that your contributions will be licensed under the MIT License.

## Thank You!

Thank you for contributing to the PayFast package! Your contributions help make this package better for everyone.`;
      }
    },
  },
}).mount("#app");

