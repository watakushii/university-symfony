<?php

namespace App\Message;

/**
 * Message class for notifying students about upcoming exams.
 * 
 * This message is dispatched to trigger the notification process
 * for all students who have exams within the next 3 days.
 * 
 * Example usage in a controller or service:
 * 
 * use App\Message\NotifyStudentsMessage;
 * use Symfony\Component\Messenger\MessageBusInterface;
 * 
 * public function __construct(
 *     private MessageBusInterface $messageBus
 * ) {}
 * 
 * public function notifyStudents(): void
 * {
 *     $message = new NotifyStudentsMessage();
 *     $this->messageBus->dispatch($message);
 * }
 */
final class NotifyStudentsMessage
{
    public function __construct()
    {
        // This message doesn't need any parameters
        // The handler will fetch all students and check their exams
    }
}
