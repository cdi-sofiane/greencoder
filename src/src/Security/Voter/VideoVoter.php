<?php

namespace App\Security\Voter;

use App\Entity\Account;
use App\Entity\Encode;
use App\Entity\Role;
use App\Entity\User;
use App\Entity\Video;
use App\Helper\RightsHelper;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PhpParser\Node\Expr\Instanceof_;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class VideoVoter extends Voter
{
    public const ACCESS_DENIED = 'Permission denied';

    public const ACCOUNT_VIDEO_LIST = 'list_account_public_videos';
    public const ACCOUNT_FOLDER_VIDEO_LIST = 'list_account_folder_video';
    public const ACCOUNT_ADD_TAGS = 'account_add_tags';
    public const ACCOUNT_REMOVE_TAGS = 'account_remove_tags';
    public const ACCOUNT_VIDEO_REPORT = 'account_video_report';
    public const ACCOUNT_READ_VIDEO_DETAILS = 'account_read_video_details';
    public const ACCOUNT_EDIT_VIDEO = 'account_edit_video';
    public const ACCOUNT_ENCODE_VIDEO = 'account_encode_video';
    public const ACCOUNT_REMOVE_VIDEO = 'account_remove_video';
    public const ACCOUNT_DOWNLOAD_VIDEO = 'account_download_video';
    public const ACCOUNT_MOVE_VIDEOS = 'account_move_videos';
    public const ACCOUNT_TRASH_VIDEO = 'account_trash_video';
    public const ACCOUNT_RESTORE_VIDEO = 'account_restore_video';

    private $em;
    private $rightsHelper;

    public function __construct(EntityManagerInterface $em, RightsHelper $rightsHelper)
    {
        $this->em = $em;
        $this->rightsHelper = $rightsHelper;
    }
    protected function supports($attribute, $subject): bool
    {

        if (!in_array($attribute, [
            self::ACCOUNT_VIDEO_LIST,
            self::ACCOUNT_ADD_TAGS,
            self::ACCOUNT_REMOVE_TAGS,
            self::ACCOUNT_READ_VIDEO_DETAILS,
            self::ACCOUNT_EDIT_VIDEO,
            self::ACCOUNT_ENCODE_VIDEO,
            self::ACCOUNT_REMOVE_VIDEO,
            self::ACCOUNT_DOWNLOAD_VIDEO,
            self::ACCOUNT_VIDEO_REPORT,
            self::ACCOUNT_MOVE_VIDEOS,
            self::ACCOUNT_TRASH_VIDEO,
            self::ACCOUNT_RESTORE_VIDEO
        ])) {
            throw new NotFoundHttpException("Resource not found");
            return false;
        }
        if (!$subject instanceof Video) {
            throw new NotFoundHttpException("Video not found");
            return false;
        }

        return true;
    }

    protected function voteOnAttribute($attribute, $video, TokenInterface $token): bool
    {
        /**
         * @var User $user
         */
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            throw new NotFoundHttpException('User Not Found!');
            return false;
        }

        /**
         * Authorise les ADMINISTRAEUR VIDMIZER
         */
        if (array_intersect($user->getRoles(), User::ACCOUNT_ADMIN_ROLES)) {
            return true;
        }
        /**
         * Verify si l'utilisateur et la video sont sur le meme account
         */
        $userAccountWith =  $user->getUserAccountRole()->filter(function ($userAccountRole) use ($video) {

            return   $userAccountRole->getAccount() == $video->getAccount();
        });

        if (!$userAccountWith->count() > 0) {
            throw new Exception("Forbidden", Response::HTTP_FORBIDDEN);
            return false;
        }

        switch ($attribute) {
            case self::ACCOUNT_VIDEO_LIST:
                return $this->canListVideos($user, $video);
                break;
            case self::ACCOUNT_READ_VIDEO_DETAILS:
                return $this->canReadVideoDetails($user, $video);
                break;
            case self::ACCOUNT_ADD_TAGS:
                return $this->canAddAccountTags($user, $video);
                break;
            case self::ACCOUNT_VIDEO_REPORT:
                return $this->canGetVideo($user, $video);
                break;
            case self::ACCOUNT_EDIT_VIDEO:
                return $this->canEditVideo($user, $video);
                break;
            case self::ACCOUNT_ENCODE_VIDEO:
                return $this->canEncodeVideo($user, $video);
                break;
            case self::ACCOUNT_REMOVE_VIDEO:
                return $this->canRemoveVideo($user, $video);
                break;
            case self::ACCOUNT_DOWNLOAD_VIDEO:
                return $this->canDownloadVideo($user, $video);
                break;
            case self::ACCOUNT_MOVE_VIDEOS:
                return $this->canMoveVideos($user, $video);
                break;
            case self::ACCOUNT_TRASH_VIDEO:
                return $this->canTrashVideo($user, $video);
                break;
            case self::ACCOUNT_RESTORE_VIDEO:
                return $this->canRestoreVideo($user, $video);
                break;
        }

        return false;
    }

    private function canRestoreVideo($user, $video): bool
    {
        return $this->canEditVideo($user, $video);
    }

    private function canTrashVideo($user, $video): bool
    {
        return $this->canEditVideo($user, $video);
    }


    private function canMoveVideos($user, $video): bool
    {
        return $this->verifyCanDoActionOnVideo($user, $video);
    }

    private function canListVideos(User $user, Video $video): bool
    {
        $isGranted = $this->rightsHelper->verifyUserAccountRole($user, $video->getAccount(), [Role::ROLE_ADMIN, Role::ROLE_EDITOR]);
        if (!$isGranted) {
            throw new AccessDeniedException(self::ACCESS_DENIED);
            return false;
        }

        if ($video->getFolder()) {
            $currentFolderRole = $this->rightsHelper->findUserFolderRoleHeritage($user, $video->getFolder());
            if (!$currentFolderRole) {
                throw new AccessDeniedException(self::ACCESS_DENIED);
                return false;
            }
        }

        return true;
    }


    private function canDownloadVideo($user, $video): bool
    {
        return $this->canEditVideo($user, $video);
    }

    private function canRemoveVideo($user, $video): bool
    {
        $permission = 'video_delete';
        return $this->verifyUserPermissionByFolderThenAccount($user, $video, $permission);
    }

    private function canEncodeVideo($user, $video): bool
    {
        return $this->canEditVideo($user, $video);
    }

    private function canEditVideo(User $user, Video $video): bool
    {
        if (!$this->hasAccessToAccount($user, $video->getAccount())) {
            throw new AccessDeniedException(self::ACCESS_DENIED);
            return false;
        }

        if ($video->getFolder() == null) {
            $roleInAccount = $this->rightsHelper->FindUserAccountRole($user, $video->getAccount());
            if (in_array($roleInAccount, [Role::ROLE_READER])) {
                return false;
            }
        }

        if (!$this->hasEditionRoleInFolder($user, $video)) {
            throw new AccessDeniedException(self::ACCESS_DENIED);
            return false;
        }

        return true;
    }

    private function canReadVideoDetails(User $user, Video $video): bool
    {
        if (!$this->hasAccessToAccount($user, $video->getAccount())) {
            throw new AccessDeniedException(self::ACCESS_DENIED);
            return false;
        }

        if ($video->getFolder()) {
            $currentFolderRole = $this->rightsHelper->findUserFolderRoleHeritage($user, $video->getFolder());
            if (!$currentFolderRole) {
                throw new AccessDeniedException(self::ACCESS_DENIED);
                return false;
            }
        }
        return true;
    }


    /**
     * verify sur l account et/ou sur le folder si on peut ajouter/supp sur la video un tags
     * return boolean
     */
    private function canAddAccountTags(User $user, Video $video): bool
    {
        return $this->verifyCanDoActionOnVideo($user, $video);
    }

    private function canGetVideo($user, $video): bool
    {
        return $this->verifyCanDoActionOnVideo($user, $video);
    }

    private function verifyCanDoActionOnVideo($user, $video): bool
    {
        if (!$this->hasAccessToAccount($user, $video->getAccount())) {
            return false;
        }
        if ($video->getFolder() == null) {
            $roleInAccount = $this->rightsHelper->FindUserAccountRole($user, $video->getAccount());

            if (in_array($roleInAccount, [Role::ROLE_READER])) {
                return false;
            }
        }

        if ($video->getFolder()) {
            $currentFolderRole = $this->rightsHelper->findUserFolderRoleHeritage($user, $video->getFolder());
            if ($currentFolderRole == Role::ROLE_READER) {
                return false;
            }
        }
        return true;
    }

    private function hasEditionRoleInFolder(User $user, Video $video): bool
    {
        if ($video->getFolder()) {
            $currentFolderRole = $this->rightsHelper->findUserFolderRoleHeritage($user, $video->getFolder());
            if ($currentFolderRole == Role::ROLE_READER) {
                return false;
            }
        }
        return true;
    }

    private function hasAccessToAccount(User $user, Account $account): bool
    {
        $userAccountRole = $user->getUserAccountRole()->filter(function ($userAccountRole) use ($account) {
            return $userAccountRole->getAccount() == $account;
        });

        if (!$userAccountRole->count() > 0) {
            return false;
        }
        return true;
    }

    private function verifyUserPermissionByFolderThenAccount(User $user, Video $video, string $permission): bool
    {
        $account = $video->getAccount();
        $folder = $video->getFolder();

        if ($folder == null) {
            return $this->rightsHelper->verifyInAccountUserRight($user, $account, $permission);
        }

        return $this->rightsHelper->canManageFolder($user, $folder, $permission);
    }
}
