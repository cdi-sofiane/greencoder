<?php

namespace App\Services\Consumption;

interface ConsumptionInterface
{
    /**
     * array with uuid of video or encode
     *
     * @param $args
     * @return mixed
     */
    public function addConsumptionRow($data, $args): void;

    /**
     * sum of all targeted video Video|Encode to calcule gain
     *
     * @param $video
     * @return array Video|Encode
     */
    public function findComsumptionsRow($video = null, $launched = null, $dateDebutFacturation = null, $dateFin = null, $user = null);
}