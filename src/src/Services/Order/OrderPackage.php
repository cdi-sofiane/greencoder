<?php

namespace App\Services\Order;

use App\Entity\Account;
use App\Entity\Encode;
use App\Entity\Forfait;
use App\Entity\Order;
use App\Entity\Role;
use App\Entity\User;
use App\Entity\Video;
use App\Repository\AccountRepository;
use App\Repository\OrderRepository;
use App\Repository\UserAccountRoleRepository;
use App\Repository\UserRepository;
use App\Repository\VideoRepository;
use App\Services\AuthorizationService;
use App\Services\Forfait\ForfaitManager;
use Symfony\Component\Form\FormFactoryInterface;

class OrderPackage
{
    public const ORDER_FREE_PACK_DAY = "15 day";
    public const ORDER_EXPIRATION_MONTH = "1 month";
    public const ORDER_EXPIRATION_YEAR = "1 year";
    private $orderRepository;
    private $user;
    private $creditSecond;
    private $creditBits;
    private $hasRessources;
    private $videoRepository;
    private $second_rest;
    private $bits_rest;
    private $userRepository;
    private $account;
    private $accountRepository;
    private $forfaitManager;
    private $userAccountRoleRepository;



    public function __construct(
        OrderRepository $orderRepository,
        VideoRepository $videoRepository,
        AccountRepository $accountRepository,
        UserRepository $userRepository,
        UserAccountRoleRepository $userAccountRoleRepository,
        ForfaitManager $forfaitManager
    ) {
        $this->orderRepository = $orderRepository;
        $this->videoRepository = $videoRepository;
        $this->accountRepository = $accountRepository;
        $this->accountRepository = $accountRepository;
        $this->forfaitManager = $forfaitManager;
        $this->userAccountRoleRepository = $userAccountRoleRepository;
    }

    public function discardOrderPackage(Order $order)
    {
        /* consume an order and maj user credit*/
        /* need to check Nature and Type*/
        /* an order can't be  back to isConsumed == false */

        $this->account = $order->getAccount();

        if ($order->getForfait()->getType() != Forfait::TYPE_GRATUIT) {
            $this->creditBits = $this->account->getCreditStorage() - $order->getBits();
            $this->creditSecond = $this->account->getCreditEncode() - $order->getSeconds();
        }
        $this->majAccountCredit();
    }
    /**
     * When admin encode a stored video for an user it consume only storage credit if user dont have any it return a response
     *
     * @param User $user
     * @param Video $video
     * @return void
     */
    public function  adminCheckOrderCreditForUser(Account $account, Video $video): self
    {

        $this->account = $account;
        $this->creditSecond = 0;
        $this->creditBits = 0;
        $this->hasRessources = true;

        $filters['account'] = $this->account;
        $filters['isConsumed'] = false;
        $filters['isActive'] = null;
        $filters['nextUpdate'] = true;
        $filters['expiredAt'] = (new \DateTimeImmutable('now'));
        /* @var $totalCreditDisponible count   all current active orders credit  with expiredAt >= $current date time */
        $this->verifyAvailableCredit($filters);

        if (($this->hasBits() < $video->getSize() &&
            $video->getIsStored() === true)) {

            $this->hasRessources = false;
            return $this;
        }
        // recherche un forfait dont la nature est differente d'encodage
        $filters['nature'] = Forfait::NATURE_ENCODAGE;
        /** @var  Order actifOrder **/
        $actifOrder = $this->orderRepository->findOrderToSold($filters);

        if ($actifOrder == null) {
            $this->hasRessources = false;
            return $this;
        }

        $this->adminSoldStorageOrder($actifOrder, $video);
        return $this;
    }
    /**
     * Decompt le stokage sur un forfait hybride ou stokage
     *
     * @param [type] $orders
     * @param [type] $video
     * @return void
     */
    public function adminSoldStorageOrder($orders, $video)
    {
        $this->bits_rest = $video->getSize();
        foreach ($orders as $order) {
            $this->decomptStorageOrderCredit($order, $video);
            $this->adminDecomptHybrideStorage($order, $video);
        }
        if ($order->getForfait()->getType() != Forfait::TYPE_GRATUIT) {
            $this->getCurrentSumOfAvailableCredits();
            $this->majAccountCredit();
        }
    }

    public function majAccountCredit(): void
    {
        $this->account->setCreditEncode($this->hasSeconds());
        $this->account->setCreditStorage($this->hasBits());

        $this->accountRepository->add($this->account);
    }

    /**
     * @return int
     */
    public function hasSeconds()
    {
        return $this->creditSecond;
    }

    /**
     * @return int
     */
    public function hasBits()
    {
        return $this->creditBits;
    }


    public function checkOrderCredit($account, $video)
    {

        $this->account = $account;
        $this->creditSecond = 0;
        $this->creditBits = 0;
        $this->hasRessources = true;


        $filters['account'] = $this->account;
        $filters['isConsumed'] = false;
        $filters['isActive'] = null;
        $filters['nextUpdate'] = true;
        $filters['expiredAt'] = (new \DateTimeImmutable('now'));
        /* @var $totalCreditDisponible count   all current active orders credit  with expiredAt >= $current date time */
        $this->verifyAvailableCredit($filters);


        if ($this->hasSeconds() < $video->getDuration()) {
            $this->hasRessources = false;
            return $this;
        }

        if (($this->hasBits() < $video->getSize() &&
            $video->getIsStored() === true)) {
            $this->hasRessources = false;
            return $this;
        }

        /* retrive  all current active orders with nextUpdate >= $current date time and order
        them by nextUpdate ASC meaning put first the one with the closest nextupdate time  */

        $actifOrder = $this->orderRepository->findOrderToSold($filters);

        if ($actifOrder == null) {
            return $this;
        }

        $this->soldOrders($actifOrder, $video);
        return $this;
    }
    /**
     * allow unstored video to be stored , deduce storage size from account if there is enougt storage credit
     *
     * @param Video $video
     * @return void
     */
    public function keepUnstoredVideo(Video $video)
    {
        $filters['account'] = $this->account;
        $filters['isConsumed'] = false;
        $filters['isActive'] = null;
        $filters['nextUpdate'] = true;
        $filters['nextUpdate'] = true;
        $filters['nature'] = Forfait::NATURE_STOCKAGE;
        $filters['expiredAt'] = (new \DateTimeImmutable('now'));
        $actifOrder = $this->orderRepository->findOrderToSold($filters);

        if ($actifOrder == null) {
            return $this;
        }

        $this->soldOrders($actifOrder, $video);
        return $this;
    }

    /**
     * count la totaliter des credit encodage ou stockage disponible pour l utilisateur est les set dans les attributs
     * creditSecond ,creditBits
     * met a jour les info de credit de l'utilisateur concerner
     */
    public function verifyAvailableCredit($filters)
    {
        //find if there is enough credit to procced encodage or encodage + storage
        $totalCreditDisponible = $this->orderRepository->totalCreditAvailable($filters);

        $this->creditSecond = $totalCreditDisponible['totalEncode'];
        $this->creditBits = $totalCreditDisponible['totalStorage'];

        return $this;
    }
    /**
     * Undocumented function
     * @param $second_rest dureer de la video a deduire
     * @param $bits_rest size de la video a deduire
     * @param [type] $actifOrder
     * @param Video $video
     * @return void
     */
    private function soldOrders($actifOrder, Video $video)
    {
        // prepare le compteur de ressourse d'une video (ce qui sera deduis sur le orders (commandes)

        if (count($actifOrder) > 1) {
            $orderPriority = [];

            //organise les ordres par niveau de consomation (le quel sera solder en premier lors de l'encodage)
            /** @var Order $order */
            foreach ($actifOrder as $order) {
                if ($order->getForfait()->getType() === Forfait::TYPE_GRATUIT) {
                    $order->setIsConsumed(true);
                    $this->orderRepository->update($order);
                    $this->checkOrderCredit($order->getAccount(), $video);
                }
                $orderPriority[] = $order;
            }

            $this->second_rest = $video->getDuration();
            $this->bits_rest = $video->getSize();
            foreach ($orderPriority as $ordonedOrders) {
                $this->creditOrder($ordonedOrders, $video);
            }
            $filters['account'] = $order->getAccount();
            $filters['isConsumed'] = false;
            $filters['isActive'] = null;
            $filters['expiredAt'] = (new \DateTimeImmutable('now'));
            $this->verifyAvailableCredit($filters);
            $this->majAccountCredit();
            /**@var User $user */
            return $this;
        } else {
            /**@var Video $video */

            foreach ($actifOrder as $order) {
                $this->creditOrder($order, $video);
                /**@var Order $order */
                if ($order->getForfait()->getNature() != Forfait::NATURE_HYBRID) {
                    $this->majAccountCredit();
                }
            }
        }
    }

    private function creditOrder($order, Video $video)
    {

        $this->decomptEncodageOrderCredit($order, $video);
        $this->decomptStorageOrderCredit($order, $video);
        $this->decomptHybrideOrderCredit($order, $video);
    }


    private function decomptEncodageOrderCredit($order, $video)
    {
        if ($order->getForfait()->getNature() === Forfait::NATURE_ENCODAGE) {
            $order->setBits(0);
            $this->second_rest = $order->getSeconds() - $this->second_rest;

            $order->setSeconds($this->second_rest);
            if ($this->second_rest < 0) {
                $this->second_rest = -$this->second_rest;
                $order->setSeconds(0);
            } else {
                $this->second_rest = 0;
            }
            $this->orderRepository->update($order);
        }
    }

    private function decomptHybrideOrderCredit($order, $video)
    {
        if ($order->getForfait()->getNature() === Forfait::NATURE_HYBRID) {
            $this->creditSecond = $order->getSeconds() - $video->getDuration();
            $this->creditBits = $order->getBits();
            $order->setSeconds($this->creditSecond);
            if ($video->getIsStored() === true) {
                $this->creditBits = $order->getBits() - $video->getSize();
                $order->setBits($this->creditBits);
            }
            $this->orderRepository->update($order);
        }
    }
    private function decomptStorageOrderCredit(Order $order, Video $video)
    {


        if ($order->getForfait()->getNature() === Forfait::NATURE_STOCKAGE) {
            $order->setSeconds(0);

            if ($video->getIsStored() === true) {
                $this->bits_rest = $order->getBits() - $this->bits_rest;

                $order->setBits($this->bits_rest);
                if ($this->bits_rest < 0) {
                    $this->bits_rest = -$this->bits_rest;
                    $order->setBits(0);
                } else {
                    $this->bits_rest = 0;
                }
            }
            $this->orderRepository->update($order);
        }
    }

    private function adminDecomptHybrideStorage($order, $video)
    {
        if ($video->getIsStored() === true) {
            if ($order->getForfait()->getNature() == Forfait::NATURE_HYBRID) {
                $this->creditBits = $order->getBits() - $video->getSize();
                $order->setBits($this->creditBits);
            }
            $this->orderRepository->update($order);
        }
    }

    private function AddStorageOrderCredit(Order $order, $videosSize)
    {
        if ($order->getForfait()->getNature() === Forfait::NATURE_STOCKAGE) {
            $order->setSeconds(0);
            $this->bits_rest = $order->getBits() + $videosSize;
            $order->setBits($this->bits_rest);
            $this->orderRepository->update($order);
        }
    }

    /**
     * @param Order $order
     * @return bool
     */
    public function renewOrder(Order $order): bool
    {
        $this->account = $order->getAccount();
        if ($order->getIsConsumed() === true && $order->getForfait()->getType() == Forfait::TYPE_ABONNEMENT) {

            if ($order->getSubscriptionPlan() == Order::DOWNGRADE) {
                $this->orderPack($order->getNextForfait(), $this->getAccount());
            } else {

                $this->orderPack($order->getForfait(), $this->getAccount());
            }
            return true;
        }
        return false;
    }

    /**
     * @param Forfait $forfait
     * @param  Account $account
     */
    public function orderPack(Forfait $forfait, $account, string $reference = null)
    {
        $this->account = $account;

        $currentStorageLeft  = $this->getCurrentStorageCapacityOrZero($forfait);

        /**
         * @var UserAccountRole
         */
        $targetUser = $this->FindAdminAccountRole();

        if ($targetUser == null) {
            return false;
        }


        if (in_array($targetUser->getRole()->getCode(), [User::USER_ACCOUNT_ADMIN_ROLE])) {
            $days = $this->definePeriod($forfait);
            $this->user = $targetUser->getUser();
            $order =  new Order();
            $order->setAccount($account)
                ->setForfait($forfait)
                ->setBits($currentStorageLeft)
                ->setSeconds($forfait->getDuration())
                ->setOriginalSeconds($forfait->getDuration())
                ->setOriginalBits($forfait->getSizeStorage())
                ->setIsConsumed(false)
                ->setReference($reference)
                ->setIsRenewable(!($forfait->getType() == Forfait::TYPE_GRATUIT))
                ->setNextUpdate((new \DateTimeImmutable('now'))->modify($days === self::ORDER_FREE_PACK_DAY ? self::ORDER_FREE_PACK_DAY : self::ORDER_EXPIRATION_MONTH))
                ->setExpireAt((new \DateTimeImmutable('now'))->modify('+' . $days));
            if ($forfait->getType() == Forfait::TYPE_CREDIT) {
                $order->setNextUpdate((new \DateTimeImmutable('now'))->modify(self::ORDER_EXPIRATION_YEAR));
            }


            $order = $this->orderRepository->create($order);
            $this->getCurrentSumOfAvailableCredits();
            $this->majAccountCredit();
            return $order;
        }
    }

    public function definePeriod(Forfait $forfait)
    {
        switch ($forfait->getType()) {
            case Forfait::TYPE_GRATUIT:
                return self::ORDER_FREE_PACK_DAY;
            case Forfait::TYPE_ABONNEMENT:
            case Forfait::TYPE_CREDIT:

                return self::ORDER_EXPIRATION_YEAR;
        }
    }

    public function getCurrentSumOfAvailableCredits(): self
    {

        $filters['account'] = $this->getAccount();
        $filters['nature'] = Forfait::NATURE_HYBRID;
        $filters['isConsumed'] = false;
        $totalCreditDisponible = $this->orderRepository->totalCreditAvailable($filters);

        $this->creditSecond = $totalCreditDisponible['totalEncode'];
        $this->creditBits = $totalCreditDisponible['totalStorage'];

        $this->getAccount()->setCreditStorage($this->hasBits());
        $this->getAccount()->setCreditEncode($this->hasSeconds());

        return $this;
    }


    public function getAccount()
    {
        return $this->account;
    }

    public function setAccount($account): self
    {
        $this->account = $account;
        return $this;
    }
    public function downgradeOrder(Order $currentOrder, Forfait $nextForfait): bool
    {
        $currentOrder->setSubscriptionPlan(Order::DOWNGRADE);
        $currentOrder->setNextForfait($nextForfait);
        $currentOrder->setIsRenewable(true);
        $this->orderRepository->update($currentOrder);
        return true;
    }

    /**
     * @param Order $currentOrder le forfait current
     */
    public function UpgradeOrder(Order $currentOrder, Forfait $nextForfait): bool
    {
        $this->consumedOrder($currentOrder);

        $currentOrder->setSubscriptionPlan(Order::UPGRADE);
        $currentOrder->setNextForfait($nextForfait);

        $this->getCurrentSumOfAvailableCredits();

        $this->saveOrder($currentOrder, $nextForfait);


        return true;
    }

    public function saveOrder(Order $currentOrder, Forfait $forfait)
    {
        $this->account = $currentOrder->getAccount();
        $targetUser = $this->FindAdminAccountRole();

        if (!array_intersect($targetUser->getRole(), User::USER_ACCOUNT_ADMIN_ROLE)) {
            return;
        }
        $days = $this->definePeriod($forfait);
        $new_order = new Order();

        $currentConsumedBits = $currentOrder->getOriginalBits() - $currentOrder->getBits();

        $new_order->setForfait($forfait)
            ->setAccount($this->getAccount())
            ->setBits(($forfait->getSizeStorage() - $currentConsumedBits))
            ->setSeconds($forfait->getDuration())
            ->setOriginalSeconds($forfait->getDuration())
            ->setOriginalBits($forfait->getSizeStorage())
            ->setIsConsumed(false)
            ->setIsRenewable(!($forfait->getType() == Forfait::TYPE_GRATUIT))
            ->setCreatedAt($currentOrder->getCreatedAt())
            ->setNextUpdate((new \DateTimeImmutable('now'))->modify($days === self::ORDER_FREE_PACK_DAY ? self::ORDER_FREE_PACK_DAY : self::ORDER_EXPIRATION_MONTH))
            ->setUpdatedAt(new \DateTimeImmutable('now'))
            ->setExpireAt($currentOrder->getExpireAt());

        $this->orderRepository->create($new_order);
        $this->getCurrentSumOfAvailableCredits();
        $this->accountRepository->add($this->getAccount());
        return $new_order;
    }

    public function consumedOrder(Order $order): self
    {
        $this->account = $order->getAccount();
        $order->setIsConsumed(true);

        $this->orderRepository->update($order);
        return $this;
    }

    /**
     * give back used credit to current actif Abonnement if exist
     * @infos  user from video
     * @var Encode|Video $orginalVideo
     */
    public function giveBackCreditForFullyRemovedStoredVideos($orginalVideo, $args = null): bool
    {

        $this->account = $orginalVideo->getAccount();
        $args = [
            'video' => $orginalVideo,
            'isStored' => true,
            'isDeleted' => true
        ];
        $video = $this->videoRepository->findVideos($this->getAccount(), $args);

        if ($video == null) {
            return false;
        }

        $orderArgs = [
            'nature' => Forfait::NATURE_STOCKAGE,
            'isConsumed' => false
        ];
        $orders = $this->orderRepository->findFilteredOrder($orginalVideo->getAccount(), $orderArgs);

        if ($orders == null) {
            return false;
        }
        $order = $orders[0];
        $currentOrderBits = $order->getBits() + $orginalVideo->getSize();

        $order->setBits($currentOrderBits);
        $this->orderRepository->update($order);
        $this->getCurrentSumOfAvailableCredits();

        $this->majAccountCredit();
        return true;
    }

    public function giveBackCreditEncodeForFullyRemovedVideos(Video $orginalVideo, $args = null)
    {
        $this->account = $orginalVideo->getAccount();
        $args = [
            'video' => $orginalVideo,
            'isDeleted' => true
        ];
        $video = $this->videoRepository->findVideos($this->getAccount(), $args);

        if ($video == null) {
            return false;
        }
        $orderArgs = [
            'nature' => Forfait::NATURE_ENCODAGE,
            'isConsumed' => false
        ];
        /**
         * @var Order $order
         */
        $orders = $this->orderRepository->findFilteredOrder($orginalVideo->getAccount(), $orderArgs);
        if ($orders == null) {
            return false;
        }
        $order = $orders[0];

        $currentOrderBits = $order->getSeconds() + $orginalVideo->getDuration();
        $order->setSeconds($currentOrderBits);
        $this->orderRepository->update($order);
        $this->getCurrentSumOfAvailableCredits();
        $this->accountRepository->add($this->getAccount());
        return true;
    }

    public function hasRessources()
    {
        return $this->hasRessources;
    }

    /**
     * generate order reference
     *
     * @return string
     */
    public function getReference(): string
    {
        $count = $this->orderRepository->countOrderPerDay();

        $pr_id = sprintf("%03d", strval(intval($count['1']) + 1));
        $reference = date("ymd") . strtoupper($pr_id);
        return $reference;
    }
    /**
     * return User $user retourne un utilisateur d'un account avec le accountRole admin
     */
    public function findAdminAccountRole()
    {
        return $this->userAccountRoleRepository->findAccountOwner($this->account);
    }

    /**
     * re calcule la capaciter actuel de storage
     */
    public function getActualStorageSize(Forfait $forfait)
    {
        if ($forfait->getNature() == Forfait::NATURE_STOCKAGE) {
            return   $this->videoRepository->countStoredVideosSize($this->getAccount());
        }
    }

    public function findActiveStorageOrderForUser($account)
    {
        $filters = [
            'type' => Forfait::TYPE_ABONNEMENT,
            'nature' => Forfait::NATURE_STOCKAGE,
            'isConsumed' => false,
            'account' => $account
        ];

        return $this->orderRepository->findActiveOrder($filters);
    }

    public function findActiveEncodageOrderForUser($account)
    {
        $filters = [
            'type' => Forfait::TYPE_ABONNEMENT,
            'nature' => Forfait::NATURE_ENCODAGE,
            'isConsumed' => false,
            'account' => $account
        ];
        /**
         * @var Order $currentEncodageOrder
         */
        return $this->orderRepository->findActiveOrder($filters);
    }

    public function addPackageToAccount(Account $account): void
    {
        $filter['isAutomatic'] = true;
        $filter['isActive'] = true;
        $filter['isEntreprise'] = false;
        $filter['type'] = Forfait::TYPE_GRATUIT;

        $freePackage = $this->forfaitManager->findOne(null, $filter);
        if ($freePackage != null) {
            $this->orderPack($freePackage, $account);
        }
    }

    private function getCurrentStorageCapacityOrZero($forfait)
    {
        $countCurrentlyInStorage = $this->getActualStorageSize($forfait);
        if ($countCurrentlyInStorage == 0) {
            return $forfait->getSizeStorage();
        }
        $usableStorage = $forfait->getSizeStorage() - $countCurrentlyInStorage;

        $usableStorage = $usableStorage <= 0 ? 0 : $usableStorage;
        return $usableStorage;
    }


    public function checkStorage(Account $account, $videos): bool
    {
        if (empty($videos)) {
            return true;
        }

        $this->account = $account;
        $this->creditSecond = 0;
        $this->creditBits = 0;
        $this->hasRessources = true;
        $folderSize = 0;

        $filters['account'] = $this->account;
        $filters['isConsumed'] = false;
        $filters['isActive'] = null;
        $filters['nextUpdate'] = true;
        $filters['expiredAt'] = (new \DateTimeImmutable('now'));
        /* @var $totalCreditDisponible count   all current active orders credit  with expiredAt >= $current date time */
        $this->verifyAvailableCredit($filters);

        foreach ($videos as $video) {
            if ($video->getIsStored()) {
                $folderSize += $video->getSize();
            }
        }

        if ($this->hasBits() < $folderSize) {
            $this->hasRessources = false;
            return false;
        }
        // recherche un forfait dont la nature est differente d'encodage
        $filters['nature'] = Forfait::NATURE_ENCODAGE;
        /** @var  Order actifOrder **/
        $actifOrder = $this->orderRepository->findOrderToSold($filters);

        if ($actifOrder == null) {
            $this->hasRessources = false;
            return false;
        }

        return true;
    }

    public function soldSotrage($account, $videos)
    {
        $filters['account'] = $account;
        $filters['isConsumed'] = false;
        $filters['isActive'] = null;
        $filters['nextUpdate'] = true;
        $filters['expiredAt'] = (new \DateTimeImmutable('now'));
        $filters['nature'] = Forfait::NATURE_ENCODAGE;

        /** @var  Order actifOrder **/
        $actifOrder = $this->orderRepository->findOrderToSold($filters);

        if ($actifOrder == null) {
            $this->hasRessources = false;
            return false;
        }

        foreach ($videos as $video) {
            $this->adminSoldStorageOrder($actifOrder, $video);
        }
        return true;
    }


    /**
     * give back used credit to current actif Abonnement if exist
     * @infos  user from video
     * @var Encode|Video $orginalVideo
     */
    public function giveBackAccountCredit($video): bool
    {

        $this->account = $video->getAccount();
        if (!$video->getIsStored()) {
            return false;
        }

        $orderArgs = [
            'nature' => Forfait::NATURE_STOCKAGE,
            'isConsumed' => false
        ];
        $orders = $this->orderRepository->findFilteredOrder($video->getAccount(), $orderArgs);

        if ($orders == null) {
            return false;
        }
        $order = $orders[0];
        $currentOrderBits = $order->getBits() + $video->getSize();

        $order->setBits($currentOrderBits);
        $this->orderRepository->update($order);
        $this->getCurrentSumOfAvailableCredits();

        $this->majAccountCredit();
        return true;
    }
}
