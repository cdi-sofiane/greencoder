<?php

namespace App\Entity;

use App\Repository\UserAccountRoleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;


/**
 * @ORM\Entity(repositoryClass=UserAccountRoleRepository::class)
 * @UniqueEntity(fields={"account", "user"})
 */
class UserAccountRole
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;


    /**
     * @ORM\ManyToOne(targetEntity=Account::class, inversedBy="userAccountRole",cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"me", "list_users"})
     */
    private $account;

    /**
     * @ORM\ManyToOne(targetEntity=Role::class, inversedBy="userAccountRole")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"list_users"})
     *
     */
    private $role;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="userAccountRole")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"me"})
     *
     */
    private $user;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
