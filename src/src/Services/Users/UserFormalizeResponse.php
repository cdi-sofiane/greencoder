<?php

namespace App\Services\Users;

use App\Services\JsonResponseMessage;
use App\Helper\SerializerHelper;
use App\Repository\UserRepository;
use App\Services\DataFormalizerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class UserFormalizeResponse implements DataFormalizerInterface
{
    const LIST = 'list';
    const ONE = 'one';
    protected $serializer;
    protected $jwtManager;
    protected $token;
    protected $userRepository;

    public function __construct(SerializerHelper         $serializer,
                                JWTTokenManagerInterface $jwtManager,
                                TokenExtractorInterface  $token,
                                UserRepository           $userRepository)
    {
        $this->serializer = $serializer;
        $this->jwtManager = $jwtManager;
        $this->token = $token;
        $this->userRepository = $userRepository;
    }

    /**
     * retrive an entity or a list of entity and normalize it to create a response
     * ex: {"code"=>200 ,"message"=>"success","data"[{items}]}
     *
     * @param $item
     * @return JsonResponseMessage
     */
    public function extract($item, $group = null, bool $isTypePagination = false): JsonResponseMessage
    {
        $data = $this->serializer->normalize($isTypePagination == true ? $item->getItems() : $item, JsonEncoder::FORMAT, ['groups' => $group != null ? $group : self::LIST]);

        $toPaginate = (new JsonResponseMessage())
            ->setCode(Response::HTTP_OK)
            ->setContent($data)
            ->setError(['user(s) successfuly retrived!']);
        if ($isTypePagination == true) {
            $toPaginate->currentPage = $item->getCurrentPageNumber();
            $toPaginate->totalItem = $item->getTotalItemCount();
            $toPaginate->itemPerPage = $item->getItemNumberPerPage();
        }


        return $toPaginate;
    }

}