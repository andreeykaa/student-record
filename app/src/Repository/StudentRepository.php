<?php

namespace App\Repository;

use App\Entity\Student;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Student>
 */
class StudentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Student::class);
    }

    public function getStudentsCount(): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.deleted IS NULL')
            ->andWhere('s.is_active = 1')
            ->getQuery()->getSingleScalarResult();
    }

    public function getStudents(): array
    {
        return $this->createQueryBuilder('s')
            ->select('
                s.id AS id,
                s.first_name AS firstName,
                s.last_name AS lastName,
                s.is_active AS isActive,
                s.school_grade AS schoolGrade,
                s.lessons_count AS lessonsCount
            ')
            ->where('s.deleted IS NULL')
            ->orderBy('s.first_name', 'ASC')
            ->getQuery()->getArrayResult();
    }

    public function searchByFirstNameOrLastName(string $query, int $limit = 8): array
    {
        $query = mb_strtolower(trim($query));

        return $this->createQueryBuilder('s')
            ->where('s.deleted IS NULL')
            ->andWhere('LOWER(s.first_name) LIKE :query OR LOWER(s.last_name) LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('s.last_name', 'ASC')
            ->addOrderBy('s.first_name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }
}
