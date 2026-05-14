<?php

namespace App\Controller;

use App\Entity\Lesson;
use App\Form\LessonType;
use App\Repository\LessonRepository;
use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[IsGranted('ROLE_ADMIN')]
#[Route('/lesson')]
final class LessonController extends AbstractController
{
    #[Route(name: 'app_lesson_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $lesson = new Lesson();
        $form = $this->createForm(LessonType::class, $lesson);

        return $this->render('lesson/index.html.twig', [
            'lessonForm' => $form->createView(),
            'openLessonId' => $request->query->get('open_lesson'),
            'initialDate' => $request->query->get('date'),
        ]);
    }

    /**
     * @throws DateMalformedStringException
     */
    #[Route('/events', name: 'app_lesson_events', methods: ['GET'])]
    public function events(Request $request, LessonRepository $lessonRepository): JsonResponse
    {
        $timezone = new DateTimeZone('Europe/Kyiv');
        $start = new DateTimeImmutable($request->query->get('start'))->setTimezone($timezone);
        $end = new DateTimeImmutable($request->query->get('end'))->setTimezone($timezone);

        $lessons = $lessonRepository->findForCalendar($start, $end);

        $events = [];

        foreach ($lessons as $lesson) {
            $student = $lesson->getStudent();
            $studentName = $student
                ? trim($student->getFirstName() . ' ' . $student->getLastName())
                : 'Без учня';

            $events[] = [
                'id' => $lesson->getId(),
                'studentName' => $studentName,
                'title' => $lesson->getTitle(),
                'start' => $lesson->getStartAt()?->setTimezone($timezone)->format('Y-m-d\TH:i:s'),
                'end' => $lesson->getEndAt()?->setTimezone($timezone)->format('Y-m-d\TH:i:s'),
                'backgroundColor' => match ($lesson->getStatus()) {
                    'completed' => '#198754',
                    'canceled' => '#dc3545',
                    default => '#0d6efd',
                },
                'borderColor' => match ($lesson->getStatus()) {
                    'completed' => '#198754',
                    'canceled' => '#dc3545',
                    default => '#0d6efd',
                },
                'extendedProps' => [
                    'studentId' => $student?->getId(),
                    'studentName' => $studentName,
                    'status' => $lesson->getStatus(),
                    'notes' => $lesson->getNotes(),
                ],
            ];
        }
        return $this->json($events);
    }

    #[Route('/create', name: 'app_lesson_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $lesson = new Lesson();
        $form = $this->createForm(LessonType::class, $lesson);

        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->jsonFormErrors($form);
        }

        $lesson->setCreatedAt(new DateTimeImmutable());
        $lesson->setUpdatedAt(new DateTimeImmutable());

        $em->persist($lesson);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Заняття створено',
        ]);
    }

    #[Route('/{id}/update', name: 'app_lesson_update', methods: ['POST'])]
    public function update(Lesson $lesson, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $oldStatus = $lesson->getStatus();

        $form = $this->createForm(LessonType::class, $lesson);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->jsonFormErrors($form);
        }

        $this->writeOffLessonIfNeeded($lesson, $oldStatus);
        $lesson->setUpdatedAt(new DateTimeImmutable());

        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Заняття оновлено',
        ]);
    }

    #[Route('/{id}/update-time', name: 'app_lesson_update_time', methods: ['POST'])]
    public function updateTime(Lesson $lesson, Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->isCsrfTokenValid('lesson_update_time', (string) $request->request->get('_token'))) {
            return $this->json([
                'success' => false,
                'message' => 'Невірний CSRF token.',
            ], 400);
        }

        $startRaw = (string) $request->request->get('start_at');
        $endRaw = (string) $request->request->get('end_at');

        if ($startRaw === '' || $endRaw === '') {
            return $this->json([
                'success' => false,
                'message' => 'Не передано дату або час.',
            ], 400);
        }

        try {
            $kyiv = new DateTimeZone('Europe/Kyiv');
            $utc = new DateTimeZone('UTC');

            $startAt = new DateTimeImmutable($startRaw, $kyiv)->setTimezone($utc);
            $endAt = new DateTimeImmutable($endRaw, $kyiv)->setTimezone($utc);
        } catch (Throwable) {
            return $this->json([
                'success' => false,
                'message' => 'Невірний формат дати.',
            ], 400);
        }

        if ($endAt <= $startAt) {
            return $this->json([
                'success' => false,
                'message' => 'Час завершення має бути пізніше за час початку.',
            ], 400);
        }

        $lesson->setStartAt($startAt);
        $lesson->setEndAt($endAt);
        $lesson->setUpdatedAt(new DateTimeImmutable());

        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Час заняття оновлено.',
        ]);
    }

    private function jsonFormErrors(FormInterface $form): JsonResponse
    {
        $errors = [];

        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        if (!$form->isSubmitted()) {
            $errors[] = 'Форма не була відправлена';
        }

        return $this->json([
            'success' => false,
            'errors' => $errors,
        ], 400);
    }

    private function writeOffLessonIfNeeded(Lesson $lesson, string $oldStatus): void
    {
        $newStatus = $lesson->getStatus();
        $student = $lesson->getStudent();

        if ($oldStatus !== 'completed' && $newStatus === 'completed' && !$lesson->getIsWrittenOff()) {
            $student?->setLessonsCount($student->getLessonsCount() - 1);
            $lesson->setIsWrittenOff(true);
        }
    }
}
