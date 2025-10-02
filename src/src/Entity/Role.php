<?php

namespace App\Entity;

use App\Entity\EntityTrait\SlugTrait;
use App\Entity\EntityTrait\UuidTrait;
use App\Repository\RoleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=RoleRepository::class)
 */
class Role
{
    const ROLE_ADMIN = 'admin';
    const ROLE_EDITOR = 'editor';
    const ROLE_READER = 'reader';
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
     * @Groups({"me","list_users"})
     */
    private $uuid;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"me","list_users"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"me","list_users"})
     */
    private $code;

    /**
     * @ORM\OneToMany(targetEntity=UserAccountRole::class, mappedBy="role", orphanRemoval=true)
     */
    private $UserAccountRole;

    /**
     * @ORM\OneToMany(targetEntity=AccountRoleRight::class, mappedBy="role", orphanRemoval=true)
     */
    private $AccountRoleRight;

    /**
     * @ORM\OneToMany(targetEntity=UserFolderRole::class, mappedBy="role")
     */
    private $userFolderRoles;

    public function __construct()
    {
        $this->UserAccountRole = new ArrayCollection();
        $this->AccountRoleRight = new ArrayCollection();
        $this->userFolderRoles = new ArrayCollection();
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
     * @return Collection<int, UserAccountRole>
     */
    public function getUserAccountRole(): Collection
    {
        return $this->UserAccountRole;
    }

    public function addUserAccountRole(UserAccountRole $userAccountRole): self
    {
        if (!$this->UserAccountRole->contains($userAccountRole)) {
            $this->UserAccountRole[] = $userAccountRole;
            $userAccountRole->setRole($this);
        }

        return $this;
    }

    public function removeUserAccountRole(UserAccountRole $userAccountRole): self
    {
        if ($this->UserAccountRole->removeElement($userAccountRole)) {
            // set the owning side to null (unless already changed)
            if ($userAccountRole->getRole() === $this) {
                $userAccountRole->setRole(null);
            }
        }

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
            $accountRoleRight->setRole($this);
        }

        return $this;
    }

    public function removeAccountRoleRight(AccountRoleRight $accountRoleRight): self
    {
        if ($this->AccountRoleRight->removeElement($accountRoleRight)) {
            // set the owning side to null (unless already changed)
            if ($accountRoleRight->getRole() === $this) {
                $accountRoleRight->setRole(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UserFolderRole>
     */
    public function getUserFolderRoles(): Collection
    {
        return $this->userFolderRoles;
    }

    public function addUserFolderRole(UserFolderRole $userFolderRole): self
    {
        if (!$this->userFolderRoles->contains($userFolderRole)) {
            $this->userFolderRoles[] = $userFolderRole;
            $userFolderRole->setRole($this);
        }

        return $this;
    }

    public function removeUserFolderRole(UserFolderRole $userFolderRole): self
    {
        if ($this->userFolderRoles->removeElement($userFolderRole)) {
            // set the owning side to null (unless already changed)
            if ($userFolderRole->getRole() === $this) {
                $userFolderRole->setRole(null);
            }
        }

        return $this;
    }
}
