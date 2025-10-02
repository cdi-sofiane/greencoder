<?php

namespace App\Command;

use App\Entity\Account;
use App\Entity\Forfait;
use App\Entity\Order;
use App\Repository\AccountRepository;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use App\Services\AuthorizationService;
use App\Services\MailerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;

class FreePackCleanStorageCommand extends Command
{
    protected static $defaultName = 'freePack:clean';
    private $orderRepository;
    private $userRepository;
    private $output;
    private $mailerService;
    private $accountRepository;

    public function __construct(
        OrderRepository $orderRepository,
        UserRepository $userRepository,
        MailerService $mailerService,
        AccountRepository $accountRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->userRepository = $userRepository;
        $this->accountRepository = $accountRepository;
        parent::__construct();
        $this->mailerService = $mailerService;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $outputStyle = new OutputFormatterStyle('red', 'black', ['bold', 'blink']);
        $outputStyle2 = new OutputFormatterStyle('green', 'black', ['bold', 'blink']);
        $output->getFormatter()->setStyle('fire', $outputStyle);
        $output->getFormatter()->setStyle('good', $outputStyle2);
        $filters['startAt'] = new \DateTimeImmutable('now');
        $filters['roles'] = AuthorizationService::AS_PILOTE;
        $filters['endAt'] = (new \DateTimeImmutable('now'))->modify('- 90 day');

        $account = $this->accountRepository->findAccountWithPilote($filters);
        /**
         * @var Account $account
         */


        $accountWithoutOrder = $this->accountRepository->findAccountWithoutActiveOrder($filters);
        foreach ($accountWithoutOrder as $acco) {
        }

        $removable = [];
        $removable['expiredOrder'] = null;
        foreach ($accountWithoutOrder as $account) {
            /** @var Order $currentOrders */
            $removable[$account->getId()] = false;
            foreach ($account->getOrders() as $currentOrders) {
                //exclude user if ther is an order still actif
                if ($currentOrders->getExpireAt() > $filters['startAt']) {
                    $removable[$currentOrders->getAccount()->getId()] = true;
                }

                if (($currentOrders->getForfait()->getType() === Forfait::TYPE_GRATUIT ||
                        $currentOrders->getForfait()->getNature() === Forfait::NATURE_STOCKAGE) &&
                    $removable[$currentOrders->getAccount()->getId()] === false
                ) {
                    $removable['expiredOrder'][] = $currentOrders;
                }
            }
        }

        /**@var Order $expiredOrder */
        $data = [];
        if ($removable['expiredOrder'] != null) {
            foreach ($removable['expiredOrder'] as $expiredOrder) {
                $account = $expiredOrder->getAccount();
                $pilote = $this->userRepository->findAccountPilote($account);
                $dateToMail = (new \DateTimeImmutable('now'))->format('Y-m-d');
                $dateExpirationplus = $expiredOrder->getExpireAt();
                $data = [
                    'date_expiration' => $dateExpirationplus->format('Y-m-d'),
                    'date_interval' => 0,
                    'subject' => 'Expiration de votre forfait GreenEncoder',
                    'date_removal' => $dateExpirationplus->modify('+ 91 day')->format('Y-m-d')
                ];

                if ($dateExpirationplus->modify('+ 1 day')->format('Y-m-d') == $dateToMail) {
                    $data['date_interval'] = $dateExpirationplus->modify('+ 1 day')->format('Y-m-d');
                    $this->mailerService->sendMail($pilote, MailerService::MAIL_EXPIRED_FREE_ORDER, $data);
                    $output->writeln('<good> + 1 Day =>' . $pilote->getEmail() . '</good>');
                } elseif ($dateExpirationplus->modify('+ 7 day')->format('Y-m-d') == $dateToMail) {
                    $data['date_interval'] = $dateExpirationplus->modify('+ 7 day')->format('Y-m-d');
                    $this->mailerService->sendMail($pilote, MailerService::MAIL_EXPIRED_FREE_ORDER, $data);
                    $output->writeln('<good> + 7 Day =>' . $pilote->getEmail() . '</good>');
                } elseif ($dateExpirationplus->modify('+ 15 day')->format('Y-m-d') == $dateToMail) {
                    $data['date_interval'] = $dateExpirationplus->modify('+ 15 day')->format('Y-m-d');
                    $this->mailerService->sendMail($pilote, MailerService::MAIL_EXPIRED_FREE_ORDER, $data);
                    $output->writeln('<good> + 15 Day =>' . $pilote->getEmail() . '</good>');
                } elseif ($dateExpirationplus->modify('+ 60 day')->format('Y-m-d') == $dateToMail) {
                    $data['date_interval'] = $dateExpirationplus->modify('+ 60 day')->format('Y-m-d');
                    $this->mailerService->sendMail($pilote, MailerService::MAIL_EXPIRED_FREE_ORDER, $data);
                    $output->writeln('<good> + 60 Day =>' . $pilote->getEmail() . '</good>');
                } elseif ($dateExpirationplus->modify('+ 90 day')->format('Y-m-d') == $dateToMail) {
                    $data['date_interval'] = $dateExpirationplus->modify('+ 90 day')->format('Y-m-d');
                    $this->mailerService->sendMail($pilote, MailerService::MAIL_EXPIRED_FREE_ORDER, $data);
                    $output->writeln('<good> + 90 Day =>' . $pilote->getEmail() . '</good>');
                } elseif ($dateExpirationplus->modify('+ 91 day')->format('Y-m-d') == $dateToMail) {
                    $arguments = [
                        '--user' => $pilote,
                        '--isStorage' => true,
                        '--dateEncode' => 0,
                        '--type' => 'encode'
                    ];
                    $command = $this->getApplication()->find('storage:remove');
                    $greetInput = new ArrayInput($arguments);
                    $command->run($greetInput, $output);
                }
            }
        } else {
            $output->writeln('<fire> no expired orders</fire>');
        }
    }
}
