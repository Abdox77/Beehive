<?php

namespace App\Entity;

use App\Repository\HarvesterRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\DBAL\Types\Types;


#[ORM\Entity(repositoryClass: HarvestRepository::class)]
class Harvest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private ?int $weightG = null;

    #[ORM\ManyToOne(inversedBy: 'harvests')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Hive $hive = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;
        return $this;
    }
    
    public function getWeightG(): ?int
    {
        return $this->weightG;
    }
    
    public function setWeightG(int $weightG): static
    {
        $this->weightG = $weightG;
        return $this;
    }

    public function getHive(): ?Hive
    {
        return $this->hive;
    }

    public function setHive(?Hive $hive): static
    {
        $this->hive = $hive;
        return $this;
    }
}
