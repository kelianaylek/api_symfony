<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class PostController
 * @package App\Controller
 * @Route("/api/posts")
 */
class PostController extends BaseController
{
    private EntityManagerInterface $entityManager;
    private PostRepository $postRepository;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        PostRepository $postRepository,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    )
    {
        $this->entityManager = $entityManager ;
        $this->postRepository = $postRepository ;
        $this->serializer = $serializer ;
        $this->validator = $validator;
    }

    /**
     * @return JsonResponse
     * @Route(name="api_posts_collection_get", methods={"GET"})
     *
     */
    public function collection(): JsonResponse
    {
        $posts = $this->postRepository->findAll();

        return $this->json($posts, 200, [], ["groups" => ["post", "user", "comment", "likers", "poll", "poll_posts", "poll_choices"]]);
    }

    /**
     * @Route("/{id}", name="api_posts_item_get", methods={"GET"})
     * @param Post $post
     * @return JsonResponse
     */
    public function item(Post $post): JsonResponse
    {
        return $this->json($post, 200, [], ["groups" => ["post", "user", "comment","likers", "poll", "poll_posts", "poll_choices"]]);
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
        $post->setAuthor($author);
        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return $this->json($post, 201, [], ["groups" => ["post", "user", "comment","likers"]]);
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

        return $this->json($post, 200, [], ["groups" => ["post", "user", "comment","likers"]]);
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
        if ($userLoggedInId !== $id) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas liker ce post avec ce compte.');
        }
        $post = $this->entityManager->getRepository(Post::class)->find($id);
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        $post->likeBy($user);
        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return $this->json($post, 201, [], ["groups" => ["post", "user", "comment","likers"]]);
    }

    /**
     * @Route("/removeLike/{id}/{userId}", name="api_posts_item_remove_like", methods={"PUT"})
     */
    public function removeLike($id, $userId): JsonResponse
    {
        $userLoggedIn = $this->getUser();
        $userLoggedInId = $userLoggedIn->getId();
        if ($userLoggedInId !== $id) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas liker ce post avec ce compte.');
        }
        $post = $this->entityManager->getRepository(Post::class)->find($id);
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        $post->dislikeBy($user);
        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return $this->json($post, 201, [], ["groups" => ["post", "user", "comment","likers"]]);
    }
}
