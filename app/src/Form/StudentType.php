<?php

namespace App\Form;

use App\Entity\Student;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StudentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('first_name', TextType::class, [
                'label' => "Ім'я",
                'required' => true,
            ])
            ->add('last_name', TextType::class, [
                'label' => "Прізвище",
                'required' => true,
            ])
            ->add('school_grade', ChoiceType::class, [
                'label' => 'Клас / курс',
                'required' => true,
                'placeholder' => '',
                'choices' => array_flip(Student::$school_grades)
            ])
            ->add('is_active', CheckboxType::class, [
                'label' => 'Активний учень',
                'required' => false,
            ])
            ->add('lessons_count', IntegerType::class, [
                'label' => "Кількість занять",
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Student::class,
        ]);
    }
}
