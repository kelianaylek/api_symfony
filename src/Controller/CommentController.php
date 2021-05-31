<?php

namespace App\Controller;

use App\Repository\CommentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
class CommentController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private CommentRepository $commentRepository;
    private SerializerInterface $serializer;

    public function __construct(
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        CommentRepository $commentRepository,
        SerializerInterface $serializer
    ) {
        $this->entityManager = $entityManager ;
        $this->commentRepository = $commentRepository ;
        $this->serializer = $serializer ;
    }

    /**
     * @return JsonResponse
     * @Route(name="api_comments_collection_get", methods={"GET"})
     */
    public function collection(): JsonResponse
    {
        $comments = $this->commentRepository->findAll();
        return $this->json($comments);
    }

    /**
     * @Route("/{id}", name="api_comments_item_get", methods={"GET"})
     * @param Comment $comment
     * @return JsonResponse
     */
    public function item(Comment $comment): JsonResponse
    {
        return $this->json($comment);
    }

    /**
     * @Route("/{postId}/{userId}", name="api_comments_post_comment", methods={"POST"})
     * @param Request $request
     * @param int $userId
     * @param int $postId
     * @return JsonResponse
     */
    public function comment(Request $request, int $userId, int $postId): JsonResponse
    {
        $comment = $this->serializer->deserialize($request->getContent(), Comment::class, "json");

        $author = $this->entityManager->getRepository(User::class)->find($userId);
        $comment->setAuthor($author);
        $post = $this->entityManager->getRepository(Post::class)->find($postId);
        $comment->setPost($post);
        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        return $this->json($comment, 201);
    }

    /**
     * @Route("/{id}", name="api_comments_item_put", methods={"PUT"})
     * @param Request $request
     * @param Comment $comment
     * @return JsonResponse
     */
    public function put(Request $request, Comment $comment): JsonResponse
    {
        $this->serializer->deserialize(
            $request->getContent(),
            Comment::class,
            "json",
            [AbstractNormalizer::OBJECT_TO_POPULATE => $comment ]
        );
        $this->entityManager->flush();

        return $this->json($comment, 200);
    }

    /**
     * @Route("/{id}", name="api_comments_item_delete", methods={"DELETE"})
     * @param Comment $comment
     * @return JsonResponse
     */
    public function delete(Comment $comment): JsonResponse
    {
        $this->entityManager->remove($comment);
        $this->entityManager->flush();

        return $this->json(204);
    }
}
