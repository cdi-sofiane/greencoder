<?php

namespace App\Command;

use App\Entity\Video;
use App\Helper\DeleteVideoHelper;
use App\Repository\VideoRepository;
use App\Services\Storage\S3Storage;
use App\Services\Storage\StorageInterface;
use App\Services\Video\VideoManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveBlockedVideosFromStorage extends Command
{
    protected static $defaultName = 'storage:remove:blocked';
    public $em;
    protected $videoRepository;
    protected $simulationRepository;
    protected $storage;
    private $videoManager;
    private $output;
    public function __construct(
        S3Storage       $storage,
        VideoManager           $videoManager,
        EntityManagerInterface $em
    ) {
        $this->em = $em;
        $this->storage = $storage;
        parent::__construct();
        $this->output = '';

        $this->videoManager = $videoManager;
    }


    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $outputStyle = new OutputFormatterStyle('red', 'black', ['bold', 'blink']);
        $outputStyle2 = new OutputFormatterStyle('green', 'black', ['bold', 'blink']);
        $this->output->getFormatter()->setStyle('fire', $outputStyle);
        $this->output->getFormatter()->setStyle('good', $outputStyle2);

        $args = [
            'encodingState' => Video::ENCODING_ANALYSING,
            'isDeleted' => false,
        ];
        /** @var videoRepository $videoRepository  */
        $videoRepository = $this->em->getRepository(Video::class);
        $videos = $videoRepository->findVideos(null, $args);
        if ($videos == null) {
            $output->writeln("<fire>nothing to clear</fire>");
            return;
        }



        $toDelete = [];
        /**
         * @var Video $video
         */
        foreach ($videos as $video) {

            if (false === DeleteVideoHelper::removeableVideo($video)) {
                continue;
            }


            $encodes = $video->getEncodes();
            if ($encodes != null) {
                foreach ($encodes as $encode) {

                    $this->videoManager->removeIncompletEncoding($encode->getUuid());
                }
            }


            $video->setEncodingState(Video::ENCODING_ERROR);
            $this->videoRepository->updateVideo($video);
            $dateStart = new \DateTimeImmutable('now');
            $output->writeln("<good>" .
                $dateStart->format("y:m:d H:i:s") .
                " | INFO " .
                " | video_uuid : " . $video->getUuid() .
                " | encodingState : " . $video->getEncodingState() .
                " | lastUpdate : " . $video->getUpdatedAt()->format("y:m:d H:i:s") .
                " | email:" . $video->getAccount()->getEmail() .
                " | bits:" . $video->getAccount()->getCreditStorage() .
                " | seconds:" . $video->getAccount()->getCreditEncode() .
                "</good>");
        }
    }
}
