<?php

namespace App\Controller;

use App\Entity\Event;
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
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Swagger\Annotations as SWG;

/**
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
     * List all posts.
     *
     * This is the list of all posts.
     *
     * @Route(name="api_posts_collection_get", methods={"GET"})
     * @SWG\Response(
     *     response=Response::HTTP_OK,
     *     description="Returns all posts",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Post::class, groups={"post", "user", "comment", "likers", "poll", "poll_posts", "poll_choices", "post_event", "event"}))
     *     )
     * )
     * @SWG\Tag(name="posts")
     */
    public function collection(): JsonResponse
    {
        $posts = $this->postRepository->findAll();

        return $this->json($posts, Response::HTTP_OK, [], ["groups" => ["post", "user", "comment", "likers", "poll", "poll_posts", "poll_choices", "post_event", "event", "post_author"]]);
    }

    /**
     * Return the specified post.
     *
     * This call return a specific post.
     *
     * @Route("/{id}", name="api_posts_item_get", methods={"GET"})
     * @SWG\Response(
     *     response=Response::HTTP_OK,
     *     description="Returns a specific post",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Post::class, groups={"post", "user", "comment", "likers", "poll", "poll_posts", "poll_choices", "post_event", "event"}))
     *     )
     * )
     *     @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="Post not found"
     *     ),
     * @SWG\Tag(name="posts")
     */
    public function item(Post $post): JsonResponse
    {
        return $this->json($post, Response::HTTP_OK, [], ["groups" => ["post", "user", "comment","likers", "poll", "poll_posts", "poll_choices", "post_event", "event", "post_author"]]);
    }

    /**
     * Create a new post.
     * This call create a new post.
     * @Route(name="api_posts_collection_post", methods={"POST"})
     * @SWG\Parameter( name="Authorization", in="header", required=true, type="string", default="Bearer TOKEN", description="Authorization" )
     * @SWG\Parameter(
     *          name="post data",
     *          in="body",
     *          type="json",
     *          description="Post data",
     *          required=true,
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(property="content", type="string"),
     *              @SWG\Property(property="image", type="string"),
     *          )
     *     ),
     * @SWG\Response(
     *     response=Response::HTTP_CREATED,
     *     description="Returns a specific user",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Post::class, groups={"user", "posts", "post"}))
     *     )
     * )
     * @SWG\Response(
     *         response=Response::HTTP_BAD_REQUEST,
     *         description="A problem occured with a field"
     *     ),
     * @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="This user does not exists"
     *     ),
     * @SWG\Tag(name="posts")
     */
    public function post(Request $request): JsonResponse
    {
        $post = $this->serializer->deserialize($request->getContent(), Post::class, "json");
        if ($response = $this->postValidation($post, $this->validator)) {
            return $response;
        }
        $user = $this->getUser();
        if($user === null){
            return $this->json(null, Response::HTTP_NOT_FOUND);
        }
        $post->setAuthor($user);
        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return $this->json($post, Response::HTTP_CREATED, [], ["groups" => ["post", "user", "comment","likers"]]);
    }

    /**
     * Update a post.
     * This call update a post.
     * @Route("/{id}", name="api_posts_item_put", methods={"PUT"})
     * @SWG\Parameter(
     *          name="post data",
     *          in="body",
     *          type="json",
     *          description="Post data",
     *          required=true,
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(property="content", type="string"),
     *              @SWG\Property(property="image", type="string"),
     *          )
     *     ),
     * @SWG\Response(
     *     response=Response::HTTP_CREATED,
     *     description="Returns a specific post after updated",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Post::class, groups={"user", "posts", "post"}))
     *     )
     * )
     * @SWG\Response(
     *         response=Response::HTTP_BAD_REQUEST,
     *         description="A problem occured with a field"
     *     ),
     * @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="This post does not exists"
     *     ),
     * @SWG\Tag(name="posts")
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
     * Delete a specified post.
     *
     * This call delete a specific post.
     * @Route("/{id}", name="api_posts_item_delete", methods={"DELETE"})
     * @SWG\Response(
     *     response=Response::HTTP_NO_CONTENT,
     *     description="Post deleted successfully",
     * )
     * @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="Post not found"
     *     ),
     * @SWG\Tag(name="posts")
     */
    public function delete(Post $post): JsonResponse
    {
        $this->entityManager->remove($post);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Add a like to post.
     * This call update a post by adding a like.
     * @Route("/addLike/{id}", name="api_posts_item_add_like", methods={"PUT"})
     * @SWG\Parameter( name="Authorization", in="header", required=true, type="string", default="Bearer TOKEN", description="Authorization" )
     * @SWG\Response(
     *     response=Response::HTTP_CREATED,
     *     description="Returns a specific post after updated with a like",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Post::class, groups={"post", "user", "comment","likers"}))
     *     )
     * )
     * @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="This post does not exists"
     *     ),
     * @SWG\Tag(name="posts")
     */
    public function addLike($id): JsonResponse
    {
        $userLoggedIn = $this->getUser();
        $post = $this->entityManager->getRepository(Post::class)->find($id);
        if($post === null){
            return $this->json(null, Response::HTTP_NOT_FOUND);
        }
        $post->likeBy($userLoggedIn);
        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return $this->json($post, Response::HTTP_CREATED, [], ["groups" => ["post", "user", "comment","likers"]]);
    }

    /**
     * Remove a like from post.
     * This call update a post by removing a like.
     * @Route("/removeLike/{id}", name="api_posts_item_remove_like", methods={"PUT"})
     * @SWG\Parameter( name="Authorization", in="header", required=true, type="string", default="Bearer TOKEN", description="Authorization" )
     * @SWG\Response(
     *     response=Response::HTTP_CREATED,
     *     description="Returns a specific post after updated with a like",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Post::class, groups={"post", "user", "comment","likers"}))
     *     )
     * )
     * @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="This post does not exists"
     *     ),
     * @SWG\Tag(name="posts")
     */
    public function removeLike($id): JsonResponse
    {
        $userLoggedIn = $this->getUser();
        $post = $this->entityManager->getRepository(Post::class)->find($id);
        if($post === null){
            return $this->json(null, Response::HTTP_NOT_FOUND);
        }
        $post->dislikeBy($userLoggedIn);
        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return $this->json($post, Response::HTTP_CREATED, [], ["groups" => ["post", "user", "comment","likers"]]);
    }

    /**
     * Add event to post.
     * This call update a post by adding an event.
     * @Route("/addEvent/{id}/{eventId}", name="api_posts_item_add_event", methods={"PUT"})
     * @SWG\Parameter( name="Authorization", in="header", required=true, type="string", default="Bearer TOKEN", description="Authorization" )
     * @SWG\Response(
     *     response=Response::HTTP_OK,
     *     description="Returns a specific post after updated with an event",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Post::class, groups={"post", "user", "post_event", "event"}))
     *     )
     * )
     * @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="This post or event does not exists"
     *     ),
     * @SWG\Response(
     *         response=Response::HTTP_FORBIDDEN,
     *         description="You don't have the authorization do to this"
     *     ),
     * @SWG\Tag(name="posts")
     */
    public function addEvent(Post $post, $eventId): JsonResponse
    {
        $userLoggedIn = $this->getUser();
        $postAuthor = $post->getAuthor();
        if ($userLoggedIn != $postAuthor) {
            throw $this->createAccessDeniedException('Ce post ne vous appartient pas.');
        }
        if($post->getEvent() != null ){
            throw $this->createAccessDeniedException('Ce post a déjà un évènement associé.');
        }
        $event = $this->entityManager->getRepository(Event::class)->find($eventId);
        if($event === null){
            throw $this->createAccessDeniedException("Cet évènement n'existe pas.");
        }
        $eventAuthor = $event->getOwner();
        if($eventAuthor != $userLoggedIn){
            throw $this->createAccessDeniedException('Cet évènement ne vous appartient pas.');
        }
        $eventPost = $event->getPost();
        if($eventPost != null){
            throw $this->createAccessDeniedException('Cet évènement est déjà associé à un post.');
        }
        $post->setEvent($event);
        $event->setPost($post);
        $this->entityManager->persist($post);
        $this->entityManager->persist($event);
        $this->entityManager->flush();

        return $this->json($post, Response::HTTP_OK, [], ["groups" => ["post", "user", "post_event", "event"]]);
    }

    /**
     * Remove event to post.
     * This call update a post by removing an event.
     * @Route("/removeEvent/{id}", name="api_posts_item_remove_event", methods={"PUT"})
     * @SWG\Parameter( name="Authorization", in="header", required=true, type="string", default="Bearer TOKEN", description="Authorization" )
     * @SWG\Response(
     *     response=Response::HTTP_OK,
     *     description="Returns a specific post after updated with an event",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Post::class, groups={"post", "user", "post_event", "event"}))
     *     )
     * )
     * @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="This post does not exists"
     *     ),
     * @SWG\Response(
     *         response=Response::HTTP_FORBIDDEN,
     *         description="You don't have the authorization do to this"
     *     ),
     * @SWG\Tag(name="posts")
     */
    public function removeEvent(Post $post): JsonResponse
    {
        $userLoggedIn = $this->getUser();
        $postAuthor = $post->getAuthor();
        if ($userLoggedIn != $postAuthor) {
            throw $this->createAccessDeniedException('Ce post ne vous appartient pas.');
        }
        if($post->getEvent() === null ){
            throw $this->createAccessDeniedException("Ce post n'a pas d'évènement associé.");
        }
        $postEvent = $post->getEvent();
        $post->setEvent(null);
        $postEvent->setPost(null);
        $this->entityManager->persist($post);
        $this->entityManager->persist($postEvent);
        $this->entityManager->flush();

        return $this->json($post, Response::HTTP_OK, [], ["groups" => ["post", "user", "post_event", "event"]]);
    }
}
