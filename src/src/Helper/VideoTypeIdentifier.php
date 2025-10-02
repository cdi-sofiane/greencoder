<?php

namespace App\Helper;

use App\Repository\EncodeRepository;
use App\Repository\VideoRepository;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;

class VideoTypeIdentifier
{
    private $videoRepository;
    private $encodeRepository;
    private $extension;
    /**
     * @var FFProb
     */
    private $FFProb;

    public function __construct(VideoRepository $videoRepository, EncodeRepository $encodeRepository )
    {
        $this->encodeRepository = $encodeRepository;
        $this->videoRepository = $videoRepository;
        $this->FFProb = FFProbe::create();
    }

    public function identify($videoUuid)
    {
        $original = $this->videoRepository->findOneBy(['uuid' => $videoUuid]);
        $encode = $this->encodeRepository->findOneBy(['uuid' => $videoUuid]);
        $video = null;
        if ($original != null) {
            $video = $original;
        } elseif ($encode != null) {
            $video = $encode;
        }
        if ($video == null) {
            return null;
        }
        return $video;
    }

    public function roundify($value)
    {
        return round($value, 2, PHP_ROUND_HALF_UP);
    }

    /**
     * determine if file extension  from symfony gessExtension methode
     *
     * @param string $extensionGesseur
     * @return $this
     */
    public function findExtension(string $extensionGesseur): self
    {
        $arr = [
            'mov' => 'qt',
        ];

        if (in_array($extensionGesseur, $arr, true)) {
            foreach ($arr as $key => $value) {
                if ($value === $extensionGesseur) {
                    $this->extension = $key;
                    return $this;
                }

            }
        }
        $this->extension = $extensionGesseur;
        return $this;
    }

    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * @param $uploadFile
     * @return bool
     */
    public function mimeTypeVerify($uploadFile)
    {
        return $this->FFProb->isValid($uploadFile);
    }

    /**
     * @param  $uploadFile
     * @return mixed
     */
    public function duration($uploadFile)
    {
        return $this->FFProb
            ->format($uploadFile->getRealPath())
            ->get('duration');
    }
}