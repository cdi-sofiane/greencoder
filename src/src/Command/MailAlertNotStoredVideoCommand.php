<?php

namespace App\Command;

use App\Entity\Video;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use App\Repository\VideoRepository;
use App\Services\MailerService;
use App\Services\Order\OrderPackage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MailAlertNotStoredVideoCommand extends Command
{
    protected static $defaultName = 'alertMail:send';

    /**
     * @var VideoRepository
     */
    private $videoRepository;
    /**
     * @var MailerService
     */
    private $mailerService;
    /**
     * @var UserRepository
     */
    private $userRepository;
    public function __construct(
        UserRepository  $userRepository,
        MailerService   $mailerService,
        VideoRepository $videoRepository
    ) {
        $this->userRepository = $userRepository;
        $this->mailerService = $mailerService;
        $this->videoRepository = $videoRepository;
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $outputStyle = new OutputFormatterStyle('red', 'black', ['bold', 'blink']);
        $outputStyle2 = new OutputFormatterStyle('green', 'black', ['bold', 'blink']);
        $output->getFormatter()->setStyle('empty', $outputStyle);
        $output->getFormatter()->setStyle('updated', $outputStyle2);
        $filter['isStored'] = false;
        $interval = "+ 1 day  ";
        $listVideos = $this->videoRepository->findNotStoredVideoCloseToExpise($filter, $interval);
        if (empty($listVideos)) {
            $output->writeln('<empty>nothing to send </empty>');

            return;
        }
        $arr = [];
        /**
         * @var Video $video
         */
        foreach ($listVideos as $video) {
            if (($video->getDeletedAt())->modify(' - 12 hour') <= new \DateTimeImmutable('now')) {
                $arr['users'][$video->getUser()->getUuid()]['videos']['12'][] = $video;
            } elseif (($video->getCreatedAt())->modify(' - 24 hour') <= new \DateTimeImmutable('now')) {
                $arr['users'][$video->getUser()->getUuid()]['videos']['24'][] = $video;
            }
        }
        foreach ($arr['users'] as $key => $videos) {
            $user = $this->userRepository->findOneBy(['uuid' => $key]);
            $data['subject'] = 'Attention vos vidéos non stockées vont bientôt être supprimées';
            $data['videos'] = $videos['videos'];
            $arr12hour = isset($videos['videos']['12']) != null ? count($videos['videos']['12']) : 0;
            $arr24hour = isset($videos['videos']['24']) != null ? count($videos['videos']['24']) : 0;
            $data['count'] = $arr12hour + $arr24hour;
            $data['user'] = $this->userRepository->findAccountPilote($user->getAccount());
            $this->mailerService->sendMail($user, MailerService::MAIL_ALERT_DELETE_VIDEO_NOT_STORED, $data);
        }
    }
}
