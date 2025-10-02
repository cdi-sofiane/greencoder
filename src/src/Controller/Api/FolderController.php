<?php

namespace App\Controller\Api;

use App\Entity\Folder;
use App\Services\Folder\FolderManager;
use App\Services\Video\VideoManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\Dto\DtoShareFolder;


/**
 * @Route("/folders",name="folders_")
 */
class FolderController extends AbstractController
{
  private $folderManager;
  public function __construct(FolderManager $folderManager)
  {
    $this->folderManager = $folderManager;
  }
  /**
   * @Route ("",name="create_folder",methods={"POST"})
   * @OA\Post (
   *  tags={"Folders"},
   *  summary="create folder in account",
   *  description="create folder in account depend on user rights, name must be unique in same lvl same parent folder ",
   * @OA\RequestBody(
   *      @OA\MediaType(mediaType="application/json",
   *
   *          @OA\Schema(
   *              required={"account_uuid","name"},
   *              @OA\Property (property="account_uuid",type="string"),
   *              @OA\Property (property="folder_uuid",type="string",description="add to create sub-folder "),
   *              @OA\Property (property="name",type="string"),
   *
   *      )),
   *     ),
   * )
   * @OA\Response(response=201,description="created",
   *       @Model(type=Folder::class,groups={"folder:read"})
   * ))
   * @IsGranted("ROLE_USER",message="This account is forbidden!"),
   * @Security(name="Bearer")
   */
  public function createFolder(Request $request, FolderManager $folderManager)
  {

    $data = $folderManager->createAccountFolder();
    return new JsonResponse($data->displayData(), $data->displayHeader());
  }
  /**
   * @Route("/{folder_uuid}",name="folder_one",methods={"GET"})
   * @OA\Get (
   *  tags={"Folders"},
   *  summary="find a folder by uuid",
   *  description="find one folder ",
   * )
   *  @IsGranted("ROLE_USER",message="This account is forbidden!"),
   * @Security(name="Bearer")
   */
  public function findFolder(Request $request, FolderManager $folderManager)
  {
    $data = $folderManager->findOneFolder();
    return new JsonResponse($data->displayData(), $data->displayHeader());
  }
  /**
   * @Route("/{folder_uuid}/share",name="user_invitation",methods={"POST"})
   * @OA\Post (
   *  tags={"Folders"},
   *  summary="Invitation as folder editor or reader",
   *  description="share folder content for any new or existing user , as depending on role user ca see and manage  children's folder  ",
   * @OA\RequestBody(
   *         @OA\JsonContent(ref=@Model(type=DtoShareFolder::class,groups={"folder:share"}))
   *     ),
   *     ),
   * )
   * @IsGranted("ROLE_USER",message="This account is forbidden!"),
   * @Security(name="Bearer")
   */
  public function folderInvitation(Request $request, FolderManager $folderManager)
  {

    $data = $folderManager->inviteUserToFolder();
    return new JsonResponse($data->displayData(), $data->displayHeader());
  }

  /**
   * @Route("",name="folder_all",methods={"GET"})
   * @OA\Get (
   *  tags={"Folders"},
   *  summary="find a folders account",
   *  description="find account folders",
   *
   *      @OA\Parameter (name="account_uuid",in="query",description="account uuid",@OA\Schema(type="string"),required=true),
   *      @OA\Parameter (name="folder_uuid",in="query",description="folder uuid",@OA\Schema(type="string")),
   * )
   * @OA\Response(response=201,description="created",
   *       @Model(type=Folder::class,groups={"folder:read"})
   * ))
   *  @IsGranted("ROLE_USER",message="This account is forbidden!"),
   * @Security(name="Bearer")
   */
  public function findAccountFolders(Request $request, FolderManager $folderManager)
  {

    $data = $folderManager->findAccountRootVideoTeck();
    return new JsonResponse($data->displayData(), $data->displayHeader());
  }
  /**
   * @Route("/{folder_uuid}",name="folder_edit",methods={"PATCH"})
   * @OA\Patch (
   *  tags={"Folders"},
   *  summary="edit folder",
   *  description="change folder name ",
   * @OA\RequestBody(
   *      @OA\MediaType(mediaType="application/json",
   *          @OA\Schema(
   *              @OA\Property (property="name",type="string"),
   *      )),
   *     ),
   * )
   * @IsGranted("ROLE_USER",message="This account is forbidden!"),
   * @Security(name="Bearer")
   */
  public function editFolder(Request $request, FolderManager $folderManager)
  {

    $data = $folderManager->editFolders();
    return new JsonResponse($data->displayData(), $data->displayHeader());
  }

  /**
   * @Route("/{folder_uuid}/members",name="folder_members",methods={"GET"})
   * @OA\Get(
   * tags={"Folders"},
   *  summary="list folder members",
   *  description="display folder members)",
   * )
   *  @OA\Response(response=201,description="created",
   *      @Model(type=Folder::class,groups={"folder:read"})
   * ))
   * @IsGranted("ROLE_USER",message="This account is forbidden!"),
   * @Security(name="Bearer")
   */
  public function folderMembers(Request $request, FolderManager $folderManager)
  {
    $data = $folderManager->findMembersInFolder();
    return new JsonResponse($data->displayData(), $data->displayHeader());
  }

  /**
   * @Route("/{folder_uuid}/members",name="folder_remove_members",methods={"DELETE"})
   * @OA\Delete(
   * tags={"Folders"},
   *  summary="remove member",
   *  description="remove  user from folder)",
   *  @OA\RequestBody(
   *      @OA\MediaType(mediaType="application/json",
   *          @OA\Schema(
   *              @OA\Property (property="user_uuid",type="string"),
   *      )),
   *     ),
   * )
   * )
   *  @OA\Response(response=201,description="created",
   *       @Model(type=Folder::class,groups={"folder:read"})
   * ))
   * @IsGranted("ROLE_USER",message="This account is forbidden!"),
   * @Security(name="Bearer")
   */
  public function removeFolderMember(Request $request, FolderManager $folderManager)
  {
    $data = $folderManager->removeMemberFromFolder();
    return new JsonResponse($data->displayData(), $data->displayHeader());
  }

  /**
   * @Route("/{folder_uuid}/members",name="folder_sub_members",methods={"PATCH"})
   * @OA\Patch(
   * tags={"Folders"},
   *  summary="Change member role in folder",
   *  description="Modify user role in folder editor / reader",
   *  * @OA\RequestBody(
   *         @OA\JsonContent(ref=@Model(type=DtoShareFolder::class,groups={"folder:edit:role"}))
   *     ),
   *     ),
   * )
   *  @OA\Response(response=201,description="created",
   *       @Model(type=Folder::class,groups={"folder:read"})
   * ))
   * @IsGranted("ROLE_USER",message="This account is forbidden!"),
   * @Security(name="Bearer")
   */
  public function changeUserRoleFolder(Request $request, FolderManager $folderManager)
  {

    $data = $folderManager->switchUserFolderRole();
    return new JsonResponse($data->displayData(), $data->displayHeader());
  }


  /**
   * @Route("/{folder_uuid}/trash",name="trash_folder",methods={"PATCH"})
   * @OA\Patch(
   *  tags={"Folders"},
   *  summary="trash folder",
   *  description="delete folder temporarie",
   *     @OA\Parameter (name="folder_uuid",in="path",description="folder identifier"),
   * )
   * @OA\Response(
   *     response=200,
   *     description="Folder"
   * )
   * @IsGranted("ROLE_USER",message="This account is forbidden!"),
   * @Security(name="Bearer")
   */
  public function trash($folder_uuid)
  {
    $data = $this->folderManager->trashFolder($folder_uuid);
    return new JsonResponse($data->displayData(), $data->displayHeader());
  }

  /**
   * @Route("/{folder_uuid}/restore",name="restore_folder",methods={"PATCH"})
   * @OA\Patch(
   *  tags={"Folders"},
   *  summary="restore folder",
   *  description="restore folder",
   *     @OA\Parameter (name="folder_uuid",in="path",description="folder identifier"),
   * )
   * @OA\Response(
   *     response=200,
   *     description="Folder"
   * )
   * @IsGranted("ROLE_USER",message="This account is forbidden!"),
   * @Security(name="Bearer")
   */
  public function restore($folder_uuid)
  {
    $data = $this->folderManager->restoreFolder($folder_uuid);
    return new JsonResponse($data->displayData(), $data->displayHeader());
  }


  /**
   * @Route("/{folder_uuid}",name="delete_folder",methods={"DELETE"})
   * @OA\Delete(
   *  tags={"Folders"},
   *  summary="delete folder",
   *  description="delete folder",
   *     @OA\Parameter (name="folder_uuid",in="path",description="folder identifier"),
   * )
   * @OA\Response(
   *     response=200,
   *     description="Folder"
   * )
   * @IsGranted("ROLE_USER",message="This account is forbidden!"),
   * @Security(name="Bearer")
   */
  public function deleteFolder($folder_uuid)
  {
    $data = $this->folderManager->deleteFolder($folder_uuid);
    return new JsonResponse($data->displayData(), $data->displayHeader());
  }


  /**
   * @Route("/{folder_uuid}/download",name="download_folder",methods={"Get"})
   * @OA\Get(
   *  tags={"Folders"},
   *  summary="download folder",
   *  description="download folder",
   *     @OA\Parameter (name="folder_uuid",in="path",description="folder identifier"),
   * )
   * @OA\Response(
   *     response=200,
   *     description="Folder"
   * )
   * @IsGranted("ROLE_USER",message="This account is forbidden!"),
   * @Security(name="Bearer")
   */
  public function downloadFolder(string $folder_uuid)
  {
    return $this->folderManager->downloadFolder($folder_uuid, $this->getUser());
  }

  /**
   * @Route("/{folder_uuid}/videos",name="videos_folder",methods={"Get"})
   * @OA\Get(
   *  tags={"Folders"},
   *  summary="folder's videos",
   *  description="folder's videos",
   *     @OA\Parameter (name="folder_uuid",in="path",description="folder identifier"),
   * )
   * @OA\Response(
   *     response=200,
   *     description="Folder"
   * )
   * @IsGranted("ROLE_USER",message="This account is forbidden!"),
   * @Security(name="Bearer")
   */
  public function getFolderVideos(string $folder_uuid)
  {
    $data = $this->folderManager->getAllFolderVideos($folder_uuid);
    return new JsonResponse($data->displayData(), $data->displayHeader());
  }
}
