<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Command to create a user with a specific role.
 * 
 * Usage:
 * php bin/console app:create-user admin@university.edu password123 ROLE_ADMIN
 * php bin/console app:create-user teacher@university.edu password123 ROLE_TEACHER
 * php bin/console app:create-user student@university.edu password123 ROLE_STUDENT
 */
#[AsCommand(
    name: 'app:create-user',
    description: 'Create a new user with specified role'
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'User email')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'User password')
            ->addOption('role', null, InputOption::VALUE_REQUIRED, 'User role (ROLE_ADMIN, ROLE_TEACHER, ROLE_STUDENT)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getOption('email');
        $password = $input->getOption('password');
        $role = $input->getOption('role');

        if (!$email || !$password || !$role) {
            $io->error('Please provide email, password, and role options');
            $io->note('Example: php bin/console app:create-user --email=admin@university.edu --password=admin123 --role=ROLE_ADMIN');
            return Command::FAILURE;
        }

        // Validate role
        $validRoles = ['ROLE_ADMIN', 'ROLE_TEACHER', 'ROLE_STUDENT'];
        if (!in_array($role, $validRoles)) {
            $io->error("Invalid role. Must be one of: " . implode(', ', $validRoles));
            return Command::FAILURE;
        }

        // Check if user already exists
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if ($existingUser) {
            $io->error("User with email {$email} already exists");
            return Command::FAILURE;
        }

        // Create user
        $user = new User();
        $user->setEmail($email);
        $user->setRoles([$role]);
        
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success("User created successfully!");
        $io->table(
            ['Property', 'Value'],
            [
                ['Email', $user->getEmail()],
                ['Role', $role],
                ['ID', $user->getId()],
            ]
        );

        return Command::SUCCESS;
    }
}

