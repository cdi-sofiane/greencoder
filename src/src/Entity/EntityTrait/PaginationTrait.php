<?php

namespace App\Entity\EntityTrait;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

trait PaginationTrait
{
    /**
     * 
     * @Assert\Range(
     *      min = 1,
     * groups={"report:list"}
     * )
     * @Groups({"report:list"})
     */

    private $limit = 12;
    /**
     * 
     * @Assert\Range(
     *      min = 1,
     * groups={"report:list"}
     * )
     * @Groups({"report:list"})
     */
    private $page = 1;



    /**
     * Get the value of limit
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Set the value of limit
     *
     * @return  self
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Get the value of page
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Set the value of page
     *
     * @return  self
     */
    public function setPage($page)
    {
        $this->page = $page;

        return $this;
    }
}
