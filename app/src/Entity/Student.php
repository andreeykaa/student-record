<?php

namespace App\Entity;

use App\Repository\StudentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StudentRepository::class)]
class Student
{
    /**
     * @var array|string[]
     */
    public static array $school_grades = [
        5 => '5 клас',
        6 => '6 клас',
        7 => '7 клас',
        8 => '8 клас',
        9 => '9 клас',
        10 => '10 клас',
        11 => '11 клас',
        12 => '1 курс',
        13 => '2 курс',
        14 => '3 курс',
        15 => '4 курс',
        16 => '5 курс',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $first_name = null;

    #[ORM\Column(length: 255)]
    private ?string $last_name = null;

    #[ORM\Column]
    private ?int $school_grade = null;

    #[ORM\Column(nullable: true)]
    private ?bool $is_active = true;

    #[ORM\Column(nullable: true)]
    private ?int $lessons_count = null;

    /**
     * @var Collection<int, Lesson>
     */
    #[ORM\OneToMany(targetEntity: Lesson::class, mappedBy: 'student')]
    private Collection $lessons;

    #[ORM\OneToOne(inversedBy: 'student', cascade: ['persist', 'remove'])]
    private ?User $user = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deleted = null;

    public function __construct()
    {
        $this->lessons = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(string $first_name): static
    {
        $this->first_name = $first_name;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(string $last_name): static
    {
        $this->last_name = $last_name;

        return $this;
    }

    public function getSchoolGrade(): ?int
    {
        return $this->school_grade;
    }

    public function setSchoolGrade(int $school_grade): static
    {
        $this->school_grade = $school_grade;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->is_active;
    }

    public function setIsActive(?bool $is_active): static
    {
        $this->is_active = $is_active;

        return $this;
    }

    public function getLessonsCount(): ?int
    {
        return $this->lessons_count;
    }

    public function setLessonsCount(?int $lessons_count): static
    {
        $this->lessons_count = $lessons_count;

        return $this;
    }

    public static function getSchoolGradeLabel(?int $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return self::$school_grades[$value] ?? null;
    }

    /**
     * @return Collection<int, Lesson>
     */
    public function getLessons(): Collection
    {
        return $this->lessons;
    }

    public function addLesson(Lesson $lesson): static
    {
        if (!$this->lessons->contains($lesson)) {
            $this->lessons->add($lesson);
            $lesson->setStudent($this);
        }

        return $this;
    }

    public function removeLesson(Lesson $lesson): static
    {
        if ($this->lessons->removeElement($lesson)) {
            // set the owning side to null (unless already changed)
            if ($lesson->getStudent() === $this) {
                $lesson->setStudent(null);
            }
        }

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getDeleted(): ?\DateTimeImmutable
    {
        return $this->deleted;
    }

    public function setDeleted(?\DateTimeImmutable $deleted): static
    {
        $this->deleted = $deleted;

        return $this;
    }
}
