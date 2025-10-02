<?php

namespace App\Form\Dto;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Gedmo\Mapping\Annotation as Gedmo;
use App\Form\Dto\DtoTag;

class DtoTags
{
    /**
     * @Groups ({"tags:add","tags:all"})
     * @OA\Property (property="account_uuid")),
     */
    public $account_uuid;

    /**
     *
     * @Groups ({"tags:add","tags:all"})
     * @Assert\NotBlank (groups={"tags:add","tags:add"})
     * @Assert\NotNull  (groups={"tags:add","tags:remove"})
     * @Assert\Count(
     *      groups={"tags:add","tags:remove"},
     *      min = 1,
     *      minMessage = "you must specify at least one tag",
     * )
     * @OA\Property (property="tags", type="array", @OA\Items(ref=@Model(type=DtoTag::class))),
     */
    private $tags;

    /**
     * @Groups ({"tags:add"})
     * @Assert\NotBlank (groups={"tags:add","tags:remove"})
     * @Assert\NotNull  (groups={"tags:add","tags:remove"})
     * @Assert\Count(
     *      groups={"tags:add","tags:remove"},
     *      min = 1,
     *      minMessage = "you must specify at least one video",
     * )
     * @OA\Property (type="array",@OA\Items(type="string")),
     */
    private $videos;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->videos = new ArrayCollection();
    }

    public function getTags()
    {
        return $this->tags;
    }


    public function setTags(array $tags): DtoTags
    {
        $this->tags = $tags;
        return $this;
    }


    public function getVideos()
    {
        return $this->videos;
    }

    /**
     * @param array $videos
     * @return DtoTags
     */
    public function setVideos(array $videos): DtoTags
    {
        $this->videos = $videos;
        return $this;
    }
}
