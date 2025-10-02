<?php

namespace App\Services\Account;

use App\Entity\Video;
use App\Entity\Account;
use App\Entity\AccountRoleRight;
use App\Entity\Folder;
use App\Entity\Right;
use App\Entity\Role;
use App\Entity\User;
use App\Form\Dto\DtoAccount;
use App\Form\Dto\DtoChangeRole;
use App\Form\TrashFilterType;
use App\Helper\FileHelper;
use App\Repository\AccountRepository;
use App\Repository\AccountRoleRightRepository;
use App\Repository\FolderRepository;
use App\Repository\OrderRepository;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use App\Repository\VideoRepository;
use App\Security\Voter\AccountVoter;
use App\Security\Voter\FolderVoter;
use App\Security\Voter\RoleAccountVoter;
use App\Security\Voter\VideoVoter;
use App\Services\AbstactValidator;
use App\Services\ApiKeyService;
use App\Services\AuthorizationService;
use App\Services\DataFormalizerResponse;
use App\Services\Folder\FolderManager;
use App\Services\JsonResponseMessage;
use App\Services\MailerService;
use App\Services\Order\OrderPackage;
use App\Services\Permission\Account\UserAccountRolesService;
use App\Services\Permission\Folder\UserFolderRoleService;
use App\Services\Storage\S3Storage;
use App\Services\Users\UserAccountInvitation;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class AccountManager extends AbstactValidator
{

    private $request;
    private $serializer;
    private $accountRepository;
    private $validator;
    private $dataFormalizerResponse;
    private $authorizationService;
    private $userRepository;
    private $apiKeyService;
    private $accountService;
    private $userAccountInvitation;
    private $mailerService;
    private $videoRepository;
    private $orderRepository;
    private $security;
    private $authorizationChecker;
    private $accountVoter;
    private $roleRepository;
    private $userAccountRolesService;
    private $orderPackage;
    private $em;
    private $storage;
    private $userFolderRoleService;
    private $folderVoter;
    private $videoVoter;
    private $folderManager;
    private $folderRepository;
    private $formFactory;
    private $accountRoleRightRepository;

    public function __construct(
        RequestStack $requestStack,
        DataFormalizerResponse $dataFormalizerResponse,
        SerializerInterface $serializer,
        AccountRepository $accountRepository,
        ValidatorInterface $validator,
        AuthorizationService $authorizationService,
        UserRepository $userRepository,
        AccountService $accountService,
        UserAccountInvitation $userAccountInvitation,
        MailerService $mailerService,
        ApiKeyService $apiKeyService,
        OrderPackage  $orderPackage,
        VideoRepository  $videoRepository,
        Security  $security,
        AuthorizationCheckerInterface  $authorizationChecker,
        OrderRepository  $orderRepository,
        AccountVoter $accountVoter,
        RoleRepository $roleRepository,
        EntityManagerInterface $em,
        UserFolderRoleService $userFolderRoleService,
        S3Storage $storage,
        UserAccountRolesService $userAccountRolesService,
        FolderVoter $folderVoter,
        VideoVoter  $videoVoter,
        FolderManager $folderManager,
        FolderRepository $folderRepository,
        FormFactoryInterface    $formFactory,
        AccountRoleRightRepository   $accountRoleRightRepository
    ) {

        $this->request = $requestStack->getCurrentRequest();
        $this->serializer = $serializer;
        $this->accountRepository = $accountRepository;
        $this->validator = $validator;
        $this->dataFormalizerResponse = $dataFormalizerResponse;
        $this->authorizationService = $authorizationService;
        $this->userRepository = $userRepository;
        $this->apiKeyService = $apiKeyService;
        $this->accountService = $accountService;
        $this->mailerService = $mailerService;
        $this->userAccountInvitation = $userAccountInvitation;
        $this->orderPackage = $orderPackage;
        $this->videoRepository = $videoRepository;
        $this->orderRepository = $orderRepository;
        $this->security = $security;
        $this->authorizationChecker = $authorizationChecker;
        $this->accountVoter = $accountVoter;
        $this->roleRepository = $roleRepository;
        $this->em = $em;
        $this->storage = $storage;
        $this->userFolderRoleService = $userFolderRoleService;
        $this->userAccountRolesService = $userAccountRolesService;
        $this->folderVoter = $folderVoter;
        $this->videoVoter = $videoVoter;
        $this->folderManager = $folderManager;
        $this->folderRepository = $folderRepository;
        $this->formFactory = $formFactory;
        $this->accountRoleRightRepository = $accountRoleRightRepository;
    }

    public function getAccounts($user)
    {

        $group = 'account:one';

        $account = $this->accountRepository->findOneBy(['uuid' =>  $this->request->attributes->get('account_uuid')]);

        $this->authorizationChecker->isGranted([AccountVoter::ACCOUNT_FIND_ONE], $account);

        $this->detailAccountInfos($account);
        return $this->dataFormalizerResponse->extract($account, $group, false, 'Account successfuly retived', Response::HTTP_OK);
    }
    public function getAllAccounts(User $user)
    {


        $isAuth = $this->authorizationService->check_access($user);

        if (!$isAuth) {
            return (new JsonResponseMessage)->setCode(Response::HTTP_UNAUTHORIZED)->setError('unauthorized action');
        }

        $targetUser = $this->authorizationService->getTargetUserOrNull($user);


        $query = $this->request->query->all();

        $group = 'account:list';
        $data = $this->serializer->deserialize(
            json_encode($query),
            DtoAccount::class,
            'json',
            [
                'object_to_populate' =>  new DtoAccount(),
                "groups" => $group,
                AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true
            ],

        );

        $err = $this->validator->validate($data, null, $group);
        if ($err->count() > 0) {
            return $this->err($err);
        }

        $filters =  $data->getArray();


        $accounts = $this->accountRepository->findFilteredAccount($filters, $targetUser);

        /**
         * @var Account $account
         */
        foreach ($accounts as $k => $account) {
            $this->detailAccountInfos($account);
        }


        return $this->dataFormalizerResponse->extract($accounts, $group, true, 'Account successfuly retived', Response::HTTP_OK, $filters);
    }

    public function editAccounts($user)
    {

        $body = json_decode($this->request->getContent(), true);
        $account = $this->accountRepository->findOneBy(['uuid' => $this->request->attributes->get('account_uuid')]);

        $this->accountVoter->vote($this->security->getToken(), $account, [AccountVoter::ACCOUNT_EDIT_ONE]);

        $group = "account:pilote:edit";
        if (array_intersect($user->getRoles(), User::ACCOUNT_ADMIN_ROLES)) {
            $group = "account:admin:edit";
        }

        $accounts = $this->serializer->deserialize(
            $this->request->getContent(),
            Account::class,
            'json',
            [
                'object_to_populate' =>  $account,
                "groups" => $group,
                AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true
            ],
        );

        $err = $this->validator->validate($accounts, null, $group);
        if ($err->count() > 0) {
            return $this->err($err);
        }

        $account->setDisplayName($user);

        $account = $this->accountRepository->add($account);
        return  $this->dataFormalizerResponse->extract($account, "account:pilote:edit", false, 'Account successfuly edited', Response::HTTP_OK);
    }

    private function detailAccountInfos($account)
    {


        foreach ($account->getOrders() as $orderKey => $orderValue) {
            $account->order_uuid[] = $orderValue->getUuid();
        }
    }


    public function multiAccounts($user)
    {
        $account = $this->accountRepository->findOneBy(['uuid' => $this->request->attributes->get('account_uuid')]);

        if ($account == null) {
            return (new JsonResponseMessage())->setCode(Response::HTTP_NOT_FOUND)->setError('Account Not found');
        }

        $group = 'update_admin';

        $data = $this->serializer->deserialize(
            $this->request->getContent(),
            Account::class,
            'json',
            [
                'object_to_populate' => $account,
                "groups" => $group,
                AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true
            ]

        );

        $err = $this->validator->validate($data, null, $group);

        if ($err->count() > 0) {
            return $this->err($err);
        }
        if ($data->getIsMultiAccount()) {

            $data->setDisplayName();
        }
        $this->accountRepository->add($data);
        return $this->dataFormalizerResponse->extract($data, $group);
    }

    public function inviteToAccount()
    {
        $user = $this->security->getUser();

        $filter = [
            'uuid' => $this->request->attributes->get('account_uuid'),
            'isActive' => true
        ];

        $account = $this->accountRepository->findOneBy($filter);

        $this->accountVoter->vote($this->security->getToken(), $account, [accountVoter::ACCOUNT_INVITATION]);
        if (!$account->getIsMultiAccount()) {
            throw new Exception("can't invite, is not multi-account", Response::HTTP_FORBIDDEN);
        }
        $group = "account:invitation";
        $body = json_decode($this->request->getContent(), true);

        $role = isset($body['role']) != null ? $body['role'] : "reader";

        $role = $this->roleRepository->findOneBy(['code' => $role]);
        $message = "Invitation was send";

        $newUser = $this->serializer->deserialize(
            $this->request->getContent(),
            User::class,
            'json',
            [
                'object_to_populate' => new User(),
                'groups' => $group
            ]
        );

        $err = $this->validator->validate($newUser, null, $group);

        if ($err->count() > 0) {
            return $this->err($err);
        }

        $countMembers = count($account->getMembers());
        if ($countMembers >= $account->getMaxInvitations()) {
            throw new Exception("Account limite invitation !", Response::HTTP_FORBIDDEN);
        }
        $code = Response::HTTP_OK;
        $existingUser = $this->userRepository->findOneBy(['email' => $body["email"]]);
        $mailData = [
            'sendBy' => $user,
            'toAccount' => $account
        ];
        switch ($existingUser) {
            case true:
                if ($this->userAccountRolesService->userAlreadyInAccount($account, $existingUser)) {
                    throw new Exception("Success", Response::HTTP_OK);
                }
                $this->userAccountRolesService->addUserToAccount($account, $existingUser, $role->getCode());

                $existingUser->getIsActive() ?
                    $this->mailerService->mailInviteExistingUserToAccount($existingUser, $mailData) :
                    $this->mailerService->mailInviteToAccount($existingUser, $mailData);
                break;
            case false:
                $this->userAccountInvitation->inviteCollaboratorToAccount($newUser);
                $existingUser = $this->userRepository->register($newUser);
                $this->userAccountRolesService->addUserToAccount($account, $existingUser, $role->getCode());

                $this->mailerService->mailInviteToAccount($existingUser, $mailData);
                break;

            default:
                # code...
                break;
        }

        $code = Response::HTTP_OK;

        $data = [];
        return $this->dataFormalizerResponse->extract($data, $group, false, $message, $code);
    }


    public function prepareToMail($account, $user, $toUser)
    {

        $mailData = [
            'sendBy' => $user,
            'toAccount' => $account
        ];
        $this->mailerService->mailInviteToAccount(
            $toUser,
            $mailData
        );
    }

    public function userJoinAccount($user = null)
    {

        if ($user->getRoles()[0] != AuthorizationService::AS_USER) {
            return (new JsonResponseMessage)->setCode(Response::HTTP_FORBIDDEN)->setError('Forbiden action');
        }


        $groups = 'user:invite:edit';
        $user->setPassword('');

        $editedUser =  $this->serializer->deserialize(
            $this->request->getContent(),
            User::class,
            'json',
            [
                'object_to_populate' => $user,
            ]
        );

        $err = $this->validator->validate($editedUser, null, $groups);

        if ($err->count() > 0) {

            return $this->err($err);
        }

        $editedUser->setIsActive(true);
        $editedUser->setIsDelete(false);
        $this->userRepository->updatePassword($editedUser, false);
        $this->userRepository->update($editedUser);

        return $this->dataFormalizerResponse->extract(null);
    }

    public function toggleAccount($user = null)
    {


        $account_uuid = $this->request->attributes->get('account_uuid');
        $account =  $this->accountRepository->findOneBy(['uuid' => $account_uuid]);


        if ($account == null) {
            return (new JsonResponseMessage)->setCode(Response::HTTP_NOT_FOUND)->setError('Account Not found');
        }

        $account->setIsActive(!$account->getIsActive());
        $this->accountRepository->add($account);
        return  $this->dataFormalizerResponse->extract($account, "", false, 'Account successfully edited', Response::HTTP_ACCEPTED);
    }

    public function changeUserRole()
    {
        $account = $this->em->getRepository(Account::class)->findOneBy(['uuid' => $this->request->attributes->get('account_uuid')]);
        // dd($account);
        $this->accountVoter->vote($this->security->getToken(), $account, [AccountVoter::ACCOUNT_USER_EDIT_ROLE]);

        $newUserRole = $this->serializer->deserialize(
            $this->request->getContent(),
            DtoChangeRole::class,
            'json',
            [
                'object_to_populate' => new DtoChangeRole(),
                "groups" => 'account:roles'
            ],

        );
        $err = $this->validator->validate($newUserRole, null, "account:roles");

        if ($err->count() > 0) {

            return $this->err($err);
        }
        $targetUser = $this->userRepository->findOneBy(['uuid' => $newUserRole->user_uuid]);

        if ($targetUser == null) {
            throw new Exception("not found", Response::HTTP_NOT_FOUND);
        }

        $userAccountRole = $this->userAccountRolesService->findUserAccounts($targetUser, $account);

        $role = $this->userAccountRolesService->findUserRoleInAccount($targetUser, $account);

        if ($role->getCode() == Role::ROLE_ADMIN) {
            throw new Exception("forbidden", Response::HTTP_FORBIDDEN);
        }
        $newRole = $this->em->getRepository(Role::class)->findOneBy(['code' => $newUserRole->role]);

        $member = $this->userAccountRolesService->editUserToAccount($userAccountRole, $newRole);

        $role = $member->getRole();
        $member->getUser()->setAccountRole($role);
        $member->getUser();

        return $this->dataFormalizerResponse->extract($member, 'list_users', false, 'success', Response::HTTP_OK);
    }


    public function newAccountVideoEngage(User $user): Account
    {
        $account = new Account();
        $account->setUuid();
        $account->setIsActive(true);
        $account->setEmail($user->getEmail());
        $account->setUsages(Account::USAGE_PRO);
        $account->setIsMultiAccount(true);
        $this->accountRepository->add($account);
        return $account;
    }
    /**
     * Undocumented function
     *
     * @param [type] $user
     * @return JsonResponseMessage
     */
    public function getAccountHistorys($user = null): JsonResponseMessage
    {
        $accountHistorys = $this->accountRepository->lastCreatedAccountWithPilote();

        $resultCsmAccountView = [];

        $i = 0;
        /** @var Account $account */

        foreach ($accountHistorys as $account) {
            $resultCsmAccountView[$i]['id'] = $account->getId();
            $resultCsmAccountView[$i]['uuid'] = $account->getUuid();
            $resultCsmAccountView[$i]['createdAt'] = $account->getCreatedAt();
            $resultCsmAccountView[$i]['lastConnection'] = $account->getLastConnection();
            $resultCsmAccountView[$i]['email'] = $account->getEmail();
            $resultCsmAccountView[$i]['users'] = $account->getUserAccountRole(function ($userAccountRole) use ($account) {

                return ($userAccountRole->getUser()->getUser()->getRole()->getCode() == Role::ROLE_ADMIN) && ($account == $userAccountRole->getAccount());
            })->first()->getUser();
            $video = $this->videoRepository->findBy(['account' => $account], ['createdAt' => 'DESC'], ['limit' => 1]);
            $resultCsmAccountView[$i]['videos'] = [];
            if ($video != null) {
                $resultCsmAccountView[$i]['videos'] = $video;
            }
            $order = $this->orderRepository->findBy(['account' => $account], ['createdAt' => 'DESC'], ['limit' => 1]);
            $resultCsmAccountView[$i]['orders'] = [];
            if ($order != null) {
                $resultCsmAccountView[$i]['orders'] = $order;
            }
            $i++;
        }

        $filters = ['page' => 1, 'limit' => 30];
        return $this->dataFormalizerResponse->extract(
            $resultCsmAccountView,
            "account:history:encode",
            true,
            'ressource(s) successfuly retrived!',
            Response::HTTP_OK,
            $filters
        );
    }

    public function uploadAccountLogo()
    {
        $account = $this->accountRepository->findOneBy(['uuid' => $this->request->attributes->get('account_uuid')]);

        $this->accountVoter->vote($this->security->getToken(), $account, [AccountVoter::ACCOUNT_UPLOAD_LOGO]);


        $logo = FileHelper::addAccountLogo($this->request->files->get('file'));
        $url = $this->storage->UploadImage($logo, $account->getLogo());
        if ($url) {
            $account->setLogo($url);
            $this->accountRepository->add($account);
            return (new JsonResponseMessage())->setCode(Response::HTTP_OK)->setContent($url)->setError(['Logo Added successfully!']);
        }
        return (new JsonResponseMessage())->setCode(Response::HTTP_BAD_REQUEST)->setError(['Bad Request!']);
    }

    public function removeAccountMember($user)
    {

        $account = $this->accountRepository->findOneBy(['uuid' => $this->request->attributes->get('account_uuid')]);
        $this->accountVoter->vote($this->security->getToken(), $account, [AccountVoter::ACCOUNT_REMOVE_USER]);

        $targetUser = $this->userRepository->findOneBy(['uuid' => json_decode($this->request->getContent(), true)['user_uuid']]);



        $this->userAccountRolesService->removeFromAccount($targetUser, $account);
        $folders = $account->getFolders();
        if ($folders != null) {

            foreach ($folders as $folder) {

                $this->userFolderRoleService->removeUserFolderRole($targetUser, $folder);
            }
        }
        return (new JsonResponseMessage)->setCode(Response::HTTP_OK)->setError('success');
    }

    /**
     * edit account name with the right value (company name, user name or email)
     * @param Account $account
     * @return void
     */
    public function editAccountName(Account $account, User $targetUser): void
    {
        $account->setDisplayName($targetUser);
        $this->em->flush();
    }

    public function moveVideos()
    {
        $accountUuid = $this->request->attributes->get('account_uuid');
        $account = $this->em->getRepository(Account::class)->findOneBy(['uuid' => $accountUuid]);
        $request = json_decode($this->request->getContent(), true);

        if (isset($request['account_uuid'])) {
            $destAccount = $this->em->getRepository(Account::class)->findOneBy(['uuid' => $request['account_uuid']]);
            $this->accountVoter->vote(
                $this->security->getToken(),
                $destAccount,
                [AccountVoter::ACCOUNT_MOVE_VIDEOS_BETWEEN_ACCOUNT]
            );

            if ($account == $destAccount) {
                throw new Exception('destiantion account must be different to origin account', Response::HTTP_BAD_REQUEST);
            }
        }

        if (isset($request['folder_uuid'])) {
            $folderAccount = $destAccount ?? $account;
            $destFolder = $this->em->getRepository(Folder::class)->findOneBy(['uuid' => $request['folder_uuid'], 'account' => $folderAccount]);
            $this->folderVoter->vote($this->security->getToken(), $destFolder, [FolderVoter::EDIT_FOLDER]);
        } else {
            $destFolder = null;
            $this->accountVoter->vote($this->security->getToken(), $account, [AccountVoter::ACCOUNT_MOVE_VIDEOS]);
        }

        foreach ($request['videos'] as $videoUuid) {
            $videoToMove = $this->videoRepository->findOneBy(['uuid' => $videoUuid, 'account' => $account]);

            $isGranted = $this->videoVoter->vote(
                $this->security->getToken(),
                $videoToMove,
                [VideoVoter::ACCOUNT_MOVE_VIDEOS]
            );

            if ($isGranted == -1) {
                continue;
            }

            if (isset($destAccount)) {

                /**
                 * @var OrderPackage $res
                 */
                $res = $this->orderPackage->adminCheckOrderCreditForUser($destAccount,  $videoToMove);


                if (!$res->hasRessources() && $videoToMove->getIsStored()) {
                    continue;
                }


                $this->orderPackage->giveBackAccountCredit($videoToMove);
                $videoToMove->setAccount($destAccount);
            }

            $videoToMove->setFolder($destFolder);
            $this->videoRepository->updateVideo($videoToMove);
            $data[] = $videoToMove->getUuid();
        }

        return (new JsonResponseMessage)
            ->setCode(Response::HTTP_OK)
            ->setContent($data)
            ->setError(sizeof($data) . ' videos moved');
    }

    public function moveFolders()
    {
        $accountUuid = $this->request->attributes->get('account_uuid');
        $account = $this->em->getRepository(Account::class)->findOneBy(['uuid' => $accountUuid]);
        $request = json_decode($this->request->getContent(), true);


        if (isset($request['folder_uuid'])) {
            $destFolder = $this->em->getRepository(Folder::class)->findOneBy(['uuid' => $request['folder_uuid']]);
            $this->folderVoter->vote($this->security->getToken(), $destFolder, [FolderVoter::EDIT_FOLDER]);
        } else {
            $destFolder =  null;
            $this->accountVoter->vote($this->security->getToken(), $account, [AccountVoter::ACCOUNT_MOVE_FOLDERS]);
        }

        if ($destFolder && $destFolder->getLevel() >= 2) {
            throw new Exception("We have reached the highest level in this folder!", Response::HTTP_BAD_REQUEST);
        }

        $currentLevel = $destFolder ? $destFolder->getLevel() : 0;
        $folders = $this->folderRepository->findFoldersByUuids($request['folders'], $account);

        if (empty($folders)) {
            throw new Exception("Folders not found!", Response::HTTP_NOT_FOUND);
        }

        $lastChildLevel = 1;
        foreach ($folders as $folderToMoved) {
            $level = $this->folderManager->countNestedLevels($folderToMoved, $lastChildLevel);

            if ($level > $lastChildLevel) {
                $lastChildLevel = $level;
            }
        }

        if ($lastChildLevel + $currentLevel >= 3) {
            throw new Exception("We have reached the highest level in this folder!", Response::HTTP_BAD_REQUEST);
        }


        if (isset($request['account_uuid'])) {
            $destAccount = $this->em->getRepository(Account::class)->findOneBy(['uuid' => $request['account_uuid']]);
            $this->accountVoter->vote($this->security->getToken(), $destAccount, [AccountVoter::ACCOUNT_MOVE_VIDEOS_BETWEEN_ACCOUNT]);

            $videos = [];
            foreach ($folders as $folderToMoved) {
                $videos = array_merge($videos, $this->folderManager->getAllFoldersVideosRecursive($folderToMoved));
            }
            if (!$this->orderPackage->checkStorage($destAccount, $videos)) {
                throw new Exception("The destination account is missing the essential stockage!", Response::HTTP_BAD_REQUEST);
            }
        }

        $level = $destFolder ? $destFolder->getLevel() + 1 : 0;

        $data = [];
        foreach ($folders as $folderToMoved) {
            $isGranted = $this->folderVoter->vote($this->security->getToken(), $folderToMoved, [FolderVoter::MOVE_FOLDERS]);

            if ($isGranted == -1) {
                continue;
            }

            if (isset($destAccount)) {
                $videos = $this->folderManager->getAllFoldersVideosRecursive($folderToMoved);
                if (!$this->orderPackage->soldSotrage($destAccount, $videos)) {
                    $data[] = ['uuid' => $folderToMoved->getUuid(), 'status' => 'NOT_MOVED', 'message' => 'no resources'];
                    continue;
                }

                foreach ($videos as $video) {

                    $this->orderPackage->giveBackAccountCredit($video);
                    $video->setAccount($destAccount);
                }
            }

            $folderToMoved->setParentFolder($destFolder);
            $this->folderManager->updateFolderRecursively($folderToMoved, $destAccount ?? null, $level);
            $data[] = ['uuid' => $folderToMoved->getUuid(), 'status' => 'MOVED', 'message' => 'Success'];
        }
        $this->em->flush();

        return (new JsonResponseMessage)
            ->setCode(Response::HTTP_OK)
            ->setContent($data)
            ->setError('Folders moved with success');
    }


    public function getTrash(string $account_uuid)
    {
        $account = $this->accountRepository->findOneBy(['uuid' => $account_uuid]);

        $this->accountVoter->vote($this->security->getToken(), $account, [AccountVoter::ACCOUNT_FIND_TRASH]);

        $request = $this->request->query->all();
        $form = $this->formFactory->create(TrashFilterType::class);
        $form->submit($request);
        $filters = $form->getData();

        $videos = $this->videoRepository->findFilteredVideos(array_merge($filters, [
            'account' => $account
        ]));


        $folders = $this->folderRepository->findFilteredFolders(array_merge($filters, [
            'isInTrash' => true,
            'account' => $account,
            'level' => 0,
            'parentFolder' => null
        ]));

        return $this->dataFormalizerResponse->extract(array_merge($folders, $videos),  "trash", false, "Data Successfully retrieved");
    }

    public function editRights(string $account_uuid)
    {

        $account = $this->accountRepository->findOneBy(['uuid' => $account_uuid]);
        $this->accountVoter->vote($this->security->getToken(), $account, [AccountVoter::ACCOUNT_EDIT_RIGHTS]);

        $request = json_decode($this->request->getContent(), true);

        $role = $this->em->getRepository(Role::class)->findOneBy(['code' => 'editor']);

        foreach ($request as $key => $isRight) {

            if (!in_array($key, ['video_delete', 'account_invite', 'report_encode', 'report_config'])) {
                continue;
            }

            $accountRoleRight = $this->accountRoleRightRepository->findRightWithAccountRole([
                'rightCode' => $key,
                'account' => $account,
                'role' =>  $role
            ]);

            if ($isRight && !$accountRoleRight) {
                $newAccountRoleRight = new AccountRoleRight();
                $right = $this->em->getRepository(Right::class)->findOneBy(['code' => $key]);
                $newAccountRoleRight->setAccount($account)->setRole($role)->setRights($right);
                $this->em->persist($newAccountRoleRight);
                $account->addAccountRoleRight($newAccountRoleRight);
                continue;
            }

            if (!$isRight && $accountRoleRight) {
                $account->removeAccountRoleRight($accountRoleRight);
                continue;
            }
        }

        $this->em->flush();
        return (new JsonResponseMessage)
            ->setCode(Response::HTTP_OK)
            ->setError('Permission has been edited successfully');
    }

    public function getOneByEmail()
    {

        $email = $this->request->query->get('email');
        $account = $this->accountRepository->findOneBy(['email' => $email]);

        $this->accountVoter->vote($this->security->getToken(), $account, [AccountVoter::ACCOUNT_FIND_ONE_BY_EMAIL]);

        return $this->dataFormalizerResponse->extract($account, 'account:list', false, 'Account successfuly retived', Response::HTTP_OK);
    }

    public function uploadFolders(string $account_uuid)
    {
        $account = $this->accountRepository->findOneBy(['uuid' => $account_uuid]);
        $folders = json_decode($this->request->getContent(), true);

        if ($folders[0]['uuid']) {
            $folder = $this->folderRepository->findOneBy(['uuid' => $folders[0]['uuid']]);
            $this->folderVoter->vote($this->security->getToken(), $folder, [FolderVoter::ENCODE_IN_FOLDER]);
        } else {
            $this->accountVoter->vote($this->security->getToken(), $account, [AccountVoter::ACCOUNT_CREATE_FOLDER]);
        }

        $videos = [];
        foreach ($folders as $folder) {
            $videos = $this->folderManager->createFolderRecursivly($folder, $account, $videos);
        }
        $this->em->flush();
        return $this->dataFormalizerResponse->extract($videos, null, false, "folder's successfuly uploaded");
    }
}
