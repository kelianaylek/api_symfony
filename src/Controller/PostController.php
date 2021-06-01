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
class PostController extends AbstractController
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

        return $this->json($posts, 200, [], ["groups" => "get"]);
    }

    /**
     * @Route("/{id}", name="api_posts_item_get", methods={"GET"})
     * @param Post $post
     * @return JsonResponse
     */
    public function item(Post $post): JsonResponse
    {
        return $this->json($post, 200, [], ["groups" => "get"]);
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

        $errors = $this->validator->validate($post);
        if (count($errors) > 0) {
            $formattedErrors = [];
            foreach ($errors as $error) {
                $formattedErrors[$error->getPropertyPath()] = [
                    'message' => sprintf('The property "%s" with value "%s" violated a requirement (%s)', $error->getPropertyPath(), $error->getInvalidValue(), $error->getMessage())
                ];
            }
            return $this->json($formattedErrors, 400);
        }

        $author = $this->entityManager->getRepository(User::class)->find($userId);
        $post->setAuthor($author);
        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return $this->json($post, 201);
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
        $this->entityManager->flush();

        return $this->json($post, 200);
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

        return $this->json(204);
    }
}
