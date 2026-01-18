<?php

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameRepository::class)]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $pointsTeamA = 0;

    #[ORM\Column]
    private ?int $pointsTeamB = 0;

    #[ORM\Column]
    private ?int $gameState = null;

    #[ORM\ManyToOne]
    private ?Player $activePlayer = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPointsTeamA(): ?int
    {
        return $this->pointsTeamA;
    }

    public function setPointsTeamA(int $pointsTeamA): static
    {
        $this->pointsTeamA = $pointsTeamA;

        return $this;
    }

    public function getPointsTeamB(): ?int
    {
        return $this->pointsTeamB;
    }

    public function setPointsTeamB(int $pointsTeamB): static
    {
        $this->pointsTeamB = $pointsTeamB;

        return $this;
    }

    public function getGameState(): ?int
    {
        return $this->gameState;
    }

    public function setGameState(int $gameState): static
    {
        $this->gameState = $gameState;

        return $this;
    }

    public function getActivePlayer(): ?Player
    {
        return $this->activePlayer;
    }

    public function setActivePlayer(?Player $activePlayer): static
    {
        $this->activePlayer = $activePlayer;

        return $this;
    }
}
