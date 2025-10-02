<?php

namespace App\Services\Video;

use App\Entity\Encode;
use App\Entity\Folder;
use App\Entity\Simulation;
use App\Entity\User;
use App\Entity\Video;
use App\Form\Dto\DtoCredit;
use App\Form\Dto\DtoEncodeProgress;
use App\Form\Dto\DtoTag;
use App\Form\Dto\DtoTags;
use App\Form\SimulationFormType;
use App\Form\VideoFilterType;
use App\Form\VideoType;
use App\Helper\PlaylistHelper;
use App\Helper\ThumbnailHelper;
use App\Helper\VideoTypeIdentifier;
use App\Interfaces\Videos\VideosCollectionHandlerInterface;
use App\Repository\AccountRepository;
use App\Repository\EncodeRepository;
use App\Services\Consumption\ConsumptionManager;
use App\Services\Order\OrderPackage;
use App\Services\Storage\S3Storage;
use App\Services\Tags\TagsService;
use App\Services\Video\ApiEncode;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Repository\SimulationRepository;
use App\Repository\UserRepository;
use App\Repository\VideoRepository;
use App\Security\Voter\AccountVoter;
use App\Security\Voter\FolderVoter;
use App\Security\Voter\VideoVoter;
use App\Services\AbstactValidator;
use App\Services\JsonResponseMessage;
use App\Services\AuthorizationService;
use App\Services\DataFormalizerResponse;
use App\Services\Video\Handler\VideoStoragePropertyInterceptor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;
use App\Helper\RightsHelper;
use App\Services\Folder\FolderManager;
use Exception;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class VideoManager extends AbstactValidator
{
    public const ACTION_FORBIDDEN = 'This Action is forbidden for this account!';
    public const VIDEOS_NOT_FOUND = 'Video(s) not found!';

    public const ESTIMATE = 'estimate';
    public const ENCODE = 'encode';
    public const PROGRESS = 'progress';
    private $launched = ['download', 'stream'];
    private $user;
    /**
     * Undocumented variable
     *
     * @var User
     */
    private $targetUser;
    private $request;
    private $authorisation;
    private $videoRepository;
    private $encodeRepository;
    private $dataFormalizer;
    private $simulationRepository;
    /**
     * @var Video
     */
    private $currentVideo;
    private $storage;
    private $apiEncode;
    private $validator;
    private $urlGenerator;
    private $consumptionManager;
    private $paginator;
    private $videoTypeIdentifier;
    private $orderPackage;
    private $appParams;
    /**
     * @var ThumbnailHelper
     */
    private $thumbnailHelper;
    /**
     * @var DtoCredit
     */
    private $dtoCredit;
    /**
     * @var Serializer
     */
    private $serializer;
    /**
     * @var TagsService
     */
    private $tagsService;

    /**
     * @var VideosCollectionHandlerInterface
     */
    private $videosCollectionHandlerInterface;
    private $accountRepository;
    private $accountVoter;
    private $security;
    private $folderVoter;
    private $em;
    private $formFactory;
    private $videoVoter;
    private $rightsHelper;
    private $folderManager;


    public function __construct(
        ValidatorInterface      $validator,
        RequestStack            $request,
        AuthorizationService    $authorisation,
        VideoRepository         $videoRepository,
        DataFormalizerResponse $dataFormalizer,
        SimulationRepository    $simulationRepository,
        S3Storage               $storage,
        UrlGeneratorInterface   $router,
        ApiEncode               $apiEncode,
        ConsumptionManager      $consumptionManager,
        EncodeRepository        $encodeRepository,
        PaginatorInterface      $paginator,
        FormFactoryInterface    $formFactory,
        ParameterBagInterface   $appParams,
        VideoTypeIdentifier      $videoTypeIdentifier,
        OrderPackage            $orderPackage,
        ThumbnailHelper         $thumbnailHelper,
        DtoCredit               $dtoCredit,
        SerializerInterface     $serializer,
        AccountRepository       $accountRepository,
        AccountVoter            $accountVoter,
        VideosCollectionHandlerInterface     $videosCollectionHandlerInterface,
        TagsService             $tagsService,
        FolderVoter             $folderVoter,
        EntityManagerInterface             $em,
        Security                $security,
        videoVoter              $videoVoter,
        rightsHelper            $rightsHelper,
        FolderManager           $folderManager
    ) {
        $this->validator = $validator;
        $this->authorisation = $authorisation;
        $this->videoRepository = $videoRepository;
        $this->encodeRepository = $encodeRepository;
        $this->dataFormalizer = $dataFormalizer;
        $this->storage = $storage;
        $this->urlGenerator = $router;
        $this->apiEncode = $apiEncode;
        $this->consumptionManager = $consumptionManager;
        $this->paginator = $paginator;
        $this->videoTypeIdentifier = $videoTypeIdentifier;
        $this->request = $request->getCurrentRequest();
        $this->orderPackage = $orderPackage;
        $this->appParams = $appParams;
        $this->thumbnailHelper = $thumbnailHelper;
        $this->dtoCredit = $dtoCredit;
        $this->serializer = $serializer;
        $this->accountRepository = $accountRepository;
        $this->videosCollectionHandlerInterface = $videosCollectionHandlerInterface;
        $this->tagsService = $tagsService;
        $this->accountVoter = $accountVoter;
        $this->security = $security;
        $this->folderVoter = $folderVoter;
        $this->em = $em;
        $this->formFactory = $formFactory;
        $this->videoVoter = $videoVoter;
        $this->rightsHelper = $rightsHelper;
        $this->folderManager = $folderManager;
        $this->simulationRepository = $simulationRepository;
    }

    public function encode($user)
    {
        $uploadFile = $this->request->files->get('file');
        if (!isset($uploadFile)) {
            return (new JsonResponseMessage())
                ->setCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->setError(['File is missing']);
        }
        $this->targetUser = $this->authorisation->getTargetUserOrNull($user);

        if ($this->targetUser == null) {
            $this->targetUser = $user;
        }
        if (!$this->videoTypeIdentifier->mimeTypeVerify($uploadFile)) {
            return (new JsonResponseMessage())
                ->setCode(Response::HTTP_UNSUPPORTED_MEDIA_TYPE)
                ->setError(['Unsupported Media Type']);
        }

        $request = $this->request->request->all();
        $account = $this->accountRepository->findOneBy(["uuid" => $request['account_uuid']]);

        $folderUuid = $this->request->request->get('folder_uuid');
        $folder = null;
        if ($folderUuid) {
            $folderRepo = $this->em->getRepository(Folder::class);
            $folder = $folderRepo->findOneBy(["uuid" => $folderUuid, "account" => $account]);

            if ($folder) {
                $this->folderVoter->vote($this->security->getToken(), $folder, [FolderVoter::ENCODE_IN_FOLDER]);
            }
        } else {
            $this->accountVoter->vote($this->security->getToken(), $account, [AccountVoter::ACCOUNT_ENCODE]);
        }

        $ffmpegVideoDuration = $this->videoTypeIdentifier->duration($uploadFile);

        $videoExtensionHelper = $this->videoTypeIdentifier->findExtension($uploadFile->guessExtension());
        $video = new Video();
        $video->setUuid('')
            ->setUser($this->targetUser)
            ->setAccount($account)
            ->setFolder($folder)
            ->setEncodedBy($this->getEncodedBy($user));

        $path = $this->urlGenerator->generate("video_download", ["video_uuid" => $video->getUuid()]);
        $isStored = VideoStoragePropertyInterceptor::handle($account, $this->targetUser);
        $data = array_merge($request, [
            "name" => pathinfo($uploadFile->getClientOriginalName(), PATHINFO_FILENAME),
            "size" => $uploadFile->getSize(),
            "duration" => $ffmpegVideoDuration,
            "extension" => $videoExtensionHelper->getExtension(),
            "maxDownloadAuthorized" => $isStored ? null : Video::MAX_DOWNLOAD_AUTHORIZED,
            "isStored" => $isStored,
            "downloadLink" => $_ENV['APP_DOMAINE'] . $path,
            "deletedAt" => !$isStored ? (new \DateTimeImmutable('now'))
                ->modify('+ ' . Video::INTERVAL_REMOVE_DAY . 'day')
                ->modify('tomorrow + 1 hour ') : null
        ]);

        $form = $this->formFactory->create(VideoType::class, $video);
        $form->submit($data);
        $video = $form->getData();

        $err = $this->validator->validate($video, null, ['encode']);
        if ($err->count() > 0) {
            return $this->err($err);
        }

        $verifyAndCreditOrder = $this->verifyAndCreditOrder($user, $video, $account);
        if ($verifyAndCreditOrder instanceof JsonResponseMessage) {
            return $verifyAndCreditOrder;
        }
        $this->currentVideo = $this->videoRepository->create($video);
        if ($this->request->request->has('tags') != null) {
            $req['tags'] =  $this->request->request->get('tags');
            $tags = json_decode($req['tags']);
            $this->tagsService->createTagsFolderForUser($video, $tags);
        }

        $this->request->request->add(['currentVideo' => $this->currentVideo]);
        $video->setPlaylist((new PlaylistHelper($video))->generatePlaylistLink());
        $this->thumbnailHelper->createThumbnailFormVideo($uploadFile, $this->currentVideo);

        return $this->dataFormalizer->extract($this->currentVideo, 'encode', false, "video(s) successfuly created!");
    }

    /**
     * verify if acount has right to encode and if credit exist and reduce it when encoding
     *
     * @param [type] $user
     * @param [type] $video
     * @return JsonResponseMessage | void
     */
    private function verifyAndCreditOrder($user,  $video, $account)
    {

        if (array_intersect($user->getRoles(), User::ACCOUNT_ROLES)) {
            /**@var OrderPackage $obj */
            $account = $video->getAccount();
            $obj = $this->orderPackage->checkOrderCredit($account, $video);

            $this->dtoCredit->creditStorage = $obj->hasBits();
            $this->dtoCredit->creditEncode = $obj->hasSeconds();
            if ($obj->hasRessources() === false) {
                return $this->responseCredits($obj, $video);
            }
        }

        if (
            (array_intersect($user->getRoles(), User::ACCOUNT_ADMIN_ROLES)) &&
            ((array_intersect($this->targetUser->getRoles(), User::ACCOUNT_ROLES)) &&
                $video->getIsStored() === true)
        ) {
            /**@var OrderPackage $obj */
            $obj = $this->orderPackage->adminCheckOrderCreditForUser($account, $video);

            $this->dtoCredit->creditStorage = $obj->hasBits();
            $this->dtoCredit->creditEncode = $obj->hasSeconds();
            if ($obj->hasRessources() === false) {
                return $this->responseCredits($obj, $video);
            }
        }
    }

    public function estimate(): JsonResponseMessage
    {
        $uploadFile = $this->request->files->get('file');
        if (!isset($uploadFile)) {
            return (new JsonResponseMessage())
                ->setCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->setError(['Upload file is missing']);
        }

        if (!$this->videoTypeIdentifier->mimeTypeVerify($uploadFile)) {
            return (new JsonResponseMessage())
                ->setCode(Response::HTTP_UNSUPPORTED_MEDIA_TYPE)
                ->setError(['Unsupported Media Type']);
        }
        $videoExtensionHelper = $this->videoTypeIdentifier->findExtension($uploadFile->guessExtension());
        $simulation = new Simulation();
        $simulation->setUuid('')
            ->setCreatedAt(new \DateTimeImmutable('now'))
            ->setName(pathinfo($uploadFile->getClientOriginalName(), PATHINFO_FILENAME))
            ->setSize($uploadFile->getSize())
            ->setDuration('')
            ->setIsDeleted(false)
            ->setExtension($videoExtensionHelper->getExtension());
        $this->currentVideo = $this->simulationRepository->create($simulation);
        $this->request->request->add(['currentVideo' => $this->currentVideo]);
        return $this->estimateResponse($uploadFile);
    }

    private function estimateResponse($uploadFile): JsonResponseMessage
    {
        $this->storage->videoUpload($uploadFile, $this->currentVideo);
        $data = $this->apiEncode->prepare(self::ESTIMATE, $this->currentVideo);
        if ($data instanceof JsonResponseMessage) {
            return $data;
        }
        $rep = json_decode($data->getBody()->getContents(), true);
        if ($data->getStatusCode() === Response::HTTP_OK) {
            /**@var Simulation $simulation */
            $simulation = $this->currentVideo;
            $simulation->setOriginalSize($rep['size'] ?? null)
                ->setEstimateSize($rep['estimatedSize'] ?? null)
                ->setGainPercentage($rep['gain'] ?? null)
                ->setVideoQuality(
                    $rep['resolution']
                        ? $rep['resolution']['width'] . 'x' . $rep['resolution']['height']
                        : null
                )
                ->setDuration($rep['duration'] ?? null)
                ->setFps($rep['fps'] ?? null)
                ->setFrameCount($rep['frameCount'] ?? null);


            /** @var Simulation $currentSimulation */
            $this->currentVideo = $this->simulationRepository->updateSimulation($simulation);
            $resp = [
                "uuid" => $this->currentVideo->getUuid(),
                "name" => $this->currentVideo->getName(),
                "extension" => $this->currentVideo->getExtension(),
                "videoQuality" => $this->currentVideo->getVideoQuality(),
                "videoSize" => $this->currentVideo->getSize(),
                "duration" => $this->currentVideo->getDuration(),
                "createdAt" => $this->currentVideo->getCreatedAt(),
                "originalSize" => $this->currentVideo->getOriginalSize(),
                "estimateSize" => $this->currentVideo->getEstimateSize(),
                "gain" => $this->currentVideo->getGainPercentage(),
                "fps" => $this->currentVideo->getFps(),
                "frameCount" => $this->currentVideo->getFrameCount()
            ];
            return (new JsonResponseMessage())
                ->setCode($data->getStatusCode())
                ->setError([$rep['detail'] ?? 'Success'])
                ->setContent($resp);
        }
        return (new JsonResponseMessage())
            ->setCode($data->getStatusCode())
            ->setError([$rep['detail'] ?? 'Failed'])
            ->setContent($rep);
    }

    public function findAll($user)
    {
        $request = $this->request->query->all();
        $form = $this->formFactory->create(VideoFilterType::class);
        $form->submit($request);
        $filters = $form->getData();

        $video = new Video();
        $video->setCreatedAt($filters['startAt']);
        $video->setCreatedAt($filters['endAt']);
        $video->setIsStored($filters['isStored']);
        $video->setIsDeleted($filters['isDeleted']);
        $video->setIsMultiEncoded($filters['isMultiEncoded']);

        $err = $this->validator->validate($video, null, ['filters']);
        if ($err->count() > 0) {
            return $this->err($err);
        }

        $filters['isStored'] = $video->getIsStored();
        $filters['isMultiEncoded'] = $video->getIsMultiEncoded();
        $filters['isDeleted'] = $video->getIsDeleted();

        $account = $this->accountRepository->findOneBy(['uuid' => $filters['account_uuid']]);

        if (!array_intersect($user->getRoles(), User::ACCOUNT_ADMIN_ROLES)) {
            $this->accountVoter->vote($this->security->getToken(), $account, [AccountVoter::ACCOUNT_VIDEO_LIST]);
        }

        if (isset($filters['folder_uuid'])) {
            $folderRepository = $this->em->getRepository(Folder::class);
            $folder = $folderRepository->findOneBy(['uuid' => $filters['folder_uuid']]);
            $this->folderVoter->vote($this->security->getToken(), $folder, [FolderVoter::SHOW_FOLDER_CONTENT]);
            $filters['folder'] = $folder;
        }

        if ($account  == null && array_intersect($this->security->getUser()->getRoles(), User::ACCOUNT_ADMIN_ROLES)) {
            $filters['countable'] = true;
        }

        if ($account != null && ($filters['name'] != null || $filters['tags'] != null)) {
            $accessibleFolderCollection = $this->folderManager->findAccountRootVideoTeck()->getContent();
            $folderIds = $this->extractIds($accessibleFolderCollection);
            $filters['folderId'] = $folderIds;
        }


        $videoCollection = $this->videoRepository->findVideos($account, $filters);
        $videoCollection  = $this->videosCollectionHandlerInterface->handle($videoCollection, $filters);

        return $this->dataFormalizer->extract(
            $videoCollection,
            "list_of_videos",
            true,
            "Video(s) successfully retrieved!",
            Response::HTTP_OK,
            $filters
        );
    }

    public function extractIds($data)
    {
        $ids = [];

        foreach ($data as $item) {
            if (isset($item['id'])) {
                $ids[] = $item['id'];
            }

            if (isset($item['subFolders']) && is_array($item['subFolders'])) {
                $ids = array_merge($ids, $this->extractIds($item['subFolders']));
            }
        }

        return $ids;
    }

    public function findOne($user): JsonResponseMessage
    {
        $videoUuid = $this->request->attributes->get('video_uuid');
        $video = $this->videoRepository->findOneBy(['uuid' => $videoUuid]);

        $this->videoVoter->vote($this->security->getToken(), $video, [VideoVoter::ACCOUNT_READ_VIDEO_DETAILS]);

        $video = $this->consumptionManager->calculeForVideo($video);
        if ($video->getGainOptimisation() == 0) {
            $this->videoRepository->updateVideo($video);
        }
        if ($video->getProgress() <= 0) {
            $this->currentVideo = $video;
        }

        if ($video->getFolder()) {
            $folderRole = $this->rightsHelper->findUserFolderRoleHeritage($user, $video->getFolder());
            $video->getFolder()->setMemberRole($folderRole);
        }

        return $this->dataFormalizer->extract($video, 'one_video', false, "Video Successfully retrieved");
    }

    public function downloadFile($user = null)
    {
        $videoUuid = $this->request->attributes->get('video_uuid');
        $video = $this->videoTypeIdentifier->identify($videoUuid);

        if ($video instanceof Video) {
            $this->videoVoter->vote($this->security->getToken(), $video, [VideoVoter::ACCOUNT_DOWNLOAD_VIDEO]);
        } else if ($video instanceof Encode) {
            $this->videoVoter->vote($this->security->getToken(), $video->getVideo(), [VideoVoter::ACCOUNT_DOWNLOAD_VIDEO]);
        }

        if (array_intersect($user->getRoles(), User::ACCOUNT_ROLES)) {
            if ($video instanceof Video) {

                if ($video->getMaxDownloadAuthorized() === 0) {
                    $jsonResponse = (new JsonResponseMessage())
                        ->setCode(Response::HTTP_FORBIDDEN)
                        ->setError('limit download exceed, contact support for more information !!');
                    return new JsonResponse($jsonResponse->displayData(), $jsonResponse->displayHeader());
                }
                if ($video->getIsStored() === false) {
                    $video->setMaxDownloadAuthorized($video->getMaxDownloadAuthorized() - 1);
                    $this->videoRepository->updateVideo($video);
                }
            }
            if ($video instanceof Encode) {
                if (($video->getMaxDownloadAuthorized() === 0)) {
                    $jsonResponse = (new JsonResponseMessage())
                        ->setCode(Response::HTTP_FORBIDDEN)
                        ->setError('limit download exceed, contact support for more information !!');
                    return new JsonResponse($jsonResponse->displayData(), $jsonResponse->displayHeader());
                }
                if ($video->getVideo()->getIsStored() === false) {
                    $video->setMaxDownloadAuthorized($video->getMaxDownloadAuthorized() - 1);
                    $this->encodeRepository->updateEncode($video);
                }
            }
        }

        $this->consumptionManager->addConsumptionRow($video, $this->launched[0]);
        return $this->storage->videoFileDownload($video);
    }

    public function addDownloadCount($user = null)
    {
        $videoUuid = $this->request->attributes->get('video_uuid');
        $video = $this->videoTypeIdentifier->identify($videoUuid);

        if ($video == null) {
            $jsonResponse = (new JsonResponseMessage())
                ->setCode(Response::HTTP_NOT_FOUND)
                ->setError('Invalid file');
            return new JsonResponse($jsonResponse->displayData(), $jsonResponse->displayHeader());
        }

        if ($video instanceof Video && $video->getIsStored() === false) {
            $video->setMaxDownloadAuthorized($video->getMaxDownloadAuthorized() + 1);
            $this->videoRepository->updateVideo($video);
            $jsonResponse = (new JsonResponseMessage())
                ->setCode(Response::HTTP_OK)
                ->setError(['Download count has been added!']);
            return new JsonResponse($jsonResponse->displayData(), $jsonResponse->displayHeader());
        }
        if ($video instanceof Encode && $video->getVideo()->getIsStored() === false) {
            $video->setMaxDownloadAuthorized($video->getMaxDownloadAuthorized() + 1);
            $this->encodeRepository->updateEncode($video);
            $jsonResponse = (new JsonResponseMessage())
                ->setCode(Response::HTTP_OK)
                ->setError(["Download count has been added!"]);
            return new JsonResponse($jsonResponse->displayData(), $jsonResponse->displayHeader());
        }
        $jsonResponse = (new JsonResponseMessage())->setCode(Response::HTTP_OK)->setError('This is a stored Video');
        return new JsonResponse($jsonResponse->displayData(), $jsonResponse->displayHeader());
    }

    public function streamThumbnail()
    {
        $videoUuid = $this->request->attributes->get('video_uuid');

        $video = $this->videoRepository->findOneBy(['uuid' => $videoUuid]);

        if ($video == null) {
            $jsonResponse = (new JsonResponseMessage())->setCode(Response::HTTP_NOT_FOUND)->setError('Invald file');
            return new JsonResponse($jsonResponse->displayData(), $jsonResponse->displayHeader());
        }
        $fileName = $video->getUuid() . '_' . $video->getSlugName() . '_thumbnail';

        $thumbnail = $this->request->query->get('thumbnail') == 'HD' ? $fileName . '_HD.jpeg' : $fileName . '_SD.jpeg';

        return $this->storage->thumbnailFileStream($thumbnail);
    }

    public function streamFile()
    {
        $videoUuid = $this->request->attributes->get('video_uuid');
        $video = $this->videoTypeIdentifier->identify($videoUuid);
        if ($video == null) {
            $jsonResponse = (new JsonResponseMessage())->setCode(Response::HTTP_NOT_FOUND)->setError('Invald file');
            return new JsonResponse($jsonResponse->displayData(), $jsonResponse->displayHeader());
        }
        $this->consumptionManager->addConsumptionRow($video, $this->launched[1]);
        return $this->storage->videoFileStream($video);
    }

    public function encodeResponse($uploadFile): JsonResponseMessage
    {
        $this->storage->videoUpload($uploadFile, $this->currentVideo);
        $this->apiEncode->prepare(self::ENCODE, $this->currentVideo);
        return (new JsonResponseMessage())
            ->setCode(Response::HTTP_ACCEPTED)
            ->setError('video has been send to encoder');
    }

    public function copyResponse($video): JsonResponseMessage
    {
        $this->copyMediaFilesAndRename($video);
        $this->copyVideoTags($video);
        $this->apiEncode->prepare(self::ENCODE, $this->currentVideo);
        return (new JsonResponseMessage())
            ->setCode(Response::HTTP_ACCEPTED)
            ->setError('video has been send to encoder');
    }

    public function addEncodedVideo(Video $video, $rep)
    {
        $encodedVideos = $rep['encodedVideos'];
        if (isset($encodedVideos) && ($encodedVideos != null)) {
            foreach ($encodedVideos as $enc => $value) {
                $objEncode = new Encode();
                $objEncode->setExtension('mp4')
                    ->setUuid()
                    ->setName($video->getName())
                    ->setSlugName($video->getName())
                    ->setSize(0)
                    ->setQuality($enc)
                    ->setLink($value)
                    ->setIsDeleted(false)
                    ->setMaxDownloadAuthorized($video->getIsStored() === true ? null : Encode::MAX_DOWNLOAD_AUTHORIZED)
                    ->setExternalId($objEncode->getUuid())
                    ->setStreamLink($_ENV['OVH_PUBLIC_STORAGE_LINK'] . $objEncode->getLink())
                    ->setDownloadLink($_ENV['APP_DOMAINE'] . $this->urlGenerator->generate("video_download", ["video_uuid" => $objEncode->getUuid()]))
                    ->setCreatedAt(new \DateTimeImmutable('now'))
                    ->setUpdatedAt(new \DateTimeImmutable('now'));
                $encoded = $this->encodeRepository->save($objEncode);
                $video->addEncode($encoded);
                $this->videoRepository->updateVideo($video);
            }
        }
    }

    public function pingProgress($user = null)
    {
        $videoUuid = $this->request->attributes->get('video_uuid');
        $video = $this->videoRepository->findOneBy(['uuid' => $videoUuid]);

        $this->videoVoter->vote($this->security->getToken(), $video, [VideoVoter::ACCOUNT_ENCODE_VIDEO]);

        $this->currentVideo = $video;
        return $this->pingResponse();
    }

    private function pingResponse()
    {
        /** @var Video|Encode $currentVideo */
        $rep['uuid'] = $this->currentVideo->getUuid();
        $rep['progress'] = $this->currentVideo->getProgress();
        $rep['isUploadComplete'] = $this->currentVideo->getIsUploadComplete();
        $rep['encoding'] = $this->currentVideo->getEncodingState();
        if ($this->currentVideo->getIsUploadComplete() === false) {
            $this->storage->findInStorage($this->currentVideo);
            return $this->dataFormalizer->extract($this->currentVideo, 'uploading', false, 'Video is uploading');
        } else {
            if (
                $this->currentVideo->getProgress() === 100 &&
                $this->currentVideo->getEncodingState() === Video::ENCODING_ENCODED
            ) {
                return (new JsonResponseMessage())
                    ->setContent($rep)
                    ->setCode(Response::HTTP_OK)
                    ->setError(['Video(s) encoded!']);
            }

            unset($rep['job id']);
            $rep['isUploadComplete'] = $this->currentVideo->getIsUploadComplete();
            $rep['progress'] = $this->currentVideo->getProgress();
            $rep['uuid'] = $this->currentVideo->getUuid();
            $rep['encoding'] = $this->currentVideo->getEncodingState();
            return (new JsonResponseMessage())
                ->setContent($rep)
                ->setCode(Response::HTTP_PROCESSING)
                ->setError(['Encoding in progress!!']);
        }
    }

    public function removeVideo()
    {
        $videoUuid = $this->request->attributes->get('video_uuid');
        $this->currentVideo = $this->videoTypeIdentifier->identify($videoUuid);
        $sourceVideo = $this->currentVideo instanceof Video
            ? $this->currentVideo
            : $this->currentVideo->getVideo();

        $isGranted = $this->videoVoter->vote($this->security->getToken(), $sourceVideo, [VideoVoter::ACCOUNT_REMOVE_VIDEO]);

        if ($isGranted == -1) {
            throw new AccessDeniedException('Not enough permissions');
        }
        if ($this->storage->findInStorage($this->currentVideo) === false) {
            return (new JsonResponseMessage())
                ->setCode(Response::HTTP_NOT_FOUND)
                ->setError("video's Not found in storage");
        }

        $this->toDelete();
        if ($this->currentVideo->getIsDeleted()) {
            $this->storage->videoDelete($this->currentVideo);
            $orginalVideo = $this->currentVideo instanceof Video ? $this->currentVideo : $this->currentVideo->getVideo();
            // only user's videos can trigger the action to give back storage credits
            if (array_intersect($orginalVideo->getUser()->getRoles(), User::ACCOUNT_ROLES)) {
                $this->orderPackage->giveBackCreditForFullyRemovedStoredVideos($orginalVideo);
            }
            $this->removePlaylistElements($orginalVideo);
            return (new JsonResponseMessage())
                ->setCode(Response::HTTP_OK)
                ->setError("Video successfully removed!");
        }
        return (new JsonResponseMessage())->setCode(Response::HTTP_NOT_ACCEPTABLE)->setError("Video not deleted");
    }

    private function toDelete()
    {
        $this->currentVideo
            ->setStreamLink(null)
            ->setDownloadLink(null)
            ->setIsDeleted(true);

        if ($this->currentVideo instanceof Encode) {
            $this->encodeRepository->deleteEncode($this->currentVideo);
        }
        if ($this->currentVideo instanceof Video) {
            $this->videoRepository->deleteVideo($this->currentVideo);
        }
    }

    public function multiRemoveVideo()
    {
        $videos = json_decode($this->request->getContent('videos'), true);

        $data = [];

        foreach ($videos['videos'] as $videoUuid) {
            $this->currentVideo = $this->videoTypeIdentifier->identify($videoUuid);
            $sourceVideo = $this->currentVideo instanceof Video ? $this->currentVideo : $this->currentVideo->getVideo();
            if ($this->currentVideo === null) {
                $data['invalid'][] = $videoUuid;
                continue;
            }

            $isGranted = $this->videoVoter->vote($this->security->getToken(), $sourceVideo, [VideoVoter::ACCOUNT_REMOVE_VIDEO]);

            if ($isGranted == -1) {
                $data['unauthorized'][] = $videoUuid;
                continue;
            }

            if (($this->storage->findInStorage($this->currentVideo) === false) && ($this->currentVideo->getIsDeleted() === true)) {
                $data['alreadyDeleted'][] = $videoUuid;
                continue;
            }

            $this->toDelete();

            $this->storage->videoDelete($this->currentVideo);
            $data['done'][] = $videoUuid;
            $orginalVideo = $this->currentVideo instanceof Video
                ? $this->currentVideo
                : $this->currentVideo->getVideo();
            // only user's videos can trigger the action to give back storage credits
            if (in_array($orginalVideo->getUser()->getRoles()[0], User::ACCOUNT_ROLES)) {
                $this->orderPackage->giveBackCreditForFullyRemovedStoredVideos($orginalVideo);
            }
            $this->removePlaylistElements($orginalVideo);
        }
        return $this->dataFormalizer->extract($data, null, false);
    }

    private function removePlaylistElements($orginalVideo)
    {
        $filter = ['isDeleted' => true, 'video' => $orginalVideo];
        $hasFullyDeleted = $this->videoRepository->findVideos(null, $filter);

        if ($hasFullyDeleted != null) {
            $this->storage->removeWithPrefix($orginalVideo);
            $orginalVideo->setPlaylist(null);
            $this->videoRepository->updateVideo($orginalVideo);
        }
    }

    public function removeIncompletEncoding($videoUuid)
    {
        $this->currentVideo = $this->videoTypeIdentifier->identify($videoUuid);

        $this->toDelete();
        if ($this->currentVideo->getIsDeleted()) {
            $this->storage->videoDelete($this->currentVideo);
            $orginalVideo = $this->currentVideo instanceof Video
                ? $this->currentVideo
                : $this->currentVideo->getVideo();
            // only user's videos can trigger the action to give back storage credits
            if (in_array($orginalVideo->getUser()->getRoles()[0], User::ACCOUNT_ROLES)) {
                $hasReturnedBits = $this->orderPackage->giveBackCreditForFullyRemovedStoredVideos($orginalVideo);
            }
            $this->removePlaylistElements($orginalVideo);
            return (new JsonResponseMessage())->setCode(Response::HTTP_OK)->setError("Video successfully removed!");
        }
        return (new JsonResponseMessage())->setCode(Response::HTTP_NOT_ACCEPTABLE)->setError("Video not deleted");
    }

    public function retryEncode()
    {
        $videoUuid = $this->request->attributes->get('video_uuid');
        $this->currentVideo = $this->videoTypeIdentifier->identify($videoUuid);

        $this->videoVoter->vote($this->security->getToken(), $this->currentVideo, [VideoVoter::ACCOUNT_ENCODE_VIDEO]);

        if ($this->currentVideo instanceof Encode) {
            $encode = $this->currentVideo;
            $originalVideo = $this->currentVideo->getVideo();
            switch ($originalVideo->getEncodingState()) {
                case Video::ENCODING_RETRY:
                    return (new JsonResponseMessage())->setCode(Response::HTTP_FORBIDDEN)->setError("Retry to encode this video!");
                    //todo waiting more info
                    //                case Video::ENCODING_ANALYSING:
                    //                    return (new JsonResponseMessage())->setCode(Response::HTTP_FORBIDDEN)->setError("Analysing video!");
                    //                case Video::ENCODING_ENCODING:
                    //                    return (new JsonResponseMessage())->setCode(Response::HTTP_FORBIDDEN)->setError("Encoding video!");

            }

            if ($this->storage->findInStorage($encode) === true) {
                $this->storage->videoDelete($encode);
            }
            $playlist = new PlaylistHelper($originalVideo);
            $originalVideo->setProgress(0);
            $encode->setSize(0);
            $originalVideo->setEncodingState(Video::ENCODING_RETRY);

            $this->encodeRepository->updateEncode($encode);
            $message = 'selected video will be re encoded';
            $originalVideo->setQualityNeed($encode->getQuality());
            $originalVideo->setIsMultiEncoded(false);
            $originalVideo->setPlaylist($playlist->generatePlaylistLink());
            $this->apiEncode->prepare(self::ENCODE, $originalVideo);
            $this->currentVideo = $originalVideo;
            $code = Response::HTTP_OK;
        } else {
            if ($this->currentVideo instanceof Video) {
                switch ($this->currentVideo->getEncodingState()) {
                    case Video::ENCODING_RETRY:
                        return (new JsonResponseMessage())->setCode(Response::HTTP_FORBIDDEN)->setError("Retry to encode this video!");
                        //todo waiting more info
                        //                    case Video::ENCODING_ANALYSING:
                        //                        return (new JsonResponseMessage())->setCode(Response::HTTP_FORBIDDEN)->setError("Analysing video!");
                        //                    case Video::ENCODING_ENCODING:
                        //                        return (new JsonResponseMessage())->setCode(Response::HTTP_FORBIDDEN)->setError("Encoding video!");
                }
                foreach ($this->currentVideo->getEncodes() as $encode) {
                    if ($this->storage->findInStorage($encode) === true) {
                        $this->storage->videoDelete($encode);
                    }

                    if ($encode->getIsDeleted()) {
                        $this->revertDeletedStateWhenReTry($encode);
                    }
                    $encode->setSize(0);
                }
                $this->currentVideo->setProgress(0);
                $this->currentVideo->setEncodingState(Video::ENCODING_PENDING);
                $data = $this->apiEncode->prepare(self::ENCODE, $this->currentVideo);

                $this->videoRepository->updateVideo($this->currentVideo);
                $message = "Re encoding task was send to encoder sucessfully";
                $code = Response::HTTP_OK;
            }
        }


        return $this->dataFormalizer->extract($this->currentVideo, 'encode:retry', false, $message, $code);
    }


    public function encodeProgress()
    {
        $video_uuid = $this->request->attributes->get('video_uuid');

        $video = $this->videoRepository->findOneBy(['uuid' => $video_uuid]);

        $this->videoVoter->vote($this->security->getToken(), $video, [VideoVoter::ACCOUNT_ENCODE_VIDEO]);

        $encodeProgress = $this->serializer->deserialize(
            $this->request->getContent(),
            DtoEncodeProgress::class,
            'json'
        );
        $err = $this->validator->validate($encodeProgress, null, ['encode:progress']);
        if ($err->count() > 0) {
            return $this->err($err);
        }

        $this->currentVideo = $video;
        $encodeProgress->video_uuid = $this->currentVideo->getUuid();
        return $this->progressResponse($encodeProgress);
    }

    public function progressResponse($encodeProgress)
    {

        $progress = false;
        if ($this->currentVideo->getEncodingState() != Video::ENCODING_ENCODED) {
            $this->currentVideo->setEncodingState($encodeProgress->status);
            $this->currentVideo->setProgress($encodeProgress->progress);
        }
        // a remetre quand l encoder debugera ces erreurs
        // if ($encodeProgress->status == Video::ENCODING_ERROR) {
        //     $encodes = $this->currentVideo->getEncodes();
        //     foreach ($encodes as $encode) {
        //         $this->removeIncompletEncoding($encode->getUuid());
        //     }

        //     $this->currentVideo = $encode->getVideo();
        // }
        if ($encodeProgress->progress == 100 || $encodeProgress->status == Video::ENCODING_ENCODED) {

            $encodes = $this->currentVideo->getEncodes();

            foreach ($encodes as $encode) {
                $this->storage->findInStorage($encode);
                $this->currentVideo->setEncodingState(Video::ENCODING_ENCODING);
                $this->currentVideo->setProgress(91);
                if ($encodes[0]->getSize() > 0) {
                    $progress = true;
                    $this->currentVideo->setEncodingState(Video::ENCODING_ENCODED);
                    $this->currentVideo->setProgress(100);
                    $this->currentVideo->setGainOptimisation($this->consumptionManager->calculeEncodeHighestVideo($this->currentVideo));
                }
            }
        }

        $this->currentVideo = $this->videoRepository->updateVideo($this->currentVideo);
        if ($this->currentVideo->getProgress() == 100) {
            return $this->dataFormalizer->extract($this->currentVideo, 'encode:progress', false, 'Encoding is complete');
        }

        return $this->dataFormalizer->extract($this->currentVideo, 'encode:progress', false, 'Encoding is in process');
    }

    public function editVideoInfo()
    {
        $videoUuid = $this->request->attributes->get('video_uuid');
        $video = $this->videoRepository->findOneBy(['uuid' => $videoUuid]);


        $this->videoVoter->vote($this->security->getToken(), $video, [VideoVoter::ACCOUNT_EDIT_VIDEO]);

        $data = $this->serializer->deserialize(
            $this->request->getContent(),
            Video::class,
            'json',
            [
                'object_to_populate' => $video,
                "groups" => 'video:edit'
            ]
        );
        $err = $this->validator->validate($data, null, ['video:edit']);

        if ($err->count() > 0) {
            return $this->err($err);
        }

        $video = $this->videoRepository->updateVideo($video);
        return $this->dataFormalizer->extract(
            $video,
            ['video:edit', 'video:edit:json'],
            false,
            'Video(s) successfully modified!'
        );
    }


    public function storeVideo()
    {
        $videoUuid = $this->request->attributes->get('video_uuid');
        $video = $this->videoRepository->findOneBy(['uuid' => $videoUuid, 'isDeleted' => false, 'isStored' => false]);

        $this->videoVoter->vote($this->security->getToken(), $video, [VideoVoter::ACCOUNT_EDIT_VIDEO]);

        if ($video->getIsStored()) {
            return (new JsonResponseMessage())
                ->setCode(Response::HTTP_FORBIDDEN)
                ->setError(['Video(s) is already stored']);
        }

        $video->setIsStored(true);
        if (array_intersect($video->getUser()->getRoles(), User::ACCOUNT_ROLES)) {
            /** @var OrderPackage $obj  */
            $obj = $this->orderPackage->adminCheckOrderCreditForUser($video->getAccount(),  $video);
            $this->dtoCredit->creditStorage = $obj->hasBits();
            $this->dtoCredit->creditEncode = $obj->hasSeconds();
            if ($obj->hasRessources() === false) {
                return $this->responseCredits($obj, $video);
            }
        }
        $video = $this->videoRepository->updateVideo($video);
        return $this->dataFormalizer->extract($video, ['one_video'], false, 'Video(s) successfully stored!');
    }
    /**
     *  Encode with new mediaType
     *
     * @param [type] $user
     * @return JsonResponseMessage
     */
    public function reEncodeFromExistingVideo($user = null)
    {
        $videoUuid = $this->request->attributes->get('video_uuid');
        $mediaType = json_decode($this->request->getContent());

        $this->targetUser = $this->authorisation->getTargetUserOrNull($user);

        $selectedVideo = $this->videoRepository->findOneBy(['isDeleted' => false, 'uuid' => $videoUuid]);

        $this->videoVoter->vote($this->security->getToken(), $selectedVideo, [VideoVoter::ACCOUNT_ENCODE_VIDEO]);

        $this->targetUser = $this->targetUser != null ? $user : $selectedVideo->getUser();

        $newVideo = new Video();

        /**
         * @var \App\Entity\Video $newVideo
         */

        $newVideo
            ->setUuid('')
            ->setDownloadLink($_ENV['APP_DOMAINE'] . $this->urlGenerator->generate("video_download", ["video_uuid" => $newVideo->getUuid()]))
            ->setMediaType($mediaType->mediaType)
            ->setName($selectedVideo->getName())
            ->setQualityNeed($selectedVideo->getVideoQuality())
            ->setIsStored(VideoStoragePropertyInterceptor::handle($selectedVideo->getAccount(), $selectedVideo->getUser()))
            ->setIsMultiEncoded($selectedVideo->getIsMultiEncoded())
            ->setDuration($selectedVideo->getDuration())
            ->setSize($selectedVideo->getSize())
            ->setVideoQuality('')
            ->setEncodedBy($this->getEncodedBy($user))
            ->setExtension($selectedVideo->getExtension())
            ->setIsArchived(false)
            ->setTitle($selectedVideo->getTitle())
            ->setFolder($selectedVideo->getFolder())
            ->setIsDeleted(false)
            ->setIsInTrash(false)
            ->setIsUploadComplete(false)
            ->setUser($selectedVideo->getUser())
            ->setAccount($selectedVideo->getAccount())
            ->setProgress(0)
            ->setGainOptimisation(0)
            ->setEncodingState(Video::ENCODING_PENDING)
            ->setMaxDownloadAuthorized($selectedVideo->getIsStored() === true ? null : Video::MAX_DOWNLOAD_AUTHORIZED)
            ->setCreatedAt(new \DateTimeImmutable('now'))
            ->setUpdatedAt(new \DateTimeImmutable('now'))
            ->setDeletedAt($selectedVideo->getIsStored() != true ? (new \DateTimeImmutable('now'))
                ->modify('+ ' . Video::INTERVAL_REMOVE_DAY . 'day')
                ->modify('tomorrow + 1 hour ') : null);
        $err = $this->validator->validate($newVideo, null, ['encode']);
        if ($err->count() > 0) {
            return $this->err($err);
        }


        $verifyAndCreditOrder = $this->verifyAndCreditOrder($user, $newVideo, $selectedVideo->getAccount());
        if ($verifyAndCreditOrder instanceof JsonResponseMessage) {
            return $verifyAndCreditOrder;
        }

        $this->currentVideo = $this->videoRepository->create($newVideo);
        $this->request->request->add(['selectedVideo' => $selectedVideo]);
        $newVideo->setPlaylist((new PlaylistHelper($newVideo))->generatePlaylistLink());

        return $this->dataFormalizer->extract($newVideo, ['one_video'], false, 'video(s) successfuly copied!');
    }
    /**
     * copy files in Strorage
     * thumbnails and video source
     * video
     * @return void
     */
    private function copyMediaFilesAndRename(Video $selectedVideo)
    {
        $prefixSourceName = $selectedVideo->getUuid() . '_' . $selectedVideo->getSlugName();
        $prefixCopyName = $this->currentVideo->getUuid() . '_' . $this->currentVideo->getSlugName();
        $filesNames = [
            'thumbnails' => [
                'sd' => [
                    'source' => $prefixSourceName . '_thumbnail_SD.jpeg',
                    'copy' => $prefixCopyName  . '_thumbnail_SD.jpeg',
                ],
                'hd' => [
                    'source' => $prefixSourceName . '_thumbnail_HD.jpeg',
                    'copy' => $prefixCopyName  . '_thumbnail_HD.jpeg',
                ],
                'ld' => [
                    'source' => $prefixSourceName . '_thumbnail_LD.jpeg',
                    'copy' => $prefixCopyName  . '_thumbnail_LD.jpeg',
                ],
            ],
            'videos' => [
                'source' => $selectedVideo->getLink(),
                'copy' => $this->currentVideo->getLink(),
            ]
        ];
        foreach ($filesNames as $key => $filesType) {
            if ($key === 'videos') {
                $this->storage->copyFileToS3AndRename($filesType['source'], $filesType['copy']);
            }
            if ($key === 'thumbnails')
                foreach ($filesType as $types) {

                    $this->storage->copyFileToS3AndRename($types['source'], $types['copy']);
                }
        }
    }

    private function responseCredits($obj, $video)
    {
        $filemo = $this->videoTypeIdentifier->roundify($video->getSize() / 1000000);
        $mo = $obj->hasBits() != 0 ? $this->videoTypeIdentifier->roundify($obj->hasBits() / 1000000) : 0;
        return (new JsonResponseMessage())
            ->setCode(Response::HTTP_FORBIDDEN)
            ->setError([
                "this file size is " . $filemo . ' mo  and you have , ' . $mo . " mo left!",
                "this file lenght is " . $video->getDuration() . ' sec  and you have , ' . $obj->hasSeconds() . " sec left!"
            ])
            ->setContent([$this->dtoCredit]);
    }

    private function revertDeletedStateWhenReTry(Encode $encode)
    {
        $encode->setStreamLink($_ENV['OVH_PUBLIC_STORAGE_LINK'] . $encode->getLink());
        $encode->setIsDeleted(false);
        $encode->setDownloadLink($_ENV['APP_DOMAINE'] . $this->urlGenerator->generate("video_download", ["video_uuid" => $encode->getUuid()]));
    }

    private function getEncodedBy($user)
    {
        $fullname = $this->targetUser->getFirstname() . ' ' . $this->targetUser->getLastName();

        $encodedBy = $fullname != " " ? $fullname : $this->targetUser->getEmail();
        if (in_array($user->getRoles()[0], User::ACCOUNT_ADMIN_ROLES)) {
            $encodedBy =  "admin:" .  $user->getEmail();
        }
        return $encodedBy;
    }

    private function resultEncode($video, $data = null)
    {
        $this->currentVideo = $video;
        if ($data instanceof JsonResponseMessage) {
            return $data;
        }
        $rep = json_decode($data, true);
        /**@var Video $video */
        $video = $this->currentVideo;
        $playlist = new PlaylistHelper($video);
        $video->setDuration($rep['duration'])
            ->setSize($rep['size'])
            ->setVideoQuality($rep['resolution']['width'] . 'x' . $rep['resolution']['height'])
            ->setRecommendedResolution($rep['bestResolution']['width'] . 'x' . $rep['bestResolution']['height'])
            ->setUpdatedAt(new \DateTimeImmutable('now'))
            ->setIsUploadComplete(true)
            ->setPlaylist($playlist->generatePlaylistLink());


        $video = $this->videoRepository->updateVideo($video);

        $this->addEncodedVideo($video, $rep);

        $obj = $this->videoRepository->findOneBy(['uuid' => $video->getUuid()]);

        return $this->dataFormalizer->extract($obj, 'one_video', false, "video(s) successfuly retrived!");
    }

    public function populateVideo()
    {
        $video = $this->videoRepository->findOneBy(['uuid' => $this->request->attributes->get('video_uuid')]);

        if ($video == null || $video->getEncodes()->count() > 0) {
            return (new JsonResponseMessage())->setCode(Response::HTTP_NOT_ACCEPTABLE)->setError('can\'t alow  ');
        }
        $data = $this->request->getContent();

        $this->resultEncode($video, $data);
        return $this->dataFormalizer->extract($video, 'one_video', false, "video(s) successfuly retrived!");
    }

    private function copyVideoTags(Video $video)
    {
        $tags = $video->getTags();
        if ($tags != null) {
            foreach ($tags  as $tag) {
                $this->currentVideo->addTag($tag);
                $this->videoRepository->updateVideo($this->currentVideo);
            }
        }
    }


    public function trashVideo(string $video_uuid)
    {
        $video = $this->videoRepository->findOneBy(['uuid' => $video_uuid, 'isInTrash' => false]);
        $this->videoVoter->vote($this->security->getToken(), $video, [VideoVoter::ACCOUNT_TRASH_VIDEO]);
        $video->setIsInTrash(true);
        $video->setFolder(null);
        $video->setUpdatedAt(new \DateTimeImmutable('now'));
        $this->em->flush();
        return (new JsonResponseMessage())->setCode(Response::HTTP_OK)->setError("Video moved to trash successfully!");
    }


    public function restoreVideo(string $video_uuid)
    {
        $video = $this->videoRepository->findOneBy(['uuid' => $video_uuid, 'isInTrash' => true]);
        $this->videoVoter->vote($this->security->getToken(), $video, [VideoVoter::ACCOUNT_RESTORE_VIDEO]);
        $video->setIsInTrash(false);
        $video->setUpdatedAt(new \DateTimeImmutable('now'));
        $this->em->flush();
        return (new JsonResponseMessage())->setCode(Response::HTTP_OK)->setError("Video restored successfully!");
    }
}
