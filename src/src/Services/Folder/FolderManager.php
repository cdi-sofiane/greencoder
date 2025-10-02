<?php


namespace App\Services\Folder;

use App\Entity\Role;
use App\Security\Voter\AccountVoter;
use App\Services\AbstactValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;
use App\Entity\Account;
use App\Entity\Folder;
use App\Entity\User;
use App\Entity\UserAccountRole;
use App\Entity\UserFolderRole;
use App\Form\Dto\DtoShareFolder;
use App\Form\Dto\DtoUploadFolder;
use App\Helper\RightsHelper;
use App\Repository\EncodeRepository;
use App\Repository\FolderRepository;
use App\Repository\VideoRepository;
use App\Security\Voter\FolderVoter;
use App\Security\Voter\RoleAccountVoter;
use App\Services\AuthorizationService;
use App\Services\Consumption\ConsumptionManager;
use App\Services\DataFormalizerResponse;
use App\Services\JsonResponseMessage;
use App\Services\MailerService;
use App\Services\Permission\Account\UserAccountRolesService;
use App\Services\Permission\Folder\UserFolderRoleService;
use App\Services\Storage\S3Storage;
use App\Services\Users\UserAccountInvitation;
use DateTimeImmutable;
use Exception;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use ZipArchive;

class FolderManager extends AbstactValidator
{
  private $security;
  private $accountVoter;
  private $folderVoter;
  private $em;
  private $request;
  private $authorizationChecker;
  private $serializer;
  private $validator;
  private $dataFormalizerResponse;
  private $userAccountInvitation;
  private $userAccountRolesService;
  private $userFolderRoleService;
  private $rightsHelper;
  private $mailerService;
  private $videoRepository;
  private $folderRepository;
  private $storage;
  private $consumptionManager;
  private $encodeRepository;

  public function __construct(
    Security $security,
    AccountVoter $accountVoter,
    FolderVoter $folderVoter,
    EntityManagerInterface $em,
    RequestStack $requestStack,
    SerializerInterface $serializer,
    ValidatorInterface $validator,
    DataFormalizerResponse $dataFormalizerResponse,
    AuthorizationCheckerInterface $authorizationChecker,
    UserAccountRolesService $userAccountRolesService,
    UserFolderRoleService $userFolderRoleService,
    RightsHelper $rightsHelper,
    UserAccountInvitation $userAccountInvitation,
    VideoRepository $videoRepository,
    FolderRepository $folderRepository,
    MailerService $mailerService,
    S3Storage  $storage,
    ConsumptionManager  $consumptionManager,
    EncodeRepository $encodeRepository
  ) {
    $this->security = $security;
    $this->accountVoter = $accountVoter;
    $this->folderVoter = $folderVoter;
    $this->em = $em;
    $this->request = $requestStack->getCurrentRequest();
    $this->authorizationChecker = $authorizationChecker;
    $this->serializer = $serializer;
    $this->validator = $validator;
    $this->dataFormalizerResponse = $dataFormalizerResponse;
    $this->userAccountInvitation = $userAccountInvitation;
    $this->userAccountRolesService = $userAccountRolesService;
    $this->userFolderRoleService = $userFolderRoleService;
    $this->rightsHelper = $rightsHelper;
    $this->videoRepository = $videoRepository;
    $this->folderRepository = $folderRepository;
    $this->mailerService = $mailerService;
    $this->storage = $storage;
    $this->consumptionManager = $consumptionManager;
    $this->encodeRepository = $encodeRepository;
  }

  public function createAccountFolder()
  {
    $accountRepository = $this->em->getRepository(Account::class);
    $body = json_decode($this->request->getContent(), true);

    if (!empty($body['folder_uuid'])) {
      return $this->createChildrenFolder();
    }
    $account = $accountRepository->findOneBy(['uuid' => $body['account_uuid']]);

    $this->authorizationChecker->isGranted([AccountVoter::ACCOUNT_CREATE_FOLDER], $account);

    $folder = $this->buildFolder($account, null);

    if (!$folder instanceof Folder) {
      return $folder;
    }
    /**
     * @var \App\Repository\FolderRepository $folderRepository
     */
    $folderRepository = $this->em->getRepository(Folder::class);
    $folder = $folderRepository->add($folder);
    return $this->dataFormalizerResponse->extract($folder, 'folder:read', false, "folder's successfuly created");
  }


  public function createChildrenFolder()
  {
    $folderRepository = $this->em->getRepository(Folder::class);
    $body = json_decode($this->request->getContent());
    $parentFolder = $folderRepository->findOneBy(['uuid' => $body->folder_uuid]);

    $this->folderVoter->vote($this->security->getToken(),  $parentFolder, [FolderVoter::CREATE_FOLDER]);


    $folder = $this->buildFolder($parentFolder->getAccount(), $parentFolder);
    if (!$folder instanceof Folder) {
      return $folder;
    }
    /**
     * @var \App\Repository\FolderRepository $folderRepository
     */
    $folderRepository = $this->em->getRepository(Folder::class);
    $folder = $folderRepository->add($folder);
    return $this->dataFormalizerResponse->extract($folder, 'folder:read', false, "folder's successfuly created");
  }

  public function editFolders()
  {

    $folderRepository = $this->em->getRepository(Folder::class);
    $folder = $folderRepository->findOneBy(['uuid' => $this->request->attributes->get('folder_uuid')]);

    $this->folderVoter->vote($this->security->getToken(), $folder, [FolderVoter::EDIT_FOLDER]);

    $groups = "folder:edit";
    $folder = $this->serializer->deserialize(
      $this->request->getContent(),
      Folder::class,
      'json',
      [
        AbstractObjectNormalizer::OBJECT_TO_POPULATE => $folder,
        "groups" => $groups
      ],
    );
    $err = $this->validator->validate($folder, null, $groups);

    if ($err->count() > 0) {
      return $this->err($err);
    }
    /**
     * @var \App\Repository\FolderRepository $folderRepository
     */
    $folderRepository = $this->em->getRepository(Folder::class);
    $folderRepository->add($folder);
    return $this->dataFormalizerResponse->extract($folder, 'folder:read', false, "folder's successfuly created");

    return (new JsonResponseMessage())->setCode(Response::HTTP_OK)->setContent($folder)->setError('success!');
  }


  public function findOneFolder()
  {
    $folderRepository = $this->em->getRepository(Folder::class);

    $folder = $folderRepository->findOneBy(['uuid' => $this->request->attributes->get('folder_uuid'), 'isInTrash' => false]);
    /**
     * @var Folder $folder;
     */
    $this->folderVoter->vote($this->security->getToken(), $folder, [FolderVoter::READ_FOLDER]);

    return $this->dataFormalizerResponse->extract($folder, 'folder:read', false, "folder's successfuly retrived");
  }


  public function findAccountRootVideoTeck()
  {
    $accountRepository = $this->em->getRepository(Account::class);

    $account = $accountRepository->findOneBy(['uuid' => $this->request->query->get('account_uuid')]);

    $folderRepository = $this->em->getRepository(Folder::class);

    $folder = $folderRepository->findOneBy(['uuid' => $this->request->query->get('folder_uuid'), 'isInTrash' => false]);
    $filters = [
      'account' => $account,
      'folder' => $folder,
      'level' =>  $folder == null ? 0 : null,
      'isInTrash' => false
    ];
    /**
     * @var FolderRepository $folderRepository
     */
    $rootFolder = $folderRepository->findAccountFolders($filters);

    $arr = $this->filterAccessibleFolders($account, $rootFolder, $this->security->getUser());
    return $this->dataFormalizerResponse->extract($arr, 'folder:read', false, "folder's successfuly retrived");
  }




  /**
   * @todo a refactoriser pour enlever les doublons
   */
  public function filterAccessibleFolders(Account $account,  $folders, User $user)
  {

    if (array_intersect($user->getRoles(), User::ACCOUNT_ADMIN_ROLES)) {
      $user = $account->getOwner();
    }

    $userAccountRole = $account->getUserAccountRole()->filter(function ($userAccountRole) use ($user, $account) {
      return $userAccountRole->getUser() == $user && $userAccountRole->getAccount() == $account;
    })->first();

    $accountRole = $userAccountRole ? $userAccountRole->getRole()->getCode() : Role::ROLE_READER;

    $accessibleFolders = [];

    foreach ($folders as $folder) {
      $folderRole = $accountRole == Role::ROLE_READER ? $this->rightsHelper->findUserFolderRoleHeritage($user, $folder) : Role::ROLE_EDITOR;

      if ($folderRole === Role::ROLE_EDITOR) {
        $accessibleFolder = self::buildSharedFolder($folder, $folderRole);

        $subfolders = $folder->getSubfolders();
        if ($subfolders != null) {
          $accessibleSubFolders = $this->formatSharedFolder($subfolders, $folderRole);
          $accessibleFolder['subFolders'] = $accessibleSubFolders;
        }

        $accessibleFolders[] = $accessibleFolder;
      } else {

        $accessibleFolder = self::buildSharedFolder($folder, $folderRole);

        $subfolders = $folder->getSubfolders();
        if ($subfolders != null) {
          $accessibleSubFolders = $this->filterAccessibleFolders($account, $subfolders, $user);
          $accessibleFolder['subFolders'] = $accessibleSubFolders;
          if ($folderRole == Role::ROLE_READER) {
            $accessibleFolders[] = $accessibleFolder;
          } else {
            $accessibleFolders = array_merge($accessibleFolders, $accessibleSubFolders);
          }
        }
      }
    }

    return $accessibleFolders;
  }

  private function formatSharedFolder($folders, $folderRole)
  {

    $accessibleSubFolders = [];
    foreach ($folders as $folder) {
      $accessiblesFolder = self::buildSharedFolder($folder, $folderRole);
      if ($folder->getSubFolders() != null) {
        $accessibleSubFolder = $this->formatSharedFolder($folder->getSubFolders(), $folderRole);
        $accessiblesFolder['subFolders'] = $accessibleSubFolder;
      }

      $accessibleSubFolders[] = $accessiblesFolder;
    }

    return $accessibleSubFolders;
  }

  public static function buildSharedFolder(Folder $folder, $folderRole)
  {

    return [
      "id" => $folder->getId(),
      "uuid" => $folder->getUuid(),
      "name" => $folder->getName(),
      "level" => $folder->getLevel(),
      "createdBy" => $folder->getCreatedBy(),
      "createdAt" => $folder->getCreatedAt(),
      "updatedAt" => $folder->getUpdatedAt(),
      "isArchived" => $folder->getIsArchived(),
      "members" => $folder->getMembers(),
      "folderRole" => $folderRole,
      'subFolders' => []
    ];
  }

  public function inviteUserToFolder()
  {

    $folderRepository = $this->em->getRepository(Folder::class);
    $shareFolder = $this->serializer->deserialize(
      $this->request->getContent(),
      DtoShareFolder::class,
      'json',
      [
        AbstractObjectNormalizer::OBJECT_TO_POPULATE => new DtoShareFolder(),
        "groups" => 'folder:share'
      ],
    );
    $err = $this->validator->validate($shareFolder, null, ['folder:share']);
    $shareFolder->folder_uuid = $this->request->attributes->get('folder_uuid');
    if ($err->count() > 0) {
      return $this->err($err);
    }
    /**
     * @var Folder $folder
     */
    $folder = $folderRepository->findOneBy(['uuid' => $shareFolder->folder_uuid]);
    $account = $folder->getAccount();

    $userRepository = $this->em->getRepository(User::class);
    $targetUser = $userRepository->findOneBy(['email' => $shareFolder->email]);

    $this->folderVoter->vote($this->security->getToken(), $folder, [FolderVoter::SHARE_FOLDER]);

    $countMembers = count($account->getMembers());
    if ($countMembers >= $account->getMaxInvitations()) {
      throw new Exception("Account limite invitation !", Response::HTTP_FORBIDDEN);
    }
    $isInAccount = false;
    $mailData = [
      'sendBy' => $this->security->getUser(),
      'toAccount' => $account
    ];

    if ($targetUser == null) {

      $targetUser = $this->addUserToAccountFromSharedFolderinvitation($folder, $shareFolder);

      $targetUser->getIsActive() ?
        $this->mailerService->mailInviteExistingUserToAccount($targetUser, $mailData) :
        $this->mailerService->mailInviteToAccount($targetUser, $mailData);
      $isInAccount = true;
    }

    $userInAccountRole = $account->getUserAccountRole()->filter(function ($userAccountRole) use ($targetUser) {
      return $userAccountRole->getUser() === $targetUser;
    });

    if ($userInAccountRole->count() === 0) {
      if (!$isInAccount &&   ($countMembers <= $account->getMaxInvitations())) {
        $this->userAccountRolesService->addUserToAccount($account, $targetUser, Role::ROLE_READER);

        $targetUser->getIsActive() ?
          $this->mailerService->mailInviteExistingUserToAccount($targetUser, $mailData) :
          $this->mailerService->mailInviteToAccount($targetUser, $mailData);
      }
    }

    $role = $this->em->getRepository(Role::class)->findOneBy(['code' => $shareFolder->role]);

    if ($shareFolder->role == Role::ROLE_EDITOR) {
      // verify si un des parent a deja un role editor jusqu'as l account
      $parentRole = $this->rightsHelper->findUserFolderRoleHeritage($targetUser, $folder);
      if ($parentRole == Role::ROLE_EDITOR) {

        throw new Exception("Already have access to this folder", Response::HTTP_CONFLICT);
      }
    }
    if ($shareFolder->role == Role::ROLE_READER) {
      // verify si un des parent a deja un role editor jusqu'as l account
      $parentRole = $this->rightsHelper->findUserFolderRoleHeritage($targetUser, $folder);

      if ($parentRole == null) {
        // si aucun des folder n as de droit verifier le role dans le cas de reader
        $accountHasRole = $this->rightsHelper->FindUserAccountRole($targetUser, $account);
      } else {
        if ($shareFolder->role == Role::ROLE_READER) {

          throw new Exception("Already have access to this folder", Response::HTTP_CONFLICT);
        }
      }
    }
    // verifi si l utilisateur es deja enregistrer sur le folder ou un des ses parent

    $this->userFolderRoleService->addUserFolder($targetUser, $folder, $role);

    $folderRepository = $this->em->getRepository(UserFolderRole::class);

    /**
     * @var Folder $folder
     */
    $userFolderRole = $folderRepository->findBy(['folder' => $folder]);
    $members = [
      "members" => []
    ];
    $i = 0;
    foreach ($userFolderRole as $member) {
      $members['members'][$i]['uuid'] = $member->getUser()->getUuid();
      $members['members'][$i]['firstName'] = $member->getUser()->getFirstName();
      $members['members'][$i]['lastName'] = $member->getUser()->getLastName();
      $members['members'][$i]['email'] = $member->getUser()->getEmail();
      $members['members'][$i]['folderRole'] = $member->getRole()->getCode();
      $i++;
    }

    return $this->dataFormalizerResponse->extract($members, ["folder:read"], false, "folder successfully shared to user");
  }

  public function verifyUserIsInviterInFoldeRecursive(User $targetUser, Folder $folder)
  {

    $userFolderRoleRepository = $this->em->getRepository(UserFolderRole::class);

    $userFolderRole = $userFolderRoleRepository->findOneBy(['user' => $targetUser, 'folder' => $folder]);
    if ($userFolderRole == null) {
      if ($folder->getLevel() == 0) {

        $userAccountRole =  $folder->getAccount()->getUserAccountRole()->filter(function ($userAccountRole) use ($targetUser) {
          //verify sir le root folder as une permission
        });
      }


      return $this->verifyUserIsInviterInFoldeRecursive($targetUser, $folder->getParentFolder());
    }
    $accountFolderRole = $folder->getUserFolderRoles()->filter(function ($userFolderRole) {
    });
  }

  public function addUserToAccountFromSharedFolderinvitation($folder, $shareFolder)
  {
    $account = $folder->getAccount();
    $role = $this->em->getRepository(Role::class)->findOneBy(['code' => 'reader']);
    $userRepository = $this->em->getRepository(User::class);

    $newUser = (new User())
      ->setEmail($shareFolder->email);
    $newUser = $this->userAccountInvitation->inviteCollaboratorToAccount($newUser);
    /**
     * @var \App\Repository\UserRepository $userRepository
     */

    $targetuser = $userRepository->register($newUser);
    $this->userAccountRolesService->addUserToAccount($account, $targetuser, $role->getCode());

    return $targetuser;
  }


  public function findMembersInFolder()
  {
    $folderRepository = $this->em->getRepository(Folder::class);
    /**
     * @var Folder $folder
     */
    $folder = $folderRepository->findOneBy(['uuid' => $this->request->attributes->get('folder_uuid')]);
    $this->folderVoter->vote($this->security->getToken(), $folder, [FolderVoter::FIND_FOLDER_USERS]);

    $folderMembers = $folder->getMembers();

    return $this->dataFormalizerResponse->extract($folderMembers, ["me"], false, "user's successfuly retrived");
  }

  public function removeMemberFromFolder()
  {
    $body = json_decode($this->request->getContent(), true) ?? [];

    $folderRepository = $this->em->getRepository(Folder::class);
    /**
     * @var Folder $folder
     */
    $folder = $folderRepository->findOneBy(['uuid' => $this->request->attributes->get('folder_uuid')]);
    $this->folderVoter->vote($this->security->getToken(), $folder, [FolderVoter::REMOVE_MEMBER_FOLDER]);

    $userRepository = $this->em->getRepository(User::class);
    /**
     * @var User user;
     */
    $targetUser = $userRepository->findOneBy(['uuid' => $body['user_uuid']]);

    $userFolderRole = $folder->getUserFolderRoles()->filter(function ($userFolderRole) use ($targetUser) {
      return $userFolderRole->getUser() == $targetUser;
    })->first();
    if ($userFolderRole == null) {
      throw new Exception("Not fount ", Response::HTTP_NOT_FOUND);
    }

    $this->userFolderRoleService->removeUserFolderRole($targetUser, $folder);

    $folderMembers = $folder->getMembers();

    return $this->dataFormalizerResponse->extract($folderMembers, ["me"], false, "user's successfuly retrived");
  }
  public function switchUserFolderRole()
  {
    $folderRepository = $this->em->getRepository(Folder::class);


    $shareFolder = $this->serializer->deserialize(
      $this->request->getContent(),
      DtoShareFolder::class,
      'json',
      [
        AbstractObjectNormalizer::OBJECT_TO_POPULATE => new DtoShareFolder(),
        "groups" => 'folder:edit:role'
      ],
    );
    $err = $this->validator->validate($shareFolder, null, ['folder:edit:role']);
    $shareFolder->folder_uuid = $this->request->attributes->get('folder_uuid');
    if ($err->count() > 0) {
      return $this->err($err);
    }
    /**
     * @var Folder $folder
     */
    $folder = $folderRepository->findOneBy(['uuid' => $shareFolder->folder_uuid]);

    /**
     * ici le role de l'utilisateur sur l account et editeur , ou le role sur le folder et editeur
     */
    $this->folderVoter->vote($this->security->getToken(), $folder, [FolderVoter::SHARE_FOLDER]);
    $account = $folder->getAccount();
    //find if user exist in application
    $usereRepository = $this->em->getRepository(User::class);
    $targetuser = $usereRepository->findOneBy(['uuid' => $shareFolder->user_uuid]);

    if ($targetuser == null) {

      $targetuser = $this->addUserToAccountFromSharedFolderinvitation($folder, $shareFolder);
      $mailData = [
        'sendBy' => $this->security->getUser(),
        'toAccount' => $account
      ];
      $this->mailerService->mailInviteExistingUserToAccount($targetuser, $mailData);
    }
    $role = $this->em->getRepository(Role::class)->findOneBy(['code' => $shareFolder->role]);

    // verifi si l utilisateur es deja enregistrer sur le folder ou un des ses parent

    $this->userFolderRoleService->addUserFolder($targetuser, $folder, $role);

    return $this->dataFormalizerResponse->extract($folder, 'folder:read', false, "folder's successfuly retrived");
  }


  private function buildFolder($account, $parrentFolder = null)
  {
    $folder = new Folder();
    $folder->setUuid('');
    $folder->setIsInTrash(0);
    $folder->setAccount($account);
    $folder->setParentFolder($parrentFolder);

    $folder->setCreatedBy($this->security->getUser()->getUsername());

    $folderpre = $this->serializer->deserialize(
      $this->request->getContent(),
      Folder::class,
      'json',
      [
        AbstractObjectNormalizer::OBJECT_TO_POPULATE => $folder,
        "groups" => 'folder:create'
      ],
    );

    $err = $this->validator->validate($folderpre, null, 'folder:create');

    if ($err->count() > 0) {

      return $this->err($err);
    }

    return $folder;
  }


  public function updateFolderRecursively(Folder $folder, Account $account = null, $level = 0)
  {
    // Update the level with the new value
    $folder->setLevel($level);
    $folder->setUpdatedAt(new \DateTimeImmutable('now'));

    if ($account) {
      $folder->setAccount($account);
    }
    // Update the folder name
    $newFolderName = $this->makeUniqueFolderName($folder,   $folder->getName());
    $folder->setName($newFolderName);

    // Call the function recursively for each child folder
    foreach ($folder->getSubfolders() as $childFolder) {
      $this->updateFolderRecursively($childFolder, $account, $folder->getLevel() + 1);
    }
  }

  public function makeUniqueFolderName($folder, $newFolderName, $inc = 1): string
  {

    $existingFolder = $this->em->getRepository(Folder::class)->findOneBy([
      'name' => $newFolderName,
      'level' => $folder->getLevel(),
      'isInTrash' => $folder->getIsInTrash(),
      'account' => $folder->getAccount(),
      'parentFolder' => $folder->getParentFolder()
    ]);

    if ($existingFolder && $existingFolder->getUuid() != $folder->getUuid()) {
      $newFolderName =  $folder->getName() . " ($inc)";
      $inc++;

      // Call the function recursively with the modified name
      return $this->makeUniqueFolderName($folder, $newFolderName, $inc);
    }

    return $newFolderName;
  }

  public function getAllFoldersVideosRecursive(Folder $folder, $videos = [])
  {
    $videos = array_merge($videos, $folder->getVideos()->toArray());

    foreach ($folder->getSubfolders() as $childFolder) {
      $videos = $this->getAllFoldersVideosRecursive($childFolder, $videos);
    }

    return $videos;
  }


  public function moveToTrashFolderRecursively(Folder $folder, $isInTrash = false, $level = 0)
  {
    // Update the level with the new value
    $folder->setLevel($level);
    $folder->setIsInTrash($isInTrash);
    $folder->setUpdatedAt(new \DateTimeImmutable('now'));

    // Update the folder name
    $newFolderName = $this->makeUniqueFolderName($folder, $folder->getName());
    $folder->setName($newFolderName);

    // Call the function recursively for each child folder
    foreach ($folder->getSubfolders() as $childFolder) {
      $this->moveToTrashFolderRecursively($childFolder, $isInTrash, $folder->getLevel() + 1);
    }
  }

  public function deleteRecursivly($folder)
  {

    $videos = $this->getAllFoldersVideosRecursive($folder);
    foreach ($videos as $video) {
      $video->setDeletedAt(new \DateTimeImmutable('now'));
      $video->setIsDeleted(true);
      $video->setFolder(null);
    }

    foreach ($folder->getUserFolderRoles() as $userRole) {
      $folder->removeUserFolderRole($userRole);
    }
    $this->em->remove($folder);

    // Call the function recursively for each child folder
    foreach ($folder->getSubfolders() as $childFolder) {
      $this->deleteRecursivly($childFolder);
    }
  }

  public function trashFolder(string $folder_uuid)
  {
    $folder = $this->em->getRepository(Folder::class)->findOneBy(['uuid' => $folder_uuid, 'isInTrash' => false]);
    $this->folderVoter->vote($this->security->getToken(), $folder, [FolderVoter::TRASH_FOLDER]);
    $this->moveToTrashFolderRecursively($folder, true);
    $folder->setParentFolder(null);
    $videos = $this->getAllFoldersVideosRecursive($folder);
    foreach ($videos as $video) {
      $video->setIsInTrash(true);
    }
    $this->em->flush();
    return (new JsonResponseMessage())->setCode(Response::HTTP_OK)->setError("Folder moved to trash successfully!");
  }


  public function restoreFolder(string $folder_uuid)
  {
    $folder =  $this->em->getRepository(Folder::class)->findOneBy(['uuid' => $folder_uuid, 'isInTrash' => true]);
    $this->folderVoter->vote($this->security->getToken(), $folder, [FolderVoter::RESTORE_FOLDER]);
    $this->moveToTrashFolderRecursively($folder, false);
    $videos = $this->getAllFoldersVideosRecursive($folder);
    foreach ($videos as $video) {
      $video->setIsInTrash(false);
    }
    $this->em->flush();
    return (new JsonResponseMessage())->setCode(Response::HTTP_OK)->setError("Folder restored successfully!");
  }


  public function deleteFolder(string $folder_uuid)
  {
    $folderRepository = $this->em->getRepository(Folder::class);

    $folder = $folderRepository->findOneBy(['uuid' => $folder_uuid]);
    /**
     * @var Folder $folder;
     */
    $this->folderVoter->vote($this->security->getToken(), $folder, [FolderVoter::EDIT_FOLDER]);

    $this->deleteRecursivly($folder);

    $this->em->flush();

    return $this->dataFormalizerResponse->extract(null, null, false, "folder's successfuly deleted");
  }

  public function countNestedLevels(Folder $folder, $level = 1)
  {
    $maxLevel = $level;

    if ($folder->getSubfolders()) {
      foreach ($folder->getSubfolders() as $childFolder) {
        $maxLevel = max($maxLevel, $this->countNestedLevels($childFolder, $level + 1));
      }
    }

    return $maxLevel;
  }

  private function extractVideos($folder, &$videos)
  {
    if (!empty($folder['videos'])) {
      $videos = array_merge($videos, $folder['videos']);
    }

    if (!empty($folder['subFolders'])) {
      foreach ($folder['subFolders'] as $subFolder) {
        $this->extractVideos($subFolder, $videos);
      }
    }
  }


  public function createFolderRecursivly($folder,  $account, $videos = [],  $parentFolder = null)
  {


    /**
     * we reach the limit allowed to create folder
     * create the last folder and extracts all videos
     * then stop the call recursive by making subFolders = []
     */
    if ($folder['level'] >= 2) {
      $folderVideos = [];
      $this->extractVideos($folder, $folderVideos);
      $folder['videos'] = $folderVideos;
      $folder['subFolders'] = [];
    }

    $newFolder = null;
    // upload in library
    if ($folder['level'] == -1) {
      $uuid = null;
    }
    // upload in old folder
    elseif ($folder['uuid'] && $folder['level'] != -1) {
      $uuid = $folder['uuid'];
      $newFolder = $this->folderRepository->findOneBy(['uuid' => $uuid]);
    }
    // upload new folder
    elseif (!$folder['uuid'] && $folder['level'] != -1) {
      $newFolder = $this->buildFolder($account, $parentFolder);
      $this->folderRepository->add($newFolder, false);
      $newFolder->setLevel($folder['level']);
      $newFolder->setIsInTrash(false);
      $newFolder->setName($folder['name']);
      $newFolderName = $this->makeUniqueFolderName($newFolder,  $folder['name']);
      $newFolder->setName($newFolderName);

      $uuid = $newFolder->getUuid();
    }

    // update folder_uuid for eeach video file to upload
    if (!empty($folder['videos'])) {
      foreach ($folder['videos'] as $video) {
        $video['folder_uuid'] = $uuid;
        array_push($videos, $video);
      }
    }

    // Call the function recursively for each child folder
    if (!empty($folder['subFolders'])) {
      foreach ($folder['subFolders'] as $childFolder) {
        $videos = $this->createFolderRecursivly($childFolder, $account, $videos, $newFolder);
      }
    }

    return $videos;
  }


  public function getTags($video)
  {
    $tags = [];
    if (!empty($video->getTags())) {
      $arr = $video->getTags()->toArray();
      usort($arr, function ($a, $b) {
        return $a->getFolderOrder() > $b->getFolderOrder();
      });

      foreach ($arr as $tag) {
        array_push($tags, (string) $tag->getTagName());
      }
    }

    if (!empty($tags)) {
      return  '/' . join('/', $tags) . '/';
    }

    return '/';
  }

  public function zipFolders(&$zip, $folder, $isVidmizer, $path = '')
  {

    // create folder path for each video
    $path .= '/ ' . $folder->getName();
    $zip->addEmptyDir($path);

    if (!empty($folder->getVideos())) {
      foreach ($folder->getVideos() as $video) {

        $tags = $this->getTags($video);


        foreach ($video->getEncodes() as $encode) {
          if (!$isVidmizer) {
            if ($encode->getMaxDownloadAuthorized() === 0) {
              continue;
            }

            if ($video->getIsStored() === false) {
              $encode->setMaxDownloadAuthorized($encode->getMaxDownloadAuthorized() - 1);
              $this->encodeRepository->updateEncode($encode, false);
            }
          }
          $object = $this->storage->getObject($encode);
          if ($object) {
            $this->consumptionManager->addConsumptionRow($encode, 'download');
            $fileName = $encode->getName() . '_' . $encode->getQuality() . '.' . $encode->getExtension();
            $zip->addFromString($path . $tags . $fileName, $object['Body']);
          }
        }
      }
    }

    if (!empty($folder->getSubFolders())) {
      foreach ($folder->getSubFolders() as $childFolder) {
        $this->zipFolders($zip, $childFolder, $isVidmizer, $path);
      }
    }
  }

  public function downloadFolder(string $folder_uuid, $user = null)
  {

    $folder =  $this->em->getRepository(Folder::class)->findOneBy(['uuid' => $folder_uuid]);
    $this->folderVoter->vote($this->security->getToken(), $folder, [FolderVoter::DOWNLOAD_FOLDER]);

    $isVidmizer = !(array_intersect($user->getRoles(), User::ACCOUNT_ROLES));

    // Create a new ZipArchive object
    $zip = new ZipArchive();
    $zipFileName = 'greenencoder.zip';

    if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
      $this->zipFolders($zip, $folder, $isVidmizer);
      $this->em->flush();
      $zip->close();

      $response = new Response(
        file_get_contents($zipFileName),
        Response::HTTP_OK,
        [
          'Content-Type' => 'application/zip',
          'Content-Disposition' => 'attachment; filename="' . basename($zipFileName) . '"',
          'Content-Length' => filesize($zipFileName)
        ]
      );

      unlink($zipFileName); // Delete file

      return $response;
    }

    throw new BadRequestHttpException("Cannot download this folder");
  }


  public function getAllFolderVideos(string $folder_uuid)
  {
    $folder =  $this->em->getRepository(Folder::class)->findOneBy(['uuid' => $folder_uuid]);
    $this->folderVoter->vote($this->security->getToken(), $folder, [FolderVoter::SHOW_FOLDER_CONTENT]);
    $videos = $this->getAllFoldersVideosRecursive($folder);
    return $this->dataFormalizerResponse->extract($videos, 'list_of_videos', false, "folder's videos successfuly retrived");
  }
}
