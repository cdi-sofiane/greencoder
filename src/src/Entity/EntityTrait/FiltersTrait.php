<?php

namespace App\Entity\EntityTrait;


use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

trait FiltersTrait
{
    /**
     *
     * @Groups({"report:list"})
     */
    private $search = null;

    /**
     * @Assert\Choice({"ASC", "DESC"},message="Valid fields are ASC, DESC",groups={"report:list"})
     * @Groups({"report:list"})
     */
    private $order = 'ASC';
    /**
     * @Assert\Choice({"name", "date", "video", "economie"},message="Valid fields are name, date, video,economie",groups={"report:list"})
     * @Groups({"report:list"})
     */
    private $sortBy = 'date';

    /**
     * @Groups({"report:list"})
     */
    private $startAt = '1970-01-01';

    /**
     * @Groups({"report:list"})
     */
    private $endAt = 'now';

    /**
     * Get the value of search
     */
    public function getSearch()
    {
        return $this->search;
    }

    /**
     * Set the value of search
     *
     * @return  self
     */
    public function setSearch($search)
    {
        $this->search = $search;

        return $this;
    }

    /**
     * Get the value of order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set the value of order
     *
     * @return  self
     */
    public function setOrder($order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Get the value of sortBy
     */
    public function getSortBy()
    {
        return $this->sortBy;
    }

    /**
     * Set the value of sortBy
     *
     * @return  self
     */
    public function setSortBy($sortBy)
    {

        $this->sortBy = $sortBy;

        return $this;
    }

    /**
     * Get a "YYYY-MM-DD" formatted value
     *
     * @return  string
     */
    public function getStartAt()
    {
        return $this->startAt;
    }

    /**
     * Set a "YYYY-MM-DD" formatted value
     *
     * @return  self
     */
    public function setStartAt(string $startAt)
    {
        $this->startAt = $startAt;

        return $this;
    }

    /**
     * Get a "YYYY-MM-DD" formatted value
     *
     * @return  string
     */
    public function getEndAt()
    {
        return $this->endAt;
    }

    /**
     * Set a "YYYY-MM-DD" formatted value
     *
     *
     * @return  self
     */
    public function setEndAt(string $endAt)
    {
        $this->endAt = $endAt;

        return $this;
    }
}
