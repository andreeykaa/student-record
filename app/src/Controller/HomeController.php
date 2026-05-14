<?php

namespace App\Controller;

use App\Repository\LessonRepository;
use App\Repository\StudentRepository;
use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeZone;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    /**
     * @throws DateMalformedStringException
     */
    #[Route('/', name: 'home')]
    public function index(
        StudentRepository $studentRepository,
        LessonRepository $lessonRepository
    ): Response {
        $timezone = new \DateTimeZone('Europe/Kyiv');

        $todayStart = new \DateTimeImmutable('today', $timezone);
        $todayEnd = $todayStart->modify('+1 day');

        $weekStart = $todayStart->modify('monday this week');
        $weekEnd = $weekStart->modify('+7 days');

        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            $studentsCount = $studentRepository->getStudentsCount();
            $todayLessonsCount = $lessonRepository->countScheduledBetween($todayStart, $todayEnd);
            $weekLessonsCount = $lessonRepository->countScheduledBetween($weekStart, $weekEnd);
            $todayLessons = $lessonRepository->findScheduledBetween($todayStart, $todayEnd);

            return $this->render('home/index.html.twig', [
                'studentsCount' => $studentsCount,
                'todayLessonsCount' => $todayLessonsCount,
                'weekLessonsCount' => $weekLessonsCount,
                'todayLessons' => $todayLessons,
            ]);
        }

        $student = $user?->getStudent();

        if (!$student) {
            throw $this->createAccessDeniedException();
        }

        $upcomingLessons = $lessonRepository->findUpcomingByStudent($student, 5);
        $pastLessons = $lessonRepository->findPastByStudent($student, 5);
        $todayStudentLessons = $lessonRepository->findScheduledByStudentBetween($student, $todayStart, $todayEnd);
        $weekStudentLessonsCount = $lessonRepository->countScheduledByStudentBetween($student, $weekStart, $weekEnd);

        return $this->render('home/index.html.twig', [
            'student' => $student,
            'remainingLessonsCount' => $student->getLessonsCount(),
            'todayStudentLessons' => $todayStudentLessons,
            'weekStudentLessonsCount' => $weekStudentLessonsCount,
            'upcomingLessons' => $upcomingLessons,
            'pastLessons' => $pastLessons,
        ]);
    }
}
