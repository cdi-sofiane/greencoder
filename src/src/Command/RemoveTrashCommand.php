<?php

namespace App\Command;

use App\Repository\FolderRepository;
use App\Repository\VideoRepository;
use App\Services\Folder\FolderManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveTrashCommand extends Command
{
    protected static $defaultName = 'remove:trash';
    public $em;
    private $videoRepository;
    private $folderRepository;
    private $folderManager;
    private $output;

    public function __construct(EntityManagerInterface $em,
                                VideoRepository $videoRepository,
                                FolderRepository $folderRepository,
                                FolderManager $folderManager)
    {
        $this->em = $em;
        $this->videoRepository = $videoRepository;
        $this->folderRepository = $folderRepository;
        $this->folderManager = $folderManager;

        parent::__construct();

    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $outputStyle = new OutputFormatterStyle('red', 'black', ['bold', 'blink']);
        $outputStyle2 = new OutputFormatterStyle('green', 'black', ['bold', 'blink']);
        $output->getFormatter()->setStyle('fire', $outputStyle);
        $output->getFormatter()->setStyle('deleted', $outputStyle2);


        $this->deleteVideos($output);
        $this->deleteFolders($output);

        $this->em->flush();


    }


    public function deleteVideos(OutputInterface $output)
    {
        $videos = $this->videoRepository->getVideosInTrashSince30Days();

        if(empty($videos)) {
            $output->writeln('<fire>' . '| No Videos found' . '</fire>');
            return;
        }

        foreach($videos as $key => $video) {
            $video->setIsDeleted(true);
            $video->setDeletedAt(new \DateTimeImmutable('now'));

            $deleted =
            '| video N° : ' . $key .
            '| video uuid : ' . $video->getUuid() .
            '| video name : ' . $video->getName() .
            '| account uuid : ' . $video->getAccount()->getUuid() .
            '| account name : ' . $video->getAccount()->getName();

            $output->writeln('<deleted>' . $deleted . '</deleted>');
        }
    }

    public function deleteFolders(OutputInterface $output)
    {
        $folders = $this->folderRepository->getFoldersInTrashSince30Days();

        if(empty($folders)) {
            $output->writeln('<fire>' . '| No Folders found' . '</fire>');
            return;
        }

        foreach($folders as $key => $folder) {

            $this->folderManager->deleteRecursivly($folder);

            $deleted =
            '| folder N° : ' . $key .
            '| folder uuid : ' . $folder->getUuid() .
            '| folder name : ' . $folder->getName() .
            '| account uuid : ' . $folder->getAccount()->getUuid() .
            '| account name : ' . $folder->getAccount()->getName();


            $output->writeln('<deleted>' . $deleted . '</deleted>');
        }

    }
}