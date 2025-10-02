<?php

namespace App\Services\Tags;

use App\Helper\SerializerHelper;
use App\Services\DataFormalizerInterface;
use App\Services\JsonResponseMessage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class TagsFormaizerResponse implements DataFormalizerInterface
{
    protected $serializer;
    const LIST = 'tags:list';
    const ONE = 'tags:one';

    public function __construct(SerializerHelper $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @param $item
     * @param null $group entity group parameters
     * @param bool $isTypePagination is a pagination item
     * @return JsonResponseMessage
     */
    public function extract($item, $group = null, bool $isTypePagination = false, $msg = 'video(s) successfuly retrived!', $code = Response::HTTP_OK): JsonResponseMessage
    {

        $groups = $group != null ? $group : self::LIST;
        $items = $isTypePagination == true ? $item->getItems() : $item;

        $data = $this->serializer->normalize($items, JsonEncoder::FORMAT, ['groups' => $groups]);

        $toPaginate = (new JsonResponseMessage())
            ->setCode($code)
            ->setContent($data)
            ->setError([$msg]);
        if ($isTypePagination == true) {
            $toPaginate->currentPage = $item->getCurrentPageNumber();
            $toPaginate->totalItem = $item->getTotalItemCount();
            $toPaginate->itemPerPage = $item->getItemNumberPerPage();
        }


        return $toPaginate;
    }
}