<?php

namespace App\Controller;

use App\Entity\Group;
use App\Entity\Message;
use App\Entity\User;
use App\Repository\GroupRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Swagger\Annotations as SWG;

/**
 * @Route("/api/groups")
 */
class GroupController extends BaseController
{
    private EntityManagerInterface $entityManager;
    private GroupRepository $groupRepository;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        GroupRepository $groupRepository,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    )
    {
        $this->entityManager = $entityManager ;
        $this->groupRepository = $groupRepository ;
        $this->serializer = $serializer ;
        $this->validator = $validator;
    }

    /**
     * List all groups.
     *
     * This is the list of all groups.
     *
     * @Route(name="api_groups_collection_get", methods={"GET"})
     * @SWG\Response(
     *     response=Response::HTTP_OK,
     *     description="Returns all groups",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Group::class, groups={"group", "group_users", "group_messages"}))
     *     )
     * )
     * @SWG\Tag(name="groups")
     */
    public function collection(): JsonResponse
    {
        $groups = $this->groupRepository->findAll();

        return $this->json($groups, Response::HTTP_OK, [], ["groups" => ["group", "group_users", "group_messages"]]);
    }

    /**
     * Return the specified group.
     *
     * This call return a specific group.
     *
     * @Route("/{id}", name="api_groups_item_get", methods={"GET"})
     * @SWG\Response(
     *     response=Response::HTTP_OK,
     *     description="Returns a specific group",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Group::class, groups={"group", "group_users", "group_messages"}))
     *     )
     * )
     *     @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="Group not found"
     *     ),
     * @SWG\Tag(name="groups")
     */
    public function item(Group $group): JsonResponse
    {
        return $this->json($group, Response::HTTP_OK, [], ["groups" => ["group", "group_users", "group_messages"]]);
    }

    /**
     * Create a new group.
     * This call create a new group.
     * @Route(name="api_groups_collection_post", methods={"POST"})
     * @SWG\Parameter( name="Authorization", in="header", required=true, type="string", default="Bearer TOKEN", description="Authorization" )
     * @SWG\Response(
     *     response=Response::HTTP_CREATED,
     *     description="Returns a specific group",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Group::class, groups={"group", "group_users", "group_messages"}))
     *     )
     * )
     * @SWG\Tag(name="groups")
     */
    public function post(): JsonResponse
    {
        $group = new Group();
        $admin = $this->getUser();
        $group->addGroupAdmin($admin);
        $group->addUser($admin);
        $group->setName("Nouveau groupe");

        $this->entityManager->persist($group);
        $this->entityManager->flush();

        return $this->json($group, Response::HTTP_CREATED, [], ["groups" => ["group", "group_users", "group_messages"]]);
    }

    /**
     * Update a group.
     * This call edit the name of a group.
     * @Route("/{id}", name="api_groups_item_put", methods={"PUT"})
     * @SWG\Parameter( name="Authorization", in="header", required=true, type="string", default="Bearer TOKEN", description="Authorization" )
     * @SWG\Parameter(
     *          name="name data",
     *          in="body",
     *          type="json",
     *          description="Name data",
     *          required=true,
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(property="name", type="string"),
     *          )
     *     ),
     * @SWG\Response(
     *     response=Response::HTTP_OK,
     *     description="Edit the title of a group",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Group::class, groups={"group", "group_users", "group_messages"}))
     *     )
     * )
     * @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="This group does not exists"
     *     ),
     * @SWG\Response(
     *         response=Response::HTTP_FORBIDDEN,
     *         description="You are not admin of this group"
     *     ),
     * @SWG\Tag(name="groups")
     */
    public function put(Group $group, Request $request): JsonResponse
    {
        $this->serializer->deserialize(
            $request->getContent(),
            Group::class,
            "json",
            [AbstractNormalizer::OBJECT_TO_POPULATE => $group ]
        );

        $admins = $group->getGroupAdmins();
        foreach ($admins as $admin){
            if($admin === $this->getUser()){
                if ($response = $this->postValidation($group, $this->validator)) {
                    return $response;
                }
                $this->entityManager->flush();

                return $this->json($group, Response::HTTP_OK, [], ["groups" => ["group", "group_users", "group_messages"]]);
            }
        }
        throw $this->createAccessDeniedException("Vous n\'êtes pas admin de ce groupe.");
    }

    /**
     * Update a group.
     * This call add a user to a group.
     * @Route("/addUser/{groupId}/{userId}", name="api_groups_add_user_item_put", methods={"PUT"})
     * @SWG\Parameter( name="Authorization", in="header", required=true, type="string", default="Bearer TOKEN", description="Authorization" )
     * @SWG\Response(
     *     response=Response::HTTP_CREATED,
     *     description="Returns a specific group after updated",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Group::class, groups={"group", "group_users", "group_messages"}))
     *     )
     * )
     * @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="This user ou group does not exists"
     *     ),
     * @SWG\Tag(name="groups")
     */
    public function addUser($groupId, $userId): JsonResponse
    {
        $group = $this->entityManager->getRepository(Group::class)->find($groupId);
        if($group === null){
            return $this->json(null, Response::HTTP_NOT_FOUND);
        }
        $userAdded = $this->entityManager->getRepository(User::class)->find($userId);
        if($userAdded === null){
            return $this->json(null, Response::HTTP_NOT_FOUND);
        }
        $users = $group->getUsers();
        foreach ($users as $user){
            if($user === $this->getUser()){
                $group->addUser($userAdded);
                $this->entityManager->persist($group);
                $this->entityManager->flush();
                return $this->json($group, Response::HTTP_CREATED, [], ["groups" => ["group", "group_users", "group_messages"]]);
            }
        }
        throw $this->createAccessDeniedException('Vous ne faites pas partie de ce groupe.');

    }

    /**
     * Update a group.
     * This call remove a user from a group.
     * @Route("/removeUser/{groupId}/{userId}", name="api_groups_remove_user_item_put", methods={"PUT"})
     * @SWG\Parameter( name="Authorization", in="header", required=true, type="string", default="Bearer TOKEN", description="Authorization" )
     * @SWG\Response(
     *     response=Response::HTTP_OK,
     *     description="Remove a user from group",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Group::class, groups={"group", "group_users", "group_messages"}))
     *     )
     * )
     * @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="This user ou group does not exists"
     *     ),
     * @SWG\Response(
     *         response=Response::HTTP_FORBIDDEN,
     *         description="You are not an admin of this group"
     *     ),
     * @SWG\Tag(name="groups")
     */
    public function removeUser($groupId, $userId): JsonResponse
    {
        $group = $this->entityManager->getRepository(Group::class)->find($groupId);
        if($group === null){
            return $this->json(null, Response::HTTP_NOT_FOUND);
        }
        $userRemoved = $this->entityManager->getRepository(User::class)->find($userId);
        if($userRemoved === null){
            return $this->json(null, Response::HTTP_NOT_FOUND);
        }
        $admins = $group->getGroupAdmins();
        foreach ($admins as $admin){
            if($admin === $this->getUser()){
                $group->removeUser($userRemoved);
                $this->entityManager->persist($group);
                $this->entityManager->flush();
                return $this->json($group, Response::HTTP_OK, [], ["groups" => ["group", "group_users", "group_messages"]]);
            }
        }
        throw $this->createAccessDeniedException("Vous n\'êtes pas admin de ce groupe.");

    }

    /**
     * Update a group.
     * This call add an admin to a group.
     * @Route("/addAdmin/{groupId}/{userId}", name="api_groups_add_admin_item_put", methods={"PUT"})
     * @SWG\Parameter( name="Authorization", in="header", required=true, type="string", default="Bearer TOKEN", description="Authorization" )
     * @SWG\Response(
     *     response=Response::HTTP_OK,
     *     description="Add an admin to a group",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Group::class, groups={"group", "group_users", "group_messages"}))
     *     )
     * )
     * @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="This user ou group does not exists"
     *     ),
     * @SWG\Response(
     *         response=Response::HTTP_FORBIDDEN,
     *         description="You are not an admin of this group"
     *     ),
     * @SWG\Tag(name="groups")
     */
    public function addAdmin($groupId, $userId): JsonResponse
    {
        $group = $this->entityManager->getRepository(Group::class)->find($groupId);
        if($group === null){
            return $this->json(null, Response::HTTP_NOT_FOUND);
        }
        $adminAdded = $this->entityManager->getRepository(User::class)->find($userId);
        if($adminAdded === null){
            return $this->json(null, Response::HTTP_NOT_FOUND);
        }
        $admins = $group->getGroupAdmins();
        foreach ($admins as $admin){
            if($admin === $this->getUser()){
                $group->addGroupAdmin($adminAdded);
                $group->addUser($adminAdded);
                $this->entityManager->persist($group);
                $this->entityManager->flush();
                return $this->json($group, Response::HTTP_OK, [], ["groups" => ["group", "group_users", "group_messages"]]);
            }
        }
        throw $this->createAccessDeniedException("Vous n\'êtes pas admin de ce groupe.");
    }

    /**
     * Update a group.
     * This call remove an admin from a group.
     * @Route("/removeAdmin/{groupId}/{userId}", name="api_groups_remove_admin_item_put", methods={"PUT"})
     * @SWG\Parameter( name="Authorization", in="header", required=true, type="string", default="Bearer TOKEN", description="Authorization" )
     * @SWG\Response(
     *     response=Response::HTTP_OK,
     *     description="Remove an admin from group",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Group::class, groups={"group", "group_users", "group_messages"}))
     *     )
     * )
     * @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="This user ou group does not exists"
     *     ),
     * @SWG\Response(
     *         response=Response::HTTP_FORBIDDEN,
     *         description="You are not an admin of this group"
     *     ),
     * @SWG\Tag(name="groups")
     */
    public function removeAdmin($groupId, $userId): JsonResponse
    {
        $group = $this->entityManager->getRepository(Group::class)->find($groupId);
        if($group === null){
            return $this->json(null, Response::HTTP_NOT_FOUND);
        }
        $adminRemoved = $this->entityManager->getRepository(User::class)->find($userId);
        if($adminRemoved === null){
            return $this->json(null, Response::HTTP_NOT_FOUND);
        }
        $admins = $group->getGroupAdmins();
        foreach ($admins as $admin){
            if($admin === $this->getUser()){
                $group->removeGroupAdmin($adminRemoved);
                $this->entityManager->persist($group);
                $this->entityManager->flush();
                return $this->json($group, Response::HTTP_OK, [], ["groups" => ["group", "group_users", "group_messages"]]);
            }
        }
        throw $this->createAccessDeniedException("Vous n\'êtes pas admin de ce groupe.");

    }

    /**
     * Delete a specified group.
     *
     * This call delete a specific group.
     * @Route("/{id}", name="api_group_item_delete", methods={"DELETE"})
     * @SWG\Parameter( name="Authorization", in="header", required=true, type="string", default="Bearer TOKEN", description="Authorization" )
     * @SWG\Response(
     *     response=Response::HTTP_NO_CONTENT,
     *     description="group deleted successfully",
     * )
     * @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="group not found"
     *     ),
     * @SWG\Tag(name="groups")
     */
    public function delete(Group $group): JsonResponse
    {
        $group = $this->entityManager->getRepository(Group::class)->find($group);
        $admins = $group->getGroupAdmins();
        foreach ($admins as $admin){
            if($admin === $this->getUser()){
                $this->entityManager->remove($group);
                $this->entityManager->flush();

                return $this->json(null, Response::HTTP_NO_CONTENT);
            }
        }
        throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce groupe ce post avec ce compte (pas admin).');
    }

    /**
     * Update a group.
     * This call add a message to a group.
     * @Route("/addMessage/{groupId}", name="api_groups_add_message_item_put", methods={"PUT"})
     * @SWG\Parameter( name="Authorization", in="header", required=true, type="string", default="Bearer TOKEN", description="Authorization" )
     * @SWG\Parameter(
     *          name="message data",
     *          in="body",
     *          type="json",
     *          description="Message data",
     *          required=true,
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(property="content", type="string"),
     *          )
     *     ),
     * @SWG\Response(
     *     response=Response::HTTP_OK,
     *     description="Add a message to a group",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Group::class, groups={"group", "group_users", "group_messages"}))
     *     )
     * )
     * @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="This group does not exists"
     *     ),
     * @SWG\Response(
     *         response=Response::HTTP_FORBIDDEN,
     *         description="You are not a member of this group"
     *     ),
     * @SWG\Tag(name="groups")
     */
    public function addMessage($groupId, Request $request): JsonResponse
    {
        $group = $this->entityManager->getRepository(Group::class)->find($groupId);
        if($group === null){
            return $this->json(null, Response::HTTP_NOT_FOUND);
        }
        $users = $group->getUsers();
        foreach ($users as $user){
            if($user === $this->getUser()){
                $message = $this->serializer->deserialize($request->getContent(), Message::class, "json");
                if ($response = $this->postValidation($message, $this->validator)) {
                    return $response;
                }
                $message->setAuthor($this->getUser());
                $message->setInGroup($group);
                $this->entityManager->persist($message);
                $this->entityManager->persist($group);
                $this->entityManager->flush();

                return $this->json($group, Response::HTTP_OK, [], ["groups" => ["group", "group_users", "group_messages"]]);
            }
        }
        throw $this->createAccessDeniedException('Vous ne faites pas partie de ce groupe.');

    }

    /**
     */
    /**
     * Update a group.
     * This call remove a message from a group.
     * @Route("/removeMessage/{groupId}/{messageId}", name="api_groups_remove_message_item_put", methods={"PUT"})
     * @SWG\Parameter( name="Authorization", in="header", required=true, type="string", default="Bearer TOKEN", description="Authorization" )
     * @SWG\Response(
     *     response=Response::HTTP_OK,
     *     description="Remove a message from a group",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Group::class, groups={"group", "group_users", "group_messages"}))
     *     )
     * )
     * @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="This group or message does not exists"
     *     ),
     * @SWG\Response(
     *         response=Response::HTTP_FORBIDDEN,
     *         description="You are not the author of this message"
     *     ),
     * @SWG\Tag(name="groups")
     */
    public function removeMessage($groupId, $messageId): JsonResponse
    {
        $group = $this->entityManager->getRepository(Group::class)->find($groupId);
        if($group === null){
            return $this->json(null, Response::HTTP_NOT_FOUND);
        }
        $message = $this->entityManager->getRepository(Message::class)->find($messageId);
        if($message === null){
            return $this->json(null, Response::HTTP_NOT_FOUND);
        }
        if($message->getAuthor() != $this->getUser()){
            throw $this->createAccessDeniedException("Vous n\'êtes pas l'auteur de ce message");
        }
        $group->removeMessage($message);
        $this->entityManager->persist($message);
        $this->entityManager->persist($group);
        $this->entityManager->flush();

        return $this->json($group, Response::HTTP_OK, [], ["groups" => ["group", "group_users", "group_messages"]]);
    }

    /**
     * Update a group.
     * This call edit a message from a group.
     * @Route("/editMessage/{id}", name="api_groups_edit_message_item_put", methods={"PUT"})
     * @SWG\Parameter( name="Authorization", in="header", required=true, type="string", default="Bearer TOKEN", description="Authorization" )
     * @SWG\Parameter(
     *          name="message data",
     *          in="body",
     *          type="json",
     *          description="Message data",
     *          required=true,
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(property="content", type="string"),
     *          )
     *     ),
     * @SWG\Response(
     *     response=Response::HTTP_OK,
     *     description="Edit a message from a group",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Group::class, groups={"group", "group_users", "group_messages"}))
     *     )
     * )
     * @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="This message does not exists"
     *     ),
     * @SWG\Response(
     *         response=Response::HTTP_FORBIDDEN,
     *         description="You are not the author of this message"
     *     ),
     * @SWG\Tag(name="groups")
     */
    public function editMessage(Message $message, Request $request): JsonResponse
    {
        if($message->getAuthor() != $this->getUser()){
            throw $this->createAccessDeniedException("Vous n\'êtes pas l'auteur de ce message");
        }
        $this->serializer->deserialize(
            $request->getContent(),
            Message::class,
            "json",
            [AbstractNormalizer::OBJECT_TO_POPULATE => $message ]
        );
        if ($response = $this->postValidation($message, $this->validator)) {
            return $response;
        }
        $this->entityManager->persist($message);
        $this->entityManager->flush();

        return $this->json($message, Response::HTTP_OK, [], ["groups" => ["group", "group_users", "group_messages"]]);
    }

    /**
     * Leave a group.
     * A user leave a group.
     * @Route("/leave/{groupId}", name="api_groups_leave_put", methods={"PUT"})
     * @SWG\Parameter( name="Authorization", in="header", required=true, type="string", default="Bearer TOKEN", description="Authorization" )
     * @SWG\Response(
     *     response=Response::HTTP_OK,
     *     description="User leaves the group",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Group::class, groups={"group", "group_users", "group_messages"}))
     *     )
     * )
     * @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="This group does not exists"
     *     ),
     * @SWG\Response(
     *         response=Response::HTTP_FORBIDDEN,
     *         description="You are not into this group"
     *     ),
     * @SWG\Tag(name="groups")
     */
    public function leaveGroup($groupId): JsonResponse
    {
        $group = $this->entityManager->getRepository(Group::class)->find($groupId);
        if($group === null){
            return $this->json(null, Response::HTTP_NOT_FOUND);
        }
        $userConnected = $this->getUser();
        $isInGroup = false;

        $users = $group->getUsers();
        foreach ($users as $user){
            if($user === $userConnected){
                $isInGroup = true;
                $group->removeUser($user);
                $this->entityManager->persist($group);
                $this->entityManager->flush();
            }
        }
        if(!$isInGroup){
            throw $this->createAccessDeniedException("Vous n\'êtes pas membre de ce groupe.");
        }
        $admins = $group->getGroupAdmins();
        foreach ($admins as $admin){
            if($admin === $userConnected){
                $group->removeGroupAdmin($admin);
                $this->entityManager->persist($group);
                $this->entityManager->flush();
            }
        }

        return $this->json($group, Response::HTTP_OK, [], ["groups" => ["group", "group_users", "group_messages"]]);
    }
}
