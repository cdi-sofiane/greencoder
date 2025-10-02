<?php

namespace App\Services\Users;

use App\Entity\Account;
use App\Entity\Right;
use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserAccountRole;
use App\Entity\Video;
use App\Form\Dto\DtoChangeRole;
use App\Interfaces\Videos\VideosCollectionHandlerInterface;
use App\Repository\AccountRepository;
use App\Repository\ForfaitRepository;
use App\Repository\UserRepository;
use App\Repository\VideoRepository;
use App\Security\Voter\AccountVoter;
use App\Security\Voter\UserVoter;
use App\Services\AbstactValidator;
use App\Services\Account\AccountManager;
use App\Services\ApiKeyService;
use App\Services\AuthorizationService;
use App\Services\Consumption\ConsumptionManager;
use App\Services\DataFormalizerResponse;
use App\Services\Folder\FolderManager;
use App\Services\Forfait\ForfaitManager;
use App\Services\JsonResponseMessage;
use App\Services\Order\OrderPackage;
use App\Services\Permission\Account\AccountRoleRightService;
use App\Services\Permission\Account\UserAccountRolesService;
use App\Services\Video\VideoManager;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class UserManager extends AbstactValidator
{
    private $validator;
    private $paginator;
    private $userRepository;
    private $request;
    private $dataFormalizer;
    private $videoRepository;
    private $orderPackage;
    private $consumptionManager;
    private $authorizationService;

    /**
     * @var ApiKeyService
     */
    private $apiKeyService;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var AccountRepository
     */
    private $accountReporitory;

    /**
     * @var ForfaitManager
     */
    private $forfaitManager;

    /**
     * @var AccountManager
     */
    private $accountManager;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    private $forfaitRepository;
    private $accountVoter;
    private $security;
    private $userAccountRolesService;
    private $accountRoleRightService;

    private $userVoter;
    private $folderManager;
    private $videoManager;
    private $videoHandler;


    public function __construct(
        RequestStack          $request,
        UserRepository        $userRepository,
        ValidatorInterface    $validator,
        PaginatorInterface    $paginator,
        DataFormalizerResponse $dataFormalizer,
        VideoRepository       $videoRepository,
        OrderPackage          $orderPackage,
        ConsumptionManager    $consumptionManager,
        ApiKeyService         $apiKeyService,
        AuthorizationService  $authorizationService,
        SerializerInterface   $serializer,
        AccountRepository   $accountReporitory,
        ForfaitRepository     $forfaitRepository,
        ForfaitManager        $forfaitManager,
        AccountManager        $accountManager,
        AccountVoter        $accountVoter,
        Security        $security,
        UserAccountRolesService        $userAccountRolesService,
        AccountRoleRightService        $accountRoleRightService,
        EntityManagerInterface $entityManager,
        FolderManager $folderManager,
        VideoManager $videoManager,
        VideosCollectionHandlerInterface $videoHandler,
        UserVoter   $userVoter
    ) {
        $this->validator = $validator;
        $this->paginator = $paginator;
        $this->userRepository = $userRepository;
        $this->dataFormalizer = $dataFormalizer;
        $this->request = $request->getCurrentRequest();
        $this->videoRepository = $videoRepository;
        $this->orderPackage = $orderPackage;
        $this->consumptionManager = $consumptionManager;
        $this->authorizationService = $authorizationService;
        $this->apiKeyService = $apiKeyService;
        $this->serializer = $serializer;
        $this->accountReporitory = $accountReporitory;
        $this->forfaitRepository = $forfaitRepository;
        $this->forfaitManager = $forfaitManager;
        $this->accountManager = $accountManager;
        $this->entityManager = $entityManager;
        $this->accountVoter = $accountVoter;
        $this->userAccountRolesService = $userAccountRolesService;
        $this->security = $security;
        $this->accountRoleRightService = $accountRoleRightService;
        $this->userVoter = $userVoter;
        $this->folderManager = $folderManager;
        $this->videoManager = $videoManager;
        $this->videoHandler = $videoHandler;
    }


    public function findAll($loggedUser)
    {
        $user = new User();
        $filters = $this->request->query->all();
        $filters["page"] = !empty($this->request->query->get("page")) != null && $this->request->query->get("page") != 0 ? $this->request->query->getInt("page") : 1;
        $filters["limit"] = !empty($this->request->query->get("limit")) != null ? $this->request->query->getInt("limit") : 12;
        $filters["sortBy"] = !empty($this->request->query->get("sortBy")) != null ? $this->request->query->get("sortBy") : null;
        $filters["order"] = !empty($this->request->query->get("order")) != null ? $this->request->query->get("order") : "ASC";
        $filters["startAt"] = !empty($this->request->query->get("startAt")) != null ? $this->request->query->get("startAt") : null;
        $filters["endAt"] = !empty($this->request->query->get("endAt")) != null ? $this->request->query->get("endAt") : null;

        $filters["role"] = !empty($this->request->query->get("role")) != null ? $this->request->query->get("role") : null;
        $filters["search"] = !empty($this->request->query->get("search")) != null ? $this->request->query->get("search") : null;
        $filters["isActive"] = $this->request->query->get('isActive') != null ? $this->request->query->get('isActive') : null;
        $filters["isArchive"] = $this->request->query->get('isArchive') != null ? $this->request->query->get('isArchive') : null;
        $filters["isDelete"] = $this->request->query->get('isDelete') != null ? $this->request->query->get('isDelete') : null;
        $filters["isConditionAgreed"] = $this->request->query->get('isConditionAgreed') != null ? $this->request->query->get('isConditionAgreed') : null;

        $filters["sortBy"] = $filters["sortBy"] == 'date' ? 'createdAt' : 'email';

        $user->setCreatedAt($filters['startAt']);
        $user->setCreatedAt($filters['endAt']);
        $user->setIsActive($filters['isActive']);
        $user->setIsArchive($filters['isArchive']);
        $user->setIsDelete($filters['isDelete']);
        $user->setIsConditionAgreed($filters['isConditionAgreed']);


        $err = $this->validator->validate($user, null, ['filters']);

        if (count($err) > 0) {
            return $this->err($err);
        }
        $filters["isConditionAgreed"] = $user->getIsConditionAgreed();
        $filters["isActive"] = $user->getIsActive();
        $filters["isArchive"] = $user->getIsArchive();
        $filters["isDelete"] = $user->getIsDelete();
        $filters['user'] = $user;

        $usersCollection = $this->userRepository->findUsersWithFilters($filters);


        return $this->dataFormalizer->extract($usersCollection, 'list_users', true, "user(s) successfuly retrived!", Response::HTTP_OK, $filters);
    }

    public function getAccountMembres($loggedUser)
    {

        $account = $this->accountReporitory->findOneBy(['uuid' => $this->request->query->get('account_uuid')]);
        $this->accountVoter->vote($this->security->getToken(), $account, [AccountVoter::ACCOUNT_FIND_USERS]);


        $user = new User();
        $filters = $this->request->query->all();
        $filters["page"] = !empty($this->request->query->get("page")) != null && $this->request->query->get("page") != 0 ? $this->request->query->getInt("page") : 1;
        $filters["limit"] = !empty($this->request->query->get("limit")) != null ? $this->request->query->getInt("limit") : 12;
        $filters["sortBy"] = !empty($this->request->query->get("sortBy")) != null ? $this->request->query->get("sortBy") : null;
        $filters["order"] = !empty($this->request->query->get("order")) != null ? $this->request->query->get("order") : "ASC";
        $filters["startAt"] = !empty($this->request->query->get("startAt")) != null ? $this->request->query->get("startAt") : null;
        $filters["endAt"] = !empty($this->request->query->get("endAt")) != null ? $this->request->query->get("endAt") : null;

        $filters["role"] = !empty($this->request->query->get("role")) != null ? $this->request->query->get("role") : null;
        $filters["search"] = !empty($this->request->query->get("search")) != null ? $this->request->query->get("search") : null;
        $filters["isActive"] = $this->request->query->get('isActive') != null ? $this->request->query->get('isActive') : null;
        $filters["isArchive"] = $this->request->query->get('isArchive') != null ? $this->request->query->get('isArchive') : null;
        $filters["isDelete"] = $this->request->query->get('isDelete') != null ? $this->request->query->get('isDelete') : null;
        $filters["isConditionAgreed"] = $this->request->query->get('isConditionAgreed') != null ? $this->request->query->get('isConditionAgreed') : null;

        $filters["sortBy"] = $filters["sortBy"] == 'date' ? 'createdAt' : 'email';

        $user->setCreatedAt($filters['startAt']);
        $user->setCreatedAt($filters['endAt']);
        $user->setIsActive($filters['isActive']);
        $user->setIsArchive($filters['isArchive']);
        $user->setIsDelete($filters['isDelete']);
        $user->setIsConditionAgreed($filters['isConditionAgreed']);


        $err = $this->validator->validate($user, null, ['filters']);

        if (count($err) > 0) {
            return $this->err($err);
        }
        $filters["isConditionAgreed"] = $user->getIsConditionAgreed();
        $filters["isActive"] = $user->getIsActive();
        $filters["isArchive"] = $user->getIsArchive();
        $filters["isDelete"] = $user->getIsDelete();
        $filters['user'] = $user;


        if (array_intersect($loggedUser->getRoles(), User::ACCOUNT_ADMIN_ROLES)) {



            $userPilote = $account->getOwner();

            $filters["isConditionAgreed"] = $user->getIsConditionAgreed();
            $filters["isActive"] = $user->getIsActive();
            $filters["isArchive"] = $user->getIsArchive();
            $filters['user'] = $userPilote;
        }

        // $usersCollection = $this->userRepository->findUsersWithFilters($filters, $account);

        $membersRepository = $this->entityManager->getRepository(UserAccountRole::class);
        /**
         * @var \App\Repository\UserAccountRoleRepository $membersRepository
         */
        $arrayMembers = $membersRepository->searchAccountMembers($filters, $account);
        $member = [];
        foreach ($arrayMembers as  $members) {
            $role = $members->getRole();
            $members->getUser()->setAccountRole($role);
            $member[] = $members->getUser();
        }



        return $this->dataFormalizer->extract($member, 'list_users', true, "user(s) successfuly retrived!", Response::HTTP_OK, $filters);
    }

    public function findOne($user)
    {

        /**
         * @var UserAccountRole $usr
         */
        $usr = $user->getUserAccountRole()->filter(function ($userAccountRole) use ($user) {
            return $userAccountRole->getUser() == $user && $userAccountRole->getUser()->getUuid() == $user->getUuid();
        });

        $roles = $this->entityManager->getRepository(Role::class)->findAll();
        $rights = $this->entityManager->getRepository(Right::class)->findAll();

        $configAccountRole = [];
        $k = 0;

        foreach ($roles as $role) {
            $configAccountRole['accountRoles'][$role->getCode()]['uuid'] = $role->getUuid();
            $configAccountRole['accountRoles'][$role->getCode()]['code'] = $role->getCode();
            $configAccountRole['accountRoles'][$role->getCode()]['name'] = $role->getName();
            $j = 0;
            foreach ($rights as $right) {
                $configAccountRole['accountRoles'][$role->getCode()]['rights'][$j]['uuid'] = $right->getUuid();
                $configAccountRole['accountRoles'][$role->getCode()]['rights'][$j]['code'] = $right->getCode();
                $configAccountRole['accountRoles'][$role->getCode()]['rights'][$j]['name'] = $right->getName();
                $j++;
            }
            $k++;
        }
        // dd($configAccountRole);
        $data['user'] = $user;
        $i = 0;
        foreach ($usr as $userAccountRole) {

            /**
             * @var Account $account
             */
            $account = $userAccountRole->getAccount();
            if ($account->getIsActive()) {
                $data['accounts'][$i]['uuid'] = $account->getUuid();
                $data['accounts'][$i]['email'] = $account->getEmail();
                $data['accounts'][$i]['company'] = $account->getCompany();
                $data['accounts'][$i]['name'] = $account->getName();
                $data['accounts'][$i]['creditEncode'] = $account->getCreditEncode();
                $data['accounts'][$i]['creditStorage'] = $account->getCreditStorage();
                $data['accounts'][$i]['usages'] = $account->getUsages();
                $data['accounts'][$i]['isMultiAccount'] = $account->getIsMultiAccount();
                $data['accounts'][$i]['owner'] = $account->getOwner()->getUuid();
                $data['accounts'][$i]['maxInvitations'] = $account->getMaxInvitations();
                $data['accounts'][$i]['logo'] = $account->getLogo();
                $data['accounts'][$i]['isActive'] = $account->getIsActive();
                $data['accounts'][$i]['accountRole']['uuid'] = $userAccountRole->getRole()->getUuid();
                $data['accounts'][$i]['accountRole']['code'] = $userAccountRole->getRole()->getCode();
                $data['accounts'][$i]['accountRole']['name'] = $userAccountRole->getRole()->getName();
                foreach ($userAccountRole->getAccount()->getAccountRoleRight() as  $rights) {
                    if ($userAccountRole->getRole()->getCode() == $rights->getRole()->getCode())
                        $data['accounts'][$i]['accountRole']['rights'][] = $rights->getRights();
                }
                $data['accounts'][$i]['accountRoles'] = $account->getAccountRoles();

                $i++;
            }
        };
        return $this->dataFormalizer->extract($data, 'me', false, "user(s) successfuly retrived!", Response::HTTP_OK);
    }

    public function userInfos(User $user = null)
    {
        $filters['user'] = null;
        $filters['expiredAt'] = (new \DateTimeImmutable('now'));

        $account = $this->accountReporitory->findOneBy(['uuid' => $this->request->query->get('account_uuid')]);


        $this->accountVoter->vote($this->security->getToken(), $account, [AccountVoter::ACCOUNT_DASHBOARD]);

        $filters['account'] = $account;

        if (array_intersect($user->getRoles(), User::ACCOUNT_ROLES)) {
        } else {
            if ($filters['account'] === null) {
                return $this->adminDashboard();
            }
        }

        return $this->userDashboard($filters);
    }

    private function adminDashboard()
    {

        $storedVideos = $this->videoRepository->findAll();
        $filters['role'] = [User::ACCOUNT_ROLES];

        $listUser = $this->userRepository->findUsersWithFilters($filters);

        $infosAdmin = [
            'totalUsers' => count($listUser),
            'totalVideos' => 0,
            'totalDuration' => 0,
            'totalCarbon' => 0
        ];
        foreach ($storedVideos as $video) {
            $this->consumptionManager->calculeForVideo($video);
            $infosAdmin['totalVideos'] += 1;
            foreach ($video->getEncodes() as $encode) {
                $infosAdmin['totalVideos'] += 1;
            }
            $infosAdmin['totalDuration'] += $video->getDuration();
            $infosAdmin['totalCarbon'] += $video->getCarbonConsumption();
        }
        return (new JsonResponseMessage())->setContent($infosAdmin)->setError(['success'])->setCode(Response::HTTP_OK);
    }

    private function userDashboard($filters = null)
    {
        $data = [];
        $account = $filters['account'];
        $accountVideos = $this->videoRepository->findAccountVideos($account);

        $videosFilters = [
            'isDeleted' => false,
            'isStored' => true,
            'countable' => true
        ];

        $accountStoredVideos = $this->videoRepository->findVideos($filters['account'], $videosFilters);
        $infosVideos = [
            'totalVideos' => count($accountVideos),
            'totalStoredVideos' => count($accountStoredVideos),
            'totalSavedCarbon' => 0,
            'totalUsedStorage' => 0,
            'totalDuration' => 0,
            'totalSizeOriginal' => 0,
            'totalSizeEncode' => 0,
            'totalGain' => 0,
            'totalEncoded' => 0

        ];
        foreach ($accountStoredVideos as $video) {
            $infosVideos['totalUsedStorage'] += $video->getSize();
        }
        /** @var Video $video */
        $i = 0;
        foreach ($accountVideos as $video) {
            $infosVideos['totalDuration'] += $video->getDuration();
            if ($video->getEncodingState() === Video::ENCODING_ENCODED) {
                $infosVideos['totalEncoded'] = $i;
                $infosVideos['totalSizeOriginal'] += $video->getSize();
                $highestEncoded = $this->consumptionManager->findHighestEncodedVideo($video);
                if ($highestEncoded != null) {

                    $infosVideos['totalSizeEncode'] += $highestEncoded->getSize();
                }
                $i++;
            }
        }
        $infosVideos['totalGain'] = $infosVideos['totalSizeOriginal'] != 0
            ? (($infosVideos['totalSizeOriginal'] - $infosVideos['totalSizeEncode']) / $infosVideos['totalSizeOriginal']) * 100
            : 0;

        $filters['isConsumed'] = false;
        $filters['isActive'] = true;
        $filters['account'] = $account;
        $availableCredit = $this->orderPackage->verifyAvailableCredit($filters);


        $userAvailableCredit = [
            'totalEncode' => $availableCredit->hasSeconds(),
            'totalStorage' => $availableCredit->hasBits(),
        ];



        $collection = $this->dashboardCollection($account)->getContent();

        $data = [
            "infosVideos" => $infosVideos,
            "infosCredit" => $userAvailableCredit,
            'latestEncoded' => $collection
        ];
        return (new JsonResponseMessage())->setContent($data)->setError(['success'])->setCode(Response::HTTP_OK);
    }


    private function newAccountUser($body)
    {
        $newUser = $this->serializer->deserialize(
            $this->request->getContent(),
            User::class,
            'json',
            [
                'object_to_populate' => new User(),
                "groups" => 'user:create:account'
            ]
        );
        $err = $this->validator->validate($newUser, null, ['user:create:account']);

        if ($err->count() > 0) {
            return $this->err($err);
        }

        if (!(ctype_xdigit($body->pwd) && strlen($body->pwd) % 2 == 0)) {
            return (new JsonResponseMessage())->setCode(response::HTTP_UNPROCESSABLE_ENTITY)->setError('invalid hexa');
        }
        $userAccountRole = new UserAccountRole();
        $roleRepository = $this->entityManager->getRepository(Role::class);
        $userAccountRoleRepository = $this->entityManager->getRepository(UserAccountRole::class);
        $role = $roleRepository->findOneBy(['code' => Role::ROLE_ADMIN]);


        $genericPwd = $this->apiKeyService->decrypteApiKey($body->pwd);
        $newUser->setIsActive(true);
        $newUser->setIsDelete(false);
        $newUser->setIsArchive(false);
        $newUser->setIsConditionAgreed(true);
        $newUser->setRoles(["ROLE_USER"]);
        $newUser->setPassword($genericPwd);
        $this->userRepository->updatePassword($newUser);

        $account = $this->accountManager->newAccountVideoEngage($newUser);
        $this->entityManager->persist($newUser);
        $this->entityManager->flush();

        $userAccountRole->setAccount($account)->setUser($newUser)->setRole($role);
        $userAccountRoleRepository->add($userAccountRole);
        $this->accountRoleRightService->initDefaultRight($role, $account);

        return $account;
    }

    public function createAccountForVideoEngage()
    {
        /**
         * @var User $newUser
         */
        $body = json_decode($this->request->getContent(), false);


        if (!property_exists($body, 'storage')) {
            $body->storage = false;
        }
        $account = $this->accountReporitory->findOneBy(["email" => $body->email]);
        //ajouter voter pour l'admin seulment;

        $message = "Account already exist and was updated";
        $code = Response::HTTP_OK;

        $isNewAccount = false;
        if (empty($account)) {
            $account = $this->newAccountUser($body);
            $message = "New account has been created with Package and Apikey";
            $code = Response::HTTP_CREATED;
            $isNewAccount = true;
            if (!$account instanceof Account) {
                return $account;
            }
        }

        $this->videoEngageSetMultiAccount($account);

        if ($body->storage === true) {
            if (!$isNewAccount) {
                $storageOrder = $this->orderPackage->findActiveStorageOrderForUser($account);
            }
            if (empty($storageOrder) || $isNewAccount) {
                $packsStorage = $this->forfaitManager->findPackageStorage();
                $this->orderPackage->orderPack($packsStorage[0], $account);
            }
        }

        if (!$isNewAccount) {
            $encodageOrder = $this->orderPackage->findActiveEncodageOrderForUser($account);
        }
        if (empty($encodageOrder) || $isNewAccount) {
            $packsEncodage = $this->forfaitManager->findPackageEncodage();
            $this->orderPackage->orderPack($packsEncodage[0], $account);
        }

        $response = $this->apiKeyService->getOrCreateApiKey($account);
        $response = ['apiKey' => $response->getContent()['apiKey']];

        return (new JsonResponseMessage())->setContent($response)->setCode($code)->setError($message);
    }

    public function switchUserRole($user)
    {
        $body = json_decode($this->request->getContent(), true);
        $account = $this->accountReporitory->findOneBy(['uuid' => $this->request->attributes->get('account_uuid')]);

        $this->accountVoter->vote($this->security->getToken(), $account, [AccountVoter::ACCOUNT_ADMIN_SWAP]);

        //a deplacer

        //
        $memberAccountAdmin = $this->userAccountRolesService->findAdminAccount($account);

        $targetUser = $this->userRepository->findOneBy([
            'uuid' => $body['user_uuid'],
            'isActive' => true,
            'isConditionAgreed' => true
        ]);
        $memberAccount = $this->userAccountRolesService->findUserAccounts($targetUser, $account);

        if ($memberAccount == null) {
            return (new JsonResponseMessage)->setCode(Response::HTTP_FORBIDDEN)->setError('This user is forbidden!');
        }


        $data = [
            "reader" => $memberAccountAdmin,
            "admin" => $memberAccount
        ];

        $this->swapUserRoles($data);
        $this->accountManager->editAccountName($account, $targetUser);

        return (new JsonResponseMessage)->setCode(Response::HTTP_OK)->setError('success');
    }


    public function removeUser($user)
    {
        $isAuth = $this->authorizationService->check_access($user);

        if (!$isAuth) {
            return (new JsonResponseMessage())->setCode(Response::HTTP_FORBIDDEN)->setError(['Forbidden!']);
        }
        $targetUser = $this->authorizationService->getTargetUserOrNull($user);

        if ($targetUser == null) {
            return (new JsonResponseMessage)->setCode(Response::HTTP_NOT_FOUND)->setError('user not found');
        }

        if (in_array($user->getRoles()[0], User::ACCOUNT_ADMIN_ROLES)) {
            $targetUser->setIsActive(false);
            $targetUser->setIsDelete(true);
        } else {

            if ($targetUser->getRoles()[0] == AuthorizationService::AS_USER) {
                $targetUser->setIsActive(false);
                $targetUser->setIsDelete(true);
            }
        }
        $this->userRepository->update($targetUser);
        return (new JsonResponseMessage)->setCode(Response::HTTP_OK)->setError('success');
    }

    private function swapUserRoles($data): void
    {
        $roleRepository = $this->entityManager->getRepository(Role::class);
        /**
         *@var \App\Entity\UserAccountRole $memberAccount
         */
        foreach ($data as $role => $memberAccount) {
            $role = $roleRepository->findOneBy(['code' => $role]);
            $this->userAccountRolesService->editUserToAccount($memberAccount, $role);
        }
    }


    private function videoEngageSetMultiAccount(Account $account): void
    {

        if ($account->getIsMultiAccount() === false && $account->getUsages() == Account::USAGE_PRO) {
            $account = $account->setIsMultiAccount(true);
            $this->entityManager->persist($account);
            $this->entityManager->flush();
        }
    }

    public function toggleUser(string $user_uuid)
    {
        $user = $this->userRepository->findOneBy(['uuid' => $user_uuid]);
        $this->userVoter->vote($this->security->getToken(), $user, [UserVoter::USER_EDIT]);
        $user->setIsActive(!$user->getIsActive());
        $user->setUpdatedAt(new \DateTimeImmutable('now'));
        $this->entityManager->flush();
        return (new JsonResponseMessage)->setCode(Response::HTTP_OK)->setError('User successfully edited');
    }

    private function dashboardCollection($account)
    {
        $filters = [
            'account' => $account,
            'status' => Video::ENCODING_ENCODED,
            'folderId' => null,
            'order' => 'DESC',
            'limit' => 10,
            'sortBy' => 'createdAt',
            'page' => 1,
            'isDeleted' => false
        ];

        $accessibleFolderCollection = $this->folderManager->findAccountRootVideoTeck()->getContent();

        $folderIds = $this->videoManager->extractIds($accessibleFolderCollection);

        $filters = array_merge($filters, ['folderId' => $folderIds]);

        $videoCollection = $this->videoRepository->findVideos($account, $filters);

        $videoCollections = $this->videoHandler->handle($videoCollection, $filters);
        return  $this->dataFormalizer->extract($videoCollections, 'list_of_videos', true, "success", Response::HTTP_OK, $filters);
    }
}
