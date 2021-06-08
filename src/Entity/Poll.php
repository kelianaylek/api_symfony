<?php

namespace App\Entity;

use App\Repository\PollRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;



/**
 * @ORM\Entity(repositoryClass=PollRepository::class)
 */
class Poll
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"poll"})
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity=Post::class, inversedBy="poll", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Post $post;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, inversedBy="polls")
     * @Groups({"poll"})
     */
    private Collection $users;

    /**
     * @ORM\OneToMany(targetEntity=PollChoice::class, mappedBy="poll", orphanRemoval=true)
     * @Groups({"poll"})
     */
    private Collection $pollChoices;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->pollChoices = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function setPost(Post $post): self
    {
        $this->post = $post;

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        $this->users->removeElement($user);

        return $this;
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
            $pollChoice->setPoll($this);
        }

        return $this;
    }

    public function removePollChoice(PollChoice $pollChoice): self
    {
        if ($this->pollChoices->removeElement($pollChoice)) {
            // set the owning side to null (unless already changed)
            if ($pollChoice->getPoll() === $this) {
                $pollChoice->setPoll(null);
            }
        }

        return $this;
    }
}
