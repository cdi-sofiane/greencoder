<?php

namespace App\Entity;

use App\Entity\EntityTrait\UuidTrait;
use App\Repository\InvoiceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;


/**
 * @ORM\Entity(repositoryClass=InvoiceRepository::class)
 */
class Invoice
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
     * @Groups({"invoice:list"})
     */
    private $uuid;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"invoice:list"})
     */
    private $invoiceNumber;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"invoice:list"})
     */
    private $downloadLink;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Groups({"invoice:list"})
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $updatedAt;

    /**
     * @ORM\OneToOne(targetEntity=Payment::class, mappedBy="invoices", cascade={"persist", "remove"})
     */
    private $payments;

    /**
     * @ORM\ManyToOne(targetEntity=Account::class, inversedBy="invoices")
     * @Groups({"invoice:list"})
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

    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    public function setInvoiceNumber(string $invoiceNumber): self
    {
        $this->invoiceNumber = $invoiceNumber;

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

    public function getPayments(): ?Payment
    {
        return $this->payments;
    }

    public function setPayments(Payment $payments): self
    {
        // set the owning side of the relation if necessary
        if ($payments->getInvoices() !== $this) {
            $payments->setInvoices($this);
        }

        $this->payments = $payments;

        return $this;
    }

    public function getDownloadLink(): ?string
    {
        return $this->downloadLink;
    }

    public function setDownloadLink(string $downloadLink): self
    {
        $this->downloadLink = $downloadLink;

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
