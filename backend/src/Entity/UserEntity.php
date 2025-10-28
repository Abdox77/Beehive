<?php

namespace App\Entity;

use App\Repository\UserEntityRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserEntityRepository::class)]
class UserEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer")]
    private ?int $id;

    #[ORM\Column(type:"string", length:255, nullable: false, unique: true)]
    private ?string $username;


    #[ORM\Column(type:"string", length:255, nullable: false, unique: true)]
    private ?string $email;

    #[ORM\Column(type:"string", length:255, nullable: false)]
    private ?string $password = null;


    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
