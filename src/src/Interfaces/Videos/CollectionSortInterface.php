<?php

namespace App\Interfaces\Videos;

interface CollectionSortInterface
{
    /**
     * order collection of object 
     *
     * @param [array] $collection list or objects
     * @param [array] $options 
     */
    public function __construct(array $collection, array $options);

    /**
     * Re order collection based on property_name in collection with ASC or DESC
     *
     * @param [array] $collection
     * @param [array] $options
     * @return void
     */
    public function orderAndSort();
}
