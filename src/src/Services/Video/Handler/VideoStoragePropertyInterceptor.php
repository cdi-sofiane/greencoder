<?php


namespace App\Services\Video\Handler;

use App\Entity\Account;
use App\Entity\User;

class VideoStoragePropertyInterceptor
{


  public function __construct()
  {
  }
  /**
   * @description if the target  account is MultiAccount or user has Role Admin return true
   * @var User
   * @return boolean
   */
  static function handle(Account $account, User $user)
  {
    if ($account->getIsMultiAccount() || in_array($user->getRoles()[0], User::ACCOUNT_ADMIN_ROLES)) {

      return true;
    }
    return false;
  }
}
