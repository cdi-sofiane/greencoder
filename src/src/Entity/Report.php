<?php

namespace App\Entity;

use App\Entity\EntityTrait\FiltersTrait;
use App\Entity\EntityTrait\PaginationTrait;
use App\Entity\EntityTrait\SlugTrait;
use App\Entity\EntityTrait\UuidTrait;
use App\Repository\ReportRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass=ReportRepository::class)
 */
class Report
{
    use UuidTrait;
    use SlugTrait;
    use FiltersTrait;
    use PaginationTrait;
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;
    /**
     * @ORM\Column(type="string",unique=true)
     * @Groups({"report:admin"})
     *
     */
    private $uuid;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"report:admin"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"report:admin"})
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=255,nullable=true)
     */
    private $link;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"report:admin"})
     */
    private $pdf;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"report:admin"})
     */
    private $csv;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"report:admin"})
     */
    private $totalVideos;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @Groups({"report:admin"})
     */
    private $totalCarbon;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @Groups({"report:admin"})
     */
    private $optimisation;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Groups({"report:admin"})
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Groups({"report:admin"})
     */
    private $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="reports")
     * @ORM\JoinColumn(nullable=false)
     *
     */
    private $user;

    /**
     *
     * @Groups({"report:create"})
     */
    private $totalOriginalSize;
    /**
     *
     * @Groups({"report:create"})
     */
    private $totalEncodedSize;

    /**
     * @ORM\Column(type="boolean")
     * @Assert\Type(type="boolean", message="missing boolean attribut true or false",groups={"report:list"})
     * @Assert\NotNull(groups={"report:list"},message="Choose a valid value ex: true or false.")
     * @Groups ({"report:list","report:create","report:admin"})
     */
    private $isDeleted;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private $deletedAt;

    /**
     * @ORM\ManyToOne(targetEntity=Account::class, inversedBy="reports")
     */
    private $account;

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


    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

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

    public function getPdf(): ?string
    {
        return $this->pdf;
    }

    public function setPdf(?string $pdf): self
    {
        $this->pdf = $pdf;

        return $this;
    }

    public function getCsv(): ?string
    {
        return $this->csv;
    }

    public function setCsv(?string $csv): self
    {
        $this->csv = $csv;

        return $this;
    }

    public function getTotalVideos(): ?int
    {
        return $this->totalVideos;
    }

    public function setTotalVideos(?int $totalVideos): self
    {
        $this->totalVideos = $totalVideos;

        return $this;
    }

    public function getTotalCarbon(): ?float
    {
        return $this->totalCarbon;
    }

    public function setTotalCarbon(?float $totalCarbon): self
    {
        $this->totalCarbon = $totalCarbon;

        return $this;
    }

    public function getOptimisation(): ?float
    {
        return $this->optimisation;
    }

    public function setOptimisation(?float $optimisation): self
    {
        $this->optimisation = $optimisation;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

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

    public function getTotalOriginalSize()
    {
        return $this->totalOriginalSize;
    }

    public function getTotalEncodedSize()
    {
        return $this->totalEncodedSize;
    }
    public function setTotalOriginalSize($totalOriginalSize)
    {
        $this->totalOriginalSize = $totalOriginalSize;
        return $this;
    }

    public function setTotalEncodedSize($totalEncodedSize)
    {
        $this->totalEncodedSize = $totalEncodedSize;
        return $this;
    }

    public function getIsDeleted(): ?bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted($isDeleted): self
    {
        $this->isDeleted = $isDeleted != '' ? filter_var($isDeleted, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : $isDeleted;

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

    public function toArray()
    {

        switch ($this->getSortBy()) {
            case 'date':
                $this->setSortBy('createdAt');
                break;
            case 'video':
                $this->setSortBy('totalVideos');
                break;
            case 'optimisation':
                $this->setSortBy('optimisation');
                break;
            case 'economie':
                $this->setSortBy('totalCarbon');
                break;

            default:
                # code...
                break;
        }
        return [
            'search' => $this->getSearch(),
            'sortBy' =>  $this->getSortBy(),
            'order' => $this->getOrder(),
            'page' => $this->getPage(),
            'startAt' => $this->getStartAt(),
            'endAt' => $this->getEndAt(),
            'limit' => $this->getLimit(),
            'isDeleted' => $this->getIsDeleted()
        ];
    }

    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function setAccount(?Account $account): self
    {
        $this->account = $account;

        return $this;
    }
}
