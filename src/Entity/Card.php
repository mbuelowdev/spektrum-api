<?php

namespace App\Entity;

use App\Repository\CardRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CardRepository::class)]
class Card
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $valueLeft = null;

    #[ORM\Column(length: 255)]
    private ?string $valueRight = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValueLeft(): ?string
    {
        return $this->valueLeft;
    }

    public function setValueLeft(string $valueLeft): static
    {
        $this->valueLeft = $valueLeft;

        return $this;
    }

    public function getValueRight(): ?string
    {
        return $this->valueRight;
    }

    public function setValueRight(string $valueRight): static
    {
        $this->valueRight = $valueRight;

        return $this;
    }
}
