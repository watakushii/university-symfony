<?php

namespace App\Entity;

use App\Repository\EnrollmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EnrollmentRepository::class)]
class Enrollment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'enrollments')]
    private ?Student $student = null;

    #[ORM\ManyToOne(inversedBy: 'grade')]
    private ?Course $course = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 3, scale: 2, nullable: true)]
    private ?string $grade = null;

    #[ORM\Column]
    private ?\DateTime $enrolledAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStudent(): ?Student
    {
        return $this->student;
    }

    public function setStudent(?Student $student): static
    {
        $this->student = $student;

        return $this;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): static
    {
        $this->course = $course;

        return $this;
    }

    public function getGrade(): ?string
    {
        return $this->grade;
    }

    public function setGrade(?string $grade): static
    {
        $this->grade = $grade;

        return $this;
    }

    public function getEnrolledAt(): ?\DateTime
    {
        return $this->enrolledAt;
    }

    public function setEnrolledAt(\DateTime $enrolledAt): static
    {
        $this->enrolledAt = $enrolledAt;

        return $this;
    }
}
