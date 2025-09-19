<?php

declare(strict_types=1);
/**
 * Copyright 2025- FOSSBilling
 * SPDX-License-Identifier: Apache-2.0.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace Box\Mod\Discord\Api;

use \Box\Mod\Discord\Service;
use \Box\Mod\Discord\Entity\Webhook;
use FOSSBilling\Exception;

class Admin extends \Api_Abstract
{
    /**
     * Send a test message to the specified webhook.
     */
    public function send_test_message(array $data): bool
    {
        $required = [
            'id' => 'Webhook ID is required.',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        /** @var Webhook $webhook */
        $webhook = $this->di['em']->find(Webhook::class, (int) $data['id']);

        if (!$webhook) {
            throw new Exception('Webhook not found');
        }

        if (!$webhook->isActive()) {
            throw new Exception('Webhook is inactive.');
        }
        
        /** @var Service $service */
        $service = $this->getService();

        $embeds = [
            [
                'title' => '✅ Successful test message',
                'description' => 'This message is sent by FOSSBilling. The webhook works.',
                'color' => Service::COLOR_SUCCESS,
            ]];

        $service->notifyEvent('testMessage', '', $embeds, true);

        return true;
    }

    /**
     * Create a new webhook destination.
     */
    public function create(array $data): int
    {
        $required = [
            'url' => 'Webhook URL is required.',
            'events' => 'An array of subscribed events is required.'
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $webhook = new Webhook();
        $webhook->setUrl($data['url']);
        $webhook->setEvents((array) $data['events']);
        $webhook->setActive(!empty($data['active']));

        $this->di['em']->persist($webhook);
        $this->di['em']->flush();

        return $webhook->getId();
    }

    /**
     * Update an existing webhook.
     */
    public function update(array $data): bool
    {
        $required = [
            'id' => 'Webhook ID is required.',
            'url' => 'Webhook URL is required.',
            'events' => 'An array of subscribed events is required.'
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        
        /** @var Webhook $webhook */
        $webhook = $this->di['em']->find(Webhook::class, (int) $data['id']);

        if (!$webhook) {
            throw new Exception('Webhook not found');
        }

        $webhook->setUrl($data['url']);
        $webhook->setEvents((array) $data['events']);
        $webhook->setActive(!empty($data['active']));

        $this->di['em']->flush();

        return true;
    }

    /**
     * Delete a webhook.
     */
    public function delete(array $data): bool
    {
        $required = [
            'id' => 'Webhook ID is required.',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        /** @var Webhook $webhook */
        $webhook = $this->di['em']->find(Webhook::class, (int) $data['id']);

        if (!$webhook) {
            throw new Exception('Webhook not found');
        }

        $this->di['em']->remove($webhook);
        $this->di['em']->flush();
        
        return true;
    }

    /**
     * Get a single webhook by ID.
     */
    public function get(array $data): array
    {
        $required = [
            'id' => 'Webhook ID is required.',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        /** @var Webhook $webhook */
        $webhook = $this->di['em']->find(Webhook::class, (int) $data['id']);

        if (!$webhook) {
            throw new Exception('Webhook not found');
        }

        return $webhook->toApiArray();
    }

    /**
     * List all webhooks.
     */
    public function get_list(): array
    {
        $repo = $this->di['em']->getRepository(Webhook::class);
        $list = [];

        foreach ($repo->findAll() as $webhook) {
            $list[] = $webhook->toApiArray();
        }

        return $list;
    }
}
