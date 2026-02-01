<?php

namespace App\Entity;

use App\Repository\RoomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: RoomRepository::class)]
class Room
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::GUID)]
    #[Groups(['default'])]
    private ?string $uuid = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, Player>
     */
    #[ORM\ManyToMany(targetEntity: Player::class)]
    #[ORM\JoinTable(name: 'room_players_team_a')]
    #[Groups(['default'])]
    private Collection $playersTeamA;

    /**
     * @var Collection<int, Player>
     */
    #[ORM\ManyToMany(targetEntity: Player::class)]
    #[ORM\JoinTable(name: 'room_players_team_b')]
    #[Groups(['default'])]
    private Collection $playersTeamB;

    /**
     * @var Collection<int, Card>
     */
    #[ORM\ManyToMany(targetEntity: Card::class)]
    #[Groups(['default'])]
    private Collection $playedCards;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['default'])]
    private ?string $gameState = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['default'])]
    private ?int $gamePointsTeamA = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['default'])]
    private ?int $gamePointsTeamB = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['default'])]
    private ?int $gameRoundIndex = null;

    #[ORM\ManyToOne]
    #[Groups(['default'])]
    private ?Player $gameActivePlayer = null;

    #[ORM\ManyToOne]
    #[Groups(['default'])]
    private ?Card $gameActiveCard = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['default'])]
    private ?float $gameTargetDegree = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['default'])]
    private ?string $gameCluegiverGuessText = null;

    /**
     * @var Collection<int, Guess>
     */
    #[ORM\OneToMany(targetEntity: Guess::class, mappedBy: 'room')]
    #[Groups(['default'])]
    private Collection $gameGuesses;

    public function __construct()
    {
        $this->playersTeamA = new ArrayCollection();
        $this->playersTeamB = new ArrayCollection();
        $this->playedCards = new ArrayCollection();
        $this->gameGuesses = new ArrayCollection();
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

    public function getGamePointsTeamA(): ?int
    {
        return $this->gamePointsTeamA;
    }

    public function setGamePointsTeamA(?int $gamePointsTeamA): static
    {
        $this->gamePointsTeamA = $gamePointsTeamA;

        return $this;
    }

    public function getGamePointsTeamB(): ?int
    {
        return $this->gamePointsTeamB;
    }

    public function setGamePointsTeamB(?int $gamePointsTeamB): static
    {
        $this->gamePointsTeamB = $gamePointsTeamB;

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

    public function getGameRoundIndex(): ?int
    {
        return $this->gameRoundIndex;
    }

    public function setGameRoundIndex(?int $gameRoundIndex): static
    {
        $this->gameRoundIndex = $gameRoundIndex;

        return $this;
    }

    /**
     * @return Collection<int, Player>
     */
    public function getPlayersTeamA(): Collection
    {
        return $this->playersTeamA;
    }

    public function addPlayersTeamA(Player $playersTeamA): static
    {
        if (!$this->playersTeamA->contains($playersTeamA)) {
            $this->playersTeamA->add($playersTeamA);
        }

        return $this;
    }

    public function removePlayersTeamA(Player $playersTeamA): static
    {
        $this->playersTeamA->removeElement($playersTeamA);

        return $this;
    }

    /**
     * @return Collection<int, Player>
     */
    public function getPlayersTeamB(): Collection
    {
        return $this->playersTeamB;
    }

    /**
     * @return Collection<int, Player>
     */
    public function getPlayers(): Collection
    {
        return new ArrayCollection(array_merge(
            $this->playersTeamA->toArray(),
            $this->playersTeamB->toArray()
        ));
    }

    public function addPlayersTeamB(Player $playersTeamB): static
    {
        if (!$this->playersTeamB->contains($playersTeamB)) {
            $this->playersTeamB->add($playersTeamB);
        }

        return $this;
    }

    public function removePlayersTeamB(Player $playersTeamB): static
    {
        $this->playersTeamB->removeElement($playersTeamB);

        return $this;
    }

    public function getGameActivePlayer(): ?Player
    {
        return $this->gameActivePlayer;
    }

    public function setGameActivePlayer(?Player $gameActivePlayer): static
    {
        $this->gameActivePlayer = $gameActivePlayer;

        return $this;
    }

    public function getGameActiveCard(): ?Card
    {
        return $this->gameActiveCard;
    }

    public function setGameActiveCard(?Card $gameActiveCard): static
    {
        $this->gameActiveCard = $gameActiveCard;

        return $this;
    }

    /**
     * @return Collection<int, Card>
     */
    public function getPlayedCards(): Collection
    {
        return $this->playedCards;
    }

    public function addPlayedCard(Card $playedCard): static
    {
        if (!$this->playedCards->contains($playedCard)) {
            $this->playedCards->add($playedCard);
        }

        return $this;
    }

    public function removePlayedCard(Card $playedCard): static
    {
        $this->playedCards->removeElement($playedCard);

        return $this;
    }

    public function removeAllPlayedCards(): static
    {
        $this->playedCards = new ArrayCollection();

        return $this;
    }

    public function getGameTargetDegree(): ?float
    {
        return $this->gameTargetDegree;
    }

    public function setGameTargetDegree(?float $gameTargetDegree): static
    {
        $this->gameTargetDegree = $gameTargetDegree;

        return $this;
    }

    public function getGameCluegiverGuessText(): ?string
    {
        return $this->gameCluegiverGuessText;
    }

    public function setGameCluegiverGuessText(?string $gameCluegiverGuessText): static
    {
        $this->gameCluegiverGuessText = $gameCluegiverGuessText;

        return $this;
    }

    /**
     * @return Collection<int, Guess>
     */
    public function getGameGuesses(): Collection
    {
        return $this->gameGuesses;
    }

    public function addGameGuess(Guess $gameGuess): static
    {
        if (!$this->gameGuesses->contains($gameGuess)) {
            $this->gameGuesses->add($gameGuess);
            $gameGuess->setRoom($this);
        }

        return $this;
    }

    public function removeGameGuess(Guess $gameGuess): static
    {
        $this->gameGuesses->removeElement($gameGuess);

        return $this;
    }

    public function removeAllGameGuesses(): static
    {
        $this->gameGuesses = new ArrayCollection();

        return $this;
    }
}
