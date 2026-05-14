<?php

namespace App\Form;

use App\Entity\Student;
use App\Entity\User;
use App\Repository\StudentRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Користувач',
            ])

            ->add('password', PasswordType::class, [
                'label' => $options['is_new'] ? 'Пароль' : 'Новий пароль',
                'mapped' => false,
                'required' => $options['is_new'],
                'empty_data' => '',
                'attr' => [
                    'placeholder' => $options['is_new']
                        ? 'Введіть пароль'
                        : 'Залиште порожнім, якщо не хочете змінювати пароль',
                    'autocomplete' => 'new-password',
                ],
                'constraints' => $options['is_new']
                    ? [
                        new NotBlank(message: 'Введіть пароль', normalizer: 'trim'),
                        new Length(min: 6, normalizer: 'trim', minMessage: 'Пароль має містити щонайменше {{ limit }} символів'),
                    ]
                    : [
                        new Callback(function ($value, ExecutionContextInterface $context) {
                            $password = trim((string) $value);

                            if ($password !== '' && mb_strlen($password) < 6) {
                                $context
                                    ->buildViolation('Пароль має містити щонайменше 6 символів')
                                    ->addViolation();
                            }
                        }),
                    ],
            ])
        ;

        if ($options['current_user'] == null || !in_array('ROLE_ADMIN', $options['current_user']->getRoles())) {
            $builder
                ->add('student', EntityType::class, [
                    'label' => 'Студент',
                    'class' => Student::class,
                    'choice_label' => function (Student $student) {
                        return $student->getLastName() . ' ' . $student->getFirstName();
                    },
                    'placeholder' => 'Оберіть студента',
                    'required' => true,
                    'query_builder' => function (StudentRepository $studentRepository) use ($options) {
                        $qb = $studentRepository->createQueryBuilder('s')
                            ->where('s.deleted IS NULL')
                            ->leftJoin('s.user', 'u')
                            ->orderBy('s.last_name', 'ASC')
                            ->addOrderBy('s.first_name', 'ASC');

                        if ($options['current_user'] instanceof User && $options['current_user']->getId()) {
                            $qb
                                ->andWhere('u.id IS NULL OR u.id = :currentUserId')
                                ->setParameter('currentUserId', $options['current_user']->getId());
                        } else {
                            $qb->andWhere('u.id IS NULL');
                        }

                        return $qb;
                    },
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_new' => false,
            'current_user' => null,
        ]);
    }
}
