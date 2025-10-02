<?php

namespace App\Security\Voter;

use App\Entity\Account;
use App\Entity\Report;
use App\Entity\Role;
use App\Entity\User;
use App\Helper\RightsHelper;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class ReportVoter extends Voter
{
    const ACCOUNT_DELETE_REPORT = "account_delete_report";
    const ACCOUNT_EDIT_REPORT = "account_edit_report";
    private $em;
    private $rightsHelper;

    public function __construct(EntityManagerInterface $em, RightsHelper $rightsHelper)
    {
        $this->em = $em;
        $this->rightsHelper = $rightsHelper;
    }
    protected function supports($attribute, $subject): bool
    {

        return in_array($attribute, [
            self::ACCOUNT_DELETE_REPORT,
            self::ACCOUNT_EDIT_REPORT,
        ])
            && $subject instanceof \App\Entity\Report;
    }
    protected function voteOnAttribute($attribute, $report, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }
        /**
         * Authorise les ADMINISTRAEUR VIDMIZER
         */
        if (array_intersect($user->getRoles(), User::ACCOUNT_ADMIN_ROLES)) {
            return true;
        }

        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case self::ACCOUNT_DELETE_REPORT:
                $this->canDeleteReport($user, $report);
                break;
            case self::ACCOUNT_EDIT_REPORT:
                $this->canEditReport($user, $report);
                break;
        }

        return false;
    }
    private function canEditReport(User $user, Report $report)
    {
        return $this->canDeleteReport($user, $report);
    }

    private function canDeleteReport(User $user, Report $report)
    {

        $account = $report->getAccount();
        /** recuper le role de  l'utilisateur courant sur l account  */
        $roleInAccount = $this->rightsHelper->FindUserAccountRole($user, $account);


        if ($roleInAccount == Role::ROLE_READER) {
            throw new Exception("Permission denied", Response::HTTP_FORBIDDEN);
            return false;
        }
        $hasAccountRight = $this->rightsHelper->verifyInAccountUserRight($user, $account, 'report_encode');
        if (!$hasAccountRight) {
            throw new Exception("Permission denied", Response::HTTP_FORBIDDEN);
            return false;
        }
        return true;
    }
}
