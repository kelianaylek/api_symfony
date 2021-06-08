<?php

namespace App\Entity;

use App\Repository\PollChoiceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;


/**
 * @ORM\Entity(repositoryClass=PollChoiceRepository::class)
 */
class PollChoice
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"poll_choices"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"poll_choices"})
     */
    private ?string $title;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"poll_choices"})
     */
    private ?int $answersCount;

    /**
     * @ORM\ManyToOne(targetEntity=Poll::class, inversedBy="pollChoices")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Poll $poll;

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

    public function getAnswersCount(): ?int
    {
        return $this->answersCount;
    }

    public function setAnswersCount(int $answersCount): self
    {
        $this->answersCount = $answersCount;

        return $this;
    }

    public function getPoll(): ?Poll
    {
        return $this->poll;
    }

    public function setPoll(?Poll $poll): self
    {
        $this->poll = $poll;

        return $this;
    }
}
