<?php

namespace App\Form\Dto;


use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;


class DtoUploadFolder
{

  /**
   * @OA\Property(property="uuid", type="string")
   */
  public $uuid;

  /**
   * @OA\Property(property="name", type="string")
   */
  public $name;

  /**
   * @OA\Property(property="level", type="string")
   */
  public $level;


  /**
   * @OA\Property(property="videos", type="array",
   *        @OA\Items(type="object"))
   */
  public $videos = [];

  /**
   * @OA\Property(property="subFolders", type="array",
   *        @OA\Items(type="object"))
   */
  public $subFolders = [];

}
