<?php

namespace App\Entity;

use App\Repository\ExamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExamRepository::class)]
class Exam
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'exams')]
    private ?Course $course = null;

    #[ORM\Column]
    private ?\DateTime $examDate = null;

    /**
     * @var Collection<int, Student>
     */
    #[ORM\ManyToMany(targetEntity: Student::class, inversedBy: 'exams')]
    private Collection $students;

    #[ORM\Column(length: 255)]
    private ?string $grades = null;

    public function __construct()
    {
        $this->students = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getExamDate(): ?\DateTime
    {
        return $this->examDate;
    }

    public function setExamDate(\DateTime $examDate): static
    {
        $this->examDate = $examDate;

        return $this;
    }

    /**
     * @return Collection<int, Student>
     */
    public function getStudents(): Collection
    {
        return $this->students;
    }

    public function addStudent(Student $student): static
    {
        if (!$this->students->contains($student)) {
            $this->students->add($student);
        }

        return $this;
    }

    public function removeStudent(Student $student): static
    {
        $this->students->removeElement($student);

        return $this;
    }

    public function getGrades(): ?string
    {
        return $this->grades;
    }

    public function setGrades(string $grades): static
    {
        $this->grades = $grades;

        return $this;
    }
}
