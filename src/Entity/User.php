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
     * @Groups({"user"})
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
     * @Groups({"user"})
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
    private $polls;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
        $this->polls = new ArrayCollection();
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
            $post->setPostAuthor($this);
        }

        return $this;
    }

    public function removePost(Post $post): self
    {
        if ($this->posts->removeElement($post)) {
            // set the owning side to null (unless already changed)
            if ($post->getPostAuthor() === $this) {
                $post->setPostAuthor(null);
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

    public function addPoll(Poll $poll): self
    {
        if (!$this->polls->contains($poll)) {
            $this->polls[] = $poll;
            $poll->addUser($this);
        }

        return $this;
    }

    public function removePoll(Poll $poll): self
    {
        if ($this->polls->removeElement($poll)) {
            $poll->removeUser($this);
        }

        return $this;
    }
}
