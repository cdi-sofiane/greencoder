<?php

namespace App\Helper;

use App\Entity\Video;
use App\Services\Storage\OvhStorage;
use App\Services\Storage\S3Storage;
use FFMpeg\FFMpeg;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\Fill\FillInterface;
use Imagine\Image\ImageInterface;
use Imagine\Image\ManipulatorInterface;
use Imagine\Image\Palette\Color\ColorInterface;
use Imagine\Image\Palette\PaletteInterface;
use Imagine\Image\PointInterface;
use Imagine\Image\ProfileInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ThumbnailHelper
{

    /**
     * @var OvhStorage|S3Storage
     */
    private $storage;
    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;

    public function __construct(S3Storage $storage, ParameterBagInterface $parameterBag)
    {
        $this->storage = $storage;
        $this->parameterBag = $parameterBag;
    }

    public function createThumbnailFormVideo($uploadFile,Video $video)
    {
        $ffmpeg = FFMpeg::create();
        $vi = $ffmpeg->open($uploadFile);
        $frame = $vi->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds($video->getDuration() / 3));

        $pathUploadDir = $this->parameterBag->get('video_directory');

        $frame->save($pathUploadDir . $video->getUuid() . '_' . $video->getSlugName() . '_thumbnail.jpeg');
        $this->writeThumbnail($pathUploadDir . $video->getUuid() . '_' . $video->getSlugName() . '_thumbnail.jpeg');
    }

    /**
     * Write a thumbnail image using Imagine
     *
     * @param string $thumbAbsPath full absolute path to attachment directory e.g. /var/www/project1/images/thumbs/
     */
    public function writeThumbnail($thumbAbsPath)
    {
        $boxSize = [
            ['quality' => 'HD', 'width' => 1280, "height" => 720],
            ['quality' => 'SD', 'width' => 480, "height" => 270],
            ['quality' => 'LD', 'width' => 250, "height" => 140],

        ];
        $data['thumbnail'][4] = $thumbAbsPath;
        $imagine = new Imagine();
        $image = $imagine->open($thumbAbsPath);
        for ($i = 0; $i < 3; $i++) {
            $size = new Box($boxSize[$i]['width'], $boxSize[$i]['height']);
            $image->thumbnail($size, ImageInterface::THUMBNAIL_FLAG_UPSCALE)
                ->save($this->thumbnailNameExtractor($thumbAbsPath) . '_' . $boxSize[$i]['quality'] . '.jpeg');
            $data['thumbnail'][$i] = $this->thumbnailNameExtractor($thumbAbsPath) . '_' . $boxSize[$i]['quality'] . '.jpeg';
            if ($data['thumbnail'][$i] != 4) {

                $this->storage->thumbnailUpload($data['thumbnail'][$i]);
            }

        }
        foreach ($data['thumbnail'] as $thumbnail) {
            unlink($thumbnail);
        }
    }

    private function thumbnailNameExtractor($thumbAbsPath)
    {
        return str_replace('.jpeg', '', $thumbAbsPath);
    }
}