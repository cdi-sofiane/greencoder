<?php

namespace App\Utils;


class FileUtils
{

  public function slugify(string $fileName)
  {
    $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
    $fileNameStr = pathinfo($fileName, PATHINFO_FILENAME);
    // Replaces all spaces with hyphens.
    $fileNameStr = str_replace(' ', '-', $fileNameStr);
    // Removes special chars.
    $fileNameStr = preg_replace('/[^A-Za-z0-9\-\_]/', '', $fileNameStr);
    // Replaces multiple hyphens with single one.
    $fileNameStr = preg_replace('/-+/', '-', $fileNameStr);
    if ($fileExt) {
      return $fileNameStr . '.' . $fileExt;
    }
    return $fileNameStr;
  }

  static function extension($file_name)
  {
    return pathinfo($file_name, PATHINFO_EXTENSION);
  }
}
