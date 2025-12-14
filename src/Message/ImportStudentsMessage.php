<?php

namespace App\Message;

/**
 * Message class for importing students from CSV/XLSX files.
 * 
 * This message is dispatched to trigger the asynchronous import process.
 * 
 * Example usage in a controller:
 * 
 * use App\Message\ImportStudentsMessage;
 * use Symfony\Component\Messenger\MessageBusInterface;
 * 
 * public function __construct(
 *     private MessageBusInterface $messageBus
 * ) {}
 * 
 * public function importStudents(string $filePath): void
 * {
 *     $message = new ImportStudentsMessage($filePath);
 *     $this->messageBus->dispatch($message);
 * }
 */
final class ImportStudentsMessage
{
    public function __construct(
        private readonly string $filePath
    ) {
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }
}

