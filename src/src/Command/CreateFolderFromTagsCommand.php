<?php

namespace App\Command;

use App\Entity\Video;
use App\Entity\Tags;
use App\Entity\Folder;
use App\Repository\AccountRepository;
use App\Repository\VideoRepository;
use App\Repository\TagsRepository;
use App\Repository\FolderRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreateFolderFromTagsCommand extends Command
{
    protected static $defaultName = 'tag:folder:create';
    protected $videoRepository;
    protected $accountRepository;
    protected $tagsRepository;
    protected $folderRepository;
    protected $validator;
    public $em;
    private $output;


    public function __construct(VideoRepository        $videoRepository,
                                AccountRepository      $accountRepository,
                                TagsRepository         $tagsRepository,
                                FolderRepository       $folderRepository,
                                ValidatorInterface     $validator,
                                EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->videoRepository = $videoRepository;
        $this->accountRepository = $accountRepository;
        $this->tagsRepository = $tagsRepository;
        $this->folderRepository = $folderRepository;
        $this->validator = $validator;
        parent::__construct();

    }

    protected function configure(): void
    {
        $this
            // ...
            ->addOption('tag-id', null, InputOption::VALUE_OPTIONAL, 'the id of tag used to create folders ')
            ->addOption('account-uuid', null, InputOption::VALUE_OPTIONAL, 'the uuid of account used to create folders ');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $outputStyle = new OutputFormatterStyle('red', 'black', ['bold', 'blink']);
        $outputStyle2 = new OutputFormatterStyle('green', 'black', ['bold', 'blink']);
        $output->getFormatter()->setStyle('fire', $outputStyle);
        $output->getFormatter()->setStyle('good', $outputStyle2);

        $accountUuid = $input->getOption('account-uuid') != null ? $input->getOption('account-uuid') : null;
        $tagId = $input->getOption('tag-id') != null ? $input->getOption('tag-id') : null;


        if ($accountUuid){
            $this->output->writeln($date . '| error | <fire> the CLI must have one parametre, you can use --tag-uuid or --account-uuid </fire>');
        }elseif ($tagId){
            $this->createByTag($tagId);
        }else{
            $date = (new \DateTimeImmutable('now'))->format("y-m-d H:m:s");
            $this->output->writeln($date . '| error | <fire> the CLI must have one parametre, you can use --tag-uuid or --account-uuid </fire>');
        }

    }


    public function createByTag($id)
    {

        // trouver la liste des videos avec un tag
        $initTag = $this->tagsRepository->find($id);
        $videos = $this->videoRepository->findVideosByTag($initTag);

        //chercher l'account relatif a ses videos
        $account = $this->accountRepository->findAccountByVideo($videos[0]);
        //initialiser les tableau des tags et folders
        $taglist = [];
        $folders = [];
        
        foreach($videos as $video)
        {
            // la valeur de i c'est le niveau du dossier ou du tag
            $i = 0;
            // valeur du dernier tag traiter
            $lastTag = null;
            foreach($video->getTags() as $tag)
            {
                if(!array_key_exists($i, $taglist)){
                    $taglist [$i] = [];
                    $folders [$i] = [];
                }
                
                if( !in_array($tag->getTagName(), $taglist[$i]) ){
                    
                    $taglist[$i][] = $tag->getTagName();
                    
                    if($i < 3){

                        $parrentFolder = null;

                        if($i > 0){
                            $j = array_search($lastTag, $taglist[$i - 1]);
                            $parrentFolder = $folders[$i - 1][$j];
                        }

                        // cree le dossier avec les bonnes valeurs et garder le tag utiliser et le niveau pour l'utiliser dans l'assigniation des video dans les dossiers
                        $folders[$i][] = $this->buildFolder($account, $parrentFolder, $tag->getTagName());
                        $niveau = $i;

                    }//else{
                        // $tag->setIsFolder(true);
                        // $tag->setFolderOrder($i);
                    // }

                    
                }
                $lastTag = $tag->getTagName();

                $i ++;

            }

            // assigner la video au bon dossier
            $j = array_search($lastTag, $taglist[$niveau]);
            $folders[$niveau][$j]->addVideo($video);
        }

        // enregitrer les modif
        foreach($folders as $niveau){
            foreach($niveau as $folder){
                $this->em->persist($folder);
                foreach($folder->getVideos() as $video){
                    $this->em->persist($video);
                    // foreach($video->getTags() as $tag){
                        // $this->em->persist($tag);
                    // }
                }
                
            }
        }
        $this->em->flush();
        $date = (new \DateTimeImmutable('now'))->format("y-m-d H:m:s");

        $this->output->writeln($date . ' | <good>The Folder for tag '. $initTag->getTagName() .' have been successfully created !</good>');

    }

    private function buildFolder($account, $parrentFolder = null, $name)
    {
        $folder = new Folder();
        $folder->setUuid('');
        $folder->setIsInTrash(0);
        $folder->setAccount($account);
        $folder->setParentFolder($parrentFolder);
        $folder->setName($name);

        $folder->setCreatedBy($account->getEmail());

        $err = $this->validator->validate($folder, null, 'folder:create');

        if ($err->count() > 0) {

            return $this->err($err);
        }

        return $folder;
    }


}