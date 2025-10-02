<?php

namespace App\Entity;

use App\Entity\EntityTrait\UuidTrait;
use App\Repository\ForfaitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Ramsey\Uuid\Uuid;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Validation\Constraint as MyConstraint;
use App\Validation\Constraint\Forfait as MyForfait;

/**
 * @ORM\Entity(repositoryClass=ForfaitRepository::class)
 * @ORM\Table(name="`forfait`")
 */
class Forfait
{

    use UuidTrait;

    const NATURE_ENCODAGE = "encodage";
    const NATURE_STOCKAGE = "stockage";
    const NATURE_HYBRID = "hybride";
    const TYPE_GRATUIT = "Gratuit";
    const TYPE_ONESHOT = "OneShot";
    const TYPE_CREDIT = "Credit";
    const TYPE_ABONNEMENT = "Abonnement";
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"account:history:encode"})
     */
    private $id;
    /**
     * @ORM\Column(type="string",unique=true)
     * @Groups({"list_all","update","delete","list_of_order","account:history:encode"})
     */
    private $uuid;
    /**
     * @ORM\Column(type="string", length=255)
     * @Groups  ({"list_all","update","filters","create","list_of_order","account:history:encode"})
     * @Assert\NotBlank (groups={"list_all","update","create"})
     * @Assert\NotNull  (groups={"create"})
     * @MyConstraint\IsUniqueName(groups={"create","update"})
     */
    private $name;
    /**
     * @ORM\Column(type="string",nullable=true, columnDefinition="ENUM('encodage', 'stockage','hybride')")
     * @Groups ({"list_all","filters","list_of_order","account:history:encode"})
     * @Assert\Choice({"encodage","stockage","hybride",null},message="Valid fields are 'encodage', 'stockage','hybride'",groups={"filters","create","update","list_all"})
     * @Assert\NotBlank (groups={"create","update"})
     * @Assert\NotNull (groups={"create","update"})
     * @MyForfait\NatureForfait(groups={"create","update"})
     */
    private $nature;

    /**
     * @ORM\Column(type="float", length=255)
     * @Groups ({"list_all"})
     * @Assert\Regex (
     *     pattern="/^\d{0,8}[.]?\d{1,2}$/",
     *     match=true,
     *     message="ex 5.8",
     *     groups={"create","update"}
     * )
     * @MyForfait\PriceForfait(groups={"create","update"})
     */
    private $price;

    /**
     * @ORM\Column(type="bigint", length=11,nullable=true)
     * @Groups ({"list_all"})
     * @Assert\Regex (
     *     pattern="/^\d{0,8}[.]?\d{1,2}$/",
     *     match=true,
     *     message="ex 5.8",
     *     groups={"create","update"}
     * )
     * @MyForfait\DurationForfait(groups={"create","update"})
     */
    private $duration;

    /**
     * @ORM\Column(type="bigint",nullable=true)
     * @Groups ({"list_all"})
     * @Assert\Regex (
     *     pattern="/^\d{0,9}[.]?\d{1,2}$/",
     *     match=true,
     *     groups={"create","update"}
     * )
     * @MyForfait\SizeStorageForfait(groups={"create","update"})
     */

    private $sizeStorage;
    /**
     * @Assert\DateTime
     * @Assert\Date(groups={"filters"},message="the format is YYYY-MM-DD ex=2000-01-30")
     * @var string A "YYYY-MM-DD" formatted value
     */
    public $startAt;
    /**
     * @Assert\DateTime
     * @Assert\Date(groups={"filters"},message="the format is YYYY-MM-DD ex=2000-01-30")
     * @var string A "YYYY-MM-DD" formatted value
     */
    public $endAt;
    /**
     * @ORM\Column(type="datetime_immutable")
     * @Groups ({"list_all","update"})
     * @Assert\Date(groups={"filters"},message="the format is YYYY-MM-DD ex=2000-01-30")
     * @var string A "YYYY-MM-DD" formatted value
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Groups ({"list_all","update"})
     * @Assert\Date(groups={"filters"},message="the format is YYYY-MM-DD ex=2000-01-30")
     * @var string A "YYYY-MM-DD" formatted value
     */
    private $updatedAt;

    /**
     *
     * @ORM\OneToMany(targetEntity=Order::class, mappedBy="forfait")
     */
    private $orders;

    /**
     * @ORM\Column(type="string", columnDefinition="ENUM( 'Gratuit','OneShot', 'Credit', 'Abonnement')")
     * @Assert\Choice({"Gratuit","OneShot","Credit","Abonnement",null},message="Valid fields  'Gratuit','OneShot', 'Credit', 'Abonnement'",groups={"filters","create","update","list_all"})
     * @Groups ({"list_all","update","filters","list_of_order","account:history:encode"})
     * @MyForfait\TypeForfait(groups={"create","update"})
     */
    private $type;


    /**
     * @ORM\Column(type="boolean")
     * @Groups ({"list_all","update"})
     * @Assert\NotNull (groups={"update","create"},message="missing boolean attribut true or false")
     * @MyForfait\EntrepriseForfait(groups={"create","update"})
     */
    private $isEntreprise;
    /**
     * @ORM\Column(type="boolean")
     * @Groups ({"list_all","update"})
     * @Assert\Type(type="boolean", message="missing boolean attribut true or false",groups={"update"})
     * @Assert\NotNull (groups={"update","create"},message="missing boolean attribut true or false")
     * @MyForfait\AutomaticForfait(groups={"update"})
     */
    private $isAutomatic;
    /**
     * @ORM\Column(type="boolean")
     * @Groups ({"list_all","update"})
     * @Assert\Type(type="boolean", message="missing boolean attribut true or false",groups={"create"})
     * @Assert\NotNull (groups={"update","create"},message="missing boolean attribut true or false")
     * @MyForfait\ActiveForfait(groups={"create","update"})
     */
    private $isActive;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="forfaitCreator", cascade={"persist", "remove"})
     */
    private $createdBy;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="forfaitUpdator", cascade={"persist", "remove"})
     */
    private $updatedBy;

    /**
     * @ORM\Column(type="boolean")
     * @Groups ({"list_all","update","delete"})
     */
    private $isDelete;

    /**
     * @ORM\OneToMany(targetEntity=Order::class, mappedBy="nextForfait")
     */
    private $nextOrders;

    public function __construct()
    {
        $this->nextOrders = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName($name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function setPrice($price): self
    {
        $this->price = $price;

        return $this;
    }


    public function getDuration()
    {
        return $this->duration;
    }

    public function setDuration($duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt($createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt($updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getOrders(): ?Order
    {
        return $this->orders;
    }

    public function setOrders(Order $orders): self
    {
        // set the owning side of the relation if necessary
        if ($orders->getForfait() !== $this) {
            $orders->setForfait($this);
        }

        $this->orders = $orders;

        return $this;
    }

    public function getIsAutomatic(): ?bool
    {
        return $this->isAutomatic;
    }

    public function setIsAutomatic($isAutomatic): self
    {
        $this->isAutomatic = $isAutomatic != "" ? filter_var($isAutomatic, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : $isAutomatic;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive($isActive): self
    {
        $this->isActive = $isActive != "" ? filter_var($isActive, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : $isActive;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getNature()
    {
        return $this->nature;
    }

    /**
     * @param mixed $nature
     * @return Forfait
     */
    public function setNature($nature)
    {
        $this->nature = $nature;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSizeStorage()
    {
        return $this->sizeStorage;
    }

    /**
     * @param mixed $sizeStorage
     * @return Forfait
     */
    public function setSizeStorage($sizeStorage)
    {
        $this->sizeStorage = $sizeStorage;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIsEntreprise()
    {
        return $this->isEntreprise;
    }

    /**
     * @param mixed $isEntreprise
     * @return Forfait
     */
    public function setIsEntreprise($isEntreprise)
    {

        $this->isEntreprise = $isEntreprise != "" ? filter_var($isEntreprise, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : $isEntreprise;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $updatedBy): self
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStartAt()
    {
        return $this->startAt;
    }

    /**
     * @param mixed $startAt
     * @return Forfait
     */
    public function setStartAt($startAt)
    {
        $this->startAt = $startAt;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEndAt()
    {
        return $this->endAt;
    }

    /**
     * @param mixed $endAt
     * @return Forfait
     */
    public function setEndAt($endAt)
    {
        $this->endAt = $endAt;
        return $this;
    }

    public function getIsDelete(): ?bool
    {
        return $this->isDelete;
    }

    public function setIsDelete($isDelete): self
    {
        $this->isDelete = $isDelete != "" ? filter_var($isDelete, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : $isDelete;
        return $this;
    }

    /**
     * @return Collection<int, Order>
     */
    public function getNextOrders(): Collection
    {
        return $this->nextOrders;
    }

    public function addNextOrder(Order $nextOrder): self
    {
        if (!$this->nextOrders->contains($nextOrder)) {
            $this->nextOrders[] = $nextOrder;
            $nextOrder->setNextForfait($this);
        }

        return $this;
    }

    public function removeNextOrder(Order $nextOrder): self
    {
        if ($this->nextOrders->removeElement($nextOrder)) {
            // set the owning side to null (unless already changed)
            if ($nextOrder->getNextForfait() === $this) {
                $nextOrder->setNextForfait(null);
            }
        }

        return $this;
    }
}
