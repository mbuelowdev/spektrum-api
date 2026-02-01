<?php

namespace App\Entity;

use App\Repository\GuessRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: GuessRepository::class)]
class Guess
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'guesses')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['default'])]
    private ?Player $player = null;

    #[ORM\Column]
    #[Groups(['default'])]
    private ?float $degree = null;

    #[ORM\Column]
    #[Groups(['default'])]
    private ?bool $isPreview = null;

    #[ORM\ManyToOne(inversedBy: 'gameGuesses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Room $room = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayer(): ?Player
    {
        return $this->player;
    }

    public function setPlayer(?Player $player): static
    {
        $this->player = $player;

        return $this;
    }

    public function getDegree(): ?float
    {
        return $this->degree;
    }

    public function setDegree(float $degree): static
    {
        $this->degree = $degree;

        return $this;
    }

    public function isPreview(): ?bool
    {
        return $this->isPreview;
    }

    public function setIsPreview(bool $isPreview): static
    {
        $this->isPreview = $isPreview;

        return $this;
    }

    public function getRoom(): ?Room
    {
        return $this->room;
    }

    public function setRoom(?Room $room): static
    {
        $this->room = $room;

        return $this;
    }
}
