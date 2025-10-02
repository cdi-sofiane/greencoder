<?php

namespace App\Services;

use App\Services\JsonResponseMessage;

interface DataFormalizerInterface
{
    /**
     *
     *
     * @param $item 'entity class object'
     * @param null $group 'filter group option'
     * @return mixed
     */
    public function extract($item);
}