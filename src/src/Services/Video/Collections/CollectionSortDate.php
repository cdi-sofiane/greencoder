<?php

namespace App\Services\Video\Collections;

use App\Interfaces\Videos\CollectionSortInterface;

class CollectionSortDate implements CollectionSortInterface
{
    private $collection;
    private $options;
    public function __construct($collection,  $options)
    {
        $this->collection = $collection;
        $this->options = $options;
    }
    public function orderAndSort()
    {

        return  $this->options['order'] == "ASC" ? self::orderAsc($this->collection) : self::orderDesc($this->collection);
    }

    private static function orderAsc($collection)
    {
        usort($collection, fn ($a, $b) => ($a->getCreatedAt() < $b->getCreatedAt()) ? -1 : 1);
        return $collection;
    }
    private static function orderDesc($collection)
    {
        usort($collection, fn ($a, $b) => ($a->getCreatedAt() > $b->getCreatedAt()) ? -1 : 1);
        return $collection;
    }
}
