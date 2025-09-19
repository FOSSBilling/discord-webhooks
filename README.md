# Discord/Slack Webhooks Module
A FOSSBilling extension module that enables real-time Discord/Slack notifications for billing events through webhooks.

**Important notice:** This module is made using Doctrine which isn't supported in FOSSBilling yet. We are working hard to introduce Doctrine support within FOSSBilling core. It should start working after support is added.

## Overview
The Discord module allows you to send automated notifications to Discord channels when specific events occur in your FOSSBilling instance. This helps you stay informed about important business activities like new orders, failed payments, support tickets, and security events.

## Features
- **Real-time Notifications**: Instant Discord messages when events occur
- **Event Selection**: Choose specific events or subscribe to all events
- **Multiple Webhooks**: Configure different webhooks for different channels or purposes
- **Rich Embeds**: Formatted Discord messages with colors and structured information

## Supported Events
### Security Events
- **Client Login Failed**: When a client attempts to login with invalid credentials
- **Admin Login Failed**: When an administrator attempts to login with invalid credentials
- **New Client Signup**: When a new client registers an account

### Order Events
- **Order Created (Admin)**: When an administrator creates an order
- **Order Created (Client)**: When a client places an order
- **Order Suspended**: When an order is suspended
- **Order Cancelled**: When an order is cancelled

### Invoice & Transaction Events
- **Invoice Approved**: When an invoice is approved for payment
- **Invoice Refunded**: When an invoice refund is processed
- **Transaction Created**: When a new payment transaction is recorded

### Support Ticket Events
- **New Ticket Opened**: When a client opens a new support ticket
- **Ticket Replied (Admin)**: When an administrator replies to a ticket
- **Ticket Replied (Client)**: When a client replies to a ticket

## Installation
1. **Module Installation**: The Discord module should be placed in the `src/modules/Discord/` directory of your FOSSBilling installation.

2. **Enable the Module**:
   - Navigate to **Extensions** in your FOSSBilling admin panel
   - Find the Discord module and activate it

3. **Database Setup**: The module will automatically create the necessary database tables when first activated.

## License
This module is part of FOSSBilling and is licensed under the Apache License 2.0.