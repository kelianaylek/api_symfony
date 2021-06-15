<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\GroupRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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
     * @return JsonResponse
     * @Route(name="api_groups_collection_get", methods={"GET"})
     *
     */
    public function collection(): JsonResponse
    {
        $groups = $this->groupRepository->findAll();

        return $this->json($groups, Response::HTTP_OK, [], ["groups" => []]);
    }

    /**
     * @Route("/{id}", name="api_posts_item_get", methods={"GET"})
     * @param Post $post
     * @return JsonResponse
     */
    public function item(Post $post): JsonResponse
    {
        return $this->json($post, Response::HTTP_OK, [], ["groups" => ["post", "user", "comment","likers", "poll", "poll_posts", "poll_choices"]]);
    }

    /**
     * @Route("/{userId}", name="api_posts_collection_post", methods={"POST"})
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function post(Request $request, int $userId): JsonResponse
    {
        $post = $this->serializer->deserialize($request->getContent(), Post::class, "json");
        if ($response = $this->postValidation($post, $this->validator)) {
            return $response;
        }
        $author = $this->entityManager->getRepository(User::class)->find($userId);
        if($author === null){
            return $this->json(null, Response::HTTP_NOT_FOUND);
        }
        $post->setAuthor($author);
        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return $this->json($post, Response::HTTP_CREATED, [], ["groups" => ["post", "user", "comment","likers"]]);
    }

    /**
     * @Route("/{id}", name="api_posts_item_put", methods={"PUT"})
     * @param Post $post
     * @param Request $request
     * @return JsonResponse
     */
    public function put(Post $post, Request $request): JsonResponse
    {
        $this->serializer->deserialize(
            $request->getContent(),
            Post::class,
            "json",
            [AbstractNormalizer::OBJECT_TO_POPULATE => $post ]
        );
        if ($response = $this->postValidation($post, $this->validator)) {
            return $response;
        }
        $this->entityManager->flush();

        return $this->json($post, Response::HTTP_CREATED, [], ["groups" => ["post", "user", "comment","likers"]]);
    }

    /**
     * @Route("/{id}", name="api_posts_item_delete", methods={"DELETE"})
     * @param Post $post
     * @return JsonResponse
     */
    public function delete(Post $post): JsonResponse
    {
        $this->entityManager->remove($post);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/addLike/{id}/{userId}", name="api_posts_item_add_like", methods={"PUT"})
     */
    public function addLike($id, $userId): JsonResponse
    {
        $userLoggedIn = $this->getUser();
        $userLoggedInId = $userLoggedIn->getId();
        if ($userLoggedInId != $userId) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas liker ce post avec ce compte.');
        }
        $post = $this->entityManager->getRepository(Post::class)->find($id);
        if($post === null){
            return $this->json(null, Response::HTTP_NOT_FOUND);
        }
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        $post->likeBy($user);
        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return $this->json($post, Response::HTTP_CREATED, [], ["groups" => ["post", "user", "comment","likers"]]);
    }

    /**
     * @Route("/removeLike/{id}/{userId}", name="api_posts_item_remove_like", methods={"PUT"})
     */
    public function removeLike($id, $userId): JsonResponse
    {
        $userLoggedIn = $this->getUser();
        $userLoggedInId = $userLoggedIn->getId();
        if ($userLoggedInId != $userId) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas liker ce post avec ce compte.');
        }
        $post = $this->entityManager->getRepository(Post::class)->find($id);
        if($post === null){
            return $this->json(null, Response::HTTP_NOT_FOUND);
        }
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        $post->dislikeBy($user);
        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return $this->json($post, Response::HTTP_CREATED, [], ["groups" => ["post", "user", "comment","likers"]]);
    }
}
