<?php

namespace App\Entity;

use App\Entity\EntityTrait\FiltersTrait;
use App\Entity\EntityTrait\UuidTrait;
use App\Repository\AccountRepository;
use App\Services\AuthorizationService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use App\Validation\Constraint\Account as MyAccount;
use DateTimeImmutable;
use Symfony\Component\Serializer\Annotation\SerializedName;


/**
 * @ORM\Entity(repositoryClass=AccountRepository::class)
 */
class Account
{
    const USAGE_PRO = "Professional";
    const USAGE_INDIVIDUEL = "Individual";


    use UuidTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"account:history:encode"})
     */
    private $id;
    /**
     * @ORM\Column(type="string",unique=true)
     * @Groups({"folder:read","invoice:list","list_users","me","list_of_videos","encode","one_video","list_of_order","account:list","account:pilote:edit","account:admin:edit","account:one","account:history:encode"})
     */
    private $uuid;
    /**
     *
     * @ORM\Column(type="string", length=180, unique=true)
     * @Groups({"folder:read","list_users","list_of_order","user:create:account","one_video","account:registration","account:list","account:one","list_of_videos","one_video","account:history:encode"})
     * @MyAccount\AccountEmail(groups={"account:registration"},message="An Account with this email alreaty exist")
     * @Assert\NotBlank(groups={"account:registration","update","user:create:account"},message="should be a valid email address ex: valid@email.com")
     * @Assert\Email(groups={"account:registration","update","user:create:account"},message="should be a valid email address ex: valid@email.com")
     */
    private $email;

    /**
     * @Groups({"invoice:list", "account:list","account:pilote:edit","encode","account:admin:edit","account:one","list_of_videos","one_video"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"list_users", "invoice:list", "account:registration","account:list","account:pilote:edit","account:one","list_of_videos","one_video","account:admin:edit"})
     *
     */
    private $company;

    /**
     * @Groups({"account:list","account:pilote:edit","account:admin:edit","account:one"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $address;

    /**
     * @Groups({"account:list","account:pilote:edit","account:admin:edit","account:one"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $postalCode;

    /**
     * @Groups({"account:list","account:pilote:edit","account:admin:edit","account:one"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $country;

    /**
     * @ORM\Column(type="string", columnDefinition="ENUM('Individual', 'Professional')")
     * @Groups({"list_users","account:list","account:pilote:edit","account:admin:edit","account:registration","account:one","me"})
     * @Assert\NotBlank (groups={"account:registration","update"})
     * @MyAccount\AccountUsageType(groups={},message="Usage professional should have company name")
     * @Assert\Choice({"Individual", "Professional"},groups={"account:registration","update","account:pilote:edit","account:admin:edit"},message="must be Individual or Professional")
     */
    private $usages;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"list_users","account:list","account:pilote:edit","account:admin:edit","account:one"})
     */
    private $tva;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"list_users","account:list","account:pilote:edit","account:admin:edit","account:one"})
     */
    private $siret;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $apiKey;

    /**
     * @ORM\Column(type="bigint", nullable=true)
     * @Groups({"list_users","list_of_order","account:list","me","account:one","encode"})
     */
    private $creditEncode;

    /**
     * @ORM\Column(type="bigint", nullable=true)
     * @Groups({"list_users","list_of_order","account:list","me","account:one","encode"})
     */
    private $creditStorage;

    /**
     * @ORM\Column(type="boolean")
     * @Assert\Type(type="boolean", message="missing boolean attribut true or false",groups={"update_admin","account:admin:edit"})
     * @Assert\NotNull(groups={"update_admin","account:admin:edit"},message="Choose a valid value ex: true or false.")
     * @Groups({"folder:read","list_users","list_of_order","account:list","account:one","update_admin","account:admin:isMultiAccount","me"})
     * @MyAccount\isMultiAccount(groups={"update_admin"},message="multiAccounts only for Usage Professional")
     *
     */
    private $isMultiAccount = false;



    /**
     * @ORM\OneToMany(targetEntity=Order::class, mappedBy="account")
     * @Groups({"account:history:encode"})
     */
    private $orders;

    /**
     * @ORM\OneToMany(targetEntity=Video::class, mappedBy="account")
     * @Groups({"account:history:encode"})
     */
    private $videos;

    /**
     * @ORM\OneToMany(targetEntity=Tags::class, mappedBy="account")
     *
     */
    private $tags;
    /**
     * @Groups({"account:list","account:one","me"})
     * @OA\Property(type="string")
     */
    public $owner;

    /**
     * @Groups({"account:list","account:one"})
     * @OA\Property(type="array",@OA\Items())
     *
     */
    public $members = [];
    /**
     * @Groups({"account:list"})
     * @OA\Property(type="array",@OA\Items())
     */
    public $order_uuid = [];

    /**
     * @ORM\OneToMany(targetEntity=Report::class, mappedBy="account")
     */
    private $reports;

    /**
     * @ORM\OneToOne(targetEntity=ReportConfig::class, cascade={"persist", "remove"})
     */
    private $reportConfig;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({"account:list","account:one","me"})
     */
    private $isActive;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     * @Groups({"account:list","account:one","account:history:encode"})
     */
    private $createdAt;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @MyAccount\maxInvitations(groups={"account:admin:edit"},message="maxInvitations should be greater than account members")
     * @Groups({"account:list","account:one","account:history:encode","account:admin:edit","me","account:pilote:edit"})
     */
    private $maxInvitations;
    /**
     * @ORM\OneToMany(targetEntity=Invoice::class, mappedBy="account", cascade={"persist", "remove"})
     *
     */
    private $invoices;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     * @Groups({"account:list","account:one","account:history:encode"})
     * @var string A "YYYY-MM-DD" formatted value
     */
    private $lastConnection;

    /**
     * @ORM\OneToMany(targetEntity=UserAccountRole::class, mappedBy="account", orphanRemoval=true)
     *
     */
    private $userAccountRole;

    /**
     * @ORM\OneToMany(targetEntity=AccountRoleRight::class, mappedBy="account", orphanRemoval=true)
     *
     *
     *
     */
    private $accountRoleRight;

    /**
     * @ORM\OneToMany(targetEntity=Folder::class, mappedBy="account", orphanRemoval=true)
     */
    private $folders;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"list_users","me","account:one"})
     */
    private $logo;
    /**
     * @Groups({"account:one"})
     */
    public $accountRoles;

    public function __construct()
    {
        $this->orders = new ArrayCollection();
        $this->videos = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->reports = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable('now');
        $this->userAccountRole = new ArrayCollection();
        $this->accountRoleRight = new ArrayCollection();
        $this->folders = new ArrayCollection();
        $this->folders = new ArrayCollection();
    }

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

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): self
    {
        $this->company = $company;

        return $this;
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

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): self
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getUsages(): ?string
    {
        return $this->usages;
    }

    public function setUsages(string $usages): self
    {
        $this->usages = $usages;

        return $this;
    }

    public function getTva(): ?string
    {
        return $this->tva;
    }

    public function setTva(?string $tva): self
    {
        $this->tva = $tva;

        return $this;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(?string $siret): self
    {
        $this->siret = $siret;

        return $this;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(?string $apiKey): self
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    public function getCreditEncode()
    {
        return $this->creditEncode;
    }

    public function setCreditEncode($creditEncode): self
    {
        $this->creditEncode = $creditEncode;

        return $this;
    }

    public function getCreditStorage(): ?string
    {
        return $this->creditStorage;
    }

    public function setCreditStorage($creditStorage): self
    {
        $this->creditStorage = $creditStorage;

        return $this;
    }

    public function getIsMultiAccount()
    {
        return $this->isMultiAccount;
    }

    public function setIsMultiAccount($isMultiAccount)
    {

        $this->isMultiAccount = $isMultiAccount != '' ? filter_var($isMultiAccount, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : $isMultiAccount;

        return $this;
    }





    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): self
    {
        if (!$this->orders->contains($order)) {
            $this->orders[] = $order;
            $order->setAccount($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): self
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getAccount() === $this) {
                $order->setAccount(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Video>
     */
    public function getVideos(): Collection
    {
        return $this->videos;
    }

    public function addVideo(Video $video): self
    {
        if (!$this->videos->contains($video)) {
            $this->videos[] = $video;
            $video->setAccount($this);
        }

        return $this;
    }

    public function removeVideo(Video $video): self
    {
        if ($this->videos->removeElement($video)) {
            // set the owning side to null (unless already changed)
            if ($video->getAccount() === $this) {
                $video->setAccount(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Tags>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tags $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags[] = $tag;
            $tag->setAccount($this);
        }

        return $this;
    }

    public function removeTag(Tags $tag): self
    {
        if ($this->tags->removeElement($tag)) {
            // set the owning side to null (unless already changed)
            if ($tag->getAccount() === $this) {
                $tag->setAccount(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Report>
     */
    public function getReports(): Collection
    {
        return $this->reports;
    }

    public function addReport(Report $report): self
    {
        if (!$this->reports->contains($report)) {
            $this->reports[] = $report;
            $report->setAccount($this);
        }

        return $this;
    }

    public function removeReport(Report $report): self
    {
        if ($this->reports->removeElement($report)) {
            // set the owning side to null (unless already changed)
            if ($report->getAccount() === $this) {
                $report->setAccount(null);
            }
        }

        return $this;
    }

    public function getReportConfig(): ?ReportConfig
    {
        return $this->reportConfig;
    }

    public function setReportConfig(?ReportConfig $reportConfig): self
    {
        $this->reportConfig = $reportConfig;

        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive($isActive): self
    {
        $this->isActive = $isActive != '' ? filter_var($isActive, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : $isActive;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public  function setDisplayName(User $currentUser = null)
    {
        if (
            $this->usages == self::USAGE_PRO &&
            !empty($this->company)
        ) {
            $this->name = $this->company;
            return;
        }

        if (
            $currentUser != null &&
            (!empty($currentUser->getFirstName()) || !empty($currentUser->getLastName()))
        ) {
            $this->name = implode(
                ' ',
                array_filter([
                    $currentUser->getFirstName(),
                    $currentUser->getLastName()
                ])
            );
            return;
        }

        $this->name = $this->email;
    }

    public function getLastConnection(): ?\DateTimeImmutable
    {
        return $this->lastConnection;
    }

    public function setLastConnection(\DateTimeImmutable $lastConnection): self
    {
        $this->lastConnection = $lastConnection;

        return $this;
    }

    public function getMaxInvitations()
    {
        return $this->maxInvitations;
    }

    public function setMaxInvitations($maxInvitations): self
    {
        $this->maxInvitations = $maxInvitations;

        return $this;
    }
    /**
     * @return Collection<int, UserAccountRole>
     */
    public function getUserAccountRole(): Collection
    {
        return $this->userAccountRole;
    }

    public function addUserAccountRole(UserAccountRole $userAccountRole): self
    {
        if (!$this->userAccountRole->contains($userAccountRole)) {
            $this->userAccountRole[] = $userAccountRole;
            $userAccountRole->setAccount($this);
        }

        return $this;
    }

    public function removeUserAccountRole(UserAccountRole $userAccountRole): self
    {
        if ($this->userAccountRole->removeElement($userAccountRole)) {
            // set the owning side to null (unless already changed)
            if ($userAccountRole->getAccount() === $this) {
                $userAccountRole->setAccount(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AccountRoleRight>
     */
    public function getAccountRoleRight(): Collection
    {
        return $this->accountRoleRight;
    }

    public function addAccountRoleRight(AccountRoleRight $accountRoleRight): self
    {
        if (!$this->accountRoleRight->contains($accountRoleRight)) {
            $this->accountRoleRight[] = $accountRoleRight;
            $accountRoleRight->setAccount($this);
        }

        return $this;
    }

    public function removeAccountRoleRight(AccountRoleRight $accountRoleRight): self
    {
        if ($this->accountRoleRight->removeElement($accountRoleRight)) {
            // set the owning side to null (unless already changed)
            if ($accountRoleRight->getAccount() === $this) {
                $accountRoleRight->setAccount(null);
            }
        }

        return $this;
    }

    public function getMembers()
    {
        $members =  $this->userAccountRole->map(function ($userAccountRole) {

            return $userAccountRole->getUser();
        });

        return $this->members = $members;
    }

    /**
     * @return Collection<int, Folder>
     */
    public function getFolders(): Collection
    {
        return $this->folders;
    }
    public function getOwner(): User
    {
        $members = $this->getMembers();
        $userAccountRoleOwner =  $this->userAccountRole->filter(function ($userAccountRole) {
            return $userAccountRole->getRole()->getCode() == Role::ROLE_ADMIN;
        });
        $owner = $userAccountRoleOwner->first()->getUser();

        return  $this->owner = $owner;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo)
    {
        $this->logo = $logo;
    }

    public function getAccountRoles()
    {
        $roles = [];
        $rolesRights = $this->accountRoleRight->map(function ($accountRoleRight) use ($roles) {
            /**
             * @var \App\Entity\AccountRoleRight $accountRoleRight
             */
            // dd(array_keys($roles, $accountRoleRight->getRole()->getCode()));

            $roles[$accountRoleRight->getRole()->getCode()] = $accountRoleRight->getRights()->getCode();



            return $roles;


            // return array_merge($roles, $roles);
        });
        foreach ($rolesRights as $permissionItem) {
            $role = key($permissionItem);
            $permission = current($permissionItem);

            if (!isset($organizedPermissions[$role])) {
                $organizedPermissions[$role] = [];
            }

            $organizedPermissions[$role][] = $permission;
        }
        return $this->accountRole = $organizedPermissions;
    }
}
