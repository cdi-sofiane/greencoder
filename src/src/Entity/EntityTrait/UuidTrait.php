<?php

namespace App\Entity\EntityTrait;

use Ramsey\Uuid\Uuid;

trait UuidTrait
{
    /**
     * @ORM\Column(type="string",unique=true)
     */
    private $uuid;

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid = ""): self
    {
        $this->uuid = Uuid::uuid4(random_bytes(16));
        return $this;
    }
}
