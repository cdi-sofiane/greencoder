<?php

namespace App\Entity;

use App\Entity\EntityTrait\UuidTrait;
use App\Repository\TagsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

/**
 * @ORM\Entity(repositoryClass=TagsRepository::class)
 */
class Tags
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
     * @Groups({"tags:list","list_of_videos","one_video"})
     */
    private $uuid;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups ({"list_of_videos","one_video"})
     */
    private $tagName;

    /**
     * @ORM\Column(type="boolean", options={"default": 0})
     */
    private $isFolder;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $folderOrder;

    /**
     * @ORM\ManyToMany(targetEntity=Video::class, inversedBy="tags")
     */
    private $videos;

    /**
     * @ORM\ManyToOne(targetEntity=Account::class, inversedBy="tags")
     */
    private $account;

    public function __construct()
    {
        $this->videos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTagName(): ?string
    {
        return $this->tagName;
    }

    public function setTagName(string $tagName): self
    {
        $this->tagName = $tagName;

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
        }

        return $this;
    }

    public function removeVideo(Video $video): self
    {
        $this->videos->removeElement($video);

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

    /**
     * Get the value of isFolder
     */
    public function getIsFolder()
    {
        return $this->isFolder;
    }

    /**
     * Set the value of isFolder
     *
     * @return  self
     */
    public function setIsFolder($isFolder)
    {
        $this->isFolder = $isFolder;

        return $this;
    }

    /**
     * Get the value of folderOrder
     */
    public function getFolderOrder()
    {
        return $this->folderOrder;
    }

    /**
     * Set the value of folderOrder
     *
     * @return  self
     */
    public function setFolderOrder($folderOrder)
    {
        $this->folderOrder = $folderOrder;

        return $this;
    }
}
