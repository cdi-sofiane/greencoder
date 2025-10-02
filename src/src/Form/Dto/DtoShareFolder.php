<?php


namespace App\Form\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use OpenApi\Annotations\Schema;




class DtoShareFolder
{

  /**
   * @Assert\NotBlank(message="Le champ folder_uuid ne peut pas être vide.")
   * @Assert\Uuid(message="Le champ folder_uuid doit être un UUID valide.")
   * @Groups({"folder:share","folder:edit:role"})
   * @OA\Property(property="folder_uuid",readOnly=true,type="string")
   */
  public $folder_uuid;
  /**
   * @Assert\NotBlank(message="Le champ folder_uuid ne peut pas être vide.")
   * @Assert\Uuid(message="Le champ folder_uuid doit être un UUID valide.")
   * @Groups({"folder:edit:role"})
   * @OA\Property(property="user_uuid",type="string")
   */
  public $user_uuid;

  /**
   * @Assert\NotBlank(message="Le champ role ne peut pas être vide.")
   * @Assert\Choice({"reader","editor"},message="Valid fields  'reader','editor'",groups={"folder:share"})
   * @Groups({"folder:share","folder:edit:role"})
   * @OA\Property(property="role", type="string"),
   */
  public $role;

  /**
   * @Assert\NotBlank(message="Le champ email ne peut pas être vide.")
   * @Assert\Email(message="Le format de l'email est invalide.", mode="strict")

   * @Groups({"folder:share"})
   * @OA\Property(property="email", type="string")
   */
  public $email;
}
