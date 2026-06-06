# Shopware 6 – Price on Request Plugin

Hides the product price and replaces it with a configurable inquiry button. 
Customers can submit their name, email and a message – the shop owner receives the request via email.

## Features
- Hide price per Sales Channel
- Configurable button label
- Inquiry form with modal
- Email notification to configurable recipient

## Installation
1. Copy plugin to `custom/plugins/MichaPriceOnRequest`
2. `bin/console plugin:refresh`
3. `bin/console plugin:install --activate MichaPriceOnRequest`
4. Configure recipient email under Extensions → MichaPriceOnRequest

## Requirements
- Shopware 6.1+
- PHP 8.1+
