<?php

namespace App\Form\Dto;

use App\Entity\EntityTrait\FiltersTrait;
use App\Entity\EntityTrait\PaginationTrait;
use App\Entity\User;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\DependencyInjection\Loader\Configurator\Traits\FileTrait;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

class DtoUser extends User
{
    use FiltersTrait;
    use PaginationTrait;

    /**
     *
     * @Assert\Range(
     *      min = 1,
     *      groups={"filters"}
     * )
     * @Groups({"filters"})
     */

    private $limit = 12;
    /**
     *
     * @Assert\Range(
     *      min = 1,
     * groups={"filters"}
     * )
     * @Groups({"filters"})
     */
    private $page = 1;
    /**
     *
     * @Groups({"filters"})
     */
    private $search = null;

    /**
     * @Assert\Choice({"ASC", "DESC"},message="Valid fields are ASC, DESC",groups={"filters"})
     * @Groups({"filters"})
     */
    private $order = 'ASC';
    /**
     * @Assert\Choice({"date", "email"},message="Valid fields are date, email",groups={"filters"})
     * @Groups({"filters"})
     */
    private $sortBy = 'date';

    public function getArray()
    {

        return [
            'isActive' => $this->getIsActive(),
            'isConditionAgreed' => $this->getIsConditionAgreed(),
            'search' => $this->getSearch(),
            'sortBy' =>  $this->getSortBy() == 'date' ? 'createdAt' : 'email',
            'order' => $this->getOrder(),
            'page' => $this->getPage(),
            'limit' => $this->getLimit(),
            'role' => $this->getRoles(),
            'user_uuid' => $this->getUuid(),
            "startAt" => $this->getCreatedAt(),
            "endAt" => $this->getCreatedAt()
        ];
    }
}
