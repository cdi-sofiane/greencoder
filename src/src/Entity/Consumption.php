<?php

namespace App\Entity;

use App\Entity\EntityTrait\UuidTrait;
use App\Repository\ConsumptionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ConsumptionRepository::class)
 * @ORM\Table(name="consumption")
 */
class Consumption
{
    use UuidTrait;


    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;
    /**
     * @ORM\Column(type="string",unique=true)
     */
    private $uuid;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $rate;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity=Encode::class, inversedBy="consumptions")
     * @ORM\JoinColumn(nullable=true)
     */
    private $encode;


    /**
     * @ORM\ManyToOne(targetEntity=Video::class, inversedBy="consumptions")
     * @ORM\JoinColumn(nullable=true)
     */
    private $video;

    /**
     * @ORM\Column(type="string", columnDefinition="ENUM('download', 'stream')")
     */
    private $launched;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getEncode(): ?Encode
    {
        return $this->encode;
    }

    public function setEncode(?Encode $encode): self
    {
        $this->encode = $encode;

        return $this;
    }

    public function getRate(): ?float
    {
        return $this->rate;
    }

    public function setRate(?float $rate): self
    {
        $this->rate = $rate;

        return $this;
    }

    public function getvideo(): ?Video
    {
        return $this->video;
    }

    public function setvideo(?Video $video): self
    {
        $this->video = $video;

        return $this;
    }

    public function getLaunched(): ?string
    {
        return $this->launched;
    }

    public function setLaunched(string $launched): self
    {
        $this->launched = $launched;

        return $this;
    }
}
