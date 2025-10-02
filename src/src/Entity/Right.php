<?php

namespace App\Entity;

use App\Entity\EntityTrait\SlugTrait;
use App\Entity\EntityTrait\UuidTrait;
use App\Repository\RightRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=RightRepository::class)
 * @ORM\Table(name="`right`")
 */
class Right
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
     *@Groups({"me"})
     */
    private $uuid;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"me"})
     */
    private $name;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $position;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"me"})
     */
    private $code;

    /**
     * @ORM\OneToMany(targetEntity=AccountRoleRight::class, mappedBy="rights", orphanRemoval=true)
     *
     */
    private $AccountRoleRight;

    public function __construct()
    {
        $this->AccountRoleRight = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }


    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): self
    {
        $this->position = $position;

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

    /**
     * @return Collection<int, AccountRoleRight>
     */
    public function getAccountRoleRight(): Collection
    {
        return $this->AccountRoleRight;
    }

    public function addAccountRoleRight(AccountRoleRight $accountRoleRight): self
    {
        if (!$this->AccountRoleRight->contains($accountRoleRight)) {
            $this->AccountRoleRight[] = $accountRoleRight;
            $accountRoleRight->setRights($this);
        }

        return $this;
    }

    public function removeAccountRoleRight(AccountRoleRight $accountRoleRight): self
    {
        if ($this->AccountRoleRight->removeElement($accountRoleRight)) {
            // set the owning side to null (unless already changed)
            if ($accountRoleRight->getRights() === $this) {
                $accountRoleRight->setRights(null);
            }
        }

        return $this;
    }
}
