<?php

namespace App\Entity;

use App\Entity\EntityTrait\UuidTrait;
use App\Repository\ReportConfigRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use App\Validation\Constraint\Report as MyRatio;

/**
 * @ORM\Entity(repositoryClass=ReportRepository::class)
 */
class ReportConfig
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
     */
    private $uuid;
    /**
     * 
     * @Groups({"report:get","report:admin","report:generate","report:edit"})
     * @Assert\NotBlank(groups={"report:admin","report:generate","report:edit"})
     * @Assert\Range(
     *      min = 0,
     *      notInRangeMessage = "mininum must be {{ min }}",
     *      groups={"report:admin","report:generate","report:edit"}
     * )
     * @Assert\Type(
     *     type="integer",
     *     message="The value {{ value }} is not a valid {{ type }}.",
     *     groups={"report:admin","report:generate","report:edit"}
     * )
     * @ORM\Column(type="integer")
     */
    private $totalCompletion;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"report:get","report:admin","report:generate","report:edit"})
     * @Assert\NotBlank(groups={"report:admin","report:generate","report:edit"})
     * @Assert\Range(
     *      min = 0,
     *      notInRangeMessage = "mininum must be {{ min }}",
     *      groups={"report:admin","report:generate","report:edit"}
     * )
     * @Assert\Type(
     *     type="integer",
     *     message="The value {{ value }} is not a valid {{ type }}.",
     *     groups={"report:admin","report:generate","report:edit"}
     * )
     */
    private $totalViews;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"report:get","report:edit","report:admin","report:generate"})
     * @Assert\NotBlank(groups={"report:admin","report:generate","report:edit"})
     * @Assert\Range(
     *      min = 0,
     *      max =100,
     *      notInRangeMessage = "mininum must be {{ min }} maximum must be {{ max }} ",
     *      groups={"report:admin","report:generate","report:edit"}
     * )
     * @Assert\Type(
     *     type="integer",
     *     message="The value {{ value }} is not a valid {{ type }}.",
     *     groups={"report:admin","report:generate","report:edit"}
     * )
     * @MyRatio\RatioDesktopMobile(groups={"report:admin","report:generate","report:edit"})
     */
    private $mobileRepartition;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"report:get","report:admin","report:generate","report:edit"})
     * @Assert\NotBlank(groups={"report:admin","report:generate","report:edit"})
     * @Assert\Range(
     *      min = 0,
     *      max =100,
     *      notInRangeMessage = "mininum must be {{ min }} maximum must be {{ max }} ",
     *      groups={"report:admin","report:generate","report:edit"}
     * )
     * @Assert\Type(
     *     type="integer",
     *     message="The value {{ value }} is not a valid {{ type }}.",
     *     groups={"report:admin","report:generate","report:edit"}
     * )
     * @MyRatio\RatioDesktopMobile(groups={"report:admin","report:generate","report:edit"})
     */
    private $desktopRepartition;
    /**
     * @ORM\Column(type="datetime_immutable")
     * @Assert\Date(groups={"filters"},message="the format is YYYY-MM-DD ex=2000-01-30")
     * @var string A "YYYY-MM-DD" formatted value
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Assert\Date(groups={"filters"},message="the format is YYYY-MM-DD ex=2000-01-30")
     * @var string A "YYYY-MM-DD" formatted value
     */
    private $updatedAt;

    /**
     * @ORM\OneToOne(targetEntity=Account::class, inversedBy="reportConfig", cascade={"persist", "remove"})
     */
    private $account;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"report:admin","report:generate"})
     * @Assert\NotBlank(groups={"report:admin","report:generate"})
     * @Assert\Range(
     *      min = 0,
     *      notInRangeMessage = "mininum must be {{ min }}",
     *      groups={"report:admin","report:generate"}
     * )
     * @Assert\Type(
     *     type="integer",
     *     message="The value {{ value }} is not a valid {{ type }}.",
     *     groups={"report:admin","report:generate"}
     * )
     */
    private $mobileCarbonWeight;
    /**
     * @ORM\Column(type="integer")
     * @Groups({"report:admin","report:generate"})
     * @Assert\NotBlank(groups={"report:admin","report:generate"})
     * @Assert\Range(
     *      min = 0,
     *      notInRangeMessage = "mininum must be {{ min }}",
     *      groups={"report:admin","report:generate"}
     * )
     * @Assert\Type(
     *     type="integer",
     *     message="The value {{ value }} is not a valid {{ type }}.",
     *     groups={"report:admin","report:generate"}
     * )
     */

    private $desktopCarbonWeight;

    public function getId()
    {
        return $this->id;
    }

    public function getTotalCompletion()
    {
        return $this->totalCompletion;
    }

    public function setTotalCompletion($totalCompletion): self
    {
        $this->totalCompletion = $totalCompletion;

        return $this;
    }

    public function getTotalViews()
    {
        return $this->totalViews;
    }

    public function setTotalViews($totalViews)
    {
        $this->totalViews = $totalViews;

        return $this;
    }

    public function getMobileCarbonWeight()
    {
        return $this->mobileCarbonWeight;
    }

    public function setMobileCarbonWeight($mobileCarbonWeight): self
    {
        $this->mobileCarbonWeight = $mobileCarbonWeight;

        return $this;
    }

    public function getMobileRepartition()
    {
        return $this->mobileRepartition;
    }

    public function setMobileRepartition($mobileRepartition): self
    {
        $this->mobileRepartition = $mobileRepartition;

        return $this;
    }

    public function getDesktopCarbonWeight()
    {
        return $this->desktopCarbonWeight;
    }

    public function setDesktopCarbonWeight($desktopCarbonWeight): self
    {
        $this->desktopCarbonWeight = $desktopCarbonWeight;

        return $this;
    }

    public function getDesktopRepartition()
    {
        return $this->desktopRepartition;
    }

    public function setDesktopRepartition($desktopRepartition): self
    {
        $this->desktopRepartition = $desktopRepartition;

        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

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
