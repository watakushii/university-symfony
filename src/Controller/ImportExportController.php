<?php

namespace App\Controller;

use App\Message\ImportStudentsMessage;
use App\Service\ReportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Controller for importing students and exporting course reports.
 * 
 * Example usage:
 * 
 * 1. Import students from CSV/XLSX:
 *    POST /import/students
 *    Form data: file (CSV or XLSX file)
 * 
 * 2. Export course report:
 *    GET /export/course/{courseId}?format=csv
 *    GET /export/course/{courseId}?format=pdf
 */
class ImportExportController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly ReportService $reportService,
        private readonly ValidatorInterface $validator
    ) {
    }

    /**
     * Import students from CSV/XLSX file (asynchronous).
     * 
     * @Route("/import/students", name="import_students", methods={"POST"})
     */
    public function importStudents(Request $request): Response
    {
        $file = $request->files->get('file');

        if (!$file) {
            return $this->json([
                'success' => false,
                'error' => 'No file uploaded'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validate file
        $violations = $this->validator->validate($file, [
            new File([
                'maxSize' => '10M',
                'mimeTypes' => [
                    'text/csv',
                    'text/plain',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ],
                'mimeTypesMessage' => 'Please upload a valid CSV or XLSX file',
            ])
        ]);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }

            return $this->json([
                'success' => false,
                'errors' => $errors
            ], Response::HTTP_BAD_REQUEST);
        }

        // Move uploaded file to temporary directory
        $uploadDir = sys_get_temp_dir();
        $filename = sprintf(
            'import_%s_%s.%s',
            date('Y-m-d_His'),
            uniqid(),
            $file->guessExtension()
        );
        $filePath = $uploadDir . DIRECTORY_SEPARATOR . $filename;
        $file->move($uploadDir, $filename);

        // Dispatch message for asynchronous processing
        $message = new ImportStudentsMessage($filePath);
        $this->messageBus->dispatch($message);

        return $this->json([
            'success' => true,
            'message' => 'Import process started. Check logs for progress.',
            'file' => $filename
        ]);
    }

    /**
     * Export course report in CSV or PDF format.
     * 
     * @Route("/export/course/{courseId}", name="export_course_report", methods={"GET"})
     */
    public function exportCourseReport(int $courseId, Request $request): Response
    {
        $format = $request->query->get('format', 'csv');

        if (!in_array($format, ['csv', 'pdf'])) {
            return $this->json([
                'success' => false,
                'error' => 'Invalid format. Use "csv" or "pdf"'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $filePath = $this->reportService->exportCourseReport($courseId, $format);

            if (!file_exists($filePath)) {
                throw new \RuntimeException('Generated file not found');
            }

            $filename = basename($filePath);
            $content = file_get_contents($filePath);

            // Clean up temporary file
            @unlink($filePath);

            $response = new Response($content);
            $disposition = $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $filename
            );
            $response->headers->set('Content-Disposition', $disposition);

            if ($format === 'csv') {
                $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
            } else {
                $response->headers->set('Content-Type', 'application/pdf');
            }

            return $response;
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display import form (optional - for testing).
     * 
     * @Route("/import/form", name="import_form", methods={"GET"})
     */
    public function importForm(): Response
    {
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Import Students</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        form { border: 1px solid #ddd; padding: 20px; border-radius: 5px; }
        input[type="file"] { margin: 10px 0; }
        button { background-color: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #45a049; }
        .info { background-color: #e7f3ff; padding: 15px; margin-bottom: 20px; border-left: 4px solid #2196F3; }
    </style>
</head>
<body>
    <h1>Import Students from CSV/XLSX</h1>
    
    <div class="info">
        <strong>File Format:</strong> CSV or XLSX<br>
        <strong>Required Columns:</strong> fullname, email, status, gpa (optional)<br>
        <strong>Max Size:</strong> 10MB<br>
        <strong>Processing:</strong> Asynchronous (check logs for progress)
    </div>
    
    <form id="importForm" enctype="multipart/form-data">
        <div>
            <label for="file">Select File:</label>
            <input type="file" id="file" name="file" accept=".csv,.xlsx,.xls" required>
        </div>
        <button type="submit">Import Students</button>
    </form>
    
    <div id="result" style="margin-top: 20px;"></div>
    
    <script>
        document.getElementById('importForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData();
            formData.append('file', document.getElementById('file').files[0]);
            
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<p>Uploading and processing...</p>';
            
            try {
                const response = await fetch('/import/students', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    resultDiv.innerHTML = '<p style="color: green;">✓ ' + data.message + '</p>';
                } else {
                    resultDiv.innerHTML = '<p style="color: red;">✗ Error: ' + (data.error || JSON.stringify(data.errors)) + '</p>';
                }
            } catch (error) {
                resultDiv.innerHTML = '<p style="color: red;">✗ Error: ' + error.message + '</p>';
            }
        });
    </script>
</body>
</html>
HTML;

        return new Response($html);
    }
}

