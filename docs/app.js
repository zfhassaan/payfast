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
    },
  },
}).mount("#app");

