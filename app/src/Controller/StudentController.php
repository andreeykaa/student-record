<?php

namespace App\Controller;

use App\Entity\Student;
use App\Form\StudentType;
use App\Repository\StudentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/student')]
final class StudentController extends AbstractController
{
    #[Route(name: 'app_student_index', methods: ['GET'])]
    public function index(StudentRepository $studentRepository): Response
    {
        $students = $studentRepository->getStudents();

        if ($students) {
            foreach ($students as $key => $value) {
                $students[$key]['isActive'] = $value['isActive'] ? 'Активний' : 'Неактивний';
                $students[$key]['schoolGrade'] = Student::getSchoolGradeLabel(
                    $value['schoolGrade'] !== null ? (int) $value['schoolGrade'] : null
                );
            }
        }

        return $this->render('student/index.html.twig', [
            'students' => $students,
        ]);
    }

    #[Route('/new', name: 'app_student_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $student = new Student();
        $form = $this->createForm(StudentType::class, $student);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($student);
            $em->flush();

            return $this->redirectToRoute('app_student_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('student/new.html.twig', [
            'student' => $student,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_student_show', methods: ['GET'])]
    public function show(Student $student): Response
    {
        $studentLessons = $student->getLessons()->toArray();

        usort($studentLessons, function ($a, $b) {
            return $b->getStartAt() <=> $a->getStartAt();
        });

        return $this->render('student/show.html.twig', [
            'student' => $student,
            'studentLessons' => $studentLessons,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_student_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Student $student, EntityManagerInterface $em): Response
    {
        if ($student->getDeleted()) {
            $this->addFlash('error', 'Студент видалений');
            return $this->redirectToRoute('app_student_index', [], Response::HTTP_SEE_OTHER);
        }

        $form = $this->createForm(StudentType::class, $student);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('app_student_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('student/edit.html.twig', [
            'student' => $student,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_student_delete', methods: ['POST'])]
    public function delete(Student $student, EntityManagerInterface $em): Response
    {
        if ($student->getDeleted()) {
            $this->addFlash('error', 'Студент вже видалений');
            return $this->redirectToRoute('app_student_index', [], Response::HTTP_SEE_OTHER);
        }

        $student->setDeleted(new \DateTimeImmutable());
        $student->setUser(null);
        $em->flush();

        return $this->redirectToRoute('app_student_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/students/search', name: 'app_student_search_ajax', methods: ['GET'])]
    public function searchAjax(Request $request, StudentRepository $studentRepository): JsonResponse
    {
        $query = trim((string) $request->query->get('q', ''));

        if (mb_strlen($query) < 2) {
            return $this->json([
                'students' => [],
            ]);
        }

        $students = $studentRepository->searchByFirstNameOrLastName($query);

        $data = array_map(function ($student) {
            return [
                'id' => $student->getId(),
                'full_name' => trim($student->getFirstName() . ' ' . $student->getLastName()),
                'url' => $this->generateUrl('app_student_show', [
                    'id' => $student->getId(),
                ]),
            ];
        }, $students);

        return $this->json([
            'students' => $data,
        ]);
    }
}
