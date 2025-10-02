<?php

namespace App\Security\Voter;

use App\Entity\Account;
use App\Entity\AccountRoleRight;
use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserAccountRole;
use App\Helper\RightsHelper;
use App\Services\AuthorizationService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

class AccountVoter extends Voter
{
    const ACCOUNT_INVITATION = 'invite_in_account';
    const ACCOUNT_REMOVE_USER = 'remove_account_user';
    const ACCOUNT_CREATE_FOLDER = 'account_create_folder';
    const ACCOUNT_FIND_ONE = 'find_one_account';
    const ACCOUNT_EDIT_ONE = 'edit_one_account';
    const ACCOUNT_ORDER_PACKAGE = 'account_order_pack';
    const ACCOUNT_DASHBOARD = 'account_dashboard';
    const ACCOUNT_GENERATE_APIKEY = 'account_generate_apiKey';
    const ACCOUNT_FIND_USERS = 'account_find_users';
    const ACCOUNT_USER_EDIT_ROLE = 'account_edit_role_user';
    const ACCOUNT_UPLOAD_LOGO = 'account_upload_logo';
    const ACCOUNT_ORDER_FIND = 'account_order_find';
    const ACCOUNT_ORDER_REMOVE = 'account_order_remove';
    const ACCOUNT_VIDEO_LIST = 'account_video_list';
    const ACCOUNT_ORDER_SWAP = 'account_order_swap';
    const ACCOUNT_SHOW_TAGS = 'account_show_tags';
    const ACCOUNT_REMOVE_TAGS = 'account_remove_tags';
    const ACCOUNT_ADD_TAGS = 'account_add_tags';
    const ACCOUNT_EDIT_REPORT_CONFIG = 'edit_report_config';
    const ACCOUNT_FIND_REPORT_CONFIG = 'find_report_config';
    const ACCOUNT_FIND_REPORTS = 'find_account_report';
    const ACCOUNT_CREATE_REPORT = 'create_account_report';
    const ACCOUNT_ADMIN_SWAP = 'account_swap_admin_user'; // change le role de l'admin vers un utilisateur
    const ACCOUNT_ENCODE = 'account_encode';
    const ACCOUNT_MOVE_VIDEOS = 'account_move_videos';
    const ACCOUNT_MOVE_FOLDERS = 'account_move_folders';
    const ACCOUNT_MOVE_VIDEOS_BETWEEN_ACCOUNT = 'account_move_videos_between_account';
    const ACCOUNT_MAKE_PAYMENT = 'account_make_payment';
    const ACCOUNT_FIND_INVOICE = 'account_find_invoice';
    const ACCOUNT_FIND_TRASH = 'account_find_trash';
    const ACCOUNT_EDIT_RIGHTS = 'account_edit_rights';
    const ACCOUNT_FIND_ONE_BY_EMAIL = 'account_find_one_by_email';

    private $em;
    private $rightsHelper;

    public function __construct(EntityManagerInterface $em, RightsHelper $rightsHelper)
    {
        $this->em = $em;
        $this->rightsHelper = $rightsHelper;
    }


    protected function supports($attribute, $account): bool
    {

        if (!in_array(
            $attribute,
            [
                self::ACCOUNT_INVITATION,
                self::ACCOUNT_CREATE_FOLDER,
                self::ACCOUNT_FIND_ONE,
                self::ACCOUNT_EDIT_ONE,
                self::ACCOUNT_ORDER_PACKAGE,
                self::ACCOUNT_GENERATE_APIKEY,
                self::ACCOUNT_FIND_USERS,
                self::ACCOUNT_DASHBOARD,
                self::ACCOUNT_USER_EDIT_ROLE,
                self::ACCOUNT_UPLOAD_LOGO,
                self::ACCOUNT_REMOVE_USER,
                self::ACCOUNT_ORDER_FIND,
                self::ACCOUNT_ADMIN_SWAP,
                self::ACCOUNT_ORDER_REMOVE,
                self::ACCOUNT_VIDEO_LIST,
                self::ACCOUNT_ORDER_SWAP,
                self::ACCOUNT_ORDER_PACKAGE,
                self::ACCOUNT_SHOW_TAGS,
                self::ACCOUNT_REMOVE_TAGS,
                self::ACCOUNT_EDIT_REPORT_CONFIG,
                self::ACCOUNT_FIND_REPORT_CONFIG,
                self::ACCOUNT_FIND_REPORTS,
                self::ACCOUNT_CREATE_REPORT,
                self::ACCOUNT_ENCODE,
                self::ACCOUNT_MOVE_VIDEOS,
                self::ACCOUNT_MOVE_FOLDERS,
                self::ACCOUNT_MOVE_VIDEOS_BETWEEN_ACCOUNT,
                self::ACCOUNT_MAKE_PAYMENT,
                self::ACCOUNT_FIND_INVOICE,
                self::ACCOUNT_FIND_TRASH,
                self::ACCOUNT_EDIT_RIGHTS,
                self::ACCOUNT_FIND_ONE_BY_EMAIL
            ]
        )) {
            throw new Exception("account not found", Response::HTTP_NOT_FOUND);
            return false;
        }

        if (!$account instanceof Account) {
            throw new Exception("account not found", Response::HTTP_NOT_FOUND);
            return false;
        }


        return true;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {

        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            throw new Exception("user not found", Response::HTTP_NOT_FOUND);
            return false;
        }

        if (array_intersect($user->getRoles(), User::ACCOUNT_ADMIN_ROLES)) {
            return true;
        }


        switch ($attribute) {
            case self::ACCOUNT_INVITATION:
                return $this->canInviteInAccount($user, $subject);

            case self::ACCOUNT_ORDER_REMOVE:
                return $this->canRemoveOrder($user, $subject);

                break;
            case self::ACCOUNT_ORDER_SWAP:
                return $this->canUpgradeOrder($user, $subject);

                break;
            case self::ACCOUNT_CREATE_FOLDER:
                return $this->canCreateAccountFolder($user, $subject);

                break;
            case self::ACCOUNT_FIND_ONE:

                return $this->canGetAccount($user, $subject);

                break;
            case self::ACCOUNT_EDIT_ONE:

                return $this->canEditAccount($user, $subject);

                break;
            case self::ACCOUNT_UPLOAD_LOGO:

                return $this->canUploadLogo($user, $subject);

                break;
            case self::ACCOUNT_ORDER_PACKAGE:

                return $this->canOrderPackage($user, $subject);

                break;
            case self::ACCOUNT_DASHBOARD:

                return $this->canDisplayDashboard($user, $subject);

                break;
            case self::ACCOUNT_FIND_USERS:

                return $this->canFindUsers($user, $subject);

                break;
            case self::ACCOUNT_USER_EDIT_ROLE:

                return $this->canEditAccountUserRole($user, $subject);

                break;
            case self::ACCOUNT_ADMIN_SWAP:

                return $this->canSwapAdminRoleToUser($user, $subject);

                break;
            case self::ACCOUNT_REMOVE_USER:

                return $this->canRemoveUserFromAccount($user, $subject);

                break;
            case self::ACCOUNT_ORDER_FIND:

                return $this->canFindOrders($user, $subject);

                break;
            case self::ACCOUNT_VIDEO_LIST:

                return $this->canFindVideos($user, $subject);

                break;
            case self::ACCOUNT_SHOW_TAGS:

                return $this->canShowAccountTags($user, $subject);

                break;
            case self::ACCOUNT_REMOVE_TAGS:

                return $this->canRemoveAccountTags($user, $subject);

                break;
            case self::ACCOUNT_ADD_TAGS:

                return $this->canAddAccountTags($user, $subject);

                break;
            case self::ACCOUNT_EDIT_REPORT_CONFIG:

                return $this->canEditReportConfig($user, $subject);

                break;
            case self::ACCOUNT_FIND_REPORT_CONFIG:

                return $this->cantGetReportConfig($user, $subject);

                break;
            case self::ACCOUNT_FIND_REPORTS:

                return $this->canGetReports($user, $subject);

                break;
            case self::ACCOUNT_CREATE_REPORT:

                return $this->canCreateReport($user, $subject);

                break;
            case self::ACCOUNT_GENERATE_APIKEY:
                /**
                 * only userAccountRole admin
                 */
                $roles = [Role::ROLE_ADMIN];
                return $this->canGenerateApiKey($user, $subject, $roles);

                break;
            case self::ACCOUNT_ENCODE:
                return $this->canEncode($user, $subject);
                break;
            case self::ACCOUNT_MOVE_VIDEOS:
                return $this->canMoveVideos($user, $subject);
                break;
            case self::ACCOUNT_MOVE_FOLDERS:
                return $this->canMoveFolders($user, $subject);
                break;
            case self::ACCOUNT_MOVE_VIDEOS_BETWEEN_ACCOUNT:
                return $this->canMoveToAccount($user, $subject);
                break;
            case self::ACCOUNT_MAKE_PAYMENT:
                return $this->canMakePayment($user, $subject);
                break;
            case self::ACCOUNT_FIND_INVOICE:
                return $this->canFindInvoices($user, $subject);
                break;
            case self::ACCOUNT_FIND_TRASH:
                return $this->canFindTrash($user, $subject);
                break;
            case self::ACCOUNT_EDIT_RIGHTS:
                return $this->canEditRights($user, $subject);
                break;
            case self::ACCOUNT_FIND_ONE_BY_EMAIL:
                return $this->canFindOneByEmail($user, $subject);
                break;
            default:
                throw new Exception("account not found", Response::HTTP_NOT_FOUND);
                return false;
                break;
        }

        return false;
    }

    private function canFindOneByEmail($user, $account)
    {
        $userInAccount = $user->getUserAccountRole()->filter(function ($userAccountRole) use ($account) {
            return $userAccountRole->getAccount() === $account;
        });
        if (!$userInAccount->count() > 0) {
            throw new Exception("Access Forbidden!", Response::HTTP_FORBIDDEN);
        }
        return true;
    }

    private function canEditRights($user, $account)
    {
        $userRoleInAccount = $this->rightsHelper->FindUserAccountRole($user, $account);
        if ($userRoleInAccount == Role::ROLE_READER) {
            throw new Exception("Permission denied", Response::HTTP_FORBIDDEN);
            return false;
        }
        return true;
    }

    private function canFindTrash($user, $account)
    {
        $userRoleInAccount = $this->rightsHelper->FindUserAccountRole($user, $account);
        if ($userRoleInAccount == Role::ROLE_READER) {
            throw new Exception("can't access report-config", Response::HTTP_FORBIDDEN);
            return false;
        }
        return true;
    }

    private function canFindInvoices($user, $account)
    {
        $isGranted = $this->rightsHelper->verifyInAccountUserRight($user, $account, 'account_invoice');
        if (!$isGranted) {
            throw new Exception("Permission denied", Response::HTTP_FORBIDDEN);
            return false;
        }
        return true;
    }

    private function canMakePayment($user, $account)
    {
        $isGranted = $this->rightsHelper->verifyInAccountUserRight($user, $account, 'account_payment');
        if (!$isGranted) {
            throw new Exception("Permission denied", Response::HTTP_FORBIDDEN);
            return false;
        }
        return true;
    }

    private function canMoveToAccount($user, $account)
    {
        throw new Exception("Permission denied", Response::HTTP_FORBIDDEN);
    }

    private function canMoveFolders($user, $account)
    {
        return $this->canMoveVideos($user, $account);
    }

    private function canMoveVideos($user, $account)
    {
        $userRoleInAccount = $this->rightsHelper->FindUserAccountRole($user, $account);
        if ($userRoleInAccount == Role::ROLE_READER) {
            throw new Exception("Permission denied", Response::HTTP_FORBIDDEN);
            return false;
        }
        return true;
    }

    private function canEncode($user, $account)
    {
        $userRoleInAccount = $this->rightsHelper->FindUserAccountRole($user, $account);
        if ($userRoleInAccount == Role::ROLE_READER) {
            throw new Exception("Permission denied", Response::HTTP_FORBIDDEN);
            return false;
        }
        return true;
    }

    private function canEditReportConfig($user, $account)
    {

        $userRoleInAccount = $this->rightsHelper->FindUserAccountRole($user, $account);
        if ($userRoleInAccount == Role::ROLE_READER) {
            throw new Exception("can't access report-config", Response::HTTP_FORBIDDEN);
            return false;
        }
        $hasRight = $this->rightsHelper->verifyInAccountUserRight($user, $account, 'report_config');
        if (!$hasRight) {
            throw new Exception("can't edit report-config", Response::HTTP_FORBIDDEN);
        }
        return true;
    }

    private function canCreateReport($user, $account)
    {
        return $this->canGetAccount($user, $account);
    }
    private function canGetReports($user, $account)
    {

        return $this->canGetAccount($user, $account);
    }
    private function cantGetReportConfig($user, $account)
    {
        return $this->canGetAccount($user, $account);
    }
    private function canShowAccountTags($user, $account)
    {

        return true;
    }
    private function canAddAccountTags($user, $account)
    {

        return true;
    }
    private function canRemoveAccountTags($user, $account)
    {
        $isGranted = $this->rightsHelper->verifyInAccountUserRight($user, $account, 'account_invite');
        if (!$isGranted) {
            throw new Exception("Not enought permission", Response::HTTP_FORBIDDEN);
        }
        return true;
    }
    private function canUpgradeOrder($user, $account)
    {
        return $this->canOrderPackage($user, $account);
    }
    private function canFindVideos($user, $account)
    {
        $userInAccount = $user->getUserAccountRole()->filter(function ($userAccountRole) use ($account) {
            return $userAccountRole->getAccount() === $account;
        });
        if (!$userInAccount->count() > 0) {
            throw new Exception("Access Forbidden!", Response::HTTP_FORBIDDEN);
        }

        $isGrantedRight = $this->rightsHelper->verifyInAccountUserRight($user, $account, 'video_library');
        if (!$isGrantedRight == true) {
            throw new Exception("forbidden", Response::HTTP_FORBIDDEN);
        }
        return true;
    }

    private function canRemoveOrder($user, $account)
    {
        return $this->canOrderPackage($user, $account);
    }
    private function canFindOrders($user, $account)
    {
        $this->canGetAccount($user, $account);

        $isGranted = $this->rightsHelper->verifyUserAccountRole($user, $account, [Role::ROLE_ADMIN, Role::ROLE_EDITOR]);

        if (!$isGranted) {
            throw new Exception("Not enought permission", Response::HTTP_FORBIDDEN);
        }
        return true;
    }

    private function canRemoveUserFromAccount($user, $account)
    {
        $this->canGetAccount($user, $account);
        if (!$this->rightsHelper->verifyUserAccountRole($user, $account, [Role::ROLE_ADMIN])) {
            throw new Exception("User c'ant be removed", Response::HTTP_FORBIDDEN);
            return false;
        }
        return true;
    }
    private function canSwapAdminRoleToUser($user, $account)
    {
        $isGranted = $this->canGetAccount($user, $account);
        $isGrantedRole = $this->rightsHelper->verifyUserAccountRole($user, $account, [Role::ROLE_ADMIN]);
        if (!$isGrantedRole == true) {
            throw new Exception("forbidden", Response::HTTP_FORBIDDEN);
        }
        return true;
    }
    private function canEditAccountUserRole($user, $account)
    {
        $isGranted = $this->canGetAccount($user, $account);
        $isGrantedRight = $this->rightsHelper->verifyInAccountUserRight($user, $account, 'account_invite');

        if (!$isGrantedRight == true) {
            throw new Exception("forbidden", Response::HTTP_FORBIDDEN);
        }
        return true;
    }
    private function canFindUsers($user, $account)
    {

        $isGranted = $this->canGetAccount($user, $account);

        return $isGranted;
    }
    private function canUploadLogo($user, $account)
    {
        return $this->canEditAccount($user, $account);
    }

    private function canGenerateApiKey($user, $account, $roles = null)
    {
        $hasRoles = $this->rightsHelper->verifyUserAccountRole($user, $account, $roles);
        if (!$hasRoles == true) {
            throw new Exception("Forbidden", Response::HTTP_FORBIDDEN);
        }
        return $hasRoles;
    }
    private function canDisplayDashboard($user, $account)
    {
        $isInAccount = $this->rightsHelper->verifyUserInAccount($user, $account);

        $isGranted = $this->rightsHelper->verifyInAccountUserRight($user, $account, 'dashboard');

        return true;
    }
    private function canOrderPackage($user, $account)
    {
        $this->canGetAccount($user, $account);
        $isGranted = $this->rightsHelper->verifyInAccountUserRight($user, $account, 'account_payment');

        if (!$isGranted) {
            throw new Exception("Not enough permission", Response::HTTP_FORBIDDEN);
        }
        return true;
    }
    private function canEditAccount(User $user, $account)
    {
        $userInAccount = $user->getUserAccountRole()->filter(function ($userAccountRole) use ($account) {

            return $userAccountRole->getAccount() === $account && (in_array($userAccountRole->getRole()->getCode(), [Role::ROLE_ADMIN]));
        });
        if (!$userInAccount->count() > 0) {

            throw new Exception("not enought permission ", Response::HTTP_FORBIDDEN);
            return false;
        }
        return true;
    }

    private function canGetAccount(User $user, Account $account)
    {
        $userInAccount = $user->getUserAccountRole()->filter(function ($userAccountRole) use ($account) {
            return $userAccountRole->getAccount() === $account;
        });

        if (!$userInAccount->count() > 0) {
            throw new Exception("Forbidden", Response::HTTP_FORBIDDEN);

            return false;
        }

        return true;
    }
    private function canInviteInAccount(User $user, Account $account)
    {
        $this->verifyAccountInvitation($user, $account);

        $this->canGetAccount($user, $account);

        return $this->verifyUserAccountRoleRight($user, $account);
    }

    private function canCreateAccountFolder(User $user, Account $account)
    {
        /**
         * verfier le droit d'un utilisateur sur un account
         */
        $userAccountWith =  $user->getUserAccountRole()->filter(function ($userAccountRole) use ($account) {

            return $userAccountRole->getAccount() === $account;
        });

        if (!$userAccountWith->count() > 0) {
            throw new Exception("Access Forbidden!", Response::HTTP_FORBIDDEN);

            return false;
        }
        // if (!$account->getIsMultiAccount()) {
        //     throw new Exception("Can't create Folder!", Response::HTTP_FORBIDDEN);

        //     return false;
        // }
        return  $this->rightsHelper->verifyInAccountUserRight($user, $account, 'account_invite');
    }

    private function verifyUserAccountRoleRight($user, $account)
    {
        $this->verifyAccountInvitation($user, $account);
        /**
         * @var \App\Repository\UserAccountRoleRepository $userRoleRepository
         */
        $userRoleRepository = $this->em->getRepository(UserAccountRole::class);

        $acc = $userRoleRepository->findOneBy(["account" => $account, "user" => $user]);
        if ($acc == null) {
            throw new Exception("Forbidden", Response::HTTP_FORBIDDEN);
            return false;
        }

        /**
         * @var \App\Repository\AccountRoleRightRepository $accountRoleRightRepository
         */
        $accountRoleRightRepository = $this->em->getRepository(AccountRoleRight::class);

        $userRoleAccount = $accountRoleRightRepository->findBy(['account' => $account, "role" => $acc->getRole()]);
        if ($userRoleAccount == null) {
            throw new Exception("Forbidden", Response::HTTP_FORBIDDEN);
            return false;
        }
        foreach ($userRoleAccount as  $accountRole) {

            if ($accountRole->getRights()->getCode() === 'account_invite') {

                return true;
            }
        }

        throw new Exception("Forbidden", Response::HTTP_FORBIDDEN);
        return false;
    }
    private function verifyAccountInvitation($user, $account)
    {
        if (!$account->getIsMultiAccount()) {
            throw new Exception("can\'t invite on account not multi-users", Response::HTTP_FORBIDDEN);
            return false;
        }

        if ($account->getUserAccountRole()->count() > $account->getMaxInvitations()) {
            throw new Exception("Account limite invitation", Response::HTTP_FORBIDDEN);

            return false;
        }
        return true;
    }

    private function  verifyAccountRoleAndRight($user, $account)
    {
        $this->canGetAccount($user, $account);
        if (!$this->rightsHelper->verifyUserAccountRole($user, $account, [Role::ROLE_ADMIN])) {
            throw new Exception("User can't be removed", Response::HTTP_FORBIDDEN);
            return false;
        }
        return true;
    }
}
