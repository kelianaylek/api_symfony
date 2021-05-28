<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class UserController
 * @package App\Controller
 * @Route("/api/users")
 */
class UserController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private SerializerInterface $serializer;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        SerializerInterface $serializer
    )
    {
        $this->entityManager = $entityManager ;
        $this->userRepository = $userRepository ;
        $this->serializer = $serializer ;
    }

    /**
     * @return JsonResponse
     * @Route(name="api_users_collection_get", methods={"GET"})
     */
    public function collection(): JsonResponse
    {
        $users = $this->userRepository->findAll();
        return $this->json($users);
    }

    /**
     * @Route("/{id}", name="api_users_item_get", methods={"GET"})
     * @param User $user
     * @return JsonResponse
     */
    public function item(User $user): JsonResponse
    {
        return $this->json($user);
    }

    /**
     * @Route(name="api_users_collection_post", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function post(Request $request): JsonResponse
    {
        $securityContext = $this->container->get('security.authorization_checker');
        if (!$securityContext->isGranted('IS_AUTHENTICATED_FULLY')) {
            $user = $this->serializer->deserialize($request->getContent(), User::class, "json");
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $this->json($user, 201);
        } else {
            throw $this->createAccessDeniedException('Vous êtes déjà connecté !');
        }
    }

    /**
     * @Route("/{id}", name="api_users_item_delete", methods={"DELETE"})
     * @param int $id
     * @return JsonResponse
     */
    public function delete(int $id): JsonResponse
    {
        $userLoggedIn = $this->getUser();
        $userLoggedInId = $userLoggedIn->getId();

        if ($userLoggedInId === $id) {
            $posts = $this->entityManager->getRepository(Post::class)->findBy(['author' => $id]);

            foreach ($posts as $post) {
                $commentsInPost = $this->entityManager->getRepository(Comment::class)->findBy(['author' => $id]);
                foreach ($commentsInPost as $commentInPost) {
                    $this->entityManager->remove($commentInPost);
                }
                $this->entityManager->remove($post);
            }
            $comments = $this->entityManager->getRepository(Comment::class)->findBy(['author' => $id]);
            foreach ($comments as $comment) {
                $this->entityManager->remove($comment);
            }

            $this->entityManager->remove($userLoggedIn);
            $this->entityManager->flush();
            return $this->json(204);
        } else {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce compte. !');
        }
    }

    /**
     * @Route("/{id}", name="api_users_item_put", methods={"PUT"})
     * @return JsonResponse
     */
    public function put(): JsonResponse
    {
        throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ces informations !');
    }

    /**
     * @Route("/{id}", name="api_users_item_patch", methods={"PATCH"})
     * @return JsonResponse
     */
    public function patch(): JsonResponse
    {
        throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ces informations !');
    }
}
