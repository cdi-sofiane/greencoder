<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\Response;

class JsonResponseMessage
{
    public $itemPerPage;
    public $totalItem;
    public $currentPage;
    private $code = '';
    private $token = '';
    private $error = '';
    private $content = '';

    /**
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param mixed $code
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param mixed $token
     */
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     *  ["message"]
     *
     * @param mixed $error
     */
    public function setError($error)
    {
        $this->error = $error;
        return $this;
    }

    public function displayData()
    {
        return $this->prepareData();
    }

    private function prepareData()
    {
        $data = [];
        if ($this->token != null) {
            $data = ['code' => $this->code, 'token' => $this->token];
        } else {
            $data = $this->checkCode();
        }
        return $data;
    }

    private function checkCode()
    {
        $pagination = $this->itemPerPage == null ? null : ['itemPerPage' => $this->itemPerPage, 'currentPage' => $this->currentPage, 'totalItem' => $this->totalItem];
        $content = $this->getContent() == null || $this->getContent() == "" ? [] : $this->getContent();
        switch ($this->code) {
            case Response::HTTP_OK:
            case Response::HTTP_CREATED :
            case Response::HTTP_NO_CONTENT :
                if (isset($pagination)) {
                    $data = ['code' => $this->code, 'message' => $this->error, 'data' => $content, 'pagination' => $pagination];
                } else {
                    $data = ['code' => $this->code, 'message' => $this->error, 'data' => $content];
                }
                break;
            default :

                $data = ['code' => $this->code, 'error' => $this->error, 'data' => $content];
                break;
        }

        return $data;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * $data[]
     * @param mixed $content
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    public function displayHeader()
    {
        return $this->code;
    }

    public function html()
    {
        return $this->code;
    }
}