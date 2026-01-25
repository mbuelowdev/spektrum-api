<?php

namespace App\Entity;

use App\Repository\RoomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoomRepository::class)]
class Room
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::GUID)]
    private ?string $uuid = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, Player>
     */
    #[ORM\ManyToMany(targetEntity: Player::class)]
    private Collection $players;

    #[ORM\Column(type: Types::ARRAY)]
    private array $playersTeamA = [];

    #[ORM\Column(type: Types::ARRAY)]
    private array $playersTeamB = [];

    #[ORM\Column(nullable: true)]
    private ?int $pointsTeamA = null;

    #[ORM\Column(nullable: true)]
    private ?int $pointsTeamB = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $gameState = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $activePlayer = null;

    #[ORM\Column(nullable: true)]
    private ?int $roundIndex = null;

    #[ORM\Column(type: Types::ARRAY, nullable: true)]
    private ?array $playedCards = null;

    #[ORM\Column(nullable: true)]
    private ?int $activeCard = null;

    public function __construct()
    {
        $this->players = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, Player>
     */
    public function getPlayers(): Collection
    {
        return $this->players;
    }

    public function addPlayer(Player $player): static
    {
        if (!$this->players->contains($player)) {
            $this->players->add($player);
        }

        return $this;
    }

    public function removePlayer(Player $player): static
    {
        $this->players->removeElement($player);

        return $this;
    }

    public function getPlayersTeamA(): array
    {
        return $this->playersTeamA;
    }

    public function setPlayersTeamA(array $playersTeamA): static
    {
        $this->playersTeamA = $playersTeamA;

        return $this;
    }

    public function getPlayersTeamB(): array
    {
        return $this->playersTeamB;
    }

    public function setPlayersTeamB(array $playersTeamB): static
    {
        $this->playersTeamB = $playersTeamB;

        return $this;
    }

    public function getPointsTeamA(): ?int
    {
        return $this->pointsTeamA;
    }

    public function setPointsTeamA(?int $pointsTeamA): static
    {
        $this->pointsTeamA = $pointsTeamA;

        return $this;
    }

    public function getPointsTeamB(): ?int
    {
        return $this->pointsTeamB;
    }

    public function setPointsTeamB(?int $pointsTeamB): static
    {
        $this->pointsTeamB = $pointsTeamB;

        return $this;
    }

    public function getGameState(): ?string
    {
        return $this->gameState;
    }

    public function setGameState(?string $gameState): static
    {
        $this->gameState = $gameState;

        return $this;
    }

    public function getActivePlayer(): ?string
    {
        return $this->activePlayer;
    }

    public function setActivePlayer(?string $activePlayer): static
    {
        $this->activePlayer = $activePlayer;

        return $this;
    }

    public function getRoundIndex(): ?int
    {
        return $this->roundIndex;
    }

    public function setRoundIndex(?int $roundIndex): static
    {
        $this->roundIndex = $roundIndex;

        return $this;
    }

    public function getPlayedCards(): ?array
    {
        return $this->playedCards;
    }

    public function setPlayedCards(?array $playedCards): static
    {
        $this->playedCards = $playedCards;

        return $this;
    }

    public function getActiveCard(): ?int
    {
        return $this->activeCard;
    }

    public function setActiveCard(?int $activeCard): static
    {
        $this->activeCard = $activeCard;

        return $this;
    }
}
