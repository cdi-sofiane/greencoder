<?php

namespace App\Services\Order;

use App\Helper\SerializerHelper;
use App\Services\DataFormalizerInterface;
use App\Services\JsonResponseMessage;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class OrderFormalizerResponse implements DataFormalizerInterface
{
    const LIST = 'list_of_order';
    const ONE = 'one_order';
    private $serializer;
    private $paginator;

    public function __construct(SerializerHelper $serializer, PaginatorInterface $paginator)
    {

        $this->serializer = $serializer;
        $this->paginator = $paginator;
    }

    public function extract($item, $group = null, bool $isTypePagination = false, $filters = null)
    {
        if ($isTypePagination === true) {
            $paginateData = $this->paginator->paginate(
                $item,
                $filters['page'],
                $filters['limit']

            );
        }
        $groups = $group != null ? $group : self::LIST;
        $items = $isTypePagination == true ? $paginateData : $item;

        $data = $this->serializer->normalize($items, JsonEncoder::FORMAT, ['groups' => $groups]);

        $toPaginate = (new JsonResponseMessage())
            ->setCode(Response::HTTP_OK)
            ->setContent($data)
            ->setError(['order(s) successfuly retrived!']);
        if ($isTypePagination === true) {
            $toPaginate->currentPage = $paginateData->getCurrentPageNumber();
            $toPaginate->totalItem = $paginateData->getTotalItemCount();
            $toPaginate->itemPerPage = $paginateData->getItemNumberPerPage();
        }


        return $toPaginate;
    }

}