<?php

namespace App\Repository;

use App\Entity\Tags;
use App\Entity\Video;
use App\Entity\Encode;
use App\Helper\PlaylistHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @method Video|null find($id, $lockMode = null, $lockVersion = null)
 * @method Video|null findOneBy(array $criteria, array $orderBy = null)
 * @method Video[]    findAll()
 * @method Video[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VideoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Video::class);
    }

    public function create($video)
    {
        $this->_em->persist($video);
        $this->_em->flush();
        /**@var Video $video */
        $fileName = $video->getUuid() . '_' . $video->getSlugName() . '_thumbnail';
        $video->setLink($video->getUuid() . '_' . $video->getSlugName() . '.' . $video->getExtension());
        $video->setStreamLink($_ENV['OVH_PUBLIC_STORAGE_LINK'] . $video->getLink());
        $video->setThumbnail($_ENV['OVH_PUBLIC_STORAGE_LINK'] . $fileName . '_SD.jpeg');
        $video->setThumbnailLd($_ENV['OVH_PUBLIC_STORAGE_LINK'] . $fileName . '_LD.jpeg');
        $video->setThumbnailHd($_ENV['OVH_PUBLIC_STORAGE_LINK'] . $fileName . '_HD.jpeg');
        $this->updateVideo($video);
        return $video;
    }

    public function updateVideo($video)
    {
        $video->setUpdatedAt(new \DateTimeImmutable('now'));
        $this->_em->persist($video);
        $this->_em->flush();
        return $video;
    }
    // /**
    //  * @return Video[] Returns an array of Video objects
    //  */
    public function archiveVideo($video)
    {
        /**@var Video $video */
        $video->setIsArchived(true);
        $video->setUpdatedAt(new \DateTimeImmutable('now'));
        $this->updateVideo($video);
        return $video;
    }

    public function findAccountVideos($account, $filter = null)
    {
        $query = $this->createQueryBuilder('video');
        $query->where('video.account = :acc');
        $query->setParameter('acc', $account);
        if (isset($filter['isStored']) != null) {
        }

        if (isset($filter['isDeleted']) != null) {
        }

        $q = $query->getQuery();
        return $q->getResult();
    }
    /**
     * set isDeleted To true
     *
     * @param $video
     * @return Video
     */
    public function deleteVideo($video)
    {
        /**@var Video $video */
        $video->setIsDeleted(true);
        $video->setUpdatedAt(new \DateTimeImmutable('now'));
        $video->setDeletedAt(new \DateTimeImmutable('now'));
        $this->updateVideo($video);
        return $video;
    }

    /**
     *
     * @param string $intervale use date interval to change constrain default 2 ('day')
     * @return int|mixed|string
     * @throws \Exception
     */
    public function findExpiredStorageVideo($filters = null, string $intervale)
    {

        return $this->createQueryBuilder('video')
            ->Where('video.createdAt <= :createdAt')
            ->andWhere('video.isDeleted = :delete')
            ->andWhere('video.isStored = :stored')
            ->setParameter('createdAt', (new \DateTimeImmutable('now'))->modify('-' . $intervale . 'day'))
            ->setParameter('stored', isset($filters['isStored']) != null ? $filters['isStored'] : false)
            ->setParameter('delete', false)
            ->getQuery()
            ->getResult();
    }

    public function findNotStoredVideoCloseToExpise($filters = null, string $intervale)
    {

        return $this->createQueryBuilder('video')
            ->Where('video.deletedAt <= :deletedAt')
            ->andWhere('video.isDeleted = :delete')
            ->andWhere('video.isStored = :stored')
            ->setParameter('deletedAt', (new \DateTimeImmutable('now'))->modify($intervale))
            ->setParameter('stored', isset($filters['isStored']) != null ? $filters['isStored'] : false)
            ->setParameter('delete', false)
            ->getQuery()
            ->getResult();
    }

    /**
     *
     *
     * @param User|null $user
     * @param array|null $args
     * @return [Video]
     */
    public function findVideos($account = null, $args = null)
    {

        $q0 = $this->prepareFindVideos($account, $args);
         $q0 = $this->videosIsDelete($q0, $args, $account);

        $q1 = $this->prepareFindVideos($account, $args);
        $q1 = $this->findVideoWithoutEncoded($q1, $args, $account);


        $q0 = $q0 != null ? $q0->getQuery()->getResult() : [];
        $q1 = $q1 != null ? $q1->getQuery()->getResult() : [];


        $data = array_merge($q0, $q1);

        return $data;
    }

    public function findTagsInVideo($video, $filter = null)
    {
        return $this->createQueryBuilder('video')
            ->leftJoin('video.tags', 'tags')
            ->Where('video = :video')
            ->setParameter('video', $video)
            ->andWhere('tags.tagName = :tagname')
            ->setParameter('tagname', $filter['tag'])
            ->getQuery()
            ->getResult();
    }

    public function getSizeVideosStored($account)
    {
        return $this->createQueryBuilder('v')
            ->select('SUM(v.size)')
            ->where('v.isStored != false')
            ->where('v.isDeleted != true')
            ->andWhere('v.account = :account')
            ->setParameter('account', $account)
            ->getQuery()->getSingleScalarResult();
    }

    public function countStoredVideosSize($account)
    {

        $args['isDeleted'] = false;
        $q0 = $this->prepareCountStoredVideo($account);
        $q0 = $this->videosIsDelete($q0, $args, $account);

        $q1 = $this->prepareCountStoredVideo($account);
        $q1 = $this->findVideoWithoutEncoded($q1, $args, $account);

        $q0 = $q0 != null ? $q0->getQuery()->getSingleScalarResult() : '';
        $q1 = $q1 != null ? $q1->getQuery()->getSingleScalarResult() : '';


        $data = ($q0 + $q1);

        return $data;
    }


    private function videosIsDelete($query, $args, $account = null)
    {


        if (isset($args['isDeleted']) != null) {
            $sub = $this->createQueryBuilder("");
            $sub->select("video");
            $sub->leftJoin('video.encodes', 'e', 'e.video = video ');

            if ($args['isDeleted'] == true) {

                $sub->andWhere('e.isDeleted = false');
                $sub->andwhere('e.video = video');
                $query->andWhere($query->expr()->not($query->expr()->exists($sub->getDQL())));
            } elseif ($args['isDeleted'] == false) {

                $sub->orWhere('e.isDeleted = false');
                $sub->orWhere('video.isDeleted = false');
                $sub->orderBy('e.video', 'DESC');
                $query->andWhere($query->expr()->exists($sub->getDQL()));
            }

            return $query;
        }
        return $query;
    }



    /**
     * find list of video with video.isDeleted = false and empty encode = [];
     */
    private function findVideoWithoutEncoded($query, $args, $account = null)
    {

        if (isset($args['deleted']) === false) {
            $query->leftJoin('video.encodes', 'es');

            $query->andWhere('video.isDeleted = false ');
            $query->andWhere('es.id is NULL ');
            if ($account != null) {
                $query->andWhere('video.account =:ac');
                $query->setParameter('ac', $account);
            }


            return  $query;
        }
    }
    private  function prepareFindVideos($account, $args)
    {
        $query = $this->createQueryBuilder('video');
        $query->where('video.isInTrash = false');

        if (isset($args['video']) != null) {
            $query->andwhere('video = :video');
            $query->setParameter('video', $args['video']);
        }
        if (isset($args['tags']) != null) {
            $query->leftJoin('video.tags', 'tags');
            if ($account != null) {
                $query->andwhere('tags.account = :account');
                $query->setParameter('account', $account);
            }
            $tagNames = $args['tags'];
            $subquery = $this->createQueryBuilder('v2')
                ->select('v2.id')
                ->leftJoin('v2.tags', 't')
                ->andWhere('t.tagName IN (:tagNames)')
                ->groupBy('v2.id')
                ->having('COUNT(DISTINCT t) = :tagCount')
                ->getQuery()
                ->getDQL();

            $query->andWhere("video.id IN ($subquery)");
            // $query->andWhere('SIZE(video.tags) = :tagCount');
            $query->setParameter('tagNames', $tagNames);
            $query->setParameter('tagCount', count($tagNames));
        }
        $query->andWhere('video.createdAt BETWEEN  :startAt AND  :endAt ');


        $query->setParameter('startAt', isset($args['startAt']) != null ? new \DateTimeImmutable($args['startAt']) : new \DateTimeImmutable('1970-01-01'));
        $query->setParameter('endAt', isset($args['endAt']) != null ? (new \DateTimeImmutable($args['endAt']))->add(new \DateInterval('P1D')) : new \DateTimeImmutable('now'));

        if (isset($args['name']) != null) {
            $expr = $query->expr();
            $query->andWhere(
                $expr->orX(
                    $expr->like('video.name', ':name'),
                    $expr->like('video.title', ':name')
                )
            );

            $query->setParameter('name', '%' . $args['name'] . '%');
        }
        if (isset($args['isStored']) != null) {

            $query->andWhere('video.isStored = :stored');
            $query->setParameter('stored', $args['isStored']);
        }
        if (isset($args['isMultiEncoded']) != null) {

            $query->andWhere('video.isMultiEncoded = :multiEncoded');
            $query->setParameter('multiEncoded', $args['isMultiEncoded']);
        }
        if ($account != null) {
            $query->andWhere('video.account = :acc');
            $query->setParameter('acc', $account);
        }
        if (isset($args['mediaType']) != null) {
            $query->andWhere('video.mediaType in (:mediaType)');
            $query->setParameter('mediaType', $args['mediaType']);
        }
        if (isset($args['encodingState']) != null) {
            $query->andWhere('video.encodingState in (:encodingState)');
            $query->setParameter('encodingState', $args['encodingState']);
        }

        if (isset($args['isArchived']) != null) {
            $query->andWhere('video.isArchived = :isArchived');
            $query->setParameter('isArchived', $args['isArchived']);
        }


        if (isset($args['folderId']) != null) {
            if (isset($args['folder'])) {
                $query->leftJoin('video.folder', 'fol');
                $query->andWhere($query->expr()->in('fol.id', ':folderIds'));
                $query->setParameter('folderIds', $args['folderId']);
            } else {

                $query->leftJoin('video.folder', 'fol');

                $query->andWhere(
                    $query->expr()->orX(
                        $query->expr()->in('fol.id', ':folderIds'),
                        $query->expr()->isNull('video.folder')
                    )
                );
                $query->setParameter('folderIds', $args['folderId']);
            }
        } else {

            if (isset($args['folder'])) {
                $query->andWhere('video.folder = :fo');
                $query->setParameter('fo', $args['folder']);
            } else {
                /**
                 *  permet pour l utilisateur d afficher les videos a la racine de l account ,
                 *  et pour  VIDMIZER la list de toutes les videos de tout les accounts
                 */
                if (!isset($args['countable'])) {

                    $query->andWhere('video.folder IS NULL');
                }
            }
        }
        if (isset($args['isDeleted']) != null) {
            if ($args['isDeleted'] == true) {

                $query->andWhere('video.isDeleted = true');
            }
        }


        if (isset($args['sortBy']) != null) {
            $query->orderBy('video.' . $args['sortBy'], isset($args['order']) != null ? strtoupper($args['order']) : 'ASC');
        }

        $query->groupBy('video.id');
        return $query;
    }

    private function prepareCountStoredVideo($account)
    {

        $query = $this->createQueryBuilder('video');
        $query->select('SUM(video.size)');
        $query->where('video.isStored =:isStored');
        $query->setParameter('isStored', true);
        $query->andWhere('video.account = :account');
        $query->setParameter('account', $account);

        return $query;
    }

    public function getVideosInTrashSince30Days()
    {
        $date = date('Y-m-d h:i:s', strtotime("-3 days"));

        return $this->createQueryBuilder('v')
            ->select('v')
            ->where('v.isDeleted =:isDeleted')
            ->setParameter('isDeleted', false)
            ->andwhere('v.isInTrash =:isTrashed')
            ->setParameter('isTrashed', true)
            ->andwhere('v.updatedAt < :n30days')
            ->setParameter('n30days', $date)
            ->getQuery()
            ->getResult();
    }

    public function findFilteredVideos($filters, $account = null)
    {

        $query = $this->createQueryBuilder('video');
        $query->where('video.account = :account')
            ->setParameter('account', $filters['account'])
            ->andWhere('video.folder is null');
        $query->andWhere('video.isDeleted = :isDeleted');
        $query->setParameter('isDeleted', false);
        $query->andWhere('video.isInTrash = :isInTrash');
        $query->setParameter('isInTrash', true);

        if (isset($filters['search']) != null) {
            $query->andWhere('video.title like :title')
                ->setParameter('title', '%' . $filters['search'] . '%');
        }

        if (isset($filters['order']) !== null) {
            if (isset($filters['sortBy']) !== null) {
                $query->orderBy('video.' . $filters['sortBy'], $filters['order']);
            } else {
                $query->orderBy('video.id', $filters['order']);
            }
        } else {
            $query->orderBy('video.id', 'ASC');
        }

        return $query->getQuery()->getResult();
    }

    public function findVideosByTag($tag)
    {
        $query = $this->createQueryBuilder('video');
        
        $query->where('video.isDeleted = false')
                ->andWhere(':val MEMBER OF video.tags')
                ->setParameter('val', $tag)
                ->orderBy('video.id', 'ASC');

            return $query->getQuery()->getResult();
    }
}
