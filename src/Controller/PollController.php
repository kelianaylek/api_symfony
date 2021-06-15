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
     * @Route(name="api_polls_collection_get", methods={"GET"})
     */
    public function collection(): JsonResponse
    {
        $polls = $this->pollRepository->findAll();

        return $this->json($polls, Response::HTTP_OK, [], ["groups" => ["poll", "poll_post", "poll_users", "poll_choices", "poll_users"]]);
    }

    /**
     * @Route("/{id}", name="api_polls_item_get", methods={"GET"})
     */
    public function item(Poll $poll): JsonResponse
    {
        return $this->json($poll, Response::HTTP_OK, [], ["groups" => ["poll", "poll_post", "poll_users", "poll_choices", "poll_users"]]);
    }

    /**
     * @Route("/{postId}", name="api_polls_collection_post", methods={"POST"})
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
     * @Route("/addPollChoice/{pollId}", name="api_polls_item_add_new_choice", methods={"PUT"})
     * @param $pollId
     * @param Request $request
     * @return JsonResponse
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
     * @Route("/removePollChoice/{pollId}/{pollChoiceId}", name="api_polls_item_remove_new_choice", methods={"PUT"})
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
     * @Route("/{pollId}", name="api_polls_item_delete", methods={"DELETE"})
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
     * @Route("/addVote/{pollChoice}", name="api_polls_item_add_poll_choice", methods={"PUT"})
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

