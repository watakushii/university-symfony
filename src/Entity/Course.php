<?php

namespace App\Entity;

use App\Repository\CourseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CourseRepository::class)]
class Course
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?int $credits = null;

    #[ORM\ManyToOne(inversedBy: 'courses')]
    private ?Teacher $teacher = null;

    /**
     * @var Collection<int, Enrollment>
     */
    #[ORM\OneToMany(targetEntity: Enrollment::class, mappedBy: 'course')]
    private Collection $grade;

    /**
     * @var Collection<int, Shedule>
     */
    #[ORM\OneToMany(targetEntity: Shedule::class, mappedBy: 'course')]
    private Collection $shedules;

    /**
     * @var Collection<int, Exam>
     */
    #[ORM\OneToMany(targetEntity: Exam::class, mappedBy: 'course')]
    private Collection $exams;

    public function __construct()
    {
        $this->grade = new ArrayCollection();
        $this->shedules = new ArrayCollection();
        $this->exams = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCredits(): ?int
    {
        return $this->credits;
    }

    public function setCredits(int $credits): static
    {
        $this->credits = $credits;

        return $this;
    }

    public function getTeacher(): ?Teacher
    {
        return $this->teacher;
    }

    public function setTeacher(?Teacher $teacher): static
    {
        $this->teacher = $teacher;

        return $this;
    }

    /**
     * @return Collection<int, Enrollment>
     */
    public function getGrade(): Collection
    {
        return $this->grade;
    }

    public function addGrade(Enrollment $grade): static
    {
        if (!$this->grade->contains($grade)) {
            $this->grade->add($grade);
            $grade->setCourse($this);
        }

        return $this;
    }

    public function removeGrade(Enrollment $grade): static
    {
        if ($this->grade->removeElement($grade)) {
            // set the owning side to null (unless already changed)
            if ($grade->getCourse() === $this) {
                $grade->setCourse(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Shedule>
     */
    public function getShedules(): Collection
    {
        return $this->shedules;
    }

    public function addShedule(Shedule $shedule): static
    {
        if (!$this->shedules->contains($shedule)) {
            $this->shedules->add($shedule);
            $shedule->setCourse($this);
        }

        return $this;
    }

    public function removeShedule(Shedule $shedule): static
    {
        if ($this->shedules->removeElement($shedule)) {
            // set the owning side to null (unless already changed)
            if ($shedule->getCourse() === $this) {
                $shedule->setCourse(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Exam>
     */
    public function getExams(): Collection
    {
        return $this->exams;
    }

    public function addExam(Exam $exam): static
    {
        if (!$this->exams->contains($exam)) {
            $this->exams->add($exam);
            $exam->setCourse($this);
        }

        return $this;
    }

    public function removeExam(Exam $exam): static
    {
        if ($this->exams->removeElement($exam)) {
            // set the owning side to null (unless already changed)
            if ($exam->getCourse() === $this) {
                $exam->setCourse(null);
            }
        }

        return $this;
    }
}
