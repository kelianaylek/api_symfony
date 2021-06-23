<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
/**
 * Class User
 * @package App\Entity
 * @ORM\Entity
 * @ORM\Table(name="app_user")
 */
class User implements UserInterface
{
    /**
     * @var int|null
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     * @Groups({"user", "poll_users", "group_users", "event_owner", "event_member"})
     */
    private ?int $id = null;

    /**
     * @var string
     * @ORM\Column(unique=true)
     * @Assert\NotBlank
     * @Assert\NotNull
     * @Assert\Email(
     *     message = "The email '{{ value }}' is not a valid email."
     * )
     */
    private string $email;

    /**
     * @var string
     * @ORM\Column
     * @Assert\NotBlank
     * @Assert\NotNull
     * @Assert\Length(
     *      min = 6,
     *      minMessage = "Your password must be at least {{ limit }} characters long",
     * )
     */
    private string $password;

    /**
     * @var string
     * @ORM\Column
     * @Groups({"user", "group_users", "event_owner", "event_member"})
     * @Assert\NotBlank
     * @Assert\NotNull
     * @Assert\Length(
     *      min = 2,
     *      max = 30,
     *      minMessage = "Your name must be at least {{ limit }} characters long",
     *      maxMessage = "Your name cannot be longer than {{ limit }} characters"
     * )
     */
    private string $name;

    /**
     * @var Post[]|Collection
     * @ORM\OneToMany(targetEntity=Post::class, mappedBy="author", orphanRemoval=true)
     * @Groups({"posts"})
     */
    private Collection $posts;

    /**
     * @ORM\ManyToMany(targetEntity=Poll::class, mappedBy="users")
     */
    private Collection $polls;

    /**
     * @ORM\ManyToMany(targetEntity=PollChoice::class, mappedBy="users")
     */
    private Collection $pollChoices;

    /**
     * @ORM\ManyToMany(targetEntity=Group::class, mappedBy="users")
     */
    private Collection $groups;

    /**
     * @ORM\OneToMany(targetEntity=Message::class, mappedBy="author")
     */
    private Collection $messages;


    /**
     * @ORM\ManyToMany(targetEntity=Event::class, mappedBy="members")
     */
    private Collection $events;

    /**
     * @ORM\OneToMany(targetEntity=Event::class, mappedBy="owner", orphanRemoval=true)
     */
    private Collection $ownerEvents;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
        $this->polls = new ArrayCollection();
        $this->pollChoices = new ArrayCollection();
        $this->groups = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->events = new ArrayCollection();
        $this->ownerEvents = new ArrayCollection();
    }

    /**
     * @param string $email
     * @param string $name
     * @return static
     */
    public static function create(string $email, string $name): self
    {
        $user = new self();
        $user->email = $email;
        $user->name = $name;

        return $user;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function getSalt()
    {
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->email;
    }

    public function eraseCredentials()
    {
    }

    /**
     * @return Post[]|Collection
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(Post $post): self
    {
        if (!$this->posts->contains($post)) {
            $this->posts[] = $post;
            $post->setAuthor($this);
        }

        return $this;
    }

    public function removePost(Post $post): self
    {
        if ($this->posts->removeElement($post)) {
            // set the owning side to null (unless already changed)
            if ($post->getAuthor() === $this) {
                $post->setAuthor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Poll[]
     */
    public function getPolls(): Collection
    {
        return $this->polls;
    }

    /**
     * @return Collection|PollChoice[]
     */
    public function getPollChoices(): Collection
    {
        return $this->pollChoices;
    }

    public function addPollChoice(PollChoice $pollChoice): self
    {
        if (!$this->pollChoices->contains($pollChoice)) {
            $this->pollChoices[] = $pollChoice;
            $pollChoice->addUser($this);
        }

        return $this;
    }

    public function removePollChoice(PollChoice $pollChoice): self
    {
        if ($this->pollChoices->removeElement($pollChoice)) {
            $pollChoice->removeUser($this);
        }

        return $this;
    }

    /**
     * @return Collection|Group[]
     */
    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function addGroup(Group $group): self
    {
        if (!$this->groups->contains($group)) {
            $this->groups[] = $group;
            $group->addUser($this);
        }

        return $this;
    }

    public function removeGroup(Group $group): self
    {
        if ($this->groups->removeElement($group)) {
            $group->removeUser($this);
        }

        return $this;
    }

    /**
     * @return Collection|Message[]
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages[] = $message;
            $message->setAuthor($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): self
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getAuthor() === $this) {
                $message->setAuthor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Event[]
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(Event $event): self
    {
        if (!$this->events->contains($event)) {
            $this->events[] = $event;
            $event->addMember($this);
        }

        return $this;
    }

    public function removeEvent(Event $event): self
    {
        if ($this->events->removeElement($event)) {
            $event->removeMember($this);
        }

        return $this;
    }

    /**
     * @return Collection|Event[]
     */
    public function getOwnerEvents(): Collection
    {
        return $this->ownerEvents;
    }

    public function addOwnerEvent(Event $ownerEvent): self
    {
        if (!$this->ownerEvents->contains($ownerEvent)) {
            $this->ownerEvents[] = $ownerEvent;
            $ownerEvent->setOwner($this);
        }

        return $this;
    }

    public function removeOwnerEvent(Event $ownerEvent): self
    {
        if ($this->ownerEvents->removeElement($ownerEvent)) {
            // set the owning side to null (unless already changed)
            if ($ownerEvent->getOwner() === $this) {
                $ownerEvent->setOwner(null);
            }
        }

        return $this;
    }
}
