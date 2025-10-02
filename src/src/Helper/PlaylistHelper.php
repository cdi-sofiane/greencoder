<?php

namespace App\Helper;

use App\Entity\Video;

class PlaylistHelper
{
    private $currentVideo;
    public function __construct(Video $video)
    {
        $this->currentVideo = $video;
    }

    public function generatePlaylistLink()
    {
        $prefix_name = $_ENV['OVH_PUBLIC_STORAGE_LINK'] . $this->currentVideo->getUuid() . '_' . $this->currentVideo->getSlugName();

        return $arrPlaylist = [
            [
                'src' => $prefix_name . '.' . 'mpd',
                'extension' => 'application/dash+xml'
            ],
            [
                'src' => $prefix_name . '.' . 'm3u8',
                'extension' => 'application/x-mpegURL'
            ]
        ];
    }
}
