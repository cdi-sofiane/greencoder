<?php

namespace App\Form\Dto;

use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

class DtoEncodeProgress
{

    /**
     *
     * @Groups ({"encode:progress"})
     * @Assert\Type ( type="integer")
     * @Assert\Range(
     *      min = 0,
     *      max = 100,
     *      minMessage = "Min % is 0",
     *      maxMessage = "Max % is 100",
     *      groups={"encode:progress"}
     * )
     * @var integer|string
     * @Assert\NotBlank (groups={"encode:progress"})
     */
    public $progress;
    /**
     * @var string
     * @Groups ({"encode:progress"})
     * @Assert\NotBlank (groups={"encode:progress"})
     * @Assert\Choice({"ANALYSING", "RETRY", "ENCODING", "ENCODED", "ERROR" , "PENDING"},message="Valid fields are PENDING, ANALYSING, RETRY, ENCODING,ENCODED,ERROR",groups={"encode:progress"})
     */
    public $status;
    /**
     * @var string
     * @Groups ({"encode:progress"})
     */
    public $video_uuid;
}
