<?php

namespace App\Command;


use App\Entity\Forfait;
use App\Entity\Order;
use App\Entity\User;
use App\Repository\AccountRepository;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use App\Services\AuthorizationService;
use App\Services\MailerService;
use App\Services\Order\OrderPackage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;

class OrderRenewerCommand extends Command
{
    protected static $defaultName = 'order:update';
    private $orderRepository;
    private $userRepository;
    private $accountRepository;
    private $output;
    private $mailerService;
    /**
     * @var OrderPackage
     */
    private $orderPackage;

    public function __construct(
        OrderRepository $orderRepository,
        UserRepository  $userRepository,
        MailerService   $mailerService,
        AccountRepository   $accountRepository,
        OrderPackage $orderPackage
    ) {
        $this->orderRepository = $orderRepository;
        $this->userRepository = $userRepository;
        parent::__construct();
        $this->mailerService = $mailerService;
        $this->accountRepository = $accountRepository;
        $this->orderPackage = $orderPackage;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {

        $outputStyle = new OutputFormatterStyle('red', 'black', ['bold', 'blink']);
        $outputStyle2 = new OutputFormatterStyle('green', 'black', ['bold', 'blink']);
        $output->getFormatter()->setStyle('expired', $outputStyle);
        $output->getFormatter()->setStyle('updated', $outputStyle2);
        $filters = [
            'isConsumed' => false
        ];
        $orderToUpdate = $this->orderRepository->findActiveOrder($filters);


        $tatalUserCredit = [];
        $tatalAccountCredit = [];
        $listUsers = [];
        $listAccounts = [];
        /**
         * @var Order $order
         */
        foreach ($orderToUpdate as $order) {
            $AccountOrder = $order->getAccount();
            $listAccounts[] = $AccountOrder;
            $orderUpdatedDate = ($order->getUpdatedAt())->format('y-m-d');
            $curentDate = (new \DateTimeImmutable('now'))->format('y-m-d');
            $orderExpiredDate = ($order->getExpireAt())->format('y-m-d');
            $orderNextUpdateDate = $order->getNextUpdate() != null ? ($order->getNextUpdate())->format('y-m-d') : $orderExpiredDate;

            $upddatedOrder =
                '| email : ' . $order->getAccount()->getEmail() .
                '| username : ' . $order->getAccount()->getName() .
                '| order uuid : ' . $order->getUuid() .
                '| order nature : ' . $order->getForfait()->getNature() .
                '| order Type : ' . $order->getForfait()->getType() .
                '| expiration date : ' . $orderExpiredDate .
                '| next update date : ' . $orderNextUpdateDate;
            if ($curentDate >= $orderExpiredDate) {

                $order->setUpdatedAt(new \DateTimeImmutable('now'));
                $order->setIsConsumed(true);
                $this->orderRepository->update($order);
                if ($order->getIsRenewable()) {

                    $this->orderPackage->renewOrder($order);
                }
                $output->writeln('<expired>' . $upddatedOrder . '</expired>');
                continue;
            }

            if ($orderNextUpdateDate <= $curentDate) {


                if ($orderNextUpdateDate < $orderExpiredDate) {
                    $tatalUserCredit[$order->getAccount()->getUuid()] = [];
                    $order->setUpdatedAt(new \DateTimeImmutable('now'));
                    if ($order->getOriginalBits() == null) {
                        $order->setOriginalBits($order->getForfait()->getSizeStorage());
                    }
                    if ($order->getOriginalSeconds() == null) {
                        $order->setOriginalSeconds($order->getForfait()->getDuration());
                    }
                    $this->orderRepository->update($order);
                    $this->orderPackage
                        ->setAccount($AccountOrder)
                        ->majAccountCredit();
                    /**@var Order $order */
                    $orderBits = $order->getOriginalBits() - $this->orderPackage->getActualStorageSize($order->getForfait());
                    $orderSec = $order->getOriginalSeconds() != null ? $order->getOriginalSeconds() : $order->getSeconds();
                    $order->setBits($orderBits <= 0 ? 0 : $orderBits);
                    $order->setSeconds($orderSec);
                    $order->setNextUpdate((new \DateTimeImmutable('now'))->modify('+' . OrderPackage::ORDER_EXPIRATION_MONTH));
                    $this->orderRepository->update($order);
                    $output->writeln('<updated>' . $upddatedOrder . '</updated>');
                }
            }
        }

        /**
         * @var User $user
         */
        foreach ($listAccounts as $account) {
            $filters['account'] = $account;
            $filters['nature'] = Forfait::NATURE_HYBRID;
            $totalCreditDisponible = $this->orderRepository->totalCreditAvailable($filters);

            $account->setCreditStorage($totalCreditDisponible['totalStorage']);
            $account->setCreditEncode($totalCreditDisponible['totalEncode']);
            $this->accountRepository->add($account);
        }
    }
}
