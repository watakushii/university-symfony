<?php

namespace App\MessageHandler;

use App\Entity\Student;
use App\Message\ImportStudentsMessage;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler for ImportStudentsMessage.
 * 
 * This handler:
 * - Parses CSV/XLSX files
 * - Validates each row (fullName, email, status, GPA)
 * - Creates and persists Student entities via Doctrine
 * - Logs errors for invalid rows
 * 
 * The handler uses Redis transport for asynchronous processing.
 * 
 * Note: In Symfony 6+, MessageHandlerInterface is no longer needed.
 * The #[AsMessageHandler] attribute is sufficient.
 */
#[AsMessageHandler]
final class ImportStudentsHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(ImportStudentsMessage $message): void
    {
        $filePath = $message->getFilePath();

        if (!file_exists($filePath)) {
            $this->logger->error("Import file not found: {$filePath}");
            return;
        }

        $this->logger->info("Starting student import from file: {$filePath}");

        try {
            $data = $this->parseFile($filePath);
            $results = $this->importStudents($data);

            $this->logger->info(
                sprintf(
                    'Import completed. Success: %d, Errors: %d',
                    $results['success'],
                    $results['errors']
                ),
                ['file' => $filePath]
            );
        } catch (\Exception $e) {
            $this->logger->error(
                "Error during import: {$e->getMessage()}",
                ['file' => $filePath, 'exception' => $e]
            );
        }
    }

    /**
     * Parse CSV or XLSX file and return array of rows.
     *
     * @param string $filePath
     * @return array
     * @throws \Exception
     */
    private function parseFile(string $filePath): array
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($extension === 'csv') {
            return $this->parseCsv($filePath);
        } elseif (in_array($extension, ['xlsx', 'xls'])) {
            return $this->parseXlsx($filePath);
        } else {
            throw new \InvalidArgumentException("Unsupported file format: {$extension}");
        }
    }

    /**
     * Parse CSV file.
     *
     * @param string $filePath
     * @return array
     */
    private function parseCsv(string $filePath): array
    {
        $data = [];
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \RuntimeException("Could not open CSV file: {$filePath}");
        }

        // Read header row
        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            throw new \RuntimeException("Could not read CSV header");
        }

        // Normalize headers (trim, lowercase)
        $headers = array_map('trim', array_map('strtolower', $headers));

        $rowNumber = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            $rowData = array_combine($headers, $row);
            if ($rowData !== false) {
                $data[] = $rowData;
            }
        }

        fclose($handle);
        return $data;
    }

    /**
     * Parse XLSX/XLS file using PhpSpreadsheet (if available).
     *
     * @param string $filePath
     * @return array
     * @throws \Exception
     */
    private function parseXlsx(string $filePath): array
    {
        // Check if PhpSpreadsheet is available
        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            throw new \RuntimeException(
                "XLSX support requires phpoffice/phpspreadsheet package. " .
                "Install it with: composer require phpoffice/phpspreadsheet. " .
                "Or convert your file to CSV format."
            );
        }

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $data = [];

            // Get header row
            $headers = [];
            $headerRow = $worksheet->getRowIterator(1, 1)->current();
            foreach ($headerRow->getCellIterator() as $cell) {
                $headers[] = trim(strtolower($cell->getValue()));
            }

            // Get data rows
            $rowIterator = $worksheet->getRowIterator(2);
            foreach ($rowIterator as $row) {
                $rowData = [];
                $cellIterator = $row->getCellIterator();
                $colIndex = 0;

                foreach ($cellIterator as $cell) {
                    if (isset($headers[$colIndex])) {
                        $rowData[$headers[$colIndex]] = $cell->getValue();
                    }
                    $colIndex++;
                }

                if (!empty($rowData)) {
                    $data[] = $rowData;
                }
            }

            return $data;
        } catch (\Exception $e) {
            throw new \RuntimeException("Error reading XLSX file: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Import students from parsed data.
     *
     * @param array $data
     * @return array{success: int, errors: int}
     */
    private function importStudents(array $data): array
    {
        $success = 0;
        $errors = 0;
        $rowNumber = 1;

        foreach ($data as $row) {
            $rowNumber++;

            try {
                $validation = $this->validateRow($row, $rowNumber);

                if (!$validation['valid']) {
                    $this->logger->warning(
                        "Invalid row {$rowNumber}: {$validation['error']}",
                        ['row' => $row]
                    );
                    $errors++;
                    continue;
                }

                $student = $this->createStudent($validation['data']);
                $this->entityManager->persist($student);
                $success++;

                // Flush in batches of 50
                if ($success % 50 === 0) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                }
            } catch (\Exception $e) {
                $this->logger->error(
                    "Error processing row {$rowNumber}: {$e->getMessage()}",
                    ['row' => $row, 'exception' => $e]
                );
                $errors++;
            }
        }

        // Flush remaining entities
        $this->entityManager->flush();

        return ['success' => $success, 'errors' => $errors];
    }

    /**
     * Validate a row of data.
     *
     * @param array $row
     * @param int $rowNumber
     * @return array{valid: bool, error?: string, data?: array}
     */
    private function validateRow(array $row, int $rowNumber): array
    {
        // Normalize keys (handle various column name variations)
        $normalizedRow = [];
        foreach ($row as $key => $value) {
            $normalizedKey = strtolower(trim($key));
            $normalizedRow[$normalizedKey] = trim($value);
        }

        // Extract values with fallback for different column names
        $fullname = $normalizedRow['fullname'] ?? $normalizedRow['full_name'] ?? $normalizedRow['name'] ?? null;
        $email = $normalizedRow['email'] ?? null;
        $status = $normalizedRow['status'] ?? null;
        $gpa = $normalizedRow['gpa'] ?? $normalizedRow['grade'] ?? null;

        // Validate required fields
        if (empty($fullname)) {
            return ['valid' => false, 'error' => 'Missing fullname'];
        }

        if (empty($email)) {
            return ['valid' => false, 'error' => 'Missing email'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'error' => 'Invalid email format'];
        }

        if (empty($status)) {
            return ['valid' => false, 'error' => 'Missing status'];
        }

        // Validate GPA if provided
        if ($gpa !== null && $gpa !== '') {
            $gpaValue = (float) $gpa;
            if ($gpaValue < 0 || $gpaValue > 4.0) {
                return ['valid' => false, 'error' => 'GPA must be between 0 and 4.0'];
            }
        }

        // Check if student with this email already exists
        $existingStudent = $this->entityManager->getRepository(Student::class)
            ->findOneBy(['email' => $email]);

        if ($existingStudent) {
            return ['valid' => false, 'error' => "Student with email {$email} already exists"];
        }

        return [
            'valid' => true,
            'data' => [
                'fullname' => $fullname,
                'email' => $email,
                'status' => $status,
                'gpa' => $gpa !== null && $gpa !== '' ? (string) $gpa : null,
            ]
        ];
    }

    /**
     * Create a Student entity from validated data.
     *
     * @param array $data
     * @return Student
     */
    private function createStudent(array $data): Student
    {
        $student = new Student();
        $student->setFullname($data['fullname']);
        $student->setEmail($data['email']);
        $student->setStatus($data['status']);

        if (isset($data['gpa']) && $data['gpa'] !== null) {
            $student->setGpa($data['gpa']);
        }

        return $student;
    }
}

