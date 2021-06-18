<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
/**
 * @ORM\Entity(repositoryClass=EventRepository::class)
 */
class Event
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"event"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"event"})
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private ?string $title;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"event"})
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private ?string $description;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="ownerEvents")
     * @ORM\JoinColumn(nullable=true)
     * @Groups({"event_owner"})
     */
    private ?User $owner;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, inversedBy="events")
     * @Groups({"event_member"})
     */
    private Collection $members;

    /**
     * @ORM\Column(type="date")
     * @Groups({"event"})
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private ?\DateTimeInterface $startDate;

    /**
     * @ORM\Column(type="date")
     * @Groups({"event"})
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private ?\DateTimeInterface $endDate;

    /**
     * @ORM\OneToOne(targetEntity=Post::class, mappedBy="event", cascade={"persist", "remove"})
     * @Groups({"event_post"})
     */
    private ?Post $post = null;

    public function __construct()
    {
        $this->members = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(User $member): self
    {
        if (!$this->members->contains($member)) {
            $this->members[] = $member;
        }

        return $this;
    }

    public function removeMember(User $member): self
    {
        $this->members->removeElement($member);

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function setPost(?Post $post): self
    {
        // unset the owning side of the relation if necessary
        if ($post === null && $this->post !== null) {
            $this->post->setEvent(null);
        }

        // set the owning side of the relation if necessary
        if ($post !== null && $post->getEvent() !== $this) {
            $post->setEvent($this);
        }

        $this->post = $post;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }
}
