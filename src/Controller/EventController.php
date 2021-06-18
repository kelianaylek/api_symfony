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

/**
 * @Route("/api/events")
 */class EventController extends BaseController
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
     * @Route(name="api_events_collection_get", methods={"GET"})
     */
    public function collection(): JsonResponse
    {
        $events = $this->eventRepository->findAll();
        return $this->json($events, Response::HTTP_OK, [], ["groups" => ["event", "event_owner", "event_member", "event_post"] ]);
    }

    /**
     * @Route("/{id}", name="api_events_item_get", methods={"GET"})
     */
    public function item(Event $event): JsonResponse
    {
        return $this->json($event, Response::HTTP_OK, [], ["groups" => ["event", "event_owner", "event_member", "event_post"]]);
    }

    /**
     * @Route(name="api_events_collection_post", methods={"POST"})
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
     * @Route("/{id}", name="api_events_item_put", methods={"PUT"})
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

        return $this->json($event, Response::HTTP_CREATED, [], ["groups" => ["event", "event_owner", "event_member", "event_post"]]);
    }

    /**
     * @Route("/{id}", name="api_events_item_delete", methods={"DELETE"})
=     */
    public function delete(Event $event): JsonResponse
    {
        $this->entityManager->remove($event);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/addMember/{id}/{userId}", name="api_events_item_add_member", methods={"PUT"})
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
     * @Route("/removeMember/{id}/{userId}", name="api_events_item_remove_member", methods={"PUT"})
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
}
