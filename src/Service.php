<?php

declare(strict_types=1);
/**
 * Copyright 2025- FOSSBilling
 * SPDX-License-Identifier: Apache-2.0.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace Box\Mod\Discord;

use Box\Mod\Discord\Entity\Webhook;
use FOSSBilling\Version;
use Symfony\Component\HttpClient\HttpClient;

class Service implements \FOSSBilling\InjectionAwareInterface
{
    protected ?\Pimple\Container $di = null;

    public const COLOR_INFO      = 0x3498db; // Blue
    public const COLOR_SUCCESS   = 0x2ecc71; // Green
    public const COLOR_WARNING   = 0xf1c40f; // Yellow
    public const COLOR_ERROR     = 0xe74c3c; // Red
    public const COLOR_NEUTRAL   = 0x95a5a6; // Gray

    /**
     * Events supported by the Discord module
     * Key = FOSSBilling event name
     * Value = [
     *   'label'       => string, human-friendly name,
     *   'description' => string, short explanation shown in embed,
     *   'color'       => int, one of the COLOR_XXX constants
     * ]
     */
    private const SUPPORTED_EVENTS = [
        // Security-related
        'onEventClientLoginFailed' => [
            'label' => 'Client Login Failed',
            'description' => 'A client attempted to login with invalid credentials',
            'color' => self::COLOR_WARNING,
        ],
        'onEventAdminLoginFailed' => [
            'label' => 'Admin Login Failed',
            'description' => 'An administrator attempted to login with invalid credentials',
            'color' => self::COLOR_ERROR,
        ],
        'onAfterClientSignUp' => [
            'label' => 'New Client Signup',
            'description' => 'A new client has registered',
            'color' => self::COLOR_SUCCESS,
        ],

        // Orders
        'onAfterAdminOrderCreate' => [
            'label' => 'Order Created (Admin)',
            'description' => 'An order was created by an admin',
            'color' => self::COLOR_INFO,
        ],
        'onAfterClientOrderCreate' => [
            'label' => 'Order Created (Client)',
            'description' => 'An order was created by a client',
            'color' => self::COLOR_INFO,
        ],
        'onAfterAdminOrderSuspend' => [
            'label' => 'Order Suspended',
            'description' => 'An order was suspended',
            'color' => self::COLOR_WARNING,
        ],
        'onAfterAdminOrderCancel' => [
            'label' => 'Order Cancelled',
            'description' => 'An order was cancelled',
            'color' => self::COLOR_ERROR,
        ],

        // Invoices
        'onAfterAdminInvoiceApprove' => [
            'label' => 'Invoice Approved',
            'description' => 'An invoice was approved',
            'color' => self::COLOR_SUCCESS,
        ],
        'onAfterAdminInvoiceRefund' => [
            'label' => 'Invoice Refunded',
            'description' => 'An invoice was refunded',
            'color' => self::COLOR_WARNING,
        ],
        'onAfterAdminTransactionCreate' => [
            'label' => 'Transaction Created',
            'description' => 'A new transaction was recorded',
            'color' => self::COLOR_INFO,
        ],

        // Tickets
        'onAfterClientOpenTicket' => [
            'label' => 'New Ticket Opened',
            'description' => 'A client opened a new support ticket',
            'color' => self::COLOR_INFO,
        ],
        'onAfterAdminReplyTicket' => [
            'label' => 'Ticket Replied (Admin)',
            'description' => 'An administrator replied to a ticket',
            'color' => self::COLOR_NEUTRAL,
        ],
        'onAfterClientReplyTicket' => [
            'label' => 'Ticket Replied (Client)',
            'description' => 'A client replied to a ticket',
            'color' => self::COLOR_NEUTRAL,
        ],
    ];

    public function setDi(\Pimple\Container $di): void
    {
        $this->di = $di;
    }

    public function getDi(): ?\Pimple\Container
    {
        return $this->di;
    }

    public function install(): void
    {
        $conn = $this->di['em']->getConnection();

        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS discord_webhooks (
            id INT AUTO_INCREMENT NOT NULL,
            url VARCHAR(255) NOT NULL,
            events JSON NOT NULL,
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL;

        $conn->executeStatement($sql);
    }

    public function uninstall(): void
    {
        $conn = $this->di['em']->getConnection();
        $conn->executeStatement('DROP TABLE discord_webhooks');
    }

    public function getWebhooks(): array
    {
        return $this->di['em']->getRepository(Webhook::class)->findBy(['active' => true]);
    }

    public function notifyEvent(string $eventName, string $message = '', array $embeds = [], bool $throwOnError = false): void
    {
        $webhooks = $this->getWebhooks();
        $client = HttpClient::create(['bindto' => BIND_TO]);

        foreach ($webhooks as $wh) {
            if (!in_array($eventName, $wh->getEvents()) && !in_array('all_events', $wh->getEvents())) {
                continue;
            }

            try {
                $client->request('POST', $wh->getUrl(), [
                    'json' => $this->createPayload($message, $embeds),
                ]);

            } catch (\Throwable $e) {
                if ($throwOnError) {
                    throw new \FOSSBilling\Exception('Failed webhook #' . $wh->getId() . ': ' . $e->getMessage());
                } else {
                    error_log('[Discord Module] Failed webhook #' . $wh->getId() . ': ' . $e->getMessage());
                }
            }
        }
    }

    private function createPayload(?string $message = null, ?array $embeds = null): array
    {
        $payload = [];

        // Only add content if provided. This is not the embed content but the in-chat text message.
        if (!empty($message)) {
            $payload['content'] = $message;
        }

        // Process embeds if provided
        if (!empty($embeds)) {
            // Default embed structure
            $payload['embeds'] = array_map(function($embed) {
                return array_merge([
                    'title' => null,
                    'description' => null,
                    'url' => null,
                    'color' => self::COLOR_INFO,
                    'fields' => [],
                    'footer' => [
                        'text' => 'FOSSBilling v' . Version::VERSION,
                        'icon_url' => 'https://fossbilling.org/logo.png'
                    ],
                    'timestamp' => (new \DateTime())->format(\DateTime::ISO8601)
                ], $embed);
            }, $embeds);
        }

        return $payload;
    }

    public function buildEmbedFieldsFromEvent(\Box_Event $event, array $extraFields = []): array
    {
        $params = $event->getParameters();
        $fields = [];
        
        // Common fields: key => label
        $commonFields = [
            'id' => 'ID',
            'ip' => 'IP Address',
            'email' => 'Email Address',
            'username' => 'Username',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'admin_id' => 'Admin ID',
            'client_id' => 'Client ID',
            'order_id' => 'Order ID',
            'ticket_id' => 'Ticket ID',
            'product_id' => 'Product ID',
            'invoice_id' => 'Invoice ID',
            'subscription_id' => 'Subscription ID',
            'transaction_id' => 'Transaction ID',
            'title' => 'Title',
            'price' => 'Price',
            'total' => 'Total',
            'type' => 'Type',
            'currency' => 'Currency',
            'status' => 'Status',
            'country' => 'Country',
            'company' => 'Company Name',
            'company_number' => 'Company Number',
            'company_vat' => 'VAT Number',
        ];

        $fields = $this->addFields($fields, $params, $commonFields);

        // Merge any extra fields provided
        return array_merge($fields, $extraFields);
    }

    /**
     * Adds multiple fields to the embed array if keys exist in params
     */
    private function addFields(array $fields, array $eventParams, array $keysAndLabels, bool $inline = true): array
    {
        foreach ($keysAndLabels as $key => $label) {
            if (isset($eventParams[$key])) {
                $fields[] = [
                    'name' => $label,
                    'value' => (string) $eventParams[$key],
                    'inline' => $inline,
                ];
            }
        }
        return $fields;
    }

    /**
     * Global event listener for FOSSBilling events.
     *
     * This method is automatically triggered for every event dispatched by the system.
     * It checks whether the current event is listed in the {@see SUPPORTED_EVENTS} map
     * and, if so, sends a formatted embed notification to all active Discord webhooks
     * that have subscribed to the event.
     *
     * The embed includes:
     * - A human-friendly event label
     * - A description of the event
     * - A color indicating the event type (info, success, warning, error, neutral)
     * - Extracted fields from the event parameters (IP, email, username, etc.)
     *
     * @return void
     */
    public static function onEveryEvent(\Box_Event $event): void
    {
        $di = $event->getDi();
        
        /** @var Service $service */
        $service = $di['mod_service']('discord');

        $eventName = $event->getName();

        if (!isset(self::SUPPORTED_EVENTS[$eventName])) {
            return;
        }

        $config = self::SUPPORTED_EVENTS[$eventName];
        $fields = $service->buildEmbedFieldsFromEvent($event);

        $service->notifyEvent($eventName, '', [[
            'title' => $config['label'],
            'description' => $config['description'],
            'color' => $config['color'],
            'fields' => $fields,
        ]]);
    }
}
