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
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Swagger\Annotations as SWG;

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
     * List all users.
     *
     * This is the list of all users.
     *
     * @Route(name="api_users_collection_get", methods={"GET"})
     * @SWG\Response(
     *     response=Response::HTTP_OK,
     *     description="Returns all users",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=User::class, groups={"user", "posts", "post"}))
     *     )
     * )
     * @SWG\Tag(name="users")
     */
    public function collection(): JsonResponse
    {
        $users = $this->userRepository->findAll();

        return $this->json($users, Response::HTTP_OK, [], ["groups" => ["user", "posts", "post"]]);
    }

    /**
     * Return the specified user.
     *
     * This call return a specific user.
     *
     * @Route("/{id}", name="api_users_item_get", methods={"GET"})
     * @SWG\Response(
     *     response=Response::HTTP_OK,
     *     description="Returns a specific user",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=User::class, groups={"user", "posts", "post"}))
     *     )
     * )
     *     @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="User not found"
     *     ),
     * @SWG\Tag(name="users")
     */
    public function item(User $user): JsonResponse
    {
        return $this->json($user, Response::HTTP_OK, [], ["groups" => ["user", "posts", "post"]]);
    }

    /**
     * Post a new user.
     * This call create a new user.
     * @Route(name="api_users_collection_post", methods={"POST"})
     * @SWG\Parameter(
     *          name="userCredentials",
     *          in="body",
     *          type="json",
     *          description="User data",
     *          required=true,
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(property="email", type="string"),
     *              @SWG\Property(property="name", type="string"),
     *              @SWG\Property(property="password", type="string")
     *          )
     *     ),
     * @SWG\Parameter( name="Authorization", in="header", required=false, type="string", default="Bearer TOKEN", description="Authorization" )
     * @SWG\Response(
     *     response=Response::HTTP_CREATED,
     *     description="Returns a specific user",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=User::class, groups={"user", "posts", "post"}))
     *     )
     * )
     * @SWG\Response(
     *         response=Response::HTTP_BAD_REQUEST,
     *         description="A problem occured with a field"
     *     ),
     * @SWG\Response(
     *         response=Response::HTTP_FORBIDDEN,
     *         description="You already have an account"
     *     ),
     * @SWG\Tag(name="users")
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

        return $this->json($user, Response::HTTP_CREATED, [], ["groups" => ["user", "posts", "post"]]);
    }

    /**
     * Delete a specified user.
     *
     * This call delete a specific user.
     * @Route("/{id}", name="api_users_item_delete", methods={"DELETE"})
     * @SWG\Parameter( name="Authorization", in="header", required=true, type="string", default="Bearer TOKEN", description="Authorization" )
     * @SWG\Response(
     *     response=Response::HTTP_NO_CONTENT,
     *     description="User deleted successfully",
     * )
     * @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="User not found"
     *     ),
     * @SWG\Response(
     *         response=Response::HTTP_FORBIDDEN,
     *         description="You cannot delete this user"
     *     ),
     * @SWG\Tag(name="users")
     */
    public function delete(User $user): JsonResponse
    {
        $userLoggedIn = $this->getUser();
        if ($userLoggedIn !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce compte. !');
        }
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Update user data.
     * This call update user data.
     * @Route("/{id}", name="api_users_item_put", methods={"PUT"})
     * @SWG\Response(
     *         response=Response::HTTP_UNAUTHORIZED,
     *         description="You can't update user data"
     *     ),
     * @SWG\Tag(name="users")
     */
    public function put(): JsonResponse
    {
        throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ces informations !');
    }

    /**
     * Update user data.
     * This call update user data.
     * @Route("/{id}", name="api_users_item_patch", methods={"PATCH"})
     * @SWG\Response(
     *         response=Response::HTTP_UNAUTHORIZED,
     *         description="You can't update user data"
     *     ),
     * @SWG\Tag(name="users")
     */
    public function patch(): JsonResponse
    {
        throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ces informations !');
    }

    /**
     * Return the connected user.
     *
     * This call return a specific user.
     *
     * @Route("/connected", name="api_users_item_get", methods={"GET"})
     * @SWG\Response(
     *     response=Response::HTTP_OK,
     *     description="Returns the connected",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=User::class, groups={"user", "posts", "post"}))
     *     )
     * )
     *     @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="User not found"
     *     ),
     * @SWG\Tag(name="users")
     */
    public function getConnectedUser(): JsonResponse
    {
        $user = $this->getUser();

        return $this->json($user, Response::HTTP_OK, [], ["groups" => ["user", "posts", "post"]]);
    }
}
