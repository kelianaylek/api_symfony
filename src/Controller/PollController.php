<?php

namespace App\Controller;

use App\Entity\Poll;
use App\Entity\PollChoice;
use App\Entity\Post;
use App\Entity\User;
use App\Repository\PollRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Swagger\Annotations as SWG;

/**
 * @Route("/api/polls")
 */
class PollController extends BaseController
{
    private EntityManagerInterface $entityManager;
    private PollRepository $pollRepository;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;
    private PostRepository $postRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        PollRepository $pollRepository,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        PostRepository $postRepository
    ){
        $this->entityManager = $entityManager ;
        $this->pollRepository = $pollRepository ;
        $this->serializer = $serializer ;
        $this->validator = $validator;
        $this->postRepository = $postRepository;
    }

    /**
     * List all polls.
     *
     * This is the list of all polls.
     *
     * @Route(name="api_polls_collection_get", methods={"GET"})
     * @SWG\Response(
     *     response=Response::HTTP_OK,
     *     description="Returns all polls",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Poll::class, groups={"poll", "poll_post", "poll_users", "poll_choices", "poll_users"}))
     *     )
     * )
     * @SWG\Tag(name="polls")
     */
    public function collection(): JsonResponse
    {
        $polls = $this->pollRepository->findAll();

        return $this->json($polls, Response::HTTP_OK, [], ["groups" => ["poll", "poll_post", "poll_users", "poll_choices", "poll_users"]]);
    }

    /**
     * Return the specified poll.
     *
     * This call return a specific poll.
     *
     * @Route("/{id}", name="api_polls_item_get", methods={"GET"})
     * @SWG\Response(
     *     response=Response::HTTP_OK,
     *     description="Returns a specific poll",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Poll::class, groups={"poll", "poll_post", "poll_users", "poll_choices", "poll_users"}))
     *     )
     * )
     *     @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="Poll not found"
     *     ),
     * @SWG\Tag(name="polls")
     */
    public function item(Poll $poll): JsonResponse
    {
        return $this->json($poll, Response::HTTP_OK, [], ["groups" => ["poll", "poll_post", "poll_users", "poll_choices", "poll_users"]]);
    }

    /**
     * Create a new poll.
     * This call create a new poll.
     * @Route("/{postId}", name="api_polls_collection_post", methods={"POST"})
     * @SWG\Parameter( name="Authorization", in="header", required=true, type="string", default="Bearer TOKEN", description="Authorization" )
     * @SWG\Response(
     *     response=Response::HTTP_CREATED,
     *     description="Returns a specific user",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Poll::class, groups={"poll", "poll_post", "poll_users", "poll_choices", "poll_users"}))
     *     )
     * )
     * @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="This user does not exists"
     *     ),
     * @SWG\Tag(name="polls")
     */
    public function post(int $postId): JsonResponse
    {
        $userLoggedIn = $this->getUser();
        $post = $this->entityManager->getRepository(Post::class)->find($postId);
        if($post === null){
            return $this->json(null, Response::HTTP_NOT_FOUND);
        }
        $postAuthor = $post->getAuthor();
        if ($userLoggedIn != $postAuthor) {
            throw $this->createAccessDeniedException('Ce post ne vous appartient pas.');
        }
        if($post->getPoll()){
            throw $this->createAccessDeniedException('Vous avez déjà créé un sondage pour ce post.');
        }
        $poll = new Poll();
        $poll->setPost($post);
        $this->entityManager->persist($poll);
        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return $this->json($poll, Response::HTTP_CREATED, [], ["groups" => ["post", "user", "comment","likers", "poll", "poll_post", "poll_users", "poll_choices"]]);
    }

    /**
     * Update a post by adding a poll.
     * This call update a post by adding a poll to it.
     * @Route("/addPollChoice/{pollId}", name="api_polls_item_add_new_choice", methods={"PUT"})
     * @SWG\Parameter( name="Authorization", in="header", required=true, type="string", default="Bearer TOKEN", description="Authorization" )
     * @SWG\Parameter(
     *          name="poll data",
     *          in="body",
     *          type="json",
     *          description="poll data",
     *          required=true,
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(property="title", type="string"),
     *          )
     *     ),
     * @SWG\Response(
     *     response=Response::HTTP_OK,
     *     description="Returns a specific post after updated with a poll",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Poll::class, groups={"poll", "poll_post", "poll_users", "poll_choices", "poll_users"}))
     *     )
     * )
     * @SWG\Response(
     *         response=Response::HTTP_BAD_REQUEST,
     *         description="A problem occured with a field"
     *     ),
     * @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="This poll does not exists"
     *     ),
     * @SWG\Tag(name="polls")
     */
        public function addPollChoice($pollId, Request $request): JsonResponse
    {
        $pollChoice = new PollChoice();
        $poll = $this->entityManager->getRepository(Poll::class)->find($pollId);
        if($poll === null){
            return $this->json(null, Response::HTTP_NOT_FOUND);
        }
        $post = $poll->getPost();
        $postAuthor = $post->getAuthor();
        $userLoggedIn = $this->getUser();
        if ($userLoggedIn != $postAuthor) {
            throw $this->createAccessDeniedException('Ce post ne vous appartient pas.');
        }
        $this->serializer->deserialize(
            $request->getContent(),
            PollChoice::class,
            "json",
            [AbstractNormalizer::OBJECT_TO_POPULATE => $pollChoice ]
        );
        if ($response = $this->postValidation($pollChoice, $this->validator)) {
            return $response;
        }
        $poll->addPollChoice($pollChoice);
        $this->entityManager->persist($pollChoice);
        $this->entityManager->persist($poll);
        $this->entityManager->flush();

        return $this->json($poll, Response::HTTP_OK, [], ["groups" => ["post", "user", "poll", "poll_post", "poll_users", "poll_choices"]]);
    }

    /**
     * Update a post by removing a poll.
     * This call update a post by removing a poll to it.
     * @Route("/removePollChoice/{pollId}/{pollChoiceId}", name="api_polls_item_remove_new_choice", methods={"PUT"})
     * @SWG\Parameter( name="Authorization", in="header", required=true, type="string", default="Bearer TOKEN", description="Authorization" )
     * @SWG\Response(
     *     response=Response::HTTP_OK,
     *     description="Returns a specific post after updated with a poll",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Poll::class, groups={"poll", "poll_post", "poll_users", "poll_choices", "poll_users"}))
     *     )
     * )
     * @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="This post or poll does not exists"
     *     ),
     * @SWG\Tag(name="polls")
     */
    public function removePollChoice($pollId, $pollChoiceId): JsonResponse
    {
        $poll = $this->entityManager->getRepository(Poll::class)->find($pollId);
        if($poll === null){
            return $this->json(null, Response::HTTP_NOT_FOUND);
        }
        $pollChoice = $this->entityManager->getRepository(PollChoice::class)->find($pollChoiceId);
        if($pollChoice === null){
            return $this->json(null, Response::HTTP_NOT_FOUND);
        }
        $post = $poll->getPost();
        $postAuthor = $post->getAuthor();
        $userLoggedIn = $this->getUser();
        if ($userLoggedIn != $postAuthor) {
            throw $this->createAccessDeniedException('Ce post ne vous appartient pas.');
        }
        $poll->removePollChoice($pollChoice);
        $this->entityManager->persist($pollChoice);
        $this->entityManager->persist($poll);
        $this->entityManager->flush();

        return $this->json($poll, Response::HTTP_OK, [], ["groups" => ["post", "user", "poll", "poll_post", "poll_users", "poll_choices"]]);
    }

    /**
     * Delete a specified poll.
     *
     * This call delete a specific poll.
     * @Route("/{pollId}", name="api_polls_item_delete", methods={"DELETE"})
     * @SWG\Parameter( name="Authorization", in="header", required=true, type="string", default="Bearer TOKEN", description="Authorization" )
     * @SWG\Response(
     *     response=Response::HTTP_NO_CONTENT,
     *     description="poll deleted successfully",
     * )
     * @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="poll not found"
     *     ),
     * @SWG\Tag(name="polls")
     */
    public function delete($pollId): JsonResponse
    {
        $userLoggedIn = $this->getUser();
        $poll = $this->entityManager->getRepository(Poll::class)->find($pollId);
        if($poll != null){
            $post = $poll->getPost();
            $postAuthor = $post->getAuthor();
            if ($userLoggedIn != $postAuthor) {
                throw $this->createAccessDeniedException('Ce post ne vous appartient pas.');
            }
            $this->entityManager->remove($poll);
            $this->entityManager->flush();

            return $this->json(null, Response::HTTP_NO_CONTENT);
        }

        return $this->json(null, Response::HTTP_NOT_FOUND);
    }

    /**
     * Update a post by adding a vote to a poll choice.
     * This call update a post by adding a vote to a poll choice.
     * @Route("/addVote/{pollChoice}", name="api_polls_item_add_poll_choice", methods={"PUT"})
     * @SWG\Parameter( name="Authorization", in="header", required=true, type="string", default="Bearer TOKEN", description="Authorization" )
     * @SWG\Response(
     *     response=Response::HTTP_OK,
     *     description="Returns a specific post after updated with a poll choice",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Poll::class, groups={"poll", "poll_post", "poll_users", "poll_choices", "poll_users"}))
     *     )
     * )
     * @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="This poll does not exists"
     *     ),
     * @SWG\Tag(name="polls")
     */
    public function addVoteToPollChoice($pollChoice): JsonResponse
    {
        $userLoggedIn = $this->getUser();
        $pollChoice = $this->entityManager->getRepository(PollChoice::class)->find($pollChoice);
        if($pollChoice === null){
            return $this->json(null, Response::HTTP_NOT_FOUND);
        }
        $poll = $pollChoice->getPoll();
        $pollChoicesInPoll = $poll->getPollChoices();
        foreach ($pollChoicesInPoll as $pollChoiceInPoll){
            $pollChoiceInPollUsers = $pollChoiceInPoll->getUsers();
            foreach ($pollChoiceInPollUsers as $pollChoiceInPollUser){
                if($pollChoiceInPollUser == $userLoggedIn){
                    $pollChoiceInPoll->removeUser($pollChoiceInPollUser);
                }
            }
        }
        $pollChoice->addUser($userLoggedIn);
        $this->entityManager->persist($pollChoice);
        $this->entityManager->flush();

        return $this->json($poll, Response::HTTP_OK, [], ["groups" => ["post", "user", "poll", "poll_post", "poll_users", "poll_choices"]]);
    }
}

