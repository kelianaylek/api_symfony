<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\User;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Swagger\Annotations as SWG;

/**
 * @Route("/api/events")
 */
class EventController extends BaseController
{
    private EntityManagerInterface $entityManager;
    private EventRepository $eventRepository;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        EventRepository $eventRepository,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    )
    {
        $this->entityManager = $entityManager ;
        $this->eventRepository = $eventRepository ;
        $this->serializer = $serializer ;
        $this->validator = $validator;
    }

    /**
     * List all events.
     *
     * This is the list of all events.
     *
     * @Route(name="api_events_collection_get", methods={"GET"})
     * @SWG\Response(
     *     response=Response::HTTP_OK,
     *     description="Returns all events",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Event::class, groups={"event", "event_owner", "event_member", "event_post"}))
     *     )
     * )
     * @SWG\Tag(name="events")
     */
    public function collection(): JsonResponse
    {
        $em = $this->getDoctrine()->getManager();
        $events = $em
            ->getRepository(Event::class)
            ->createQueryBuilder('e')
            ->addOrderBy('e.id', 'DESC')
            ->getQuery()
            ->execute()
        ;
        return $this->json($events, Response::HTTP_OK, [], ["groups" => ["event", "event_owner", "event_member", "event_post"] ]);
    }

    /**
     * Return the specified event.
     *
     * This call return a specific event.
     *
     * @Route("/{id}", name="api_events_item_get", methods={"GET"})
     * @SWG\Response(
     *     response=Response::HTTP_OK,
     *     description="Returns a specific event",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Event::class, groups={"event", "event_owner", "event_member", "event_post"}))
     *     )
     * )
     *     @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="Event not found"
     *     ),
     * @SWG\Tag(name="events")
     */
    public function item(Event $event): JsonResponse
    {
        return $this->json($event, Response::HTTP_OK, [], ["groups" => ["event", "event_owner", "event_member", "event_post"]]);
    }

    /**
     * Create a new event.
     * This call create a new event.
     * @Route(name="api_events_collection_post", methods={"POST"})
     * @SWG\Parameter( name="Authorization", in="header", required=true, type="string", default="Bearer TOKEN", description="Authorization" )
     * @SWG\Parameter(
     *          name="event data",
     *          in="body",
     *          type="json",
     *          description="event data",
     *          required=true,
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(property="title", type="string"),
     *              @SWG\Property(property="description", type="string"),
     *              @SWG\Property(property="startDate", example="01-01-2000"),
     *              @SWG\Property(property="endDate", example="02-01-2001"),
     *          )
     *     ),
     * @SWG\Response(
     *     response=Response::HTTP_CREATED,
     *     description="Returns a specific event",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Event::class, groups={"event", "event_owner", "event_member", "event_post"}))
     *     )
     * )
     * @SWG\Response(
     *         response=Response::HTTP_BAD_REQUEST,
     *         description="A problem occured with a field"
     *     ),
     * @SWG\Tag(name="events")
     */
    public function post(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $event = $this->serializer->deserialize($request->getContent(), Event::class, "json");
        $event->setOwner($user);

        if ($response = $this->postValidation($event, $this->validator)) {
            return $response;
        }
        $this->entityManager->persist($event);
        $this->entityManager->flush();

        return $this->json($event, Response::HTTP_CREATED, [], ["groups" => ["event", "event_owner", "event_member", "event_post"]]);
    }

    /**
     * Update an event.
     * This call update an event.
     * @Route("/{id}", name="api_events_item_put", methods={"PUT"})
     * @SWG\Parameter(
     *          name="event data",
     *          in="body",
     *          type="json",
     *          description="Event data",
     *          required=true,
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(property="title", type="string"),
     *              @SWG\Property(property="description", type="string"),
     *              @SWG\Property(property="startDate", example="04-04-2020"),
     *              @SWG\Property(property="endDate", example="02-05-2020"),
     *          )
     *     ),
     * @SWG\Response(
     *     response=Response::HTTP_OK,
     *     description="Returns a specific event after updated",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Event::class, groups={"event", "event_owner", "event_member", "event_post"}))
     *     )
     * )
     * @SWG\Response(
     *         response=Response::HTTP_BAD_REQUEST,
     *         description="A problem occured with a field"
     *     ),
     * @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="This event does not exists"
     *     ),
     * @SWG\Tag(name="events")
     */
    public function put(Event $event, Request $request): JsonResponse
    {
        $this->serializer->deserialize(
            $request->getContent(),
            Event::class,
            "json",
            [AbstractNormalizer::OBJECT_TO_POPULATE => $event ]
        );
        if ($response = $this->postValidation($event, $this->validator)) {
            return $response;
        }
        $this->entityManager->flush();

        return $this->json($event, Response::HTTP_OK, [], ["groups" => ["event", "event_owner", "event_member", "event_post"]]);
    }

    /**
     * Delete a specified event.
     *
     * This call delete a specific event.
     * @Route("/{id}", name="api_events_item_delete", methods={"DELETE"})
     * @SWG\Response(
     *     response=Response::HTTP_NO_CONTENT,
     *     description="Event deleted successfully",
     * )
     * @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="Event not found"
     *     ),
     * @SWG\Tag(name="events")
     */
    public function delete(Event $event): JsonResponse
    {
        $this->entityManager->remove($event);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Add a member to event.
     * This call update an event by adding a member.
     * @Route("/addMember/{id}/{userId}", name="api_events_item_add_member", methods={"PUT"})
     * @SWG\Parameter( name="Authorization", in="header", required=true, type="string", default="Bearer TOKEN", description="Authorization" )
     * @SWG\Response(
     *     response=Response::HTTP_OK,
     *     description="Returns a specific event after updated with a new member",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Event::class, groups={"event", "event_owner", "event_member", "event_post"}))
     *     )
     * )
     * @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="This user does not exists"
     *     ),
     * @SWG\Tag(name="events")
     */
    public function addMember(Event $event, $userId): JsonResponse
    {
        $userLoggedIn = $this->getUser();
        $eventOwner = $event->getOwner();
        if ($userLoggedIn != $eventOwner) {
            throw $this->createAccessDeniedException('Cet évènement ne vous appartient pas');
        }
        $member = $this->entityManager->getRepository(User::class)->find($userId);
        if($member === null){
            return $this->json(null, Response::HTTP_NOT_FOUND);
        }
        $event->addMember($member);
        $this->entityManager->persist($event);
        $this->entityManager->flush();

        return $this->json($event, Response::HTTP_OK, [], ["groups" => ["event", "event_owner", "event_member", "event_post"]]);
    }

    /**
     * Remove a member to event.
     * This call update an event by removing a member.
     * @Route("/removeMember/{id}/{userId}", name="api_events_item_remove_member", methods={"PUT"})
     * @SWG\Parameter( name="Authorization", in="header", required=true, type="string", default="Bearer TOKEN", description="Authorization" )
     * @SWG\Response(
     *     response=Response::HTTP_OK,
     *     description="Returns a specific event after updated by deleting a member",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Event::class, groups={"event", "event_owner", "event_member", "event_post"}))
     *     )
     * )
     * @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="This user does not exists"
     *     ),
     * @SWG\Tag(name="events")
     */
    public function removeMember(Event $event, $userId): JsonResponse
    {
        $userLoggedIn = $this->getUser();
        $eventOwner = $event->getOwner();
        if ($userLoggedIn != $eventOwner) {
            throw $this->createAccessDeniedException('Cet évènement ne vous appartient pas');
        }
        $member = $this->entityManager->getRepository(User::class)->find($userId);
        if($member === null){
            return $this->json(null, Response::HTTP_NOT_FOUND);
        }
        $event->removeMember($member);
        $this->entityManager->persist($event);
        $this->entityManager->flush();

        return $this->json($event, Response::HTTP_OK, [], ["groups" => ["event", "event_owner", "event_member", "event_post"]]);
    }

    /**
     * Participate to an event.
     * This call update an event by making a user participate to an event.
     * @Route("/participateToEvent/{id}", name="api_events_item_participate_to_event", methods={"PUT"})
     * @SWG\Parameter( name="Authorization", in="header", required=true, type="string", default="Bearer TOKEN", description="Authorization" )
     * @SWG\Response(
     *     response=Response::HTTP_OK,
     *     description="Returns a specific event after updated by adding a member to participate to an event",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Event::class, groups={"event", "event_owner", "event_member", "event_post"}))
     *     )
     * )
     * @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="This event does not exists"
     *     ),
     * @SWG\Response(
     *         response=Response::HTTP_FORBIDDEN,
     *         description="This event isnot public (no post attached)"
     *     ),
     * @SWG\Tag(name="events")
     */
    public function participateToEvent(Event $event): JsonResponse
    {
        $userLoggedIn = $this->getUser();
        if ($event->getPost() === null) {
            throw $this->createAccessDeniedException("Cet évènement n'est pas public");
        }
        $event->addMember($userLoggedIn);
        $this->entityManager->persist($event);
        $this->entityManager->flush();

        return $this->json($event, Response::HTTP_OK, [], ["groups" => ["event", "event_owner", "event_member", "event_post"]]);
    }

    /**
     * Unparticipate to an event.
     * This call update an event by making a user unparticipate to an event.
     * @Route("/unParticipateToEvent/{id}", name="api_events_item_unparticipate_to_event", methods={"PUT"})
     * @SWG\Parameter( name="Authorization", in="header", required=true, type="string", default="Bearer TOKEN", description="Authorization" )
     * @SWG\Response(
     *     response=Response::HTTP_OK,
     *     description="Returns a specific event after updated by removing a member to participate to an event",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Event::class, groups={"event", "event_owner", "event_member", "event_post"}))
     *     )
     * )
     * @SWG\Response(
     *         response=Response::HTTP_NOT_FOUND,
     *         description="This event does not exists"
     *     ),
     * @SWG\Response(
     *         response=Response::HTTP_FORBIDDEN,
     *         description="This event isnot public (no post attached)"
     *     ),
     * @SWG\Tag(name="events")
     */
    public function unParticipateToEvent(Event $event): JsonResponse
    {
        $userLoggedIn = $this->getUser();
        if ($event->getPost() === null) {
            throw $this->createAccessDeniedException("Cet évènement n'est pas public");
        }
        $event->removeMember($userLoggedIn);
        $this->entityManager->persist($event);
        $this->entityManager->flush();

        return $this->json($event, Response::HTTP_OK, [], ["groups" => ["event", "event_owner", "event_member", "event_post"]]);
    }
}
