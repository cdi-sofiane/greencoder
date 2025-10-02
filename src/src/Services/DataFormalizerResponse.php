<?php

namespace App\Services;

use App\Helper\SerializerHelper;
use App\Services\DataFormalizerInterface;
use App\Services\JsonResponseMessage;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class DataFormalizerResponse implements DataFormalizerInterface
{
    protected $serializer;
    protected $paginator;
    const LIST = 'list';
    const ONE = 'one';

    public function __construct(SerializerHelper $serializer, PaginatorInterface $paginator)
    {
        $this->serializer = $serializer;
        $this->paginator = $paginator;
    }

    /**
     * @param $item
     * @param null $group entity group parameters
     * @param bool $isTypePagination is a pagination item
     * @return JsonResponseMessage
     */
    public function extract($item, $group = null, bool $isTypePagination = false, $msg = 'ressource(s) successfuly retrived!', $code = Response::HTTP_OK, $filters = null): JsonResponseMessage
    {
        $items = $item;
        if ($isTypePagination) {

            $paginateData = $this->paginator->paginate(
                $item,
                $filters['page'],
                $filters['limit'],
                [
                    'distinct' => false,
                    'sorted' => false
                ]
            );

            $items = $isTypePagination == true ? $paginateData->getItems() : $item;
        }

        $groups = $group != null ? $group : self::LIST;


        $data = $this->serializer->normalize(
            $items,
            JsonEncoder::FORMAT,
            ['groups' => $groups]
        );

        $toPaginate = (new JsonResponseMessage())
            ->setCode($code)
            ->setContent($data)
            ->setError([$msg]);
        if ($isTypePagination == true) {
            $toPaginate->currentPage = $paginateData->getCurrentPageNumber();
            $toPaginate->totalItem = $paginateData->getTotalItemCount();
            $toPaginate->itemPerPage = $paginateData->getItemNumberPerPage();
        }


        return $toPaginate;
    }
}
