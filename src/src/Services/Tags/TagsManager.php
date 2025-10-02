<?php

namespace App\Services\Tags;

use App\Entity\Account;
use App\Entity\Tags;
use App\Entity\User;
use App\Entity\Video;
use App\Form\Dto\DtoTags;
use App\Helper\SerializerHelper;
use App\Repository\TagsRepository;
use App\Repository\UserRepository;
use App\Repository\VideoRepository;
use App\Security\Voter\AccountVoter;
use App\Security\Voter\FolderVoter;
use App\Security\Voter\VideoVoter;
use App\Services\AbstactValidator;
use App\Services\AuthorizationService;
use App\Services\DataFormalizerInterface;
use App\Services\DataFormalizerResponse;
use App\Services\JsonResponseMessage;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TagsManager extends AbstactValidator
{
    /**
     * @var AuthorizationService
     */
    private $authorizationService;
    /**
     * @var VideoRepository
     */
    private $videoRepository;
    /**
     * @var TagsRepository
     */
    private $tagsRepository;
    /**
     * @var UserRepository
     */
    private $userRepository;
    /**
     * @var Request
     */
    private $request;
    /**
     * @var TagsService
     */
    private $tagsService;
    /**
     * @var Serializer
     */
    private $serializer;
    /**
     * @var ValidatorInterface
     */
    private $validator;
    /**
     * @var TagsFormaizerResponse
     */
    private $dataFormalizer;
    private $accountVoter;
    private $security;
    private $em;
    private $folderVoter;
    private $videoVoter;

    public function __construct(
        AuthorizationService  $authorizationService,
        VideoRepository       $videoRepository,
        TagsRepository        $tagsRepository,
        UserRepository        $userRepository,
        TagsService           $tagsService,
        RequestStack          $request,
        SerializerInterface   $serializer,
        ValidatorInterface    $validator,
        EntityManagerInterface    $em,
        AccountVoter $accountVoter,
        FolderVoter $folderVoter,
        VideoVoter $videoVoter,
        Security $security,
        DataFormalizerResponse $dataFormalizer
    ) {
        $this->authorizationService = $authorizationService;
        $this->videoRepository = $videoRepository;
        $this->tagsRepository = $tagsRepository;
        $this->userRepository = $userRepository;
        $this->request = $request->getCurrentRequest();
        $this->tagsService = $tagsService;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->em = $em;
        $this->accountVoter = $accountVoter;
        $this->security = $security;
        $this->folderVoter = $folderVoter;
        $this->videoVoter = $videoVoter;
        $this->dataFormalizer = $dataFormalizer;
    }

    public function addTagsToVideos($user = null)
    {
        $data = $this->serializer->deserialize(
            $this->request->getContent('tags'),
            DtoTags::class,
            'json'
        );

        $err = $this->validator->validate($data, null, 'tags:add');
        if ($err->count() > 0) {
            return $this->err($err);
        }
        $videos = $this->videoRepository->findBy(['uuid' => $data->getVideos()]);

        foreach ($videos as $video) {

            $isGranted =  $this->videoVoter->vote($this->security->getToken(), $video, [VideoVoter::ACCOUNT_ADD_TAGS]);

            if ($isGranted == -1) {
                continue;
            }

            $this->tagsService->addOrCreateTagsForUser($video, $data->getTags());
        }

        return (new JsonResponseMessage())->setCode(Response::HTTP_OK)->setError(['Success!']);
    }

    public function findAccountTags($user = null)
    {

        $account = $this->em->getRepository(Account::class)->findOneBy(['uuid' => $this->request->query->get('account_uuid')]);


        if (array_intersect($user->getRoles(), User::ACCOUNT_ADMIN_ROLES)) {

            $tags = $account ? $account->getTags() : $this->tagsRepository->findAll();
        } else {
            $this->accountVoter->vote($this->security->getToken(), $account, [AccountVoter::ACCOUNT_SHOW_TAGS]);
            $tags =  $account->getTags();
        }

        $arrTags = [];
        foreach ($tags as $tag) {

            $arrTags[] = $tag->getTagName();
        }

        return (new JsonResponseMessage())->setCode(Response::HTTP_OK)->setError(['Tag(\'s) has been retrived!'])->setContent($arrTags);
    }

    public function removeTagsFromVideos($user = null)
    {

        $data = $this->serializer->deserialize(
            $this->request->getContent('tags'),
            DtoTags::class,
            'json'
        );

        $err = $this->validator->validate($data, null, 'tags:remove');
        if ($err->count() > 0) {
            return $this->err($err);
        }


        $videos = $this->videoRepository->findBy(['uuid' => $data->getVideos()]);

        foreach ($videos as $video) {

            $isGranted =  $this->videoVoter->vote($this->security->getToken(), $video, [AccountVoter::ACCOUNT_ADD_TAGS]);

            if ($isGranted == -1) {
                continue;
            }

            $this->tagsService->removeTagsFromVideo($video, $data->getTags());
        }
        return (new JsonResponseMessage())->setCode(Response::HTTP_OK)->setError(['tag(\'s) was removed from video(\'s)!']);
    }
}
