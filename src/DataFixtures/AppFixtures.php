<?php

namespace App\DataFixtures;

use App\Entity\Comment;
use App\Entity\Poll;
use App\Entity\PollChoice;
use App\Entity\Post;
use App\Entity\User;
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
            for ($j = 1; $j <= 5; $j++) {
                $post = Post::create("Content", $user, "image_url");
                shuffle($users);
                foreach (array_slice($users, 0, 5) as $userCanLike) {
                    $post->likeBy($userCanLike);
                }
                $manager->persist($post);

                for ($k = 1; $k <= 10; $k++) {
                    $comment = Comment::create(sprintf("Message %d", $k), $users[array_rand($users)], $post, "comment_image_url");
                    $post->addComment($comment);
                    $manager->persist($comment);
                    $manager->persist($post);
                }

                $value = rand(0,1) == 1;

                if($value == true){
                    $poll = new Poll;
                    $poll->setPost($post);
                    $post->setPoll($poll);
                    $randomPollChoiceCount = rand(2, 6);

                    for ($k = 0; $k <= $randomPollChoiceCount; $k++) {
                        $pollChoice = new PollChoice;
                        $pollChoice->setTitle("Title " . $k);
                        $pollChoice->setPoll($poll);
                        $pollChoice->setAnswersCount(rand(1, 20));
                        $poll->addPollChoice($pollChoice);

                        $manager->persist($pollChoice);

                    }
                    shuffle($users);
                    foreach (array_slice($users, 0, 5) as $userAnswersPoll) {
                        $poll->addUser($userAnswersPoll);
                    }
                    $manager->persist($post);
                    $manager->persist($poll);

                }
            }
        }

        $manager->flush();
    }
}
