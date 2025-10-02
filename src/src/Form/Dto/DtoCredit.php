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

class DtoCredit
{
    /**
     * @var integer
     * @Groups ({"credit:error"})
     */
    public $creditStorage;
    /**
     * @var integer
     * @Groups ({"credit:error"})
     */
    public $creditEncode;



}
