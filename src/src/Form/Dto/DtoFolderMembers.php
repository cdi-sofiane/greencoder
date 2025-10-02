<?php


namespace App\Form\Dto;

use App\Entity\EntityTrait\FiltersTrait;
use App\Entity\EntityTrait\PaginationTrait;
use App\Entity\User;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\DependencyInjection\Loader\Configurator\Traits\FileTrait;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

class DtoFolderMembers
{
  /**
   * @OA\Property(property="uuid",type="string")
   */
  public $uuid;
  /**
   * @OA\Property(property="email", type="string")
   */
  public $email;
  /**

   * @OA\Property(property="folderRole",type="string")
   */
  public $folderRole;
}
