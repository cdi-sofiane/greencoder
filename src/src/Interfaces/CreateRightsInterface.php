<?php


namespace App\Interfaces;

use App\Entity\Right;
use App\Entity\Role;

interface CreateRightsInterface
{
  public function createRight(Role $role, $right): array;
}
