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

/**
 * Class GroupController
 * @package App\Controller
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
     * @Route(name="api_groups_collection_get", methods={"GET"})
     */
    public function collection(): JsonResponse
    {
        $groups = $this->groupRepository->findAll();

        return $this->json($groups, Response::HTTP_OK, [], ["groups" => ["group", "group_users", "group_messages"]]);
    }

    /**
     * @Route("/{id}", name="api_groups_item_get", methods={"GET"})
     */
    public function item(Group $group): JsonResponse
    {
        return $this->json($group, Response::HTTP_OK, [], ["groups" => ["group", "group_users", "group_messages"]]);
    }

    /**
     * @Route(name="api_groups_collection_post", methods={"POST"})
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
     * @Route("/addUser/{groupId}/{userId}", name="api_groups_add_user_item_put", methods={"PUT"})
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
     * @Route("/removeUser/{groupId}/{userId}", name="api_groups_remove_user_item_put", methods={"PUT"})
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
                return $this->json($group, Response::HTTP_NO_CONTENT, [], ["groups" => ["group", "group_users", "group_messages"]]);
            }
        }
        throw $this->createAccessDeniedException("Vous n\'êtes pas admin de ce groupe.");

    }

    /**
     * @Route("/addAdmin/{groupId}/{userId}", name="api_groups_add_admin_item_put", methods={"PUT"})
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
                return $this->json($group, Response::HTTP_CREATED, [], ["groups" => ["group", "group_users", "group_messages"]]);
            }
        }
        throw $this->createAccessDeniedException("Vous n\'êtes pas admin de ce groupe.");

    }

    /**
     * @Route("/removeAdmin/{groupId}/{userId}", name="api_groups_remove_admin_item_put", methods={"PUT"})
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
                return $this->json($group, Response::HTTP_CREATED, [], ["groups" => ["group", "group_users", "group_messages"]]);
            }
        }
        throw $this->createAccessDeniedException("Vous n\'êtes pas admin de ce groupe.");

    }

    /**
     * @Route("/{id}", name="api_group_item_delete", methods={"DELETE"})
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
     * @Route("/message/{groupId}", name="api_groups_add_message_item_put", methods={"PUT"})
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

                return $this->json($group, Response::HTTP_CREATED, [], ["groups" => ["group", "group_users", "group_messages"]]);
            }
        }
        throw $this->createAccessDeniedException('Vous ne faites pas partie de ce groupe.');

    }
}
g