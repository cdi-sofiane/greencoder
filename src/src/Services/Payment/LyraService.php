<?php

namespace App\Services\Payment;


use Lyra\Client;


class LyraService
{

     /**
     * The Client instance.
     *
     * @var object
     */
    protected $client;

    /**
     * Create a new service instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->client = new Client();
        $this->client->setUsername($_ENV['LYRA_USERNAME']);
        $this->client->setEndpoint($_ENV['LYRA_ENDPOINT']);
        $this->client->setPassword($_ENV['LYRA_PASSWORD']);
        $this->client->setSHA256Key($_ENV['LYRA_SHA256KEY']);
        $this->client->setPublicKey($_ENV['LYRA_PUBLIC_KEY']);
    }

    /**
     * Get Endpoint.
     *
     * @return string
     */
    public function getEndpoint()
    {
        return $this->client->getEndpoint();
    }

    /**
     * Get PublicKey.
     *
     * @return string
     */
    public function getPublicKey()
    {
        return $this->client->getPublicKey();
    }

    /**
     * Get SHA256KEY
     *
     * @return string
     */
    public function getSHA256Key()
    {
        return $this->client->getSHA256Key();
    }

    /**
     * Check Hash.
     *
     * @return boolean
     */
    public function checkHash()
    {
        return $this->client->checkHash();
    }

    /**
     * Get Parsed Form Answer.
     *
     * @return array
     */
    public function getParsedFormAnswer()
    {
        return $this->client->getParsedFormAnswer();
    }

    /**
     * Get Hash.
     *
     * @return array
     */
    public function getLastCalculatedHash()
    {
        return $this->client->getLastCalculatedHash();
    }

    /**
     * Create payment.
     *
     * @param array $store
     * @return array
     */
    public function createPayment($store)
    {
        return $this->client->post("V4/Charge/CreatePayment", $store);
    }

}