<?php

namespace App\Security\Voter;

use App\Entity\Account;
use App\Entity\AccountRoleRight;
use App\Entity\Folder;
use App\Entity\Role;
use App\Entity\User;
use App\Helper\RightsHelper;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

use function PHPUnit\Framework\matches;

class FolderVoter extends Voter
{
    const CREATE_FOLDER = "create_folder";
    const EDIT_FOLDER = "edit_folder";
    const ARCHIVE_FOLDER = "archive_folder";
    const READ_FOLDER = "read_folder";
    const SHARE_FOLDER = "share_folder";
    const ACCOUNT_INVITE = "account_invite";
    const FIND_FOLDER_USERS = "find_users_folder";
    const REMOVE_MEMBER_FOLDER = "remove_user_folder";
    const SHOW_FOLDER_CONTENT = "show_folder_content";
    const ENCODE_IN_FOLDER = "can_encode_in_folder";
    const MOVE_VIDEOS = "move_videos";
    const MOVE_FOLDERS = "move_videos";
    const RESTORE_FOLDER = "restore_folder";
    const TRASH_FOLDER = 'trash_folder';
    const DOWNLOAD_FOLDER = 'download_folder';

    private $em;
    private $rightsHelper;
    public function __construct(
        EntityManagerInterface $em,
        RightsHelper $rightsHelper
    ) {
        $this->em = $em;
        $this->rightsHelper = $rightsHelper;
    }

    protected function supports($attribute, $subject): bool
    {

        if (!in_array($attribute, [
            self::CREATE_FOLDER,
            self::EDIT_FOLDER,
            self::READ_FOLDER,
            self::SHARE_FOLDER,
            self::FIND_FOLDER_USERS,
            self::SHOW_FOLDER_CONTENT,
            self::REMOVE_MEMBER_FOLDER,
            self::ENCODE_IN_FOLDER,
            self::MOVE_VIDEOS,
            self::MOVE_FOLDERS,
            self::TRASH_FOLDER,
            self::RESTORE_FOLDER,
            self::DOWNLOAD_FOLDER
        ])) {
            throw new Exception("not found", Response::HTTP_NOT_FOUND);
            return false;
        }
        if (!$subject instanceof Folder) {
            throw new Exception("folder not found", Response::HTTP_NOT_FOUND);
            return false;
        }

        return true;
    }

    protected function voteOnAttribute($attribute, $folder, TokenInterface $token): bool
    {

        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            throw new Exception("user not found", Response::HTTP_NOT_FOUND);
            return false;
        }

        /**
         * Authorise les ADMINISTRAEUR VIDMIZER
         */
        if (array_intersect($user->getRoles(), User::ACCOUNT_ADMIN_ROLES)) {
            return true;
        }
        /**
         * @var User $user
         * verifie si l'utilisateur existe sur l'account cible
         */
        $userAccountWith =  $user->getUserAccountRole()->filter(function ($userAccountRole) use ($folder) {

            return   $userAccountRole->getAccount() == $folder->getAccount();
        });

        if (!$userAccountWith->count() > 0) {
            throw new Exception("Forbidden", Response::HTTP_FORBIDDEN);
            return false;
        }



        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case self::CREATE_FOLDER:
                return $this->canCreateSubFolder($user, $folder);
                break;
            case self::ENCODE_IN_FOLDER:
                return $this->canEncodeInFolder($user, $folder);
                break;
            case self::EDIT_FOLDER:
                return $this->canEditFolder($user, $folder);
                break;
            case self::SHARE_FOLDER:
                return $this->canShareFolder($user, $folder);
                break;
            case self::READ_FOLDER:
                return $this->canReadFolder($user, $folder);
                break;
            case self::FIND_FOLDER_USERS:
                return $this->cantShowFolderUsers($user, $folder);
                break;
            case self::REMOVE_MEMBER_FOLDER:
                return $this->canRemoveUserFromFolder($user, $folder);
                break;
            case self::SHOW_FOLDER_CONTENT:
                return $this->canShowFolderContent($user, $folder);
                break;
            case self::MOVE_VIDEOS:
                return $this->canMoveVideos($user, $folder);
                break;
            case self::MOVE_FOLDERS:
                return $this->canMoveFolders($user, $folder);
                break;
            case self::TRASH_FOLDER:
                return $this->canTrashFolder($user, $folder);
                break;
            case self::RESTORE_FOLDER:
                return $this->canRestoreFolder($user, $folder);
                break;
            case self::DOWNLOAD_FOLDER:
                return $this->canDownloadFolder($user, $folder);
                break;
        }

        return false;
    }


    private function canDownloadFolder($user, $folder)
    {
        return $this->canEditFolder($user, $folder);
    }

    private function canRestoreFolder($user, $folder)
    {
        return $this->canEditFolder($user, $folder);
    }

    private function canTrashFolder($user, $folder)
    {
        return $this->canEditFolder($user, $folder);
    }

    private function canMoveVideos($user, $folder)
    {
        return $this->canMoveFolders($user, $folder);
    }

    private function canMoveFolders($user, $folder)
    {
        $isGranted =  $this->rightsHelper->canManageFolder($user,  $folder, self::ACCOUNT_INVITE);
        if (!$isGranted) {
            return false;
        }
        return true;
    }


    private function canShowFolderContent($user, $folder)
    {
        $userRoleInFolderOrHeritage = $this->rightsHelper->findUserFolderRoleHeritage($user, $folder);
        if ($userRoleInFolderOrHeritage == null) {
            throw new Exception("Permission Denied", Response::HTTP_FORBIDDEN);
        }
        return $userRoleInFolderOrHeritage;
    }
    private function canRemoveUserFromFolder($user, $folder)
    {
        return $this->canReadFolder($user, $folder);
    }
    private function canEncodeInFolder($user, $folder)
    {
        $isGranted = $this->rightsHelper->canManageFolder($user, $folder, "video_encode");
        if (!$isGranted) {
            throw new Exception("Permission Denied", Response::HTTP_FORBIDDEN);
            return false;
        }
        return true;
    }


    private function cantShowFolderUsers($user, $folder)
    {
        return $this->canShowFolderContent($user, $folder);
    }

    private function canReadFolder(User $user, Folder $folder)
    {

        $isGranted = $this->rightsHelper->canManageFolder($user, $folder, self::ACCOUNT_INVITE);
        if (!$isGranted) {
            throw new Exception("Permission denied", 403);
            return false;
        }
        return false;
    }

    private function canCreateSubFolder(User $user, Folder $folder)
    {

        $isGranted = $this->rightsHelper->canManageFolder($user, $folder, self::ACCOUNT_INVITE);

        if (!$isGranted) {
            throw new Exception("Permission denied", 403);
            return false;
        }

        if ($folder->getLevel() >= 2) {
            throw new Exception("Limite reached !", Response::HTTP_FORBIDDEN);
            return false;
        }

        return true;
    }

    private function canEditFolder(User $user, Folder $folder)
    {
        $isGranted =  $this->rightsHelper->canManageFolder($user,  $folder, self::ACCOUNT_INVITE);
        if (!$isGranted) {
            throw new Exception("Permission denied", 403);
            return false;
        }
        return true;
    }

    private function canShareFolder(User $user, Folder $folder)
    {

        $isGranted = $this->rightsHelper->canManageFolder($user,  $folder, self::ACCOUNT_INVITE);
        if (!$isGranted) {
            throw new Exception("Permission denied", 403);
            return false;
        }

        $account = $folder->getAccount();

        return true;
    }
}
