<?php

namespace App\Services\Tags;

use App\Entity\Tags;
use App\Entity\Video;
use App\Repository\TagsRepository;
use App\Repository\UserRepository;
use App\Repository\VideoRepository;

class TagsService
{
    /**
     * @var TagsRepository
     */
    private $tagsRepository;
    /**
     * @var VideoRepository
     */
    private $videoRepository;
    /**
     * @var UserRepository
     */
    private $userRepository;

    public function __construct(
        TagsRepository  $tagsRepository,
        VideoRepository $videoRepository,
        UserRepository  $userRepository
    ) {
        $this->tagsRepository = $tagsRepository;
        $this->videoRepository = $videoRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * @param Video $video
     * @param array $dataTags
     * @return array
     */
    public function createTagsFolderForUser(Video $video, $tags = [])
    {
        $tagsToAdd = [];
        $account = $video->getAccount();

        foreach ($tags as $tag) {
            $newTag = (new Tags())->setUuid('')
                ->setTagName($tag->name)
                ->setFolderOrder($tag->folderOrder)
                ->setIsFolder(true)
                ->setAccount($account);
            $this->tagsRepository->add($newTag);

            $tagsToAdd[] = $newTag;
        }
        $this->addOrCreateTagForVideo($video, $tagsToAdd);
    }

    /**
     * @param Video $video
     * @param array $dataTags
     * @return array
     */
    public function addOrCreateTagsForUser(Video $video, $dataTags = [])
    {
        $listOfUsableTag = [];
        $account = $video->getAccount();

        foreach ($dataTags as $tag) {
            $usableTag = $this->tagsRepository->findOneBy(['tagName' => $tag, 'isFolder' => false, 'account' => $account]);

            if ($usableTag === null) {
                $usableTag = (new Tags())->setUuid('')->setTagName($tag)->setIsFolder(0)->setAccount($account);
                $this->tagsRepository->add($usableTag);
            }
            $listOfUsableTag[] = $usableTag;
        }
        $this->addOrCreateTagForVideo($video, $listOfUsableTag);
    }

    /**
     * @param Video $video
     * @param array $listOfUsableTag
     */
    private function addOrCreateTagForVideo(Video $video, $listOfUsableTag = []): void
    {

        foreach ($listOfUsableTag as $currentUsableTags) {
            $filter['tag'] = $currentUsableTags->getTagName();
            $usabelVideoTag = $this->videoRepository->findTagsInVideo($video, $filter);
            if ($usabelVideoTag == null) {
                $video->addTag($currentUsableTags);
                $this->videoRepository->updateVideo($video);
            }
        }
    }

    /**
     * @param $video Video
     * @param array $arrtags ['string0','string1']
     */
    public function removeTagsFromVideo(Video $video, $arrtags = []): void
    {
        $removableTags = $this->tagsRepository->findBy(['tagName' => $arrtags]);

        foreach ($removableTags as $removableTag) {

            $tagToRemove = $removableTag;
            $video->removeTag($removableTag);
            $this->videoRepository->updateVideo($video);

            $this->removeUnusedtagsFromAccount($tagToRemove);
        }
    }

    /**
     * @param Tags $removableTags
     */
    private function removeUnusedtagsFromAccount(Tags $tagToRemove)
    {
        $tagVideos = $tagToRemove->getVideos();
        if (count($tagVideos) == 0) {
            $this->tagsRepository->remove($tagToRemove);
        }
    }
}
