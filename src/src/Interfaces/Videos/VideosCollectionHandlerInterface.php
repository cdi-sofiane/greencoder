<?php

namespace App\Interfaces\Videos;


interface VideosCollectionHandlerInterface
{
    /**
     * Strategy to handle collection with differents implementation  
     *
     * @param array $collection
     * @param array $options
     * @return void
     */
    public function handle(array $collection, array $options);
}
