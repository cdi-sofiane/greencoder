<?php

namespace App\Command;

use App\Entity\Encode;
use App\Entity\Simulation;
use App\Entity\Video;
use App\Repository\EncodeRepository;
use App\Repository\SimulationRepository;
use App\Repository\VideoRepository;
use App\Services\Storage\OvhStorage;
use App\Services\Storage\S3Storage;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveVideoStorageCommande extends Command
{
    protected static $defaultName = 'storage:remove';
    protected $videoRepository;
    protected $simulationRepository;
    protected $storage;
    public $em;
    private $output;
    private $encodeRepository;

    public function __construct(VideoRepository        $videoRepository,
                                EncodeRepository       $encodeRepository,
                                SimulationRepository   $simulationRepository,
                                S3Storage              $storage,
                                EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->videoRepository = $videoRepository;
        $this->simulationRepository = $simulationRepository;
        $this->storage = $storage;
        $this->encodeRepository = $encodeRepository;
        parent::__construct();

    }

    protected function configure(): void
    {
        $this
            // ...
            ->addOption('type', null, InputOption::VALUE_OPTIONAL, 'the type of video that must be deleted from storage (encode or estimate) ')
            ->addOption('dateEncode', null, InputOption::VALUE_OPTIONAL, 'the number of days before today by default is 2 day (current date minus dateEncode)')
            ->addOption('dateEstimate', null, InputOption::VALUE_OPTIONAL, 'the number of days before today by default is 1 day (current date minus dateEstimate)')
            ->addOption('user', null, InputOption::VALUE_OPTIONAL, 'find specific user s videos to remove when orders are expired (trigger by another cli)')
            ->addOption('isStorage', null, InputOption::VALUE_OPTIONAL, 'find stored videos to remove when order is expired (trigger by another cli)');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $outputStyle = new OutputFormatterStyle('red', 'black', ['bold', 'blink']);
        $outputStyle2 = new OutputFormatterStyle('green', 'black', ['bold', 'blink']);
        $output->getFormatter()->setStyle('fire', $outputStyle);
        $output->getFormatter()->setStyle('good', $outputStyle2);

        $typeOption = $input->getOption('type') != null ? $input->getOption('type') : "all";
        $dateEncodeOption = $input->getOption('dateEncode') != null ? $input->getOption('dateEncode') : "2";
        $dateEstimateOption = $input->getOption('dateEstimate') != null ? $input->getOption('dateEstimate') : "1";
        $filter['isStored'] = $input->getOption('isStorage') != null ? $input->getOption('isStorage') : false;
        $filter['user'] = $input->getOption('user') != null ? $input->getOption('user') : null;

        switch ($typeOption) {
            case 'encode':
                /*find original video + encoded video with storage option == false */

                $listOfExpiredVideo = $this->videoRepository->findExpiredStorageVideo($filter, $dateEncodeOption);
                break;
            case 'estimate':
                /*find estimate video*/
                $listOfExpiredVideo = $this->simulationRepository->findExpiredStorageVideo($filter, $dateEstimateOption);

                break;
            default:
                /*finc estimate + orginal + encoded video */
                $listOfExpiredEncode = $this->videoRepository->findExpiredStorageVideo($filter, $dateEncodeOption);
                $listOfExpiredSimulation = $this->simulationRepository->findExpiredStorageVideo($filter, $dateEstimateOption);
                $listOfExpiredVideo = array_merge($listOfExpiredEncode, $listOfExpiredSimulation);
                break;
        }
        /** @var Video $expiredVideo */

        if (empty($listOfExpiredVideo)) {
            $output->writeln('<fire>nothing to remove</fire>');
            die;
        }
        foreach ($listOfExpiredVideo as $expiredVideo) {
            if ($expiredVideo instanceof Video) {

                $this->videoExistInStorage($expiredVideo);
                /** @var Encode $encode */
                $encodes = $expiredVideo->getEncodes();
                if ($encodes != null) {
                    $this->encodedExistInStorage($encodes);
                }
            } elseif ($expiredVideo instanceof Simulation) {
                $this->simultaionExistInStorage($expiredVideo);

            }


        }

    }

    public function existInStorage($expiredVideo, $type)
    {
        $date = (new \DateTimeImmutable('now'))->format("y-m-d H:m:s");

        if ($this->storage->findInStorage($expiredVideo) == true) {
            $this->storage->videoDelete($expiredVideo);
            $this->output->writeln($date . '|' . $expiredVideo->getUuid() . '<good> ' . $type . ' was removed,reference has been set to deleted</good>');
        } else {
            $this->output->writeln($date . '|' . $expiredVideo->getUuid() . ' <fire>' . $type . ' didnt exist,reference has been set to deleted</fire>');
        }
    }

    public function encodedExistInStorage($encodes)
    {
        foreach ($encodes as $encode) {
            $this->existInStorage($encode, 'encode');
            $this->encodeRepository->deleteEncode($encode);
        }
    }

    public function videoExistInStorage($video)
    {
        $this->existInStorage($video, 'video');
        $this->videoRepository->deleteVideo($video);
    }

    public function simultaionExistInStorage($simulation)
    {
        $this->existInStorage($simulation, 'simulation');
        $this->simulationRepository->deleteSimulation($simulation);

    }
}