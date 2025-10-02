<?php

namespace App\Helper;

use App\Entity\Video;

class DeleteVideoHelper
{
    const ELAPSED_TIME = 45;
    const MINIMUM_TIME = 15 * 60;

    public function __construct() {}

    /**
     *  remove videos when analize time is blocked
     *  if video duration time <= 15 min video can be deleted after 45 min
     *  if video duration time > 15 min video can be deleted after 3 * video duration ex :  120 min * 3 after debut of analyse state
     *
     */
    public static function removeableVideo(Video $originalVideo): bool
    {

        $now = (new \DateTimeImmutable("now"))->format('y:m:d H:i:s');

        $removableInterval = false;
        if ($originalVideo->getEncodingState() === Video::ENCODING_ANALYSING) {

            /** @var Video $originalVideo */
            switch ($originalVideo->getDuration() <= self::MINIMUM_TIME) {
                case true:

                    if ($now >= $originalVideo->getCreatedAt()->modify('+' . self::ELAPSED_TIME . 'minutes')->format('y:m:d H:i:s')) {

                        return true;
                    }
                    return false;
                case false:
                    if ($now >= $originalVideo->getCreatedAt()->modify('+' . $originalVideo->getDuration() * 3 . 'minutes')->format('y:m:d H:i:s')) {

                        return true;
                    }
                    return false;
            }
        }
        return $removableInterval;
    }
}
