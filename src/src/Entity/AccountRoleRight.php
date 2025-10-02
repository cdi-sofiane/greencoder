<?php

namespace App\Entity;

use App\Repository\AccountRoleRightRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=AccountRoleRightRepository::class)
 * @UniqueEntity(fields={"account","role","rights"})
 */
class AccountRoleRight
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Account::class, inversedBy="accountRoleRight")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"me"})
     */
    private $account;

    /**
     * @ORM\ManyToOne(targetEntity=Role::class, inversedBy="accountRoleRight")
     * @ORM\JoinColumn(nullable=false)
     */
    private $role;

    /**
     * @ORM\ManyToOne(targetEntity=Right::class, inversedBy="accountRoleRight")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"me"})
     */
    private $rights;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function setRole(?Role $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getRights(): ?Right
    {
        return $this->rights;
    }

    public function setRights(?Right $rights): self
    {
        $this->rights = $rights;

        return $this;
    }
}
