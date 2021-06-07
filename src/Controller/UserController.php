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
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

    /**
 * @Route("/api/users")
 */
class UserController extends BaseController
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private SerializerInterface $serializer;
    private UserPasswordEncoderInterface $userPasswordEncoder;
    private ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        SerializerInterface $serializer,
        UserPasswordEncoderInterface $userPasswordEncoder,
        ValidatorInterface $validator
    )
    {
        $this->entityManager = $entityManager ;
        $this->userRepository = $userRepository ;
        $this->serializer = $serializer ;
        $this->userPasswordEncoder = $userPasswordEncoder;
        $this->validator = $validator;
    }

    /**
     * @Route(name="api_users_collection_get", methods={"GET"})
     */
    public function collection(): JsonResponse
    {
        $users = $this->userRepository->findAll();

        return $this->json($users, 200, [], ["groups" => "user"]);
    }

    /**
     * @Route("/{id}", name="api_users_item_get", methods={"GET"})
     */
    public function item(User $user): JsonResponse
    {
        return $this->json($user, 200, [], ["groups" => "user"]);
    }

    /**
     * @Route(name="api_users_collection_post", methods={"POST"})
     */
    public function post(Request $request): JsonResponse
    {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException('Vous êtes déjà connecté !');
        }
        $user = $this->serializer->deserialize($request->getContent(), User::class, "json");
        if ($response = $this->postValidation($user, $this->validator)) {
            return $response;
        }
        $user->setPassword($this->userPasswordEncoder->encodePassword($user, $user->getPassword()));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json($user, Response::HTTP_CREATED);
    }

    /**
     * @Route("/{id}", name="api_users_item_delete", methods={"DELETE"})
     */
    public function delete(int $id): JsonResponse
    {
        $userLoggedIn = $this->getUser();
        $userLoggedInId = $userLoggedIn->getId();
        if ($userLoggedInId !== $id) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce compte. !');
        }
        $this->entityManager->remove($userLoggedIn);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/{id}", name="api_users_item_put", methods={"PUT"})
     */
    public function put(): JsonResponse
    {
        throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ces informations !');
    }

    /**
     * @Route("/{id}", name="api_users_item_patch", methods={"PATCH"})
     */
    public function patch(): JsonResponse
    {
        throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ces informations !');
    }
}
