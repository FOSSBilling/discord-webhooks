<?php

declare(strict_types=1);
/**
 * Copyright 2025- FOSSBilling
 * SPDX-License-Identifier: Apache-2.0.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace Box\Mod\Discord\Controller;

class Admin implements \FOSSBilling\InjectionAwareInterface
{
    protected ?\Pimple\Container $di = null;

    public function setDi(\Pimple\Container $di): void
    {
        $this->di = $di;
    }

    public function getDi(): ?\Pimple\Container
    {
        return $this->di;
    }

    public function fetchNavigation()
    {
        return [
            'subpages' => [
                [
                    'location' => 'extensions',
                    'label' => __trans('Discord webhooks'),
                    'index' => 300,
                    'uri' => $this->di['url']->adminLink('discord'),
                    'class' => '',
                ],
            ],
        ];
    }

    public function register(\Box_App &$app)
    {
        $app->get('/discord', 'get_index', [], static::class);
        $app->get('/discord/webhook', 'get_webhook', [], static::class);
        $app->get('/discord/webhook/:id', 'get_webhook_edit', [], static::class);
    }

    public function get_index(\Box_App $app)
    {
        $this->di['is_admin_logged'];

        return $app->render('mod_discord_index');
    }

    public function get_webhook(\Box_App $app)
    {
        $this->di['is_admin_logged'];

        return $app->render('mod_discord_webhook_manage');
    }

    public function get_webhook_edit(\Box_App $app, $id)
    {
        $this->di['is_admin_logged'];

        $api = $this->di['api_admin'];
        $webhook = $api->discord_get(['id' => $id]);

        return $app->render('mod_discord_webhook_manage', ['webhook' => $webhook]);
    }
}