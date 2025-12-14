<?php

namespace App\Entity;

use App\Repository\TeacherRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeacherRepository::class)]
class Teacher
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $fullname = null;

    #[ORM\Column(length: 255)]
    private ?string $department = null;

    #[ORM\Column(length: 255)]
    private ?string $position = null;

    /**
     * @var Collection<int, Course>
     */
    #[ORM\OneToMany(targetEntity: Course::class, mappedBy: 'teacher')]
    private Collection $courses;

    /**
     * @var Collection<int, Shedule>
     */
    #[ORM\OneToMany(targetEntity: Shedule::class, mappedBy: 'teacher')]
    private Collection $shedules;

    public function __construct()
    {
        $this->courses = new ArrayCollection();
        $this->shedules = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFullname(): ?string
    {
        return $this->fullname;
    }

    public function setFullname(string $fullname): static
    {
        $this->fullname = $fullname;

        return $this;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(string $department): static
    {
        $this->department = $department;

        return $this;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(string $position): static
    {
        $this->position = $position;

        return $this;
    }

    /**
     * @return Collection<int, Course>
     */
    public function getCourses(): Collection
    {
        return $this->courses;
    }

    public function addCourse(Course $course): static
    {
        if (!$this->courses->contains($course)) {
            $this->courses->add($course);
            $course->setTeacher($this);
        }

        return $this;
    }

    public function removeCourse(Course $course): static
    {
        if ($this->courses->removeElement($course)) {
            // set the owning side to null (unless already changed)
            if ($course->getTeacher() === $this) {
                $course->setTeacher(null);
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
            $shedule->setTeacher($this);
        }

        return $this;
    }

    public function removeShedule(Shedule $shedule): static
    {
        if ($this->shedules->removeElement($shedule)) {
            // set the owning side to null (unless already changed)
            if ($shedule->getTeacher() === $this) {
                $shedule->setTeacher(null);
            }
        }

        return $this;
    }
}
