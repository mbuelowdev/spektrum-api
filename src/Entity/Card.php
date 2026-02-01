<?php

namespace App\Entity;

use App\Repository\CardRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CardRepository::class)]
class Card
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['default'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['default'])]
    private ?string $valueLeft = null;

    #[ORM\Column(length: 255)]
    #[Groups(['default'])]
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
