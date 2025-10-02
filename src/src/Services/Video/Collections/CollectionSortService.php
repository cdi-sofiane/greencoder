<?php

namespace App\Services\Video\Collections;

use App\Interfaces\Videos\VideosCollectionHandlerInterface;
use Exception;

class CollectionSortService implements VideosCollectionHandlerInterface
{
    public function handle(array $collection, array $options)
    {

        switch ($options['sortBy']) {
            case 'createdAt':
                $collection =  (new CollectionSortDate($collection, $options));
                break;
            case 'name':
                $collection =   (new CollectionSortName($collection, $options));
                break;

            default:
                throw new Exception("Invalid sort option");
                break;
        }
        return $collection->orderAndSort();
    }
}
