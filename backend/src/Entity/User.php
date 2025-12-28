<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Table(name:"app_user")]
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->hives = new ArrayCollection();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer")]
    private ?int $id;

    #[ORM\Column(type:"string", length:255, nullable: false)]
    private ?string $username = null;

    #[ORM\Column(type:"string", nullable: false, unique: true)]
    private ?string $email = null;

    #[ORM\Column(type:"string", length:255, nullable: false)]
    private ?string $password = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(mappedBy:"owner", targetEntity: Hive::class, cascade: ["persist", "remove"], orphanRemoval: true)]
    private Collection $hives;


    public function getId(): int
    {
        return $this->id;
    }

    public function getUserName(): string
    {
        return $this->username;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Summary of setUserName
     * @param string $username
     * @return void
     */
    public function setUserName(string $username): void
    {
        $this->username = $username;
    }

    /**
     * Summary of setPassword
     * @param string $password
     * @return void
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * Summary of setEmail
     * @param string $email
     * @return void
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getHives(): ?Collection {
        return $this->hives ?? [];
    }

    public function addHive(Hive $hive): static {
        if (!$this->hives->contains($hive)) {
            $this->hives->add($hive);
            $hive->setOwner($this);
        }
        return $this;
    }

    public function removeHive(Hive $hive): static {
        if ($this->hives->contains($hive)) {
            $this->hives->removeElement($hive);
            $hive->setOwner(null);
        }
        return $this;
    }

    /**
     * Returns the identifier for this user (e.g. email)
     */
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * Returns the roles granted to the user.
     */
    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    /**
     * Removes sensitive data from the user.
     */
    public function eraseCredentials(): void
    {
    }
}
