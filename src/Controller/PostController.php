<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class PostController
 * @package App\Controller
 * @Route("/api/posts")
 */
class PostController
{
    /**
     * @param PostRepository $postRepository
     * @param SerializerInterface $serializer
     * @return JsonResponse
     * @Route(name="api_posts_collection_get", methods={"GET"})
     */
    public function collection(PostRepository $postRepository, SerializerInterface $serializer): JsonResponse
    {
        return new JsonResponse(
            $serializer->serialize($postRepository->findAll(), "json", ["groups" => "get"]),
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    /**
     * @Route("/{id}", name="api_posts_item_get", methods={"GET"})
     * @param Post $post
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    public function item(Post $post, SerializerInterface  $serializer): JsonResponse
    {
        return new JsonResponse(
            $serializer->serialize($post, "json", ["groups" => "get"]),
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    /**
     * @Route("/{userId}", name="api_posts_collection_post", methods={"POST"})
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $entityManager
     * @param UrlGeneratorInterface $urlGenerator
     * @param int $userId
     * @return JsonResponse
     */
    public function post(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        int $userId
    ): JsonResponse {
        $post = $serializer->deserialize($request->getContent(), Post::class, "json");

        $author = $entityManager->getRepository(User::class)->find($userId);
        $post->setAuthor($author);

        $entityManager->persist($post);
        $entityManager->flush();

        return new JsonResponse(
            $serializer->serialize($post, "json"),
            JsonResponse::HTTP_CREATED,
            ["Location" => $urlGenerator->generate("api_posts_item_get", ["id" => $post->getId()])],
            true
        );
    }

    /**
     * @Route("/{id}", name="api_posts_item_put", methods={"PUT"})
     * @param Post $post
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */
    public function put(
        Post $post,
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $serializer->deserialize(
            $request->getContent(),
            Post::class,
            "json",
            [AbstractNormalizer::OBJECT_TO_POPULATE => $post ]
        );

        $entityManager->flush();

        return new JsonResponse(
            null,
            JsonResponse::HTTP_NO_CONTENT,
        );
    }

    /**
     * @Route("/{id}", name="api_posts_item_delete", methods={"DELETE"})
     * @param Post $post
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */
    public function delete(
        Post $post,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $entityManager->remove($post);
        $entityManager->flush();

        return new JsonResponse(
            null,
            JsonResponse::HTTP_NO_CONTENT,
        );
    }
}

