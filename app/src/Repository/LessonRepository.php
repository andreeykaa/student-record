<?php

namespace App\Repository;

use App\Entity\Lesson;
use App\Entity\Student;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Lesson>
 */
class LessonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lesson::class);
    }

    public function findForCalendar(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.start_at < :end')
            ->andWhere('l.end_at > :start')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('l.start_at', 'ASC')
            ->getQuery()->getResult();
    }

    public function countScheduledBetween(\DateTimeImmutable $start, \DateTimeImmutable $end): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.start_at >= :start')
            ->andWhere('l.start_at < :end')
            ->andWhere('l.status = :status')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('status', 'scheduled')
            ->getQuery()->getSingleScalarResult();
    }

    public function findScheduledBetween(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.student', 's')
            ->addSelect('s')
            ->andWhere('l.start_at >= :start')
            ->andWhere('l.start_at < :end')
            ->andWhere('l.status = :status')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('status', 'scheduled')
            ->orderBy('l.start_at', 'ASC')
            ->getQuery()->getResult();
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function findUpcomingByStudent(Student $student, int $limit = 5): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.student = :student')
            ->andWhere('l.start_at >= :now')
            ->setParameter('student', $student)
            ->setParameter('now', new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
            ->orderBy('l.start_at', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function findPastByStudent(Student $student, int $limit = 5): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.student = :student')
            ->andWhere('l.start_at < :now')
            ->setParameter('student', $student)
            ->setParameter('now', new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
            ->orderBy('l.start_at', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }

    public function findScheduledByStudentBetween(Student $student, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.student = :student')
            ->andWhere('l.start_at >= :start')
            ->andWhere('l.start_at < :end')
            ->andWhere('l.status = :status')
            ->setParameter('student', $student)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('status', 'scheduled')
            ->orderBy('l.start_at', 'ASC')
            ->getQuery()->getResult();
    }

    public function countScheduledByStudentBetween(Student $student, \DateTimeImmutable $start, \DateTimeImmutable $end): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.student = :student')
            ->andWhere('l.start_at >= :start')
            ->andWhere('l.start_at < :end')
            ->andWhere('l.status = :status')
            ->setParameter('student', $student)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('status', 'scheduled')
            ->getQuery()->getSingleScalarResult();
    }
}
