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
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;


    #[ORM\Column]
    #[Assert\Range(min: -90, max: 90)]
    private ?float $lat = null;

    #[ORM\Column]
    #[Assert\Range(min: -180, max: 180)]
    private ?float $lng = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'hives')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;


    #[ORM\OneToMany(mappedBy: 'hive', targetEntity: Intervention::class, cascade: ['persist'])]
    private Collection $interventions;

    #[ORM\OneToMany(mappedBy: 'hive', targetEntity: Harvest::class, cascade: ['persist', 'remove'])]
    private Collection $harvests;

    #[ORM\Column(length: 255)]
    private ?string $Hive = null;

    public function __construct()
    {
        $this->interventions = new ArrayCollection();
        $this->harvests = new ArrayCollection();
        $this->createAt = new \DateTimeImmutable();
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

    public function getLat(): ?float
    {
        return $this->lat;
    }

    public function setLat(float $lat): static
    {
        $this->lat = $lat;
        return $this;
    }
    
    public function getLng(): ?float
    {
        return $this->lng;
    }

    public function setLng(float $lng): static
    {
        $this->lng = $lng;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static 
    {
        $this->owner = $owner;
        return $this;
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

    /**
     * @return Collection<int, Harvest>
     */
    public function getHarvest(): Collection
    {
        return $this->harvests;
    }

    public function addHarvest(Harvest $harvest): static
    {
        if (!$this->harvests->contains($harvest)) {
            $this->harvests->add($harvest);
            $harvest->setHive($this);
        }
        return $this;
    }

    public function removeHarvest(Harvest $harvest): static
    {
        if ($this->harvests->removeElement($harvest)) {
            if($harvest->getHive() == $this) {
                $harvest->setHive(null);
            }
        }
        return $this;
    }

    public function getHive(): ?string
    {
        return $this->Hive;
    }

    public function setHive(string $Hive): static
    {
        $this->Hive = $Hive;

        return $this;
    }
}
