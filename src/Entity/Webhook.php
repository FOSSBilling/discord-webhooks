<?php

declare(strict_types=1);
/**
 * Copyright 2025- FOSSBilling
 * SPDX-License-Identifier: Apache-2.0.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace Box\Mod\Discord\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOSSBilling\Interfaces\TimestampInterface;

#[ORM\Entity]
#[ORM\Table(name: "discord_webhooks")]
#[ORM\HasLifecycleCallbacks]
class Webhook implements TimestampInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 255)]
    private string $url;

    #[ORM\Column(type: "json")]
    private array $events = [];

    #[\Doctrine\ORM\Mapping\Column(type: 'boolean')]
    protected bool $active = true;

    #[ORM\Column(type: "datetime")]
    private \DateTime $createdAt;

    #[ORM\Column(type: "datetime")]
    private \DateTime $updatedAt;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTime();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getEvents(): array
    {
        return $this->events;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function setEvents(array $events): void
    {
        $this->events = $events;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function setCreatedAt(\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function toApiArray(): array
    {
        $data = [
            'id'          => $this->getId(),
            'active'      => $this->isActive(),
            'url'         => $this->getUrl(),
            'events'      => $this->getEvents(),
            'created_at'  => $this->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at'  => $this->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];

        return $data;
    }
}
