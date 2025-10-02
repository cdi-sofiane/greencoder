<?php

namespace App\Tests\ControllerTest;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

trait traitEstimateVideo
{
    //estimate no login

    public function testEstimateFileGoodMimeType()
    {
        $client = static::createClient();
        $uploadedFile = new UploadedFile(__DIR__ . '/../Assets/video avec un slug pour tester.mp4', 'video avec un slug pour tester.mp4');
        $client->request('POST', '/api/videos/estimate', [], ['file' => $uploadedFile], []);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testEstimateFileGoodMimeTypeNoFile()
    {
        $client = static::createClient();
        $uploadedFile = new UploadedFile(__DIR__ . '/../Assets/video avec un slug pour tester.mp4', 'video avec un slug pour tester.mp4');
        $client->request('POST', '/api/videos/estimate', [], [], [],
            "{'qualityNeed' : '123x123','isMultiEncodage' : false,'isStorage' : false'}");
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testEstimateFileBadMimeType()
    {
        $client = static::createClient();
        $uploadedFile = new UploadedFile(__DIR__ . '/../Assets/document.png', 'document.png');
        $client->request('POST', '/api/videos/estimate', [], ['file' => $uploadedFile], []);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
    }
}