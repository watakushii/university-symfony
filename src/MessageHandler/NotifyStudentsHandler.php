<?php

namespace App\MessageHandler;

use App\Entity\Exam;
use App\Entity\Student;
use App\Message\NotifyStudentsMessage;
use App\Repository\StudentRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler for NotifyStudentsMessage.
 * 
 * This handler:
 * - Fetches all students from the database
 * - Checks if each student has an upcoming exam within 3 days
 * - Prepares and logs notification messages for each student
 * 
 * The handler uses Redis transport for asynchronous processing.
 * 
 * Note: In Symfony 6+, MessageHandlerInterface is no longer needed.
 * The #[AsMessageHandler] attribute is sufficient.
 */
#[AsMessageHandler]
final class NotifyStudentsHandler
{
    private const DAYS_AHEAD = 3;

    public function __construct(
        private readonly StudentRepository $studentRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(NotifyStudentsMessage $message): void
    {
        $this->logger->info('Starting student notification process for upcoming exams');

        // Fetch all students from the database
        $students = $this->studentRepository->findAll();
        
        $notifiedCount = 0;
        $now = new \DateTime();
        $threeDaysFromNow = (clone $now)->modify('+' . self::DAYS_AHEAD . ' days');

        foreach ($students as $student) {
            $upcomingExams = $this->getUpcomingExamsForStudent($student, $now, $threeDaysFromNow);

            if (count($upcomingExams) > 0) {
                $this->notifyStudent($student, $upcomingExams);
                $notifiedCount++;
            }
        }

        $this->logger->info(
            sprintf(
                'Notification process completed. Notified %d students out of %d total students.',
                $notifiedCount,
                count($students)
            )
        );
    }

    /**
     * Get upcoming exams for a student within the specified date range.
     *
     * @param Student $student
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return Exam[]
     */
    private function getUpcomingExamsForStudent(Student $student, \DateTime $startDate, \DateTime $endDate): array
    {
        $upcomingExams = [];

        foreach ($student->getExams() as $exam) {
            $examDate = $exam->getExamDate();
            
            if ($examDate && $examDate >= $startDate && $examDate <= $endDate) {
                $upcomingExams[] = $exam;
            }
        }

        return $upcomingExams;
    }

    /**
     * Prepare and send notification for a student about their upcoming exams.
     *
     * @param Student $student
     * @param Exam[] $exams
     */
    private function notifyStudent(Student $student, array $exams): void
    {
        $examDetails = [];
        foreach ($exams as $exam) {
            $course = $exam->getCourse();
            $courseName = $course ? $course->getName() : 'Unknown Course';
            $examDate = $exam->getExamDate();
            
            $examDetails[] = sprintf(
                '- %s on %s',
                $courseName,
                $examDate ? $examDate->format('Y-m-d H:i') : 'TBD'
            );
        }

        $message = sprintf(
            "Dear %s,\n\n" .
            "You have %d upcoming exam(s) within the next %d days:\n\n" .
            "%s\n\n" .
            "Please prepare accordingly.\n\n" .
            "Best regards,\n" .
            "University System",
            $student->getFullname(),
            count($exams),
            self::DAYS_AHEAD,
            implode("\n", $examDetails)
        );

        // Log the notification (in production, you would send an email here)
        $this->logger->info(
            sprintf(
                'Notification prepared for student: %s (ID: %d, Email: %s)',
                $student->getFullname(),
                $student->getId(),
                $student->getEmail()
            ),
            [
                'student_id' => $student->getId(),
                'student_email' => $student->getEmail(),
                'exams_count' => count($exams),
                'notification_message' => $message,
            ]
        );

        // TODO: In production, send actual email using Symfony Mailer:
        // $email = (new Email())
        //     ->from('noreply@university.edu')
        //     ->to($student->getEmail())
        //     ->subject('Upcoming Exams Reminder')
        //     ->text($message);
        // $this->mailer->send($email);
    }
}

