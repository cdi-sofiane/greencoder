<?php

namespace App\Services\Video;

use App\Entity\Encode;
use App\Entity\Video;
use App\Services\JsonResponseMessage;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiEncode
{

    private $request;
    private $route;
    private $container;
    private $storageName;
    private $apiBaseUrl;


    public function __construct(
        RequestStack          $requestStack,
        ContainerBagInterface $container
    ) {
        $this->request = $requestStack->getCurrentRequest();
        $this->container = $container;
        $this->storageName = $_ENV['OVH_PUBLIC_STORAGE_NAME'];
        $this->apiBaseUrl = $_ENV['API_BASE_URL'];
    }

    /**
     * @param $route
     * @param Encode|Video $currentVideo
     * @return mixed
     */
    public function prepare($route, $currentVideo)
    {
        $this->route = $route;

        $client = (new Client());

        $args = [
            'client' => $client,
            'currentVideo' => $currentVideo
        ];
        return $this->{$route}($args);
    }


    public function encode($args)
    {

        $json = json_encode(
            [

                'name' => $args['currentVideo']->getSlugName(),
                'uuid' => $args['currentVideo']->getUuid(),
                'extension' => $args['currentVideo']->getExtension(),
                'originalSize' => $args['currentVideo']->getSize(),
                'mediaType' => $args['currentVideo']->getMediaType(),
                'multiEncodage' => $args['currentVideo']->getIsMultiEncoded(),
                'qualityNeed' => $args['currentVideo']->getQualityNeed(),
                'storageName' => $this->storageName
            ]
        );
        return $this->apiRequest($args, $json);
    }

    public function progress($args)
    {

        $json = json_encode(
            [

                'name' => $args['currentVideo']->getSlugName(),
                'uuid' => $args['currentVideo']->getUuid(),
                'extension' => $args['currentVideo']->getExtension(),
                'storageName' => $this->storageName
            ]
        );
        return $args['client']->request(
            'GET',
            $this->apiBaseUrl . $this->route . '/' . $args['currentVideo']->getUuid(),
            [
                'headers' => [
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Accept' => 'application/json',
                    'Connection' => 'close'
                ]
            ]
        );
    }

    public function estimate($args)
    {
        $json = json_encode(
            [
                'uuid' => $args['currentVideo']->getUuid(),
                'link' => $args['currentVideo']->getLink(),
                'name' => $args['currentVideo']->getSlugName(),
                'extension' => $args['currentVideo']->getExtension(),
                'originalSize' => $args['currentVideo']->getSize(),
                'storageName' => $this->storageName
            ]
        );

        return $this->apiRequest($args, $json);
    }

    private function apiRequest($args, $json)
    {
        try {
            /**@var Client $d */
            return $args['client']->request(
                'POST',
                $this->apiBaseUrl . $this->route,
                [
                    'headers' => [
                        'Content-Type' => 'application/json; charset=utf-8',
                        'Accept' => 'application/json',
                        'Connection' => 'keep-alive'
                    ],
                    'body' => $json
                ]
            );
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return (new JsonResponseMessage())->setCode($e->getCode())->setError([$e->getMessage()]);
        } catch (\GuzzleHttp\Exception\TransferException $e) {
            return (new JsonResponseMessage())->setCode($e->getCode())->setError([$e->getMessage()]);
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            return (new JsonResponseMessage())->setCode($e->getCode())->setError([$e->getMessage()]);
        }
    }
}
