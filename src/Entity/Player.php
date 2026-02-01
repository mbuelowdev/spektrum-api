<?php

namespace App\Entity;

use App\Repository\PlayerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PlayerRepository::class)]
class Player
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::GUID)]
    #[Groups(['default'])]
    private ?string $uuid = null;

    #[ORM\Column(length: 255)]
    #[Groups(['default'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::BLOB, nullable: true)]
    private $profileImage;

    #[ORM\Column]
    private ?int $wins = null;

    #[ORM\Column]
    #[Groups(['default'])]
    private ?\DateTime $lastHeartbeat = null;

    /**
     * @var Collection<int, Guess>
     */
    #[ORM\OneToMany(targetEntity: Guess::class, mappedBy: 'player')]
    private Collection $guesses;

    public function __construct()
    {
        $this->guesses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getProfileImage()
    {
        return $this->profileImage;
    }

    public function setProfileImage($profileImage): static
    {
        $this->profileImage = $profileImage;

        return $this;
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

    public function getWins(): ?int
    {
        return $this->wins;
    }

    public function setWins(int $wins): static
    {
        $this->wins = $wins;

        return $this;
    }

    public function getLastHeartbeat(): ?\DateTime
    {
        return $this->lastHeartbeat;
    }

    public function setLastHeartbeat(\DateTime $lastHeartbeat): static
    {
        $this->lastHeartbeat = $lastHeartbeat;

        return $this;
    }

    /**
     * @return Collection<int, Guess>
     */
    public function getGuesses(): Collection
    {
        return $this->guesses;
    }

    public function addGuess(Guess $guess): static
    {
        if (!$this->guesses->contains($guess)) {
            $this->guesses->add($guess);
            $guess->setPlayer($this);
        }

        return $this;
    }

    public function removeGuess(Guess $guess): static
    {
        if ($this->guesses->removeElement($guess)) {
            // set the owning side to null (unless already changed)
            if ($guess->getPlayer() === $this) {
                $guess->setPlayer(null);
            }
        }

        return $this;
    }
}
