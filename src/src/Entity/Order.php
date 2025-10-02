<?php

namespace App\Entity;

use App\Entity\EntityTrait\UuidTrait;
use App\Repository\OrderRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

/**
 * @ORM\Entity(repositoryClass=OrderRepository::class)
 * @ORM\Table(name="`order`")
 */
class Order
{
    const UPGRADE = "UPGRADE";
    const DOWNGRADE = "DOWNGRADE";
    use UuidTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *  @Groups({"account:history:encode"})
     */
    private $id;
    /**
     * @ORM\Column(type="string",unique=true)
     * @Groups({"list_of_order","order:renewable","account:list","account:history:encode"})
     *
     */
    private $uuid;
    /**
     * @ORM\Column(type="datetime_immutable")
     * @Groups({"list_of_order","account:history:encode"})
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     * @Groups({"list_of_order"})
     */
    private $expireAt;

    /**
     * @ORM\Column(type="bigint", nullable=true)
     * @Groups({"list_of_order"})
     */
    private $seconds;

    /**
     * @ORM\Column(type="bigint", nullable=true)
     * @Groups({"list_of_order"})
     */
    private $bits;

    /**
     * @ORM\Column(type="boolean", options={"default": 0})
     * @Assert\Type(type="boolean", message="missing boolean attribut true or false",groups={"list_of_order","consumed"})
     * @Assert\NotNull(groups={"list_of_order","consumed"},message="Choose a valid value ex: true or false.")
     * @Groups({"list_of_order","consumed"})
     */
    private $isConsumed;

    /**
     * @Groups({"list_of_order","consumed"})
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private $nextUpdate;

    /**
     * @ORM\Column(type="bigint", nullable=true)
     */
    private $originalBits;

    /**
     * @ORM\Column(type="bigint", nullable=true)
     */
    private $originalSeconds;

    /**
     * @ORM\Column(type="boolean",options={"default": 1})
     * @Assert\Type(type="boolean", message="missing boolean attribut true or false",groups={"list_of_order","consumed","order:renewable"})
     * @Assert\NotNull(groups={"list_of_order","consumed","order:renewable"},message="Choose a valid value ex: true or false.")
     * @Groups({"list_of_order","consumed","order:renewable"})
     */
    private $isRenewable;

    /**
     * @ORM\Column(type="string", columnDefinition="ENUM('UPGRADE', 'DOWNGRADE')", nullable=true)
     * @Assert\Choice({"UPGRADE", "DOWNGRADE"},message="must be  UPGRADE  or DOWNGRADE ")
     *  @Groups({"list_of_order","consumed"})
     */
    private $subscriptionPlan;

    /**
     * @ORM\ManyToOne(targetEntity=Forfait::class, inversedBy="nextOrders")
     */
    private $nextForfait;

    /**
     * @ORM\Column(type="string", nullable=true, length=255)
     */
    private $reference;

    /**
     * @ORM\OneToOne(targetEntity=Payment::class, inversedBy="order", cascade={"persist", "remove"})
     *
     */
    private $payment;

    /**
     * @ORM\ManyToOne (targetEntity=Forfait::class, inversedBy="orders")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"list_of_order","account:history:encode"})
     */
    private $forfait;

    /**
     * @ORM\ManyToOne(targetEntity=Account::class, inversedBy="Orders")
     * @ORM\JoinColumn(nullable=true)
     * @Groups({"list_of_order","consumed"})
     */
    private $account;

    public function __construct()
    {
        $this->setUuid();
        $this->createdAt = new \DateTimeImmutable('now');
        $this->updatedAt = new \DateTimeImmutable('now');
    }

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



    public function getPayments(): ?Payment
    {
        return $this->payment;
    }

    public function setPayments(?Payment $payment): self
    {
        $this->payment = $payment;

        return $this;
    }

    public function getForfait(): ?Forfait
    {
        return $this->forfait;
    }

    public function setForfait(Forfait $forfait): self
    {
        $this->forfait = $forfait;

        return $this;
    }

    public function getExpireAt(): ?\DateTimeImmutable
    {
        return $this->expireAt;
    }

    public function setExpireAt(?\DateTimeImmutable $expireAt): self
    {
        $this->expireAt = $expireAt;

        return $this;
    }

    public function getSeconds(): ?int
    {
        return $this->seconds;
    }

    public function setSeconds(?int $seconds): self
    {
        $this->seconds = $seconds;

        return $this;
    }

    public function getBits(): ?int
    {
        return $this->bits;
    }

    public function setBits(?int $bits): self
    {
        $this->bits = $bits;

        return $this;
    }

    public function getIsConsumed(): ?bool
    {
        return $this->isConsumed;
    }

    public function setIsConsumed($isConsumed)
    {
        $this->isConsumed = $isConsumed != "" ? filter_var($isConsumed, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : $isConsumed;
        return $this;
    }

    public function getNextUpdate(): ?\DateTimeImmutable
    {
        return $this->nextUpdate;
    }

    public function setNextUpdate(?\DateTimeImmutable $nextUpdate): self
    {
        $this->nextUpdate = $nextUpdate;

        return $this;
    }

    public function getOriginalBits(): ?int
    {
        return $this->originalBits;
    }

    public function setOriginalBits(?string $originalBits): self
    {
        $this->originalBits = $originalBits;

        return $this;
    }

    public function getOriginalSeconds(): ?string
    {
        return $this->originalSeconds;
    }

    public function setOriginalSeconds(?string $originalSeconds): self
    {
        $this->originalSeconds = $originalSeconds;

        return $this;
    }

    public function getIsRenewable()
    {
        return $this->isRenewable;
    }

    public function setIsRenewable($isRenewable)
    {
        $this->isRenewable = $isRenewable != "" ? filter_var($isRenewable, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : $isRenewable;
        return $this;
    }

    public function getSubscriptionPlan(): ?string
    {
        return $this->subscriptionPlan;
    }

    public function setSubscriptionPlan(?string $subscriptionPlan): self
    {
        $this->subscriptionPlan = $subscriptionPlan;

        return $this;
    }

    public function getNextForfait(): ?Forfait
    {
        return $this->nextForfait;
    }

    public function setNextForfait(?Forfait $nextForfait): self
    {
        $this->nextForfait = $nextForfait;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;

        return $this;
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
