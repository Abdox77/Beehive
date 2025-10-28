<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name:"app_user")]
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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

}
