<?php

namespace App\DataFixtures;


use App\Entity\Comment;
use App\Entity\Event;
use App\Entity\Group;
use App\Entity\Message;
use App\Entity\Poll;
use App\Entity\PollChoice;
use App\Entity\Post;
use App\Entity\User;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    /**
     * @var UserPasswordEncoderInterface
     */
    private UserPasswordEncoderInterface $userPasswordEncoder;

    /**
     * AppFixtures constructor.
     * @param UserPasswordEncoderInterface $userPasswordEncoder
     */
    public function __construct(UserPasswordEncoderInterface  $userPasswordEncoder)
    {
        $this->userPasswordEncoder = $userPasswordEncoder;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $users = [];

        for ($i = 1; $i <= 10; $i++) {
            $user = User::create(
                sprintf("email+%d@email.com", $i),
                sprintf("name+%d", $i)
            );
            $user->setPassword($this->userPasswordEncoder->encodePassword($user, 'password'));
            $manager->persist($user);

            $users[] = $user;
        }

        foreach ($users as $user) {
            $event = new Event();
            $event->setTitle("Titre Event");
            $event->setDescription("Desc Event");
            $event->setOwner($user);
            $event->setStartDate(new DateTime("NOW"));
            $event->setEndDate(new DateTime("NOW"));
            $manager->persist($event);

            for ($j = 1; $j <= 5; $j++) {
            $post = Post::create("Content", $user, "image_url");
            shuffle($users);
            foreach (array_slice($users, 0, 5) as $userCanLike) {
                $post->likeBy($userCanLike);
            }
            $event = new Event();
            $event->setTitle("Titre Event d'un post");
            $event->setDescription("Desc Event d'un post");
            $event->setOwner($user);
            $event->setStartDate(new DateTime("NOW"));
            $event->setEndDate(new DateTime("NOW"));
            shuffle($users);
            foreach (array_slice($users, 0, 5) as $user) {
                $event->addMember($user);
            }
            $post->setEvent($event);
            $manager->persist($post);


            for ($k = 1; $k <= 10; $k++) {
                $comment = Comment::create(sprintf("Message %d", $k), $users[array_rand($users)], $post, "comment_image_url");
                $post->addComment($comment);
                $manager->persist($comment);
                $manager->persist($post);
            }

            $value = rand(0, 1) == 1;

            if ($value == true) {
                $poll = new Poll;
                $poll->setPost($post);
                $post->setPoll($poll);
                $randomPollChoiceCount = rand(2, 6);

                for ($k = 0; $k <= $randomPollChoiceCount; $k++) {
                    $pollChoice = new PollChoice;
                    $pollChoice->setTitle("Title " . $k);
                    $pollChoice->setPoll($poll);
                    $poll->addPollChoice($pollChoice);
                    shuffle($users);
                    foreach (array_slice($users, 0, 5) as $userAnswersPoll) {
                        $pollChoice->addUser($userAnswersPoll);
                    }

                    $manager->persist($pollChoice);

                }

                $manager->persist($post);
                $manager->persist($poll);

            }
            }
        }

        for ($k = 1; $k <= 10; $k++) {
            $group = new Group();
            $group->setName("Group " . $k);

            $randomInt = rand(0, count($users) - 1);
            $group->addGroupAdmin($users[$randomInt]);
            $group->addUser($users[$randomInt]);
            shuffle($users);
            foreach (array_slice($users, 0, 5) as $user) {
                $group->addUser($user);
            }
            for ($l = 1; $l <= 10; $l++) {
                $message = new Message();
                $message->setContent("Message " . $l);
                $randomInt = rand(0, count($users) -1);
                $message->setAuthor($users[$randomInt]);
                $message->setInGroup($group);
                $group->addMessage($message);
                $manager->persist($group);
                $manager->persist($message);
            }

        }
        $manager->flush();

    }
}
