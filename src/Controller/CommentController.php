<?php

namespace App\Controller;

use App\Repository\CommentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
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
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Swagger\Annotations as SWG;

/**
 * @Route("/api/comments")
 */
class CommentController extends BaseController
{
    private EntityManagerInterface $entityManager;
    private CommentRepository $commentRepository;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        CommentRepository $commentRepository,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    )
    {
        $this->entityManager = $entityManager ;
        $this->commentRepository = $commentRepository ;
        $this->serializer = $serializer ;
        $this->validator = $validator;
    }

    /**
     * List all comments.
     *
     * This is the list of all comments.
     *
     * @Route(name="api_comments_collection_get", methods={"GET"})
     * @SWG\Response(
     *     response=Response::HTTP_OK,
     *     description="Returns all comments",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Comment::class, groups={"comment", "user"}))
     *     )
     * )
     * @SWG\Tag(name="comments")
     */
    public function collection(): JsonResponse
    {
        $comments = $this->commentRepository->findAll();
        return $this->json($comments, Response::HTTP_OK, [], ["groups" => ["comment", "user"] ]);
    }

    /**
     * Return the specified comment.
     *
     * This call return a specific comment.
     *
     * @Route("/{id}", name="api_comments_item_get", methods={"GET"})
     * @SWG\Response(
     *     response=Response::HTTP_OK,
     *     description="Returns a specific comment",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Comment::class, groups={"comment", "user"}))
     *     )
     * )
     *     @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="Comment not found"
     *     ),
     * @SWG\Tag(name="comments")
     */
    public function item(Comment $comment): JsonResponse
    {
        return $this->json($comment, Response::HTTP_OK, [], ["groups" => ["comment", "user"]]);
    }

    /**
     * Create a new comment.
     * This call create a new comment attached to a post.
     * @Route("/new/{postId}", name="api_comments_post_comment", methods={"POST"})
     * @SWG\Parameter(name="Authorization", in="header", required=true, type="string", default="Bearer TOKEN", description="Authorization" )
     * @SWG\Parameter(
     *          name="Comment data",
     *          in="body",
     *          type="json",
     *          description="Comment data",
     *          required=true,
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(property="message", type="string"),
     *              @SWG\Property(property="image", type="string"),
     *          )
     *     ),
     * @SWG\Response(
     *     response=Response::HTTP_CREATED,
     *     description="Returns the created comment",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Comment::class, groups={"comment", "user"}))
     *     )
     * )
     * @SWG\Tag(name="comments")
     */
    public function comment(Request $request, int $postId): JsonResponse
    {
        $comment = $this->serializer->deserialize($request->getContent(), Comment::class, "json");
        if ($response = $this->postValidation($comment, $this->validator)) {
            return $response;
        }
        $author = $this->getUser();
        if($author === null){
            return $this->json(null, Response::HTTP_NOT_FOUND);
        }
        $comment->setAuthor($author);
        $post = $this->entityManager->getRepository(Post::class)->find($postId);
        if($post === null){
            return $this->json(null, Response::HTTP_NOT_FOUND);
        }
        $comment->setPost($post);
        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        return $this->json($comment, Response::HTTP_CREATED, [], ["groups" => ["comment", "user"]]);
    }

    /**
     * Update a comment.
     * This call update a comment.
     * @Route("/{id}", name="api_comments_item_put", methods={"PUT"})
     * @SWG\Parameter(
     *          name="comment data",
     *          in="body",
     *          type="json",
     *          description="comment data",
     *          required=true,
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(property="message", type="string"),
     *              @SWG\Property(property="image", type="string"),
     *          )
     *     ),
     * @SWG\Response(
     *     response=Response::HTTP_OK,
     *     description="Returns a specific comment after updated",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Comment::class, groups={"comment", "user"}))
     *     )
     * )
     * @SWG\Response(
     *         response=Response::HTTP_BAD_REQUEST,
     *         description="A problem occured with a field"
     *     ),
     * @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="This comment does not exists"
     *     ),
     * @SWG\Tag(name="comments")
     */
    public function put(Request $request, Comment $comment): JsonResponse
    {
        $comment = $this->serializer->deserialize(
            $request->getContent(),
            Comment::class,
            "json",
            [AbstractNormalizer::OBJECT_TO_POPULATE => $comment ]
        );
        if ($response = $this->postValidation($comment, $this->validator)) {
            return $response;
        }
        $this->entityManager->flush();

        return $this->json($comment, Response::HTTP_OK, [], ["groups" => ["comment", "user"]]);
    }

    /**
     * Delete a specified comment.
     *
     * This call delete a specific comment.
     * @Route("/{id}", name="api_comments_item_delete", methods={"DELETE"})
     * @SWG\Response(
     *     response=Response::HTTP_NO_CONTENT,
     *     description="Comment deleted successfully",
     * )
     * @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="Comment not found"
     *     ),
     * @SWG\Tag(name="comments")
     */
    public function delete(Comment $comment): JsonResponse
    {
        $this->entityManager->remove($comment);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
