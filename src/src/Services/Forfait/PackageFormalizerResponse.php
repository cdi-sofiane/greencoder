<?php

namespace App\Services\Forfait;

use App\Helper\SerializerHelper;
use App\Services\DataFormalizerInterface;
use App\Services\JsonResponseMessage;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class PackageFormalizerResponse implements DataFormalizerInterface
{
    protected $serializer;
    const LIST = 'list_all';
    const ONE = 'one_forfait';

    private $paginator;

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
    public function extract($item, $group = null, bool $isTypePagination = false, $filters = null): JsonResponseMessage
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
            ->setError(['package(s) successfuly retrived!']);
        if ($isTypePagination === true) {
            $toPaginate->currentPage = $paginateData->getCurrentPageNumber();
            $toPaginate->totalItem = $paginateData->getTotalItemCount();
            $toPaginate->itemPerPage = $paginateData->getItemNumberPerPage();
        }


        return $toPaginate;
    }


}
