<?php

namespace App\Entity;

use App\Repository\CommentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
/**
 * Class Post
 * @package App\Entity
 * @ORM\Entity
 */
class Post
{
    /**
     * @var int|null
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     * @Groups({"post", "poll_post", "event_post"})
     */
    private ?int $id = null;

    /**
     * @var string
     * @ORM\Column(type="text")
     * @Groups({"post"})
     * @Assert\NotBlank
     * @Assert\NotNull
     * @Assert\Length(
     *      max = 800,
     *      maxMessage = "Your content cannot be longer than {{ limit }} characters"
     * )
     */
    private string $content;

    /**
     * @var \DateTimeInterface
     * @ORM\Column(type="datetime_immutable")
     * @Groups({"post"})

     */
    private \DateTimeInterface $publishedAt;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="posts")
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"post_author"})

     */
    private User $author;

    /**
     * @var User[]|Collection
     * @ORM\ManyToMany(targetEntity="User")
     * @ORM\JoinTable(name="post_likes")
     * @Groups({"likers"})
     */
    private Collection $likedBy;

    /**
     * @var Comment[]|Collection
     * @ORM\OneToMany(targetEntity=Comment::class, mappedBy="post", cascade={"persist", "remove"})
     * @Groups({"comment"})
     */
    private Collection $comments;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"post"})
     */
    private ?string $image;

    /**
     * @ORM\OneToOne(targetEntity=Poll::class, mappedBy="post", orphanRemoval=true)
     * @Groups({"poll_posts"})
     */
    private ?Poll $poll;

    /**
     * @ORM\OneToOne(targetEntity=Event::class, inversedBy="post", cascade={"persist", "remove"})
     * @Groups({"post_event"})
     */
    private ?Event $event;

    /**
     * @param string $content
     * @param User $author
     * @param string $image
     * @return static
     */
    public static function create(string $content, User $author, string $image): self
    {
        $post = new self();
        $post->content = $content;
        $post->author = $author;
        $post->image = $image;

        return $post;
    }


    public function __construct()
    {
        $this->publishedAt = new \DateTimeImmutable();
        $this->likedBy = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    /**
     * @return \DateTimeImmutable|\DateTimeInterface
     */
    public function getPublishedAt()
    {
        return $this->publishedAt;
    }

    /**
     * @param \DateTimeImmutable|\DateTimeInterface $publishedAt
     */
    public function setPublishedAt($publishedAt): void
    {
        $this->publishedAt = $publishedAt;
    }

    /**
     * @return User[]|Collection
     */
    public function getLikedBy(): Collection
    {
        return $this->likedBy;
    }

    public function likeBy(User $user): void
    {
        if ($this->likedBy->contains($user)) {
            return;
        }
        $this->likedBy->add($user);
    }

    public function dislikeBy(User $user): void
    {
        if (!$this->likedBy->contains($user)) {
            return;
        }
        $this->likedBy->removeElement($user);
    }

    /**
     * @return Collection|Comment[]
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments[] = $comment;
            $comment->setPost($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getPost() === $this) {
                $comment->setPost(null);
            }
        }

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(User $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getPoll(): ?Poll
    {
        return $this->poll;
    }

    public function setPoll(Poll $poll): self
    {
        // set the owning side of the relation if necessary
        if ($poll->getPost() !== $this) {
            $poll->setPost($this);
        }

        $this->poll = $poll;

        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): self
    {
        $this->event = $event;

        return $this;
    }


}
