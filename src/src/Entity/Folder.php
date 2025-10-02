<?php

namespace App\Entity;

use App\Entity\EntityTrait\UuidTrait;
use App\Repository\FolderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use App\Validation\Constraint\Folder as MyFolder;
use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Entity\EntityTrait\FiltersTrait;
use App\Repository\AccountRepository;
use App\Services\AuthorizationService;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

/**
 * @ORM\Entity(repositoryClass=FolderRepository::class)
 * @Gedmo\Tree(type="nested")
 */
class Folder
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
     * @Groups({"folder:read"})
     * @Groups({"encode","list_of_videos","one_video", "trash"})
     */

    private $uuid;
    /**
     * @ORM\Column(type="string")
     * @Groups({"folder:create","folder:edit","folder:read"})
     * @MyFolder\Folder(groups={"folder:create","folder:edit"})
     * @Groups({"encode","list_of_videos","one_video", "trash"})
     */
    private $name;

    /**
     * @Gedmo\TreeLeft
     * @ORM\Column(type="integer")
     */
    private $lft;

    /**
     * @Gedmo\TreeRight
     * @ORM\Column(type="integer")
     */
    private $rgt;

    /**
     * @Gedmo\TreeLevel
     * @ORM\Column(type="integer")
     * @Groups({"folder:read"})
     * @Groups({"encode","list_of_videos","one_video", "trash"})
     */
    private $level;

    /**
     * @Gedmo\TreeRoot
     * @ORM\ManyToOne(targetEntity="Folder")
     * @ORM\JoinColumn(name="tree_root", referencedColumnName="id", onDelete="CASCADE")
     */
    private $root;

    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="Folder", inversedBy="subfolders")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     *
     */
    private $parentFolder;

    /**
     * @ORM\OneToMany(targetEntity="Folder", mappedBy="parentFolder")
     * @ORM\OrderBy({"lft" = "ASC"})
     * @Groups({"folder:read"})
     * @SerializedName("subFolders")
     */
    private $subfolders;

    /**
     * @ORM\ManyToOne(targetEntity=Account::class, inversedBy="folder")
     *
     */
    private $account;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"folder:read"})
     */
    private $createdBy;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     * @Groups({"folder:read"})
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     * @Groups({"folder:read", "trash"})
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="boolean")
     * @Assert\NotNull (groups={"folder:update","folder:create"},message="missing boolean attribut true or false")
     * @Groups({"folder:read"})
     */
    private $isArchived = false;

    /**
     * @ORM\Column(type="boolean", options={"default": 0})
     * @Assert\NotNull (groups={"folder:update","folder:create"},message="missing boolean attribut true or false")
     * @Groups({"folder:read"})
     */
    private $isInTrash;
    /**
     * @ORM\OneToMany(targetEntity=Video::class, mappedBy="folder")
     */
    private $videos;

    /**
     * @ORM\OneToMany(targetEntity=UserFolderRole::class, mappedBy="folder")
     */
    private $userFolderRoles;
    /**
     * @var User[]
     * @Groups({"folder:read"})
     */
    private $members = [];


    /**
     * @Groups({"one_video"})
     * @SerializedName("folderRole")
     */
    private $memberRole;

    public function __construct()
    {
        $this->subfolders = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable('now');
        $this->updatedAt = new DateTimeImmutable('now');
        $this->videos = new ArrayCollection();
        $this->userFolderRoles = new ArrayCollection();
    }

    // Getter and Setter for $id

    public function getId()
    {
        return $this->id;
    }

    // Getter and Setter for $name

    public function getName(): string
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    // Getter and Setter for $lft

    public function getLft()
    {
        return $this->lft;
    }

    public function setLft($lft)
    {
        $this->lft = $lft;
    }

    // Getter and Setter for $rgt

    public function getRgt()
    {
        return $this->rgt;
    }

    public function setRgt($rgt)
    {
        $this->rgt = $rgt;
    }

    // Getter and Setter for $level

    public function getLevel()
    {
        return $this->level;
    }

    public function setLevel($level)
    {
        $this->level = $level;
    }

    // Getter and Setter for $root

    public function getRoot()
    {
        return $this->root;
    }

    public function setRoot($root)
    {
        $this->root = $root;
    }

    // Getter and Setter for $parentFolder

    public function getParentFolder()
    {
        return $this->parentFolder;
    }

    public function setParentFolder($parentFolder)
    {
        $this->parentFolder = $parentFolder;
    }

    // Getter for $subfolders

    public function getSubfolders()
    {
        return $this->subfolders;
    }


    public function addSubfolder(Folder $subfolder)
    {
        if ($this->subfolders->count() < 3) {
            $this->subfolders[] = $subfolder;
            $subfolder->setParentFolder($this);
        } else {
            throw new \Exception("Le nombre maximum de sous-dossiers est atteint.");
        }
    }

    public function removeSubfolder(Folder $subfolder)
    {
        $this->subfolders->removeElement($subfolder);
        $subfolder->setParentFolder(null);
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

    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?string $createdBy): self
    {
        $this->createdBy = $createdBy;

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getIsArchived(): ?bool
    {
        return $this->isArchived;
    }

    public function setIsArchived(bool $isArchived): self
    {
        $this->isArchived = $isArchived != "" ? filter_var($isArchived, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : $isArchived;
        return $this;
    }


    public function getIsInTrash(): ?bool
    {
        return $this->isInTrash;
    }

    public function setIsInTrash($isTrashed): self
    {
        $this->isInTrash = $isTrashed;
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
            $video->setFolder($this);
        }

        return $this;
    }

    public function removeVideo(Video $video): self
    {
        if ($this->videos->removeElement($video)) {
            // set the owning side to null (unless already changed)
            if ($video->getFolder() === $this) {
                $video->setFolder(null);
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
            $userFolderRole->setFolder($this);
        }

        return $this;
    }

    public function removeUserFolderRole(UserFolderRole $userFolderRole): self
    {
        if ($this->userFolderRoles->removeElement($userFolderRole)) {
            // set the owning side to null (unless already changed)
            if ($userFolderRole->getFolder() === $this) {
                $userFolderRole->setFolder(null);
            }
        }

        return $this;
    }

    public function getMembers()
    {
        $members = [];
        $folderMembers =  $this->userFolderRoles->map(function ($userFolderRoles) {

            return $userFolderRoles;
        });
        $i = 0;

        if (!$folderMembers->count() > 0) {
            return $this->members = $members;
        }
        /**
         * @var UserFolderRole $member
         */
        foreach ($folderMembers as  $member) {
            $members[$i]['uuid'] = $member->getUser()->getUuid();
            $members[$i]['firstName'] = $member->getUser()->getFirstName();
            $members[$i]['lastName'] = $member->getUser()->getLastName();
            $members[$i]['email'] = $member->getUser()->getEmail();
            $members[$i]['folderRole'] = $member->getRole()->getCode();
            $i++;
        }
        return $this->members = $members;
    }

    public function getMemberRole(): ?string
    {
        return $this->memberRole;
    }

    public function setMemberRole(?string $memberRole): self
    {
        $this->memberRole = $memberRole;
        return $this;
    }
}
