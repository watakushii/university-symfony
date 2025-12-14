<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\Enrollment;
use App\Repository\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use League\Csv\Writer;

/**
 * Service for generating course/student reports in CSV or PDF format.
 */
class ReportService
{
    public function __construct(
        private readonly CourseRepository $courseRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Export course report with all students and their grades.
     *
     * @param int $courseId
     * @param string $format 'csv' or 'pdf'
     * @return string Path to the generated file
     * @throws \Exception
     */
    public function exportCourseReport(int $courseId, string $format = 'csv'): string
    {
        $course = $this->courseRepository->find($courseId);

        if (!$course) {
            throw new \InvalidArgumentException("Course with ID {$courseId} not found");
        }

        $data = $this->getCourseReportData($course);

        if ($format === 'csv') {
            return $this->exportToCsv($course, $data);
        } elseif ($format === 'pdf') {
            return $this->exportToPdf($course, $data);
        } else {
            throw new \InvalidArgumentException("Unsupported format: {$format}. Use 'csv' or 'pdf'");
        }
    }

    /**
     * Get course report data (students and grades).
     *
     * @param Course $course
     * @return array
     */
    private function getCourseReportData(Course $course): array
    {
        $enrollments = $this->entityManager
            ->getRepository(Enrollment::class)
            ->createQueryBuilder('e')
            ->where('e.course = :course')
            ->setParameter('course', $course)
            ->leftJoin('e.student', 's')
            ->addSelect('s')
            ->orderBy('s.fullname', 'ASC')
            ->getQuery()
            ->getResult();

        $data = [];
        foreach ($enrollments as $enrollment) {
            $student = $enrollment->getStudent();
            if ($student) {
                $data[] = [
                    'student_id' => $student->getId(),
                    'fullname' => $student->getFullname(),
                    'email' => $student->getEmail(),
                    'status' => $student->getStatus(),
                    'gpa' => $student->getGpa(),
                    'grade' => $enrollment->getGrade(),
                    'enrolled_at' => $enrollment->getEnrolledAt()?->format('Y-m-d H:i:s'),
                ];
            }
        }

        return $data;
    }

    /**
     * Export data to CSV file.
     *
     * @param Course $course
     * @param array $data
     * @return string Path to the generated CSV file
     */
    private function exportToCsv(Course $course, array $data): string
    {
        $filename = sprintf(
            'course_report_%s_%s.csv',
            $course->getId(),
            date('Y-m-d_His')
        );
        $filePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;

        $csv = Writer::createFromPath($filePath, 'w+');
        $csv->setOutputBOM(Writer::BOM_UTF8);
        $csv->insertOne([
            'Student ID',
            'Full Name',
            'Email',
            'Status',
            'GPA',
            'Course Grade',
            'Enrolled At'
        ]);

        foreach ($data as $row) {
            $csv->insertOne([
                $row['student_id'],
                $row['fullname'],
                $row['email'],
                $row['status'],
                $row['gpa'] ?? 'N/A',
                $row['grade'] ?? 'N/A',
                $row['enrolled_at'] ?? 'N/A',
            ]);
        }

        return $filePath;
    }

    /**
     * Export data to PDF file.
     *
     * @param Course $course
     * @param array $data
     * @return string Path to the generated PDF file
     */
    private function exportToPdf(Course $course, array $data): string
    {
        $filename = sprintf(
            'course_report_%s_%s.pdf',
            $course->getId(),
            date('Y-m-d_His')
        );
        $filePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;

        $html = $this->generatePdfHtml($course, $data);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        
        file_put_contents($filePath, $dompdf->output());

        return $filePath;
    }

    /**
     * Generate HTML content for PDF.
     *
     * @param Course $course
     * @param array $data
     * @return string
     */
    private function generatePdfHtml(Course $course, array $data): string
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; margin: 20px; }
        h1 { color: #333; border-bottom: 2px solid #333; padding-bottom: 10px; }
        h2 { color: #666; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background-color: #4CAF50; color: white; padding: 12px; text-align: left; }
        td { padding: 10px; border: 1px solid #ddd; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .info { margin: 20px 0; padding: 15px; background-color: #e7f3ff; border-left: 4px solid #2196F3; }
        .footer { margin-top: 30px; text-align: center; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <h1>Course Report: ' . htmlspecialchars($course->getName()) . '</h1>
    
    <div class="info">
        <strong>Course ID:</strong> ' . $course->getId() . '<br>
        <strong>Course Name:</strong> ' . htmlspecialchars($course->getName()) . '<br>
        <strong>Credits:</strong> ' . $course->getCredits() . '<br>
        <strong>Description:</strong> ' . htmlspecialchars($course->getDescription() ?? 'N/A') . '<br>
        <strong>Total Students:</strong> ' . count($data) . '<br>
        <strong>Generated:</strong> ' . date('Y-m-d H:i:s') . '
    </div>

    <h2>Student Grades</h2>
    <table>
        <thead>
            <tr>
                <th>Student ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Status</th>
                <th>GPA</th>
                <th>Course Grade</th>
                <th>Enrolled At</th>
            </tr>
        </thead>
        <tbody>';

        if (empty($data)) {
            $html .= '<tr><td colspan="7" style="text-align: center;">No students enrolled in this course.</td></tr>';
        } else {
            foreach ($data as $row) {
                $html .= '<tr>
                    <td>' . htmlspecialchars((string)$row['student_id']) . '</td>
                    <td>' . htmlspecialchars($row['fullname']) . '</td>
                    <td>' . htmlspecialchars($row['email']) . '</td>
                    <td>' . htmlspecialchars($row['status']) . '</td>
                    <td>' . htmlspecialchars($row['gpa'] ?? 'N/A') . '</td>
                    <td>' . htmlspecialchars($row['grade'] ?? 'N/A') . '</td>
                    <td>' . htmlspecialchars($row['enrolled_at'] ?? 'N/A') . '</td>
                </tr>';
            }
        }

        $html .= '</tbody>
    </table>
    
    <div class="footer">
        Generated by University Management System
    </div>
</body>
</html>';

        return $html;
    }
}

