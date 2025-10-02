<?php

namespace App\Form\Dto;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Gedmo\Mapping\Annotation as Gedmo;

class DtoTag
{
  /**
   * @OA\Property (property="name", type="string")
     * @Groups ({"tags:add","tags:all"})
     * @Assert\NotBlank (groups={"tags:add","tags:add"})
     * @Assert\NotNull  (groups={"tags:add","tags:remove"})
   */
  private $name;


  /**
   *  @OA\Property (type="boolean")
     * @Groups ({"tags:add","tags:all"})
     * @Assert\NotBlank (groups={"tags:add","tags:add"})
     * @Assert\NotNull  (groups={"tags:add","tags:remove"})
   */
  private $isFolder;

  /**
     * @Groups ({"tags:add","tags:all"})
     * @Assert\NotBlank (groups={"tags:add","tags:add"})
     * @Assert\NotNull  (groups={"tags:add","tags:remove"})
   * @OA\Property (type="integer")
   */
  private $folderOrder;


  /**
   * Get the value of tagName
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * Set the value of tagName
   *
   * @return  self
   */
  public function setName($tagName)
  {
    $this->name = $tagName;

    return $this;
  }

  /**
   * Get the value of isFolder
   */
  public function getIsFolder()
  {
    return $this->isFolder;
  }

  /**
   * Set the value of isFolder
   *
   * @return  self
   */
  public function setIsFolder($isFolder)
  {
    $this->isFolder = $isFolder;

    return $this;
  }

  /**
   * Get the value of folderOrder
   */
  public function getFolderOrder()
  {
    return $this->folderOrder;
  }

  /**
   * Set the value of folderOrder
   *
   * @return  self
   */
  public function setFolderOrder($folderOrder)
  {
    $this->folderOrder = $folderOrder;

    return $this;
  }
}
