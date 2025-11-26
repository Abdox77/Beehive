<?php

namespace App\Entity;

use App\Repository\HiveRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: HiveRepository::class)]
class Hive
{

    public function __construct()
    {
        $this->interventions = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?string $name = null;

    #[ORM\Column]
    private float $lat;

    #[ORM\Column]
    #[Assert\Range(min: -180, max: 180)]
    private float $lng;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(mappedBy: 'hive', targetEntity: Intervention::class, cascade: ['persist'])]
    private Collection $interventions;


    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'hives')]
    #[ORM\JoinColumn(nullable: false)]
    private User $owner;

    public function setOwner(?User $owner): void {
        $this->owner = $owner;
    }

    public function getOwner(): ?User {
        return $this->owner;
    }
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getLng(): float {
        return $this->lng;
    }

    public function getLat(): float {
        return $this->lat;
    }

    public function setName(?string $name): void {
        $this->name = $name;
    }

    public function setLat(?float $lat): void {
        $this->lat = $lat;
    }

    public function setLng(?float $lng): void {
        $this->lng = $lng;
    }

    /**
    * @return Collection<int, Intervention>
    */
    public function getIntervention(): Collection 
    {
        return $this->interventions;
    }

    public function addIntervention(Intervention $intervention): static 
    {
        if (!$this->interventions->contains($intervention)) {
            $this->interventions->add($intervention);
            $intervention->setHive($this);
        }
        return $this;
    }
}
