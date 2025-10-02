<?php

namespace App\Entity;

use App\Entity\EntityTrait\UuidTrait;
use App\Repository\UserRepository;
use App\Services\AuthorizationService;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\Table(name="`user`")
 *
 */
class User implements UserInterface
{
    const ACCOUNT_ROLES = [AuthorizationService::AS_USER];
    const ACCOUNT_ADMIN_ROLES = [AuthorizationService::AS_DEV, AuthorizationService::AS_VIDMIZER];
    const USER_ACCOUNT_ADMIN_ROLE = Role::ROLE_ADMIN;
    const USER_ACCOUNT_EDITOR_ROLE = Role::ROLE_EDITOR;
    const USER_ACCOUNT_USER_ROLE = Role::ROLE_READER;
    const USER_ACCOUNT_MEMBERS_ROLE = [Role::ROLE_READER, Role::ROLE_EDITOR];
    use UuidTrait;

    /**
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string",unique=true)
     * @Groups({"list_users","me","list_of_videos","list_of_order","encode","account:list","account:history:encode","account:one"})
     */
    private $uuid;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     * @Groups({"list_users","me","list_of_order","user:create:account","one_video","filters","account:invitation","account:history:encode","account:list","account:one"})
     * @Assert\NotBlank(groups={"registration","update","user:create:account","account:invitation"})
     * @Assert\Email(groups={"registration","update","user:create:account","account:invitation"})
     *
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     * @Groups({"list_users","me","filters","account:list","account:history:encode"})
     * @OA\Property(type="array", @OA\Items(type="string"))
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     * @Assert\NotBlank(groups={"resetpassword","registration","user:invite:edit"})
     * @Assert\NotNull(groups={"user:invite:edit"})
     * @Assert\Regex (
     *     pattern ="/^\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*$/",
     *     match=true,
     *     message="Password should have a lenght of 8 characters ,contain at least 1 Maj, 1 Min,1 Number,1 Special char ,ex=1Aa_Rv60",
     *     groups={"resetpassword","registration","update","user:invite:edit"},
     *     )
     *
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Regex (
     *     pattern="/^[a-zA-ZàáâäãåąčćęèéêëėįìíîïłńòóôöõøùúûüųūÿýżźñçčšžÀÁÂÄÃÅĄĆČĖĘÈÉÊËÌÍÎÏĮŁŃÒÓÔÖÕØÙÚÛÜŲŪŸÝŻŹÑßÇŒÆČŠŽ∂ð .'-]+$/u",
     *     message="No special character accepted",
     *     groups={"registration","update","user:invite:edit"},
     * )
     * @Groups({"list_users","me","list_of_order","one_video","user:invite:edit","account:history:encode","account:one"})
     * @Assert\NotNull(groups={"user:invite:edit"})
     * @Assert\NotBlank(groups={"user:invite:edit"})
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Regex (
     *     pattern="/^[a-zA-ZàáâäãåąčćęèéêëėįìíîïłńòóôöõøùúûüųūÿýżźñçčšžÀÁÂÄÃÅĄĆČĖĘÈÉÊËÌÍÎÏĮŁŃÒÓÔÖÕØÙÚÛÜŲŪŸÝŻŹÑßÇŒÆČŠŽ∂ð .'-]+$/u",
     *     message="No special character accepted",
     *     groups={"registration","update","user:invite:edit"}
     * )
     * @Groups({"list_users","me","list_of_order","one_video","account:history:encode"})
     * @Groups({"list_users","me","list_of_order","one_video","user:invite:edit"})
     * @Assert\NotNull(groups={"user:invite:edit"})
     * @Assert\NotBlank(groups={"user:invite:edit"})
     */
    private $lastName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"list_users","me","user:invite:edit"})
     * @Assert\Length(
     *     allowEmptyString=true,
     *     min = 10, max = 10,
     *     groups={"registration","update","user:invite:edit"}
     *     )
     *
     * @Assert\Regex(pattern="/^(\(0\))?[0-9]+$/", message="phone number ex:1454545452",groups={"registration","update","user:invite:edit"})
     * @Assert\NotNull(groups={"user:invite:edit"})
     * @Assert\NotBlank(groups={"user:invite:edit"})
     */
    private $phone;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Groups({"list_users","me","filters","account:one"})
     * @Assert\Date(groups={"filters"},message="the format is YYYY-MM-DD ex=2000-01-30")
     * @var string A "YYYY-MM-DD" formatted value
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Groups({"list_users","me","account:one"})
     */
    private $updatedAt;

    /**
     * @ORM\OneToMany(targetEntity=UserHistory::class, mappedBy="users")
     */
    private $userHistorys;

    /**
     * @ORM\OneToMany(targetEntity=Contact::class, mappedBy="user")
     */
    private $contacts;

    /**
     * @ORM\OneToMany(targetEntity=Video::class, mappedBy="user")
     *
     */
    private $videos;

    /**
     * @ORM\Column(type="boolean")
     * @Assert\Type(type="boolean", message="missing boolean attribut true or false",groups={"update_admin","filters"})
     * @Assert\NotNull(groups={"update_admin"},message="Choose a valid value ex: true or false.")
     * @Groups({"list_users","filters","account:one"})
     */
    private $isActive;

    /**
     * @ORM\Column(type="string",columnDefinition="ENUM('DARK','LIGHT')",options={"default": "LIGHT"})
     * @Groups({"list_users","me","filters","account:one"})
     * @Assert\Choice({"LIGHT","DARK"},message="Valid fields are dark or light")
     */
    private $theme;

    /**
     * @ORM\Column(type="string",columnDefinition="ENUM( 'FR','EN')",options={"default": "FR"})
     * @Groups({"list_users","me","filters","account:one"})
     * @Assert\Choice({"FR","EN"},message="Valid fields are fr or en")
     */
    private $lang;

    /**
     * @ORM\Column(type="boolean")
     * @Assert\Type(type="boolean", message="missing boolean attribut true or false",groups={"update_admin","filters"})
     * @Assert\NotNull(groups={"update_admin"},message="Choose a valid value ex: true or false.")
     * @Groups({"list_users","filters","account:one"})
     */
    private $isArchive;

    /**
     * @ORM\Column(type="boolean")
     * @Assert\Type(type="boolean", message="missing boolean attribut true or false",groups={"update_admin","filters","account:delete"})
     * @Assert\NotNull(groups={"update_admin"},message="Choose a valid value ex: true or false.")
     * @Groups({"list_users","filters","account:one"})
     */
    private $isDelete;
    /**
     * @ORM\Column(type="boolean",options={"default":0})
     * @Assert\Type(type="boolean", message="missing boolean attribut true or false",groups={"term","filters"})
     * @Assert\NotNull(groups={"term"},message="Choose a valid value ex: true or false.")
     * @Groups({"list_users","me","filters","account:one"})
     */
    private $isConditionAgreed;




    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     * @Groups({"list_users","me","account:one"})
     * @var string A "YYYY-MM-DD" formatted value
     */
    private $lastConnection;

    /**
     * @ORM\OneToMany(targetEntity=UserAccountRole::class, mappedBy="user", orphanRemoval=true)
     * @Groups({"list_users"})
     * @SerializedName("accounts")
     */
    private $userAccountRole;

    /**
     * @ORM\OneToMany(targetEntity=UserFolderRole::class, mappedBy="user")
     *
     */
    private $userFolderRoles;


    /**
     * @Groups({"list_users"});
     * @var Role
     */
    private $accountRole;


    public function __construct()
    {
        $this->userHistorys = new ArrayCollection();
        $this->contacts = new ArrayCollection();
        $this->videos = new ArrayCollection();
        $this->setUuid();
        $this->createdAt = new DateTimeImmutable('now');
        $this->updatedAt = new DateTimeImmutable('now');
        $this->theme = 'LIGHT';
        $this->lang = 'FR';
        $this->userAccountRole = new ArrayCollection();
        $this->userFolderRoles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string)$this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles()
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        //        $roles[] = 'ROLE_USER';

        return  array_unique($roles);
    }


    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }



    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }



    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getCreatedAt()
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

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection|UserHistorys[]
     */
    public function getUserHistorys(): Collection
    {
        return $this->userHistorys;
    }

    public function addUserHistorys($userHistorys): self
    {
        if (!$this->userHistorys->contains($userHistorys)) {
            $this->userHistorys[] = $userHistorys;
            $userHistorys->setUsers($this);
        }

        return $this;
    }

    public function removeUserHistorys($userHistorys): self
    {
        if ($this->userHistorys->removeElement($userHistorys)) {
            // set the owning side to null (unless already changed)
            if ($userHistorys->getUsers() === $this) {
                $userHistorys->setUsers(null);
            }
        }

        return $this;
    }




    /**
     * @return Collection|Contact[]
     */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    public function addContact(Contact $contact): self
    {
        if (!$this->contacts->contains($contact)) {
            $this->contacts[] = $contact;
            $contact->setUser($this);
        }

        return $this;
    }

    public function removeContact(Contact $contact): self
    {
        if ($this->contacts->removeElement($contact)) {
            // set the owning side to null (unless already changed)
            if ($contact->getUser() === $this) {
                $contact->setUser(null);
            }
        }

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

    public function getIsArchive(): ?bool
    {
        return $this->isArchive;
    }

    public function setIsArchive($isArchive): self
    {
        $this->isArchive = $isArchive != "" ? filter_var($isArchive, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : $isArchive;

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
    public function getIsConditionAgreed(): ?bool
    {
        return $this->isConditionAgreed;
    }

    public function setIsConditionAgreed($isConditionAgreed): self
    {
        $this->isConditionAgreed = $isConditionAgreed != "" ? filter_var($isConditionAgreed, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : $isConditionAgreed;

        return $this;
    }



    public function toArray()
    {

        return [
            'uuid' => $this->getUuid(),
            'email' => $this->getEmail(),
            'firstName' => $this->getFirstName(),
            'lastName' => $this->getLastName(),
            'phone' => $this->getPhone(),
            'isActive' => $this->getIsActive(),
            'isDelete' => $this->getIsDelete(),
            'roles' => $this->getRoles(),
        ];
    }

    public function getLastConnection(): ?\DateTimeInterface
    {
        return $this->lastConnection;
    }

    public function setLastConnection(?\DateTimeInterface $lastConnection): self
    {
        $this->lastConnection = $lastConnection;

        return $this;
    }

    /**
     * @return Collection<int, userAccountRole>
     */
    public function getUserAccountRole(): Collection
    {
        return $this->userAccountRole;
    }

    public function addUserAccountRole(UserAccountRole $userAccountRole): self
    {
        if (!$this->userAccountRole->contains($userAccountRole)) {
            $this->userAccountRole[] = $userAccountRole;
            $userAccountRole->setUser($this);
        }

        return $this;
    }

    public function removeUserAccountRole(UserAccountRole $userAccountRole): self
    {
        if ($this->userAccountRole->removeElement($userAccountRole)) {
            // set the owning side to null (unless already changed)
            if ($userAccountRole->getUser() === $this) {
                $userAccountRole->setUser(null);
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
            $userFolderRole->setUser($this);
        }

        return $this;
    }

    public function removeUserFolderRole(UserFolderRole $userFolderRole): self
    {
        if ($this->userFolderRoles->removeElement($userFolderRole)) {
            // set the owning side to null (unless already changed)
            if ($userFolderRole->getUser() === $this) {
                $userFolderRole->setUser(null);
            }
        }

        return $this;
    }


    public function getAccountRole()
    {

        return $this->accountRole;
    }
    public function setAccountRole($role)
    {
        return $this->accountRole = $role;
    }


    /**
     * Get the value of lang
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * Set the value of lang
     *
     * @return  self
     */
    public function setLang($lang)
    {
        $this->lang = $lang;

        return $this;
    }

    /**
     * Get the value of theme
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * Set the value of theme
     *
     * @return  self
     */
    public function setTheme($theme)
    {
        $this->theme = $theme;

        return $this;
    }
}
