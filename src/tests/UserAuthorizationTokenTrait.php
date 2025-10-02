<?php

namespace App\Tests;

trait UserAuthorizationTokenTrait
{
    public $client;


    public function createAuthenticatedClient($username = 'a@a1.com', $password = 'Az1-tyze')
    {
        $client = static::createClient();
        $client->request('POST',
            '/api/login',
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            json_encode(array(
                'username' => $username,
                'password' => $password,
            ))
        );

        $data = json_decode($client->getResponse()->getContent(), true);
        if (!isset($data['token'])) {
            $data['token'] = '';
        }
        $client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $data['token']));

        return $client;
    }

    public function createAuthenticatedClientApiKey($apiKey)
    {
        $client = static::createClient();
        $client->request('POST', '/api/authenticate', array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            json_encode(array(
                'apiKey' => $apiKey,
            )));

        $data = json_decode($client->getResponse()->getContent(), true);
        if (!isset($data['token'])) {
            $data['token'] = '';
        }
        $client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $data['token']));

        return $client;
    }
}