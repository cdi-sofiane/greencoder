<?php

namespace App\Form\Dto;

use App\Entity\Video;
use Symfony\Component\Validator\Constraints as Assert;

class VideoForm
{
    /**
     * @Assert\Date(groups={"filters"},message="the format is YYYY-MM-DD ex=2000-01-30")
     * @var string A "YYYY-MM-DD" formatted value
     */
    public $startAt = '1970-01-01';
    /**
     * @Assert\Date(groups={"filters"},message="the format is YYYY-MM-DD ex=2000-01-30")
     * @var string A "YYYY-MM-DD" formatted value
     */
    public $endAt = 'now';
    /**
     * @Assert\Choice({"date", "name"},groups={"filters"})
     */
    public $sortBy;
    /**
     *
     */
    public $page = 1;
    /**
     *
     */
    public $limit = 12;
    /**
     * |
     */
    public $order = 'ASC';
    /**
     * |
     */
    public $name;

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     * @return VideoForm
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPage()
    {

        return $this->page;
    }

    /**
     * @param mixed $page
     * @return VideoForm
     */
    public function setPage($page)
    {
        if ($page != null ? (int)$page : 1)
            $this->page = $page;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param mixed $limit
     * @return VideoForm
     */
    public function setLimit($limit)
    {
        if ($limit != null ? (int)$limit : 12)
            $this->limit = $limit;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param mixed $order
     * @return VideoForm
     */
    public function setOrder($order)
    {

        $this->order = $order;
        return $this;
    }


    /**
     * @return string
     */
    public function getStartAt(): \DateTimeImmutable
    {
        return $this->startAt;
    }

    /**
     * @param string $startAt
     * @return VideoForm
     */
    public function setStartAt($startAt): self
    {
        $this->startAt = $startAt;
        return $this;
    }

    /**
     * @return string
     */
    public function getEndAt(): \DateTimeImmutable
    {
        return $this->endAt;
    }

    /**
     * @param string $endAt
     * @return VideoForm
     */
    public function setEndAt($endAt): self
    {
        $this->endAt = $endAt;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSortBy(): self
    {
        return $this->sortBy;
    }

    /**
     * @param mixed $sortBy
     * @return VideoForm
     */
    public function setSortBy($sortBy): self
    {
        $this->sortBy = $sortBy;
        return $this;
    }
}
