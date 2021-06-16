<?php

namespace App\Entity;

use App\Repository\GroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=GroupRepository::class)
 * @ORM\Table(name="`group`")
 */
class Group
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"group"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"group"})
     */
    private ?string $name;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, inversedBy="groups")
     * @Groups({"group_users"})
     */
    private Collection $users;

    /**
     * @ORM\ManyToMany(targetEntity=User::class)
     * @ORM\JoinTable(name="group_admins")
     * @Groups({"group_users"})
     */
    private Collection $groupAdmins;

    /**
     * @ORM\OneToMany(targetEntity=Message::class, mappedBy="inGroup", orphanRemoval=true)
     * @Groups({"group_messages"})
     */
    private Collection $messages;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->groupAdmins = new ArrayCollection();
        $this->messages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

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
     * @return Collection|User[]
     */
    public function getGroupAdmins(): Collection
    {
        return $this->groupAdmins;
    }

    public function addGroupAdmin(User $groupAdmins): self
    {
        if (!$this->groupAdmins->contains($groupAdmins)) {
            $this->groupAdmins[] = $groupAdmins;
        }

        return $this;
    }

    public function removeGroupAdmin(User $groupAdmins): self
    {
        $this->groupAdmins->removeElement($groupAdmins);

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
            $message->setInGroup($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): self
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getInGroup() === $this) {
                $message->setInGroup(null);
            }
        }

        return $this;
    }
}
