<?php

namespace App\Services\Permission\Folder;

use App\Entity\Folder;
use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserFolderRole;
use Doctrine\ORM\EntityManagerInterface;

class UserFolderRoleService
{
  private $em;
  public function __construct(EntityManagerInterface $em)
  {
    $this->em = $em;
  }

  /**
   * share a folder for an user with specific role
   *
   *
   */
  public function addUserFolder(User $user, Folder $folder, Role $role)
  {

    switch ($role->getCode()) {
      case 'reader':
        $this->createfolderPermision($user, $folder, $role);
        break;
      case 'editor':
        $this->createfolderPermision($user, $folder, $role);
        break;

      default:
        # code...
        break;
    }
  }

  private function createfolderPermision($user, $folder, $role)
  {
    $userFolderRoleRepository = $this->em->getRepository(UserFolderRole::class);

    $userFolderrole = $userFolderRoleRepository->findOneBy(['user' => $user, "folder" => $folder]);

    if ($userFolderrole != null) {
      $userFolderrole->setRole($role);
      /**
       * @var $userFolderRoleRepository $userFolderRoleRepository
       */
      $userFolderRoleRepository->add($userFolderrole, true);
      return $userFolderrole;
    }
    $userFolderRole = new UserFolderRole();
    $userFolderRole
      ->setUser($user)
      ->setFolder($folder)
      ->setRole($role);
    /**
     * @var $userFolderRoleRepository $userFolderRoleRepository
     */

    $userFolderRoleRepository->add($userFolderRole, true);
  }

  public function removeUserFolderRole(User $user, Folder $folder)
  {

    $userFolderRole = $user->getUserFolderRoles()->filter(function ($userFolderRole) use ($folder) {
      return $userFolderRole->getFolder() == $folder;
    });

    if (!$userFolderRole->count() > 0) {
      return;
    }
    $userFolderRoleRepository = $this->em->getRepository(UserFolderRole::class);
    /**
     * @var $userFolderRoleRepository $userFolderRoleRepository
     */
    $userFolderRoleRepository->remove($userFolderRole->first());
  }
}
