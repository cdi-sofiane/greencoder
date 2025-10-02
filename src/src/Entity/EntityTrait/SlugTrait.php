<?php

namespace App\Entity\EntityTrait;


trait SlugTrait
{
    /**
     * @Gedmo\Slug(fields={"name"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $slugName;


    public function getSlugName(): ?string
    {
        return $this->slugName;
    }

    public function setSlugName(?string $slugName): self
    {
        $this->slugName = $slugName;

        return $this;
    }
}