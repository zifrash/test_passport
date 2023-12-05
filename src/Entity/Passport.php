<?php

namespace App\Entity;

use App\Repository\PassportRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PassportRepository::class)]
#[ORM\Index(columns: ['series', 'number'], name: 'PASSPORT_SERIES_NUMBER_IDX')]
class Passport
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?string $series = null;

    #[ORM\Column]
    private ?string $number = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSeries(): ?string
    {
        return $this->series;
    }

    public function setSeries(string $series): static
    {
        $this->series = $series;

        return $this;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(string $number): static
    {
        $this->number = $number;

        return $this;
    }
}
