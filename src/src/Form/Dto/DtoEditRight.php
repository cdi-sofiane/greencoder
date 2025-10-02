<?php

namespace App\Form\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class DtoEditRight
{

  /**
   * @Assert\NotBlank(message="Le champ user_uuid ne peut pas être vide.")
   * @OA\Property(property="video_delete", type="boolean"),
   */
  public $video_delete;

  /**
   * @Assert\NotBlank(message="Le champ user_uuid ne peut pas être vide.")
   * @OA\Property(property="account_invite", type="boolean"),
   */
  public $account_invite;

  /**
   * @Assert\NotBlank(message="Le champ user_uuid ne peut pas être vide.")
   * @OA\Property(property="report_encode", type="boolean"),
   */
  public $report_encode;

  /**
   * @Assert\NotBlank(message="Le champ user_uuid ne peut pas être vide.")
   * @OA\Property(property="report_config", type="boolean"),
   */
  public $report_config;

}
