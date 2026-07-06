# Shopware 6 - Price on Request Plugin

Replaces the product price with a configurable inquiry button. Customers submit their contact details and message - the shop owner receives the request via email.

## Features

- Hide product price globally or per product (via custom field)
- Configurable inquiry button with label
- Modal form with name, email, message and GDPR consent checkbox
- Email notification to shop owner
- Optional confirmation email to customer (configurable subject and text)
- Works on product detail page and category listing
- Hides "Add to cart" button when price is hidden (configurable)
- Spam protection: honeypot field and IP-based rate limiting
- Multi-language support (de-DE, en-GB)
- Sales channel aware configuration

## Installation

1. Copy plugin folder to `custom/plugins/MichaPriceOnRequest`
2. `bin/console plugin:refresh`
3. `bin/console plugin:install --activate MichaPriceOnRequest`
4. Configure under Extensions -> Price on Request

## Configuration

| Setting | Description |
|---|---|
| Recipient Email | Email address for incoming inquiries |
| Button Label | Text shown on the inquiry button |
| Mode | All products or selected products only |
| Hide Add to Cart | Hides the add to cart button when active |
| Spam Protection | Enable/disable rate limiting per IP |
| Max requests/hour | Maximum allowed requests per IP per hour |
| Confirmation Email | Send confirmation email to customer |

## Per-Product Activation

Go to any product -> Specifications -> Custom Fields -> Price on Request -> toggle active.

Only relevant when mode is set to "Selected products only".

## Requirements

- Shopware 6.5+
- PHP 8.1+