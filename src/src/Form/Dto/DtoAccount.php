<?php

namespace App\Form\Dto;

use App\Entity\Account;
use App\Entity\EntityTrait\FiltersTrait;
use App\Entity\EntityTrait\PaginationTrait;
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

class DtoAccount extends Account
{
    use FiltersTrait;
    use PaginationTrait;
    /**
     *
     * @Groups ({"account:list"})
     * @Assert\Type(type="boolean", message="missing boolean attribut true or false",groups={"account:list"})
     * @Groups ({"account:list"})
     */
    private $isMultiAccount;
    /**
     *
     * @Groups ({"account:list"})
     * @Assert\Type(type="boolean", message="missing boolean attribut true or false")

     * @Groups ({"account:list"})
     */
    private $isActive;
    /**
     *
     * @Assert\Range(
     *      min = 1,
     * groups={"account:list"}
     * )
     * @Groups({"account:list"})
     */

    private $limit = 12;
    /**
     *
     * @Assert\Range(
     *      min = 1,
     * groups={"account:list"}
     * )
     * @Groups({"account:list"})
     */
    private $page = 1;
    /**
     *
     * @Groups({"account:list"})
     */
    private $search = null;
    /**
     *
     * @Groups({"account:list"})
     */
    private $account_uuid = null;
    /**
     * @Assert\Choice({"ASC", "DESC"},message="Valid fields are ASC, DESC",groups={"account:list"})
     * @Groups({"account:list"})
     */
    private $order = 'ASC';
    /**
     * @Assert\Choice({"name", "date","lastConnection"},message="Valid fields are name or date",groups={"account:list"})
     * @Groups({"account:list"})
     */
    private $sortBy = 'date';
    public function getIsMultiAccount()
    {
        return $this->isMultiAccount;
    }
    public function setIsMultiAccount($isMultiAccount)
    {

        $this->isMultiAccount = $isMultiAccount != "" ? filter_var($isMultiAccount, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : $isMultiAccount;

        return $this;
    }
    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }
    public function setIsActive($isActive): self
    {

        $this->isActive = $isActive != "" ? filter_var($isActive, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : $isActive;

        return $this;
    }

    /**
     * Get the value of accountUuid
     */
    public function getAccountUuid()
    {
        return $this->account_uuid;
    }

    /**
     * Set the value of accountUuid
     *
     * @return  self
     */
    public function setAccountUuid($account_uuid)
    {
        $this->account_uuid = $account_uuid;

        return $this;
    }
    public function getArray()
    {

        $sortBy = $this->getSortBy() == 'date' ? 'createdAt' : $this->getSortBy();
        return [
            'isMultiAccount' => $this->getIsMultiAccount(),
            'isActive' => $this->getIsActive(),
            'search' => $this->getSearch(),
            'sortBy' =>  $sortBy,
            'order' => $this->getOrder(),
            'page' => $this->getPage(),
            'limit' => $this->getLimit(),
            'account_uuid' => $this->getAccountUuid(),
            'usages' => $this->getUsages(),
        ];
    }
}
