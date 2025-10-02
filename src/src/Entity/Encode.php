<?php

namespace App\Entity;

use App\Entity\EntityTrait\SlugTrait;
use App\Entity\EntityTrait\UuidTrait;
use App\Repository\EncodeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Gedmo\Mapping\Annotation as Gedmo;


/**
 * @ORM\Entity(repositoryClass=EncodeRepository::class)
 * @ORM\Table(name="`encode`")
 */
class Encode
{
    const MAX_DOWNLOAD_AUTHORIZED = 2;
    use UuidTrait;
    use SlugTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     */
    private $id;
    /**
     * @ORM\Column(type="string",unique=true)
     * @Groups ({"list_of_videos"})
     * @Groups ({"one_video","list_of_videos"})
     */
    private $uuid;
    /**
     * @ORM\Column(type="string", length=255)
     */
    private $externalId;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups ({"one_video","list_of_videos"})
     */
    private $quality;

    /**
     * @ORM\Column(type="bigint")
     * @Groups ({"one_video","list_of_videos"})
     */
    private $size;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups ({"one_video"})
     */
    private $link;


    /**
     * @ORM\Column(type="datetime_immutable")
     * @Groups ({"one_video"})
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Groups ({"one_video","list_of_videos"})
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $name;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups ({"one_video"})
     */
    private $extension;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups ({"one_video","list_of_videos"})
     */
    private $downloadLink;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups ({"one_video","list_of_videos"})
     */
    private $streamLink;
    /**
     * @ORM\OneToMany(targetEntity=Consumption::class, mappedBy="encode")
     */
    private $consumptionsEncode;
    /**
     * @ORM\OneToMany(targetEntity=Consumption::class, mappedBy="video")
     */
    private $consumptionsVideo;
    /**
     * @ORM\ManyToOne(targetEntity=Video::class, inversedBy="encodes")
     *
     */
    private $video;

    /**
     * @OA\Property(type="integer")
     */
    public $carbonConsumption;
    /**
     * @OA\Property(type="integer")
     */
    public $totalCarbonConsumption;
    /**
     * @OA\Property(type="integer")
     */
    public $bandwidth;
    /**
     * @OA\Property(type="integer")
     */
    public $numberOfView;
    /**
     * @OA\Property(type="integer")
     */
    public $gainCarbon;

    /**
     * @var integer
     */
    public function getGainCarbon()
    {
        return $this->gainCarbon;
    }

    /**
     * @param int|null $gainCarbon
     * @return Encode
     */
    public function setGainCarbon($gainCarbon)
    {
        $this->gainCarbon = $gainCarbon;
        return $this;
    }

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups ({"one_video","list_of_videos"})
     */
    private $maxDownloadAuthorized;

    /**
     * @ORM\Column(type="boolean")
     * @Groups ({"one_video","list_of_videos"})
     */
    private $isDeleted;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private $deletedAt;

    public function __construct()
    {
        $this->consumptions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId): self
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getQuality(): ?string
    {
        return $this->quality;
    }

    public function setQuality(string $quality): self
    {
        $this->quality = $quality;

        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(string $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(string $link): self
    {
        $this->link = $link;

        return $this;
    }

    public function getDownloadLink(): ?string
    {
        return $this->downloadLink;
    }

    public function setDownloadLink(?string $downloadLink): self
    {
        $this->downloadLink = $downloadLink;

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }


    /**
     * @return Collection|Consumption[]
     */
    public function getConsumptions(): Collection
    {
        return $this->consumptions;
    }

    public function addConsumption(Consumption $consumption): self
    {
        if (!$this->consumptions->contains($consumption)) {
            $this->consumptions[] = $consumption;
            $consumption->setEncode($this);
        }

        return $this;
    }

    public function removeConsumption(Consumption $consumption): self
    {
        if ($this->consumptions->removeElement($consumption)) {
            // set the owning side to null (unless already changed)
            if ($consumption->getEncode() === $this) {
                $consumption->setEncode(null);
            }
        }

        return $this;
    }

    public function getVideo(): ?Video
    {
        return $this->video;
    }

    public function setVideo(?Video $video): self
    {
        $this->video = $video;

        return $this;
    }


    public function getExtension(): ?string
    {
        return $this->extension;
    }

    public function setExtension(?string $extension): self
    {
        $this->extension = $extension;

        return $this;
    }

    public function getStreamLink(): ?string
    {
        return $this->streamLink;
    }

    public function setStreamLink(?string $streamLink): self
    {
        $this->streamLink = $streamLink;

        return $this;
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

    public function setTotalCarbonConsumption($result = 0)
    {
        $this->totalCarbonConsumption = $result;
        return $this;
    }

    public function getTotalCarbonConsumption()
    {
        return $this->totalCarbonConsumption;
    }


    public function setBandWidth($value = 0)
    {

        $this->bandwidth = $value;
        return $this;
    }

    public function getBandWidth()
    {
        return $this->bandwidth;
    }

    /**
     * @return mixed
     */
    public function getCarbonConsumption()
    {
        return $this->carbonConsumption;
    }

    /**
     * @param mixed $carbonConsumption
     * @return Encode
     */
    public function setCarbonConsumption($carbonConsumption)
    {
        $this->carbonConsumption = $carbonConsumption;
        return $this;
    }

    public function getMaxDownloadAuthorized(): ?int
    {
        return $this->maxDownloadAuthorized;
    }

    public function setMaxDownloadAuthorized(?int $maxDownloadAuthorized): self
    {
        $this->maxDownloadAuthorized = $maxDownloadAuthorized;

        return $this;
    }

    public function getIsDeleted(): ?bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted($isDeleted): self
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

}
