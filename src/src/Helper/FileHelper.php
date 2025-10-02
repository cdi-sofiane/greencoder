<?php


namespace App\Helper;

use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class FileHelper
{
  private $request;
  public function __construct(RequestStack $requestStack)
  {
    $this->request = $requestStack->getCurrentRequest();
  }

  static function addAccountLogo($logo)
  {

    $isValid = true;
    $message = array();


    if (!isset($logo)) {
      throw new Exception('No file specified', Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    list($width, $height, $type, $attr) = getimagesize($_FILES["file"]['tmp_name']);
    if (!(($width > "128" && $width < "1800"))) {
      $isValid = false;
      array_push($message, "Image dimension should be within min width 128 and max 1800");
    }

    $file_extension = pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION);
    $allowed_image_extension = array("png", "jpeg", "jpg");
    if (!in_array($file_extension, $allowed_image_extension)) {
      $isValid = false;
      array_push($message, "Only PNG , JPEG and JPG are allowed.");
    }

    $size = $logo->getSize();
    if (($size < 10) || ($size > 1000000)) {
      $isValid = false;
      array_push($message, "File too large.");
    }
    if (!$isValid) {
      throw new Exception(json_encode($message), Response::HTTP_UNPROCESSABLE_ENTITY);
    }
    return $logo;
  }
}
