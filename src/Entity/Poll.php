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
     * @ORM\OneToOne(targetEntity=Post::class, inversedBy="poll")
     * @ORM\JoinColumn(nullable=false)
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @Groups({"poll_post"})
     */
    private ?Post $post;

    /**
     * @ORM\OneToMany(targetEntity=PollChoice::class, mappedBy="poll", orphanRemoval=true)
     * @Groups({"poll"})
     */
    private Collection $pollChoices;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, inversedBy="poll")
     * @Groups({"poll_users"})
     */
    private Collection $users;

    public function __construct()
    {
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

    /**
     * @return Collection
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    /**
     * @param Collection $users
     */
    public function setUsers(Collection $users): void
    {
        $this->users = $users;
    }
}
