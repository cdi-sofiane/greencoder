<?php

namespace App\Services\Storage;

use App\Entity\Encode;
use App\Entity\Video;
use App\Repository\EncodeRepository;
use App\Repository\VideoRepository;
use App\Services\JsonResponseMessage;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\BufferStream;
use GuzzleHttp\Psr7\Stream;
use http\Env\Request;
use OpenStack\Common\Error\BadResponseError;
use OpenStack\Common\Error\BaseError;
use OpenStack\OpenStack;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class OvhStorage implements StorageInterface
{
    /** file chunk = 1go */
    const CHUNK_UPLOAD_SIZE = 1073741824;
    private $horizon_auth;
    private $horizon_id;
    private $horizon_pwd;
    /*  project name*/
    private $tenant_id;
    private $domain;
    private $region;
    private $client;
    private $encodeRepository;
    private $appParam;
    private $videoRepository;

    public function __construct(EncodeRepository $encodeRepository, ParameterBagInterface $appParam, VideoRepository $videoRepository)
    {
        $this->horizon_auth = $_ENV['HORIZON_AUTH_URL'];
        $this->horizon_id = $_ENV['HORIZON_USER_NAME'];
        $this->horizon_pwd = $_ENV['HORIZON_PASSWORD'];
        $this->tenant_id = $_ENV['HORIZON_PROJECT_ID'];
        $this->domain = $_ENV['HORIZON_DOMAIN_ID'];
        $this->region = $_ENV['HORIZON_REGION'];
        $this->client = $this->connection();
        $this->encodeRepository = $encodeRepository;
        $this->appParam = $appParam;
        $this->videoRepository = $videoRepository;
    }


    public function connection()
    {
        $open = new OpenStack(
            [
                'authUrl' => $this->horizon_auth,
                'region' => $this->region,
                'debugLog' => true,
                'user' => [
                    'name' => $this->horizon_id,
                    'password' => $this->horizon_pwd,
                    'domain' => ['name' => $this->domain]
                ],
                'tenantId' => $this->tenant_id,

            ]
        );
        return $open;
    }


    public function videoUpload($uploadFile, $currentVideo)
    {
        if ($uploadFile->getSize() > self::CHUNK_UPLOAD_SIZE) {
            return $this->uploadLagerFile($uploadFile, $currentVideo);
        }
        return $this->uploadNormalFile($uploadFile, $currentVideo);
    }


    public function videoFileDownload($obj)
    {

        if ($this->findInStorage($obj) == false) {
            $response = (new JsonResponseMessage())->setCode(404)->setError('this file was deleted');
            return new JsonResponse($response->displayData(), $response->displayHeader());
        }

        $container = $this->client->objectStoreV1()->getContainer($_ENV['OVH_PUBLIC_STORAGE_NAME']);
        /**@var Video $obj */

        $object = $container->getObject(Trim($obj->getLink()));


        $object->retrieve();
        $stream = $object->download(['stream' => true]);

        $content = $stream->getContents();
        $arr = [
            'file' => $content,
            'entity' => $obj
        ];
        return $this->downloadResponse($arr);

    }

    public function videoFileStream($obj)
    {

        if ($this->findInStorage($obj) == false) {
            $response = (new JsonResponseMessage())->setCode(404)->setError('this file was deleted');
            return new JsonResponse($response->displayData(), $response->displayHeader());
        }
        $container = $this->client->objectStoreV1()->getContainer($_ENV['OVH_PUBLIC_STORAGE_NAME']);
        /**@var Video $obj */
        $object = $container->getObject(Trim($obj->getLink()));
        $object->retrieve();
        $stream = $object->download();

        $content = $stream->getContents();
        $response = new Response($content);
        $response->headers->set('Content-Type', 'video/' . $obj->getExtension());
        return $response;
    }

    /*todo*/
    public function videoDelete($obj)
    {
        $container = $this->client->objectStoreV1()->getContainer($_ENV['OVH_PUBLIC_STORAGE_NAME']);
        $object = $container->getObject(Trim($obj->getLink()));
        $object->delete();
    }

    private function downloadResponse($data): Response
    {

        $response = new Response($data['file']);
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $data['entity']->getSlugName() . '.' . $data['entity']->getExtension()
        );
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', $disposition);
        return $response;
    }

    public function findInStorage($obj)
    {
        try {
            $contentInStorage = (new Client())->get($_ENV['OVH_PUBLIC_STORAGE_LINK'] . $obj->getLink());
            if ($obj instanceof Video && $obj->getIsUploadComplete() === false && $contentInStorage->getHeaders()['Content-Length'][0] > 0) {
                $obj->setIsUploadComplete(true);
                $this->videoRepository->updateVideo($obj);
            }
            if ($obj instanceof Encode && $obj->getSize() == 0 && $contentInStorage->getHeaders()['Content-Length'][0] > 0) {
                $obj->setSize($contentInStorage->getHeaders()['Content-Length'][0]);
                $this->encodeRepository->updateEncode($obj);
            }
            return true;
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return false;
        }
    }

    public function findThumbnailInStorage($thumbnail)
    {
        try {
            (new Client())->get($_ENV['OVH_PUBLIC_STORAGE_LINK'] . $thumbnail);
            return true;
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return false;
        }
    }

    public function thumbnailFileStream($thumbnail)
    {

        if ($this->findThumbnailInStorage($thumbnail) == false) {

            $response = (new JsonResponseMessage())->setCode(404)->setError("this thumbnail dont exist");
            return new JsonResponse($response->displayData(), $response->displayHeader());
        }

        $container = $this->client->objectStoreV1()->getContainer($_ENV['OVH_PUBLIC_STORAGE_NAME']);

        $object = $container->getObject($thumbnail);
        $object->retrieve();
        $stream = $object->download();

        $content = $stream->getContents();
        $response = new Response($content);
        $response->headers->set('Content-Type', 'image/jpg');
        return $response;
    }

    private function uploadLagerFile($uploadFile, $currentVideo)
    {
        $options = [
            'name' => $currentVideo->getLink(),
            'stream' => new Stream(fopen($uploadFile->getRealPath(), 'r')),
            "content-type" => $uploadFile->getMimeType(),
            'segmentContainer' => $_ENV['OVH_PUBLIC_STORAGE_NAME'],
            'segmentSize' => self::CHUNK_UPLOAD_SIZE,
            'content-length' => $uploadFile->getSize()
        ];

        $container = $this->client->objectStoreV1()->getContainer($_ENV['OVH_PUBLIC_STORAGE_NAME']);
        $container->createLargeObject($options);
        return true;
    }

    public function thumbnailUpload($thumbnail)
    {

        $arrStorageVideo = [
            "name" => str_replace($this->appParam->get('video_directory'), '', $thumbnail),
            "content" => file_get_contents($thumbnail),
            "contentType" => 'image/jpeg',// 1

        ];

        $container = $this->client->objectStoreV1()->getContainer($_ENV['OVH_PUBLIC_STORAGE_NAME']);
        $container->createObject($arrStorageVideo);
        return true;
    }

    private function uploadNormalFile($uploadFile, $currentVideo)
    {
        $arrStorageVideo = [
            "name" => $currentVideo->getLink(),
            "content" => file_get_contents($uploadFile->getRealPath()),
            "contentType" => $uploadFile->getMimeType(),// 1

        ];
        $container = $this->client->objectStoreV1()->getContainer($_ENV['OVH_PUBLIC_STORAGE_NAME']);
        $container->createObject($arrStorageVideo);
        return true;
    }


    /**
     * WARNING!!!!!
     * seulement utiliser en dev et test, clean storage de toutes les videos
     */
    public function deleteInStorage()
    {
        $kernel = $this->appParam->get('app.environment');
        if ($kernel === "dev") {
            $container = $this->client->objectStoreV1()->getContainer($_ENV['OVH_PUBLIC_STORAGE_NAME']);
            $arr = $container->listObjects();
            foreach ($arr as $objs) {
                $obj = $objs;
                echo $obj->name . '\n';
                $object = $container->getObject("$obj->name");
                $object->delete();
            }
        }
    }


}
