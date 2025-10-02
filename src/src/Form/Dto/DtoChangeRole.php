<?php

namespace App\Form\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class DtoChangeRole
{

  /**
   * @Assert\NotBlank(message="Le champ user_uuid ne peut pas être vide.")
   * @Assert\Uuid(message="Le champ user_uuid doit être un UUID valide.")
   * @Groups({"account:roles"})
   * @OA\Property(property="user_uuid", type="string"),
   */
  public $user_uuid;

  /**
   * @Assert\NotBlank(message="Le champ role ne peut pas être vide.")
   * @Assert\Choice({"reader","editor"},message="Valid fields  'reader','editor'",groups={"account:roles"})
   * @Groups({"account:roles"})
   * @OA\Property(property="role", type="string"),
   */
  public $role;
}
