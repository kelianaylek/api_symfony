<?php

namespace App\Controller;

use App\Repository\CommentRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use App\Entity\Post;
use App\Entity\User;
use App\Entity\Comment;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Class CommentController
 * @package App\Controller
 * @Route("/api/comments")
 */
class CommentController
{
    /**
     * @param CommentRepository $commentRepository
     * @param SerializerInterface $serializer
     * @return JsonResponse
     * @Route(name="api_comments_collection_get", methods={"GET"})
     */
    public function collection(CommentRepository $commentRepository, SerializerInterface $serializer): JsonResponse
    {
        return new JsonResponse(
            $serializer->serialize($commentRepository->findAll(), "json"),
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    /**
     * @Route("/{id}", name="api_comments_item_get", methods={"GET"})
     * @param Comment $comment
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    public function item(Comment $comment, SerializerInterface  $serializer): JsonResponse
    {
        return new JsonResponse(
            $serializer->serialize($comment, "json", ),
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    /**
     * @Route("/{postId}/{userId}", name="api_comments_post_comment", methods={"POST"})
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $entityManager
     * @param UrlGeneratorInterface $urlGenerator
     * @param int $userId
     * @param int $postId
     * @return JsonResponse
     */
    public function comment(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        int $userId,
        int $postId
    ): JsonResponse {
        $comment = $serializer->deserialize($request->getContent(), Comment::class, "json");
        $author = $entityManager->getRepository(User::class)->find($userId);
        $comment->setAuthor($author);
        $post = $entityManager->getRepository(Post::class)->find($postId);
        $comment->setPost($post);
        $entityManager->persist($comment);
        $entityManager->flush();

        return new JsonResponse(
            $serializer->serialize($comment, "json"),
            JsonResponse::HTTP_CREATED,
            ["Location" => $urlGenerator->generate("api_comments_item_get", ["id" => $comment->getId()])],
            true
        );
    }

    /**
     * @Route("/{id}", name="api_comments_item_put", methods={"PUT"})
     * @param Comment $comment
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */
    public function put(
        Comment $comment,
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $serializer->deserialize(
            $request->getContent(),
            Comment::class,
            "json",
            [AbstractNormalizer::OBJECT_TO_POPULATE => $comment ]
        );

        $entityManager->flush();

        return new JsonResponse(
            null,
            JsonResponse::HTTP_NO_CONTENT,
        );
    }


    /**
     * @Route("/{id}", name="api_comments_item_delete", methods={"DELETE"})
     * @param Comment $comment
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */
    public function delete(
        Comment $comment,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $entityManager->remove($comment);
        $entityManager->flush();

        return new JsonResponse(
            null,
            JsonResponse::HTTP_NO_CONTENT,
        );
    }
}

