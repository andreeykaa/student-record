<?php

namespace App\Form;

use App\Entity\Lesson;
use App\Entity\Student;
use App\Repository\StudentRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LessonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('student', EntityType::class, [
                'class' => Student::class,
                'choice_label' => function (Student $student) {
                    return trim($student->getFirstName() . ' ' . $student->getLastName());
                },
                'label' => 'Учень',
                'placeholder' => 'Оберіть учня',
                'query_builder' => function (StudentRepository $studentRepository) use ($options) {
                    return $studentRepository->createQueryBuilder('s')
                        ->where('s.deleted IS NULL')
                        ->orderBy('s.last_name', 'ASC')
                        ->addOrderBy('s.first_name', 'ASC');
                },
            ])
            ->add('title', TextType::class, [
                'label' => 'Назва',
                'required' => false,
            ])
            ->add('start_at', DateTimeType::class, [
                'label' => 'Початок',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'model_timezone' => 'UTC',
                'view_timezone' => 'Europe/Kyiv',
                'attr' => [
                    'class' => 'js-lesson-datetime',
                    'autocomplete' => 'off',
                ],
            ])
            ->add('end_at', DateTimeType::class, [
                'label' => 'Кінець',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'model_timezone' => 'UTC',
                'view_timezone' => 'Europe/Kyiv',
                'attr' => [
                    'class' => 'js-lesson-datetime',
                    'autocomplete' => 'off',
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Статус',
                'choices' => array_flip(Lesson::$lesson_statuses),
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Нотатки',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lesson::class,
        ]);
    }
}
