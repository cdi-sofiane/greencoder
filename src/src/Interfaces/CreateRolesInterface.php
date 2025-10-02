<?php


namespace App\Interfaces;

use App\Entity\Role;

interface CreateRolesInterface
{
  public function createAccountRole($type, $right): Role;
}
