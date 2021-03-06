<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * @ORM\Entity(repositoryClass=MessageRepository::class)
 */
class Message
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"group_messages"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @ORM\JoinTable(name="group_messages")
     * @Groups({"group_messages"})
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private ?string $content;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="messages")
     * @ORM\JoinColumn(nullable=false)
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @Groups({"group_messages"})
     */
    private ?User $author;

    /**
     * @ORM\ManyToOne(targetEntity=Group::class, inversedBy="messages")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Group $inGroup;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getInGroup(): ?Group
    {
        return $this->inGroup;
    }

    public function setInGroup(?Group $inGroup): self
    {
        $this->inGroup = $inGroup;

        return $this;
    }
}
