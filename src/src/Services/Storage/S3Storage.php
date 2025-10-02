<?php

namespace App\Services\Storage;

use App\Entity\Encode;
use App\Entity\Report;
use App\Entity\Video;
use App\Repository\EncodeRepository;
use App\Repository\VideoRepository;
use App\Services\JsonResponseMessage;
use App\Utils\FileUtils;
use Aws\S3\Exception\S3Exception;
use Aws\S3\MultipartUploader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Aws\S3\S3Client;
use Exception;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\VarDumper\Cloner\Data;

class S3Storage implements StorageInterface
{
    private $client;
    private $bucket;
    private $appParam;
    private $s3PublicLink;
    private $profile;
    private $region;
    private $version;
    private $encodeRepository;
    private $videoRepository;
    private $reportStorage;
    private $linkStorage;
    private $fileUtils;

    public function __construct(
        EncodeRepository      $encodeRepository,
        ParameterBagInterface $appParam,
        VideoRepository       $videoRepository,
        FileUtils $fileUtils
    ) {
        $this->encodeRepository = $encodeRepository;
        $this->appParam = $appParam;
        $this->videoRepository = $videoRepository;
        $this->bucket = $_ENV['BUCKET'];
        $this->profile = $_ENV['S3_PROFILE'];
        $this->region = $_ENV['S3_REGION'];
        $this->version = $_ENV['S3_VERSION'];
        $this->s3PublicLink = $_ENV['S3_PUBLIC_STORAGE_LINK'];
        $this->reportStorage = $_ENV['PUBLIC_REPORT_STORAGE_NAME'];
        $this->linkStorage = $_ENV['PUBLIC_REPORT_STORAGE_LINK'];
        $this->client = $this->connection();
        $this->fileUtils = $fileUtils;
    }

    public function connection()
    {
        $s3 = new S3Client([
            'profile' => $this->profile,
            'region' => $this->region,
            'version' => $this->version,
            'endpoint' => $this->s3PublicLink
        ]);
        return $s3;
    }

    public function videoUpload($uploadFile, $currentVideo)
    {
        $source = $uploadFile->getRealPath();

        $uploader = new MultipartUploader($this->client, $source, [
            'bucket' => $this->bucket,
            'key' => $currentVideo->getLink(),
            'ACL' => 'public-read',
            'params' => [
                'ContentType' => $uploadFile->getMimeType()
            ]
        ]);

        try {
            $result = $uploader->upload();
            return true;
        } catch (\Aws\Exception\MultipartUploadException $e) {
            return false;
        }
    }

    public function videoDelete($obj)
    {
        return true;
        try {

            $result = $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $obj->getLink(),
            ]);
            if ($result['DeleteMarker']) {

                return true;
            }

            return false;
        } catch (S3Exception $e) {
            return false;
        } catch (MethodNotAllowedException $ex) {
            return false;
        }
    }

    public function videoFileDownload($obj)
    {
        if ($this->findInStorage($obj) == false) {
            $response = (new JsonResponseMessage())->setCode(404)->setError('this file is not yet in storage or file was deleted');
            return new JsonResponse($response->displayData(), $response->displayHeader());
        }


        return $this->downloadResponse($obj);
    }

    /**
     * @return bool
     * @var Video|Encode $obj
     */
    public function findInStorage($obj)
    {
        $objName = $obj->getLink() != null ? $obj->getLink() : $obj->getUuid() . '_' . $obj->getSlugName() . '.' . $obj->getExtension();
        $obj->setLink($objName);
        try {
            if ($this->client->doesObjectExist($this->bucket, $obj->getLink())) {


                $contentInStorage = $this->client->headObject(array(
                    "Bucket" => $this->bucket,
                    "Key" => $obj->getLink()
                ));

                if ($obj instanceof Video && $obj->getIsUploadComplete() === false && $contentInStorage['ContentLength'] > 0) {
                    $obj->setIsUploadComplete(true);
                    $this->videoRepository->updateVideo($obj);
                }
                if ($obj instanceof Encode && $obj->getSize() == 0 && $contentInStorage['ContentLength'] > 0) {
                    $obj->setSize($contentInStorage['ContentLength']);
                    $this->encodeRepository->updateEncode($obj);
                }
                return true;
            }
            return false;
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return false;
        }
    }

    public function downloadResponse($obj)
    {


        try {
            // Get the object.
            $result = $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $obj->getLink()
            ]);
            $response = new Response($result['Body']);
            $disposition = HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                $obj->getSlugName() . '.' . $obj->getExtension()
            );
            $response->headers->set('Content-Type', $result['ContentType']);
            $response->headers->set('Content-Disposition', $disposition);
            return $response;
        } catch (S3Exception $e) {
            return (new JsonResponseMessage())->setCode(Response::HTTP_NOT_FOUND)->setError(['Video(s) not found']);
        }
    }

    public function thumbnailUpload($thumbnail)
    {
        $arrStorageVideo = [
            "name" => str_replace($this->appParam->get('video_directory'), '', $thumbnail),
            "content" => file_get_contents($thumbnail),
            "contentType" => 'image/jpeg', // 1

        ];

        $uploader = new MultipartUploader($this->client, $thumbnail, [
            'bucket' => $this->bucket,
            'key' => $arrStorageVideo['name'],
            'ACL' => 'public-read',
            'params' => [
                'ContentType' => 'image/jpeg',
            ]
        ]);
        try {
            $uploader->upload();
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
        try {
            // Get the object.
            $result = $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $thumbnail
            ]);
            $response = new Response($result['Body']);

            $response->headers->set('Content-Type', $result['ContentType']);
            return $response;
        } catch (S3Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }

    public function findThumbnailInStorage($thumbnail)
    {
        if ($this->client->doesObjectExist($this->bucket, $thumbnail)) {
            return true;
        }
        return false;
    }

    /**
     * @param Video $obj
     */
    public function videoFileStream($obj)
    {

        $this->client->registerStreamWrapper();

        $context = stream_context_create([
            's3' => ['seekable' => true]
        ]);

        if ($stream = fopen('s3://' . $this->bucket . '/' . $obj->getLink(), 'r', false, $context)) {
            while (!feof($stream)) {
                $buffer = fread($stream, 1024);
                echo $buffer;
            }
            fclose($stream);
        }
    }

    public function UploadImage($logo, $old_logo = null)
    {
        if (null !== $old_logo) {
            $name =  substr($old_logo, strlen($this->linkStorage));
            if ($this->client->doesObjectExist($this->reportStorage, $name)) {
                $this->client->deleteObject([
                    'Bucket' => $this->reportStorage,
                    'Key' => $name,
                ]);
            }
        }

        $fileName = Uuid::uuid4() . '_' . $this->fileUtils->slugify($logo->getClientOriginalName());
        $uploader = new MultipartUploader($this->client, fopen($logo->getRealPath(), 'rb'), [
            'bucket' => $this->reportStorage,
            'key' => $fileName,
            'ACL' => 'public-read',
            'params' => [
                'ContentType' => $logo->getMimeType(),
            ]
        ]);
        try {
            $uploader->upload();
            return $this->linkStorage . $fileName;
        } catch (\Aws\Exception\MultipartUploadException $e) {
            return false;
        }
    }

    public function uploadPdf($storage_name, $name)
    {
        $uploader = new MultipartUploader($this->client, fopen($this->appParam->get('public_directory') . 'uploads/' . $name, 'rb'), [
            'bucket' => $storage_name,
            'key' => $name,
            'ACL' => 'public-read',
            'params' => [
                'ContentType' => 'application/pdf',
            ]
        ]);
        try {
            return $uploader->upload();
        } catch (\Aws\Exception\MultipartUploadException $e) {
            return false;
        }
    }

    public function uploadCsv(Report $report)
    {
        $arrStorageReport = [
            "name" =>  $report->getLink() . '.csv',
            "contentType" => 'text/csv', // 1

        ];
        $uploader = new MultipartUploader($this->client, fopen($this->appParam->get('public_directory') . 'uploads/' . $report->getLink() . '.csv', 'rb'), [
            'bucket' => $this->reportStorage,
            'key' => $arrStorageReport['name'],
            'ACL' => 'public-read',
            'params' => [
                'ContentType' => $arrStorageReport['contentType'],
            ]
        ]);
        try {
            return $uploader->upload();
        } catch (\Aws\Exception\MultipartUploadException $e) {
            return false;
        }
    }

    public function removeReports($fileIdentifier)
    {
        try {

            $result = $this->client->deleteObject([
                'Bucket' => $this->reportStorage,
                'Key' => $fileIdentifier,
            ]);
            if ($result['DeleteMarker']) {

                return true;
            }

            return false;
        } catch (S3Exception $e) {
            return false;
        } catch (MethodNotAllowedException $ex) {
            return false;
        }
    }
    public function getReportFile($fileIdentifier)
    {
        try {

            $result = $this->client->getObject([
                'Bucket' => $this->reportStorage,
                'Key' => $fileIdentifier,
            ]);
            if ($result['Body']) {

                return $result['Body']->getContents();
            }

            return false;
        } catch (S3Exception $e) {
            return false;
        } catch (MethodNotAllowedException $ex) {
            return false;
        }
    }
    /**
     * remove file with partial name key ex: ['fullname':"123-12345-test-video.mp4","prefix"=>"123-12345-test"];
     */
    public function removeWithPrefix(Video $obj)
    {
        return true;
        try {
            $listObject = $this->client->listObjects([
                'Bucket' => $this->bucket,
                'Prefix' => $obj->getUuid() . '_' . $obj->getSlugName(),
            ]);

            if ($listObject['Contents'] == null) {
                return false;
            }

            foreach ($listObject['Contents'] as $content) {

                if (FileUtils::extension($content['Key']) == 'jpeg') {

                    break;
                }
                $result = $this->client->deleteObject([
                    'Bucket' => $this->bucket,
                    'Key' => $content['Key'],
                ]);
            }
            return true;
        } catch (S3Exception $e) {
            return false;
        } catch (MethodNotAllowedException $ex) {
            return false;
        }
    }

    function copyFileToS3AndRename(string $videoSourceName, string $newVideoName): bool
    {

        try {
            $result = $this->client->copyObject([
                'Bucket' => $this->bucket,
                'ACL' => 'public-read',
                'Key' => $newVideoName,
                'CopySource' => "{$this->bucket}/{$videoSourceName}",
            ]);
            if ($result['ObjectURL']) {
                return true;
            }
        } catch (Exception $e) {

            return (new JsonResponseMessage)->setCode(Response::HTTP_BAD_REQUEST)->setError('Cody of video when wrong');
        }

        return false;
    }

    public function getObject($obj)
    {
        try {
            return $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $obj->getLink(),
            ]);
        } catch (Exception $e) {
            return;
        }
    }
}
