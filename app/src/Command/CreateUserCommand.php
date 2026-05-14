<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Creates a new application user'
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'Username')
            ->addArgument('password', InputArgument::REQUIRED, 'Plain password')
            ->addOption('admin', null, InputOption::VALUE_NONE, 'Create user with ROLE_ADMIN');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $username = trim((string) $input->getArgument('username'));
        $plainPassword = (string) $input->getArgument('password');

        if ($username === '') {
            $io->error('Username cannot be empty.');

            return Command::FAILURE;
        }

        if ($plainPassword === '') {
            $io->error('Password cannot be empty.');

            return Command::FAILURE;
        }

        $existingUser = $this->userRepository->findOneBy([
            'username' => $username,
        ]);

        if ($existingUser) {
            $io->error(sprintf('User "%s" already exists.', $username));

            return Command::FAILURE;
        }

        $user = new User();
        $user->setUsername($username);

        $roles = $input->getOption('admin')
            ? ['ROLE_ADMIN']
            : ['ROLE_USER'];

        $user->setRoles($roles);

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $plainPassword
        );

        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('User "%s" has been created successfully.', $username));

        return Command::SUCCESS;
    }
}
