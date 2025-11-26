<?php

namespace App\Entity;

use App\Repository\InterventionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InterventionRepository::class)]
class Intervention
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'text')]
    private ?string $note = null;

    #[ORM\ManyToOne(targetEntity: Hive::class, inversedBy: 'interventions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Hive $hive = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable;
    }

    public function getId(): ?int 
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(string $note): static 
    {
        $this->note = $note;
        return $this;
    }

    public function getHive(): ?Hive
    {
        return $this->hive;
    }

    public function setHive(Hive $hive): static
    {
        $this->hive = $hive;
        return $this;
    }
}
