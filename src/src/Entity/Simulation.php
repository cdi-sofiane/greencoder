<?php

namespace App\Entity;

use App\Entity\EntityTrait\SlugTrait;
use App\Entity\EntityTrait\UuidTrait;
use App\Repository\SimulationRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
/**
 * @ORM\Entity(repositoryClass=SimulationRepository::class)
 * @ORM\Table(name="`simulation`")
 */
class Simulation
{
    use UuidTrait;
    use SlugTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string",unique=true)
     * @Groups ({"estimate"})
     */
    private $uuid;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups ({"estimate"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups ({"estimate"})
     */
    private $extension;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups ({"estimate"})
     */
    private $videoQuality;

    /**
     * @ORM\Column(type="integer")
     * @Groups ({"estimate"})
     */
    private $size;

    /**
     * @ORM\Column(type="integer")
     * @Groups ({"estimate"})
     */
    private $duration;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Groups ({"estimate"})
     */
    private $createdAt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups ({"estimate"})
     */
    private $originalSize;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups ({"estimate"})
     */
    private $estimateSize;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups ({"estimate"})
     */
    private $gainPercentage;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $link;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups ({"estimate"})
     */
    private $fps;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups ({"estimate"})
     */
    private $frameCount;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isDeleted;



    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getExtension(): ?string
    {
        return $this->extension;
    }

    public function setExtension(string $extension): self
    {
        $this->extension = $extension;

        return $this;
    }

    public function getVideoQuality(): ?string
    {
        return $this->videoQuality;
    }

    public function setVideoQuality(?string $videoQuality): self
    {
        $this->videoQuality = $videoQuality;

        return $this;
    }

    public function getSize(): ?string
    {
        return $this->size;
    }

    public function setSize(?string $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getDuration(): ?string
    {
        return $this->duration;
    }

    public function setDuration(?string $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getOriginalSize(): ?string
    {
        return $this->originalSize;
    }

    public function setOriginalSize(?string $originalSize): self
    {
        $this->originalSize = $originalSize;

        return $this;
    }

    public function getEstimateSize(): ?string
    {
        return $this->estimateSize;
    }

    public function setEstimateSize(string $estimateSize): self
    {
        $this->estimateSize = $estimateSize;

        return $this;
    }

    public function getGainPercentage(): ?string
    {
        return $this->gainPercentage;
    }

    public function setGainPercentage(?string $gainPercentage): self
    {
        $this->gainPercentage = $gainPercentage;

        return $this;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): self
    {
        $this->link = $link;

        return $this;
    }

    public function getFps(): ?int
    {
        return $this->fps;
    }

    public function setFps(?int $fps): self
    {
        $this->fps = $fps;

        return $this;
    }

    public function getFrameCount(): ?int
    {
        return $this->frameCount;
    }

    public function setFrameCount(?int $frameCount): self
    {
        $this->frameCount = $frameCount;

        return $this;
    }

    public function getIsDeleted(): ?bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted): self
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }


}
