<?php

namespace App\Entity;

use App\Entity\EntityTrait\SlugTrait;
use App\Entity\EntityTrait\UuidTrait;
use App\Form\Dto\DtoTags;
use App\Repository\VideoRepository;
use App\Services\Consumption\ConsumptionManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping\Index;


/**
 * @ORM\Entity(repositoryClass=VideoRepository::class)
 * @ORM\Table(name="`video`")
 */
class Video
{
    const MAX_DOWNLOAD_AUTHORIZED = 2;
    const ENCODING_ANALYSING = 'ANALYSING';
    const ENCODING_RETRY = 'RETRY';
    const ENCODING_ENCODING = 'ENCODING';
    const ENCODING_ENCODED = 'ENCODED';
    const ENCODING_ERROR = 'ERROR';
    const ENCODING_PENDING = 'PENDING';
    const INTERVAL_REMOVE_DAY = '2';
    use UuidTrait;
    use SlugTrait;


    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *  @Groups({"account:history:encode"})
     */
    private $id;
    /**
     * @ORM\Column(type="string",unique=true)
     * @Groups ({"encode","list_of_videos","one_video","uploading","encode:retry","encode:progress","account:history:encode", "trash"})
     *
     *
     */
    private $uuid;
    /**
     * @ORM\Column(type="string", length=255)
     * @Groups ({"encode","list_of_videos","one_video","uploading","account:history:encode", "trash"})
     */
    private $name;

    /**
     * @ORM\Column(type="integer")
     * @Groups ({"encode","one_video"})
     */
    private $duration;
    /**
     * @ORM\Column(type="integer",options={"default": 0})
     * @Groups ({"encode","list_of_videos","one_video","encode:retry","encode:progress"})
     */
    private $progress;
    /**
     * @ORM\Column(type="boolean")
     * @Assert\Type(type="boolean", message="missing boolean attribut true or false",groups={"encode"})
     * @Assert\NotNull(groups={"encode"},message="Choose a valid value ex: true or false.")
     * @Groups ({"encode","one_video","list_of_videos"})
     */
    private $isMultiEncoded;

    /**
     * @ORM\Column(type="boolean")
     * @Assert\Type(type="boolean", message="true or false",groups={"encode"})
     * @Assert\NotNull(groups={"encode"},message="Choose a valid value ex: true or false.")
     * @Groups ({"encode","one_video","list_of_videos"})
     */
    private $isStored;

    /**
     * @ORM\Column(type="string", length=255,nullable=true)
     * @Groups ({"encode"})
     * @Assert\Regex (
     *     pattern="/(^\d[0-9]+x([0-9]+))$/",
     *     groups={"encode"}
     * )
     */
    private $qualityNeed;

    /**
     * @ORM\Column(type="bigint")
     * @Groups ({"encode","one_video"})
     */
    private $size;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups ({"encode","one_video", "trash"})
     */
    private $videoQuality;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Groups ({"encode","list_of_videos","one_video","account:history:encode"})
     * @Assert\Date(groups={"filters"},message="the format is YYYY-MM-DD ex=2000-01-30")
     * @var string A "YYYY-MM-DD" formatted value
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Groups ({"encode","one_video", "trash"})
     */
    private $updatedAt;
    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     * @Groups ({"encode","list_of_videos","one_video"})
     * @Assert\Date(groups={"filters"},message="the format is YYYY-MM-DD ex=2000-01-30")
     * @var string A "YYYY-MM-DD" formatted value
     */
    private $deletedAt;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="videos",fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(nullable=false)
     * @Groups ({"encode","list_of_videos","one_video"})
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $link;


    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $jobId;

    /**
     * @ORM\Column(type="string",nullable=true,columnDefinition="ENUM('DEFAULT', 'WEBINAR', 'FIXED_SHOT', 'HIGH_RESOLUTION','GREEN++','ANIMATION','STILL_IMAGE','TWITCH','GREEN+')",options={"default":"ANIMATION"})
     * @Groups({"encode","list_of_videos","one_video","filters"})
     * @Assert\NotBlank (groups={"encode"})
     * @Assert\Choice({"DEFAULT", "WEBINAR", "FIXED_SHOT", "HIGH_RESOLUTION","GREEN++","ANIMATION","STILL_IMAGE","TWITCH","GREEN+"},message="Valid fields are 'DEFAULT', 'WEBINAR', 'FIXED_SHOT', 'HIGH_RESOLUTION','GREEN++','ANIMATION','STILL_IMAGE','TWITCH','GREEN+'",groups={"encode"})
     *
     */
    private $mediaType;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups ({"encode","list_of_videos","one_video"})
     */
    private $extension;


    /**
     * @ORM\OneToMany(targetEntity=Consumption::class, mappedBy="Video")
     *
     */
    private $consumptions;

    /**
     * @ORM\OneToMany(targetEntity=Encode::class, mappedBy="video")
     * @Groups ({"one_video","list_of_videos"})
     */
    private $encodes;

    /**
     * @ORM\Column(type="boolean")
     *
     */
    private $isArchived;

    /**
     * @ORM\Column(type="boolean")
     * @Assert\Type(type="boolean", message="missing boolean attribut true or false",groups={"one_video","list_of_videos"})
     * @Assert\NotNull(groups={"one_video","list_of_videos"},message="Choose a valid value ex: true or false.")
     * @Groups ({"encode","one_video","list_of_videos"})
     */
    private $isDeleted;

    /**
     * @ORM\Column(type="boolean", options={"default": 0})
     * @Assert\Type(type="boolean", message="missing boolean attribut true or false",groups={"one_video","list_of_videos"})
     * @Assert\NotNull(groups={"one_video","list_of_videos"},message="Choose a valid value ex: true or false.")
     * @Groups ({"encode","one_video","list_of_videos"})
     */
    private $isInTrash;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups ({"encode","list_of_videos","one_video"})
     */
    private $downloadLink;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups ({"encode","list_of_videos","one_video"})
     */
    private $streamLink;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups ({"encode","list_of_videos","one_video"})
     */
    private $thumbnail;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups ({"encode","one_video"})
     */
    private $thumbnailHd;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups ({"encode","one_video","list_of_videos"})
     */
    private $thumbnailLd;

    /**
     * @OA\Property(type="integer")
     *
     */
    public $carbonConsumption;
    /**
     * @OA\Property(type="integer")
     *
     */
    public $carbon = 5;

    /**
     * @OA\Property(type="integer")
     */
    public $bandwidth;
    /**
     * @ORM\Column(type="float", length=255)
     * @OA\Property(type="integer")
     * @Groups ({"one_video","list_of_videos"})
     */
    private $gainOptimisation;
    /**
     * @OA\Property(type="integer")
     */
    private $gainCarbonConsumption;
    /**
     * @OA\Property(type="integer")
     */
    public $totalCarbonConsumption;
    /**
     * @OA\Property(type="integer")
     */
    public $totalBandWidth;
    /**
     * @OA\Property(type="integer")
     */
    public $numberOfView;

    /**
     * @Groups ({"one_video"})
     */
    public $savedCarbon;

    public $gainCarbonEncode;


    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups ({"one_video","list_of_videos"})
     */
    private $maxDownloadAuthorized;

    /**
     * @ORM\Column(type="boolean",options={"default": 0})
     * @Groups ({"one_video","uploading","list_of_videos","encode:progress"})
     */
    private $isUploadComplete;

    /**
     * @ORM\Column(type="string",nullable=true,columnDefinition="ENUM( 'PENDING','ANALYSING','RETRY', 'ENCODING', 'ENCODED','ERROR')",options={"default": "PENDING"})
     * @Groups({"encode","list_of_videos","one_video","filters","encode:retry","encode:progress","account:history:encode"})
     * @Assert\Choice({"PENDING","ANALYSING", "RETRY", "ENCODING", "ENCODED","ERROR"},message="Valid fields are PENDING, ANALYSING, RETRY, ENCODING , ENCODED ,ERROR")
     *
     */
    private $encodingState;

    /**
     * @ORM\ManyToMany(targetEntity=Tags::class, mappedBy="videos")
     * @Assert\NotBlank (groups={"tags:add"})
     */
    private $tags;
    /**
     * permet d'afficher seulment la list des tagname
     *
     * @OA\Property (type="array",@OA\Items(type="string")),
     * @Groups({"encode","list_of_videos","one_video"})
     */
    private $tagsName;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotNull (groups={"video:edit"})
     * @Groups({"encode","list_of_videos","one_video","filters","video:edit"})
     */
    private $encodedBy;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotNull (groups={"video:edit"})
     * @Groups({"encode","list_of_videos","one_video","filters","video:edit","account:history:encode", "trash"})
     */
    private $title;

    /**
     * @ORM\Column(type="json", nullable=true)
     * @Assert\Json (groups={"video:edit:json"})
     * @OA\Property(type="array",@OA\Items(type="object",@OA\Property(property="src"),@OA\Property(property="extension")) )
     * @Groups({"encode","list_of_videos","one_video"})
     */
    private $playlist = [];

    /**
     * @ORM\ManyToOne(targetEntity=Account::class, inversedBy="videos")
     * @Groups({"encode","list_of_videos","one_video"})
     */
    private $account;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"encode","list_of_videos","one_video"})
     */
    private $recommendedResolution;

    /**
     * @ORM\ManyToOne(targetEntity=Folder::class, inversedBy="videos")
     * @Groups({"encode","list_of_videos","one_video"})
     */
    private $folder;

    /**
     * @return mixed
     */
    public function getSavedCarbon()
    {
        return $this->savedCarbon;
    }

    /**
     * @param mixed $savedCarbon
     * @return Video
     */
    public function setSavedCarbon($savedCarbon)
    {
        $this->savedCarbon = $savedCarbon;
        return $this;
    }

    public function __construct()
    {
        $this->consumptions = new ArrayCollection();
        $this->encodes = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->createdAt =  new \DateTimeImmutable('now');
        $this->updatedAt = new \DateTimeImmutable('now');
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

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getIsMultiEncoded(): ?bool
    {
        return $this->isMultiEncoded;
    }

    public function setIsMultiEncoded($isMultiEncoded): self
    {
        $this->isMultiEncoded = $isMultiEncoded != '' ? filter_var($isMultiEncoded, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : $isMultiEncoded;

        return $this;
    }

    public function getIsStored(): ?bool
    {
        return $this->isStored;
    }

    public function setIsStored($isStored): self
    {

        $this->isStored = $isStored != '' ? filter_var($isStored, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : $isStored;
        return $this;
    }

    public function getQualityNeed(): ?string
    {
        return $this->qualityNeed;
    }

    public function setQualityNeed($qualityNeed): self
    {
        $this->qualityNeed = $qualityNeed;

        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getVideoQuality(): ?string
    {
        return $this->videoQuality;
    }

    public function setVideoQuality(string $videoQuality): self
    {
        $this->videoQuality = $videoQuality;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): self
    {
        $this->link = $link;

        return $this;
    }

    public function getDownloadLink(): ?string
    {
        return $this->downloadLink;
    }

    public function setDownloadLink(?string $downloadLink): self
    {
        $this->downloadLink = $downloadLink;

        return $this;
    }

    public function getJobId(): ?string
    {
        return $this->jobId;
    }

    public function setJobId(?string $jobId): self
    {
        $this->jobId = $jobId;

        return $this;
    }

    public function getMediaType(): ?string
    {
        return $this->mediaType;
    }

    public function setMediaType(?string $mediaType): self
    {
        $this->mediaType = $mediaType;

        return $this;
    }

    public function getExtension(): ?string
    {
        return $this->extension;
    }

    public function setExtension(?string $extension): self
    {
        $this->extension = $extension;

        return $this;
    }

    public function getStreamLink(): ?string
    {
        return $this->streamLink;
    }

    public function setStreamLink(?string $streamLink): self
    {
        $this->streamLink = $streamLink;

        return $this;
    }

    /**
     * @return Collection|Consumption[]
     */
    public function getConsuptions(): Collection
    {
        return $this->consumptions;
    }

    public function addConsuption(Consumption $consumption): self
    {
        if (!$this->consumptions->contains($consumption)) {
            $this->consumptions[] = $consumption;
            $consumption->setVideo($this);
        }

        return $this;
    }

    public function removeConsuption(Consumption $consumption): self
    {
        if ($this->consumptions->removeElement($consumption)) {
            // set the owning side to null (unless already changed)
            if ($consumption->getVideo() === $this) {
                $consumption->setVideo(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Encode[]
     */
    public function getEncodes(): Collection
    {
        return $this->encodes;
    }

    public function addEncode(Encode $encodes): self
    {
        if (!$this->encodes->contains($encodes)) {
            $this->encodes[] = $encodes;
            $encodes->setVideo($this);
        }

        return $this;
    }

    public function removeEncode(Encode $encodes): self
    {
        if ($this->encodes->removeElement($encodes)) {
            // set the owning side to null (unless already changed)
            if ($encodes->getVideo() === $this) {
                $encodes->setVideo(null);
            }
        }

        return $this;
    }

    public function getIsArchived(): ?bool
    {
        return $this->isArchived;
    }

    public function setIsArchived(bool $isArchived): self
    {
        $this->isArchived = $isArchived;

        return $this;
    }

    public function getIsDeleted(): ?bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted($isDeleted): self
    {
        $this->isDeleted = $isDeleted != '' ? filter_var($isDeleted, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : $isDeleted;
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

    public function getThumbnail(): ?string
    {
        return $this->thumbnail;
    }

    public function setThumbnail(?string $thumbnail): self
    {
        $this->thumbnail = $thumbnail;

        return $this;
    }

    public function getThumbnailHd(): ?string
    {
        return $this->thumbnailHd;
    }

    public function setThumbnailHd(?string $thumbnailHd): self
    {
        $this->thumbnailHd = $thumbnailHd;

        return $this;
    }


    public function setBandwidth($value)
    {
        $this->bandwidth = $value;
        return $this;
    }

    public function getBandwidth()
    {
        return $this->bandwidth;
    }


    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return Video
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function setTotalCarbonConsumption($result)
    {

        $this->totalCarbonConsumption = $result;
        return $this;
    }

    public function getTotalCarbonConsumption()
    {
        return $this->totalCarbonConsumption;
    }


    /**
     * @return mixed
     */
    public function getGainOptimisation()
    {
        return $this->gainOptimisation;
    }

    /**
     * @param mixed $gainOptimisation
     * @return Video
     */
    public function setGainOptimisation($gainOptimisation)
    {
        $this->gainOptimisation = $gainOptimisation;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTotalBandWidth()
    {
        return $this->totalBandWidth;
    }

    /**
     * @param mixed $totalBandWidth
     * @return Video
     */
    public function setTotalBandWidth($totalBandWidth)
    {
        $this->totalBandWidth = $totalBandWidth;
        return $this;
    }

    /**
     * @return float
     */
    public function getCarbonConsumption(): float
    {
        return $this->carbonConsumption;
    }

    /**
     * @param float $carbonConsumption
     * @return Video
     */
    public function setCarbonConsumption(float $carbonConsumption): Video
    {
        $this->carbonConsumption = $carbonConsumption;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getGainCarbonConsumption()
    {
        return $this->gainCarbonConsumption;
    }

    /**
     * @param mixed $gainCarbonConsumption
     * @return Video
     */
    public function setGainCarbonConsumption($gainCarbonConsumption)
    {
        $this->gainCarbonConsumption = round($gainCarbonConsumption, 2, PHP_ROUND_HALF_EVEN);
        return $this;
    }

    public function getProgress(): ?int
    {
        return $this->progress;
    }

    public function setProgress(int $progress): self
    {
        $this->progress = $progress;

        return $this;
    }

    public function getMaxDownloadAuthorized(): ?int
    {
        return $this->maxDownloadAuthorized;
    }

    public function setMaxDownloadAuthorized(?int $maxDownloadAuthorized): self
    {
        $this->maxDownloadAuthorized = $maxDownloadAuthorized;

        return $this;
    }

    public function getThumbnailLd(): ?string
    {
        return $this->thumbnailLd;
    }

    public function setThumbnailLd(?string $thumbnailLd): self
    {
        $this->thumbnailLd = $thumbnailLd;

        return $this;
    }

    public function getIsUploadComplete(): ?bool
    {
        return $this->isUploadComplete;
    }

    public function setIsUploadComplete(bool $isUploadComplete): self
    {
        $this->isUploadComplete = $isUploadComplete;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getGainCarbonEncode()
    {
        return $this->gainCarbonEncode;
    }

    /**
     * @param mixed $gainCarbonEncode
     * @return Video
     */
    public function setGainCarbonEncode($gainCarbonEncode)
    {
        $this->gainCarbonEncode = $gainCarbonEncode;
        return $this;
    }

    public function getEncodingState(): ?string
    {
        return $this->encodingState;
    }

    public function setEncodingState(string $encodingState): self
    {
        $this->encodingState = $encodingState;

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
            $tag->addVideo($this);
        }

        return $this;
    }

    public function removeTag(Tags $tag): self
    {
        if ($this->tags->removeElement($tag)) {
            $tag->removeVideo($this);
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTagsName()
    {
        foreach ($this->getTags() as $tag) {

            $this->tagsName[] = $tag->getTagName();
        }
        return $this->tagsName;
    }

    /**
     * @param mixed $tagsName
     * @return Video
     */
    public function setTagsName()
    {
        foreach ($this->getTags() as $tag) {

            $this->tagsName[] = $tag->getTagName();
        }
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function getPlaylist(): ?array
    {
        return $this->playlist;
    }

    public function setPlaylist(?array $playlist): self
    {
        $this->playlist = $playlist;

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
    public function getEncodedBy(): ?string
    {
        return $this->encodedBy;
    }

    public function setEncodedBy($encodedBy): self
    {
        $this->encodedBy = $encodedBy;

        return $this;
    }

    public function getRecommendedResolution(): ?string
    {
        return $this->recommendedResolution;
    }

    public function setRecommendedResolution(?string $recommendedResolution): self
    {
        $this->recommendedResolution = $recommendedResolution;

        return $this;
    }

    public function getFolder(): ?Folder
    {
        return $this->folder;
    }

    public function setFolder(?Folder $folder): self
    {
        $this->folder = $folder;

        return $this;
    }
}
