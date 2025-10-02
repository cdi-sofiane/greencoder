<?php

namespace App\Services\Order;

use App\Entity\Account;
use App\Entity\Forfait;
use App\Entity\Order;
use App\Entity\User;
use App\Form\Dto\DtoOrder;
use App\Repository\AccountRepository;
use App\Repository\ForfaitRepository;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use App\Repository\VideoRepository;
use App\Security\Voter\AccountVoter;
use App\Services\AbstactValidator;
use App\Services\AuthorizationService;
use App\Services\DataFormalizerInterface;
use App\Services\DataFormalizerResponse;
use App\Services\JsonResponseMessage;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Asset\Package;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\Json;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OrderManager extends AbstactValidator
{

    private $validator;
    private $request;
    private $authorizationService;
    private $userRepository;
    private $paginator;
    private $orderPackage;
    private $orderRepository;
    private $forfaitRepository;
    private $dataFormalizer;
    private $accountVoter;
    private $security;
    /**
     * @var SerializerInterface
     */
    private $serializer;
    private $accountRepository;

    public function __construct(
        ValidatorInterface      $validator,
        RequestStack            $request,
        AuthorizationService    $authorizationService,
        UserRepository          $userRepository,
        PaginatorInterface      $paginator,
        OrderPackage            $orderPackage,
        OrderRepository         $orderRepository,
        ForfaitRepository       $forfaitRepository,
        DataFormalizerResponse $dataFormalizer,
        AccountRepository $accountRepository,
        AccountVoter $accountVoter,
        Security $security,
        SerializerInterface     $serializer
    ) {
        $this->validator = $validator;
        $this->authorizationService = $authorizationService;
        $this->userRepository = $userRepository;
        $this->paginator = $paginator;
        $this->orderPackage = $orderPackage;
        $this->orderRepository = $orderRepository;
        $this->request = $request->getCurrentRequest();
        $this->forfaitRepository = $forfaitRepository;
        $this->dataFormalizer = $dataFormalizer;
        $this->serializer = $serializer;
        $this->accountVoter = $accountVoter;
        $this->security = $security;
        $this->accountRepository = $accountRepository;
    }

    public function findall($user = null)
    {
        $filters = $this->request->query->all();
        $order = new Order;
        $filters["account_uuid"] = !empty($this->request->query->get("account_uuid")) != null ? $this->request->query->get("account_uuid") : null;
        $filters["package_uuid"] = !empty($this->request->query->get("package_uuid")) != null ? $this->request->query->get("package_uuid") : null;
        $filters["page"] = !empty($this->request->query->get("page")) != null && $this->request->query->get("page") != 0 ? $this->request->query->getInt("page") : 1;
        $filters["order"] = !empty($this->request->query->get("order")) != null ? $this->request->query->get("order") : 'ASC';
        $filters["limit"] = !empty($this->request->query->get("limit")) != null ? $this->request->query->getInt("limit") : 12;
        $filters["sortBy"] = !empty($this->request->query->get("sortBy")) != null ? $this->request->query->get("sortBy") : null;
        $filters["startAt"] = !empty($this->request->query->get("startAt")) != null ? $this->request->query->get("startAt") : null;
        $filters["endAt"] = !empty($this->request->query->get("endAt")) != null ? $this->request->query->get("endAt") : null;
        $filters["nature"] = !empty($this->request->query->get("nature")) != null ? $this->request->query->get("nature") : null;
        $filters["type"] = !empty($this->request->query->get("type")) != null ? $this->request->query->get("type") : null;

        $filters["expireAt"] = !empty($this->request->query->get("expireAt")) != null ? $this->request->query->get("expireAt") : null;
        $filters["isConsumed"] = $this->request->query->get('isConsumed') != null ? $this->request->query->get('isConsumed') : null;

        $order->setExpireAt($filters["expireAt"])
            ->setIsConsumed($filters["isConsumed"]);

        $err = $this->validator->validate($order, null, ['list_all']);

        if ($err->count() > 0) {
            return $this->err($err);
        }

        $account = $this->accountRepository->findOneBy(['uuid' => $filters['account_uuid']]);

        if (!array_intersect($user->getRoles(), User::ACCOUNT_ADMIN_ROLES)) {

            $this->accountVoter->vote($this->security->getToken(), $account, [AccountVoter::ACCOUNT_ORDER_FIND]);
        }

        $filters['isConsumed'] = $order->getIsConsumed();

        $orderCollection = $this->orderRepository->findFilteredOrder($account, $filters);

        return $this->dataFormalizer->extract($orderCollection, 'list_of_order', true, "order(s) successfuly retrived!", Response::HTTP_OK, $filters);
    }
    /**
     * A modifier lors du passage au self service (security devra passer sur le PILOTE)
     */
    public function subscribeOrder(Account $account = null, Forfait $forfait = null, string $reference = null)
    {

        $body =  json_decode($this->request->getContent(), true);

        if (empty($body)) {
            $targetAccount = $account;
            $targetForfait = $forfait;
        } else {
            $targetAccount = $this->accountRepository->findOneBy(['uuid' => $body['account_uuid']]);
            $targetForfait = $this->forfaitRepository->findOneBy(['uuid' => $body['package_uuid']]);
        }
        if ($targetForfait == null) {
            return (new JsonResponseMessage())->setCode(Response::HTTP_NOT_FOUND)->setError(['Package dont exist or is Inactive']);
        }
        $this->accountVoter->vote($this->security->getToken(), $targetAccount, [AccountVoter::ACCOUNT_ORDER_PACKAGE]);


        $filters = [
            'isConsumed' => false,
            'account' => $account,
            'nature' => Forfait::NATURE_HYBRID
        ];

        $hybrideOrders = $this->orderRepository->findActiveOrder($filters);

        $orders = $this->orderRepository->findBy(['account' => $filters['account']]);

        if (count($orders) >= 1) {
            if ($targetForfait->getNature() == Forfait::NATURE_HYBRID) {
                return (new JsonResponseMessage())->setCode(Response::HTTP_NOT_ACCEPTABLE)->setError(['Can\'t subscribe with this pack !']);
            }
        }

        $filters = [];
        /** verifie si le compte as deja un abonnement de meme nature que le forfait demander  */
        if ($targetForfait->getType() == Forfait::TYPE_ABONNEMENT) {

            $filters = [
                'isConsumed' => false,
                'account' => $targetAccount,
                'type' => Forfait::TYPE_ABONNEMENT,
                'nature' => $targetForfait->getNature()
            ];

            /**
             * @var Order $abonnementOrder
             */
            $abonnementOrders = $this->orderRepository->findActiveOrder($filters);

            if (count($abonnementOrders) >= 1) {
                return (new JsonResponseMessage())->setCode(Response::HTTP_NOT_ACCEPTABLE)->setError(['You already have an active order type Abonnement']);
            }
        }
        /**
         * desactive le forfait gratuit  lors de la demande de souscription
         */
        if ($hybrideOrders != null) {
            foreach ($hybrideOrders as $hybrideOrder) {

                $hybrideOrder->setIsConsumed(true);
                $this->orderRepository->update($hybrideOrder);
            }
            if ($targetForfait->getNature() == Forfait::NATURE_HYBRID) {
                return (new JsonResponseMessage())->setCode(Response::HTTP_NOT_ACCEPTABLE)->setError(['Can\'t subscribe with this pack !']);
            }
        }

        /**
         * initialise de commande de forfait
         */

        $order = $this->orderPackage->orderPack($targetForfait, $targetAccount, $reference);

        if ($order == null) {
            return (new JsonResponseMessage())->setCode(Response::HTTP_UNPROCESSABLE_ENTITY)->setError(['Order was not completed !']);
        }

        return (new JsonResponseMessage())->setContent($order)->setCode(Response::HTTP_CREATED)->setError(['Package subcription  has been completed !']);
    }

    public function orderDisociate($user = null)
    {
        /**@var Order $order */
        $order = $this->orderRepository->findOneBy(['uuid' => $this->request->attributes->get('order_uuid'), 'isConsumed' => false]);


        if ($order == null) {
            return (new JsonResponseMessage())->setCode(Response::HTTP_NOT_FOUND)->setError('Entity not found');
        }
        $account = $this->accountRepository->findOneBy(['uuid' => $order->getAccount()->getUuid()]);

        $this->accountVoter->vote($this->security->getToken(), $account, [AccountVoter::ACCOUNT_ORDER_REMOVE]);
        $body = json_decode($this->request->getContent(), true);
        $filter['isConsumed'] = isset($body['isConsumed']) ? $body['isConsumed'] : '';
        $order->setIsConsumed($filter['isConsumed']);

        $err = $this->validator->validate($order, null, ['consumed']);

        if ($err->count() > 0) {
            return $this->err($err);
        }
        $this->orderPackage->discardOrderPackage($order);
        return (new JsonResponseMessage())->setCode(Response::HTTP_OK)->setError(['Order has been discarded !']);
    }

    public function orderRenewable($user = null)
    {

        $isAuth = $this->authorizationService->check_access($user);
        if ($isAuth != true) {
            return (new JsonResponseMessage())->setCode(Response::HTTP_FORBIDDEN)->setError(['This Action is forbidden for this account!']);
        }


        $targetUser = $this->authorizationService->getTargetUserOrNull($user);

        if (in_array($user->getRoles()[0], User::ACCOUNT_ROLES)) {
            $account = $targetUser->getAccount();
            $order = $this->orderRepository->findOneBy(['uuid' => $this->request->attributes->get('order_uuid'), 'isConsumed' => false, "account" => $account]);
        }
        if (in_array($user->getRoles()[0], User::ACCOUNT_ADMIN_ROLES)) {
            $order = $this->orderRepository->findOneBy(['uuid' => $this->request->attributes->get('order_uuid'), 'isConsumed' => false]);
        }
        if ($order == null) {
            return (new JsonResponseMessage())->setCode(Response::HTTP_NOT_FOUND)->setError('Order(\'s) not found');
        }

        $dtoOrder = $this->serializer->deserialize(
            $this->request->getContent('isRenewable'),
            DtoOrder::class,
            'json'
        );
        $err = $this->validator->validate($dtoOrder, null, ['order:renewable']);

        if ($err->count() > 0) {
            return $this->err($err);
        }
        if ($order->getExpireAt()->modify('- 1 month') <= new \DateTimeImmutable('now')) {
            return (new JsonResponseMessage())->setCode(Response::HTTP_UNPROCESSABLE_ENTITY)->setError('Can\'t modify order anymore ');
        }
        $order->setIsRenewable($dtoOrder->getIsRenewable());
        $order = $this->orderRepository->update($order);

        return $this->dataFormalizer->extract($order, 'order:renewable', false, "order(s) successfuly modified!", Response::HTTP_OK,);
    }

    public function orderSwap($user = null)
    {


        $packageUuid = json_decode($this->request->getContent('package_uuid'), true)['package_uuid'] ?? null;
        $orderUuid = $this->request->attributes->get('order_uuid') ?? null;


        $orderFilters = [
            'uuid' => $orderUuid,
            'isConsumed' => false,
            "beforExpiredAt" => new \DateTimeImmutable('now'),
        ];
        /**
         * @var Order $validOrder
         */
        $validOrder = $this->orderRepository->findActiveOrder($orderFilters);
        if (!$validOrder) {
            throw new Exception("Order not found", Response::HTTP_NOT_FOUND);
        }
        $account = $validOrder[0]->getAccount();

        $this->accountVoter->vote($this->security->getToken(), $account, [AccountVoter::ACCOUNT_ORDER_SWAP]);

        $packFilter = [
            'type' => Forfait::TYPE_ABONNEMENT,
            'nature' => $validOrder[0]->getForfait()->getNature(),
            'uuid' => $packageUuid,
            'isActive' => true,
        ];

        $validPack = $this->forfaitRepository->findOneBy($packFilter);

        if (!$validPack) {

            return (new JsonResponseMessage())->setError('Package is not valid')->setCode(Response::HTTP_NOT_ACCEPTABLE);
        }
        /**
         * @todo a revoir les condition qui permettent le downgrad ,upgrade d'un forfait stockage
         */
        if ($validPack->getNature() == Forfait::NATURE_STOCKAGE) {
            $isSuperiorValue = $validOrder[0]->getBits() > (int) $validPack->getSizeStorage() ? false : true;

            //     if (!$isSuperiorValue) {
            //         return (new JsonResponseMessage)->setCode(Response::HTTP_NOT_ACCEPTABLE)->setError("volume of stored videos superior to package requested");
            //     }
        }

        if ($validPack->getNature() == Forfait::NATURE_ENCODAGE) {
            $isSuperiorValue = $validOrder[0]->getOriginalSeconds() <= $validPack->getDuration() ? true : false;
        }
        switch ($isSuperiorValue) {
            case true:

                $isValid = $this->orderPackage->UpgradeOrder($validOrder[0], $validPack);

                $message = $isValid === true ? 'upgrade successfully' : 'upgrade failed';
                break;
            case false:

                if ($validOrder[0]->getExpireAt()->modify(' - 1 month') <= new \DateTimeImmutable('now')) {
                    $message = "Can't downgrade current order";
                    return (new JsonResponseMessage())->setCode(Response::HTTP_NOT_ACCEPTABLE)->setError($message);
                }
                $isValid = $this->orderPackage->downgradeOrder($validOrder[0], $validPack);
                $message = $isValid === true ? 'downgrade successfully' : 'downgrade failed';
                break;
        }

        return (new JsonResponseMessage())->setCode(Response::HTTP_OK)->setError($message);
    }


    public function canBuyOrder(Account $account, Forfait $forfait)
    {
        if ($forfait->getNature() == Forfait::NATURE_HYBRID) {
            return false;
        }

        /**
         * verifie si le compte as deja un abonnement de meme nature que le forfait demander
         *
         */
        if ($forfait->getType() == Forfait::TYPE_ABONNEMENT) {
            $filters = [
                'isConsumed' => false,
                'account' => $account,
                'type' => Forfait::TYPE_ABONNEMENT,
                'nature' => $forfait->getNature()
            ];

            /**
             * @var Order $abonnementOrder
             */
            $abonnementOrders = $this->orderRepository->findActiveOrder($filters);

            if (count($abonnementOrders) >= 1) {
                return false;
            }
        }
        return true;
    }

    public function removeHybrideOrder(Account $account)
    {
        $filters = [
            'isConsumed' => false,
            'account' => $account,
            'nature' => Forfait::NATURE_HYBRID
        ];

        $hybrideOrders = $this->orderRepository->findActiveOrder($filters);

        if ($hybrideOrders != null) {
            foreach ($hybrideOrders as $hybrideOrder) {

                $hybrideOrder->setIsConsumed(true);
                $this->orderRepository->update($hybrideOrder);
            }
        }
    }
}
