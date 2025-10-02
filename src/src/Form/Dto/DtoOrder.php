<?php

namespace App\Form\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
class DtoOrder
{
    /**
     * @Assert\Type(type="boolean", message="missing boolean attribut true or false",groups={"list_of_order","consumed","order:renewable"})
     * @Assert\NotNull(groups={"list_of_order","consumed","order:renewable"},message="Choose a valid value ex: true or false.")
     * @Groups({"list_of_order","consumed","order:renewable"})
     */
    private $isRenewable;

    /**
     * @return mixed
     */
    public function getIsRenewable()
    {
        return $this->isRenewable;
    }

    /**
     * @param mixed $isRenewable
     * @return DtoOrder
     */
    public function setIsRenewable($isRenewable)
    {
        $this->isRenewable = $isRenewable != "" ? filter_var($isRenewable, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : $isRenewable;
        return $this;
    }

}