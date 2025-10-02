<?php

namespace App\Services\Storage;

interface StorageInterface
{
    public function connection();

    /**
     * @param $uploadFile type="video" to store
     * @param $currentVideo type="entity simulation,video" entity infos
     * @return mixed
     */
    public function videoUpload($uploadFile, $currentVideo);

    public function videoDelete($arr);

    public function videoFileDownload($arr);
}