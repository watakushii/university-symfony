<?php

namespace App\Controller;

use App\Entity\Student;
use App\Repository\StudentRepository;
use App\Service\AuditService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Example controller demonstrating role-based access control.
 * 
 * This controller shows how different roles have different access levels:
 * - Students: Can only view their own data
 * - Teachers: Can view their courses and students
 * - Admins: Full access to everything
 */
class ExampleRoleBasedController extends AbstractController
{
    /**
     * Home page - accessible to all authenticated users.
     */
    #[Route(path: '/', name: 'app_home')]
    public function home(): Response
    {
        $user = $this->getUser();
        
        return $this->render('example/home.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Student dashboard - only accessible to students.
     * Students can see their own courses, exams, and payments.
     */
    #[Route(path: '/student/dashboard', name: 'student_dashboard')]
    #[IsGranted('ROLE_STUDENT')]
    public function studentDashboard(StudentRepository $studentRepository): Response
    {
        $user = $this->getUser();
        
        // Find student by email
        $student = $studentRepository->findOneBy(['email' => $user->getUserIdentifier()]);
        
        if (!$student) {
            throw $this->createAccessDeniedException('Student record not found');
        }

        return $this->render('example/student_dashboard.html.twig', [
            'student' => $student,
        ]);
    }

    /**
     * Student courses - students can only see their own courses.
     */
    #[Route(path: '/student/courses', name: 'student_courses')]
    #[IsGranted('ROLE_STUDENT')]
    public function studentCourses(StudentRepository $studentRepository): Response
    {
        $user = $this->getUser();
        $student = $studentRepository->findOneBy(['email' => $user->getUserIdentifier()]);
        
        if (!$student) {
            throw $this->createAccessDeniedException('Student record not found');
        }

        $enrollments = $student->getEnrollments();

        return $this->render('example/student_courses.html.twig', [
            'student' => $student,
            'enrollments' => $enrollments,
        ]);
    }

    /**
     * Student exams - students can only see their own exams.
     */
    #[Route(path: '/student/exams', name: 'student_exams')]
    #[IsGranted('ROLE_STUDENT')]
    public function studentExams(StudentRepository $studentRepository): Response
    {
        $user = $this->getUser();
        $student = $studentRepository->findOneBy(['email' => $user->getUserIdentifier()]);
        
        if (!$student) {
            throw $this->createAccessDeniedException('Student record not found');
        }

        $exams = $student->getExams();

        return $this->render('example/student_exams.html.twig', [
            'student' => $student,
            'exams' => $exams,
        ]);
    }

    /**
     * Student payments - students can only see their own payments.
     */
    #[Route(path: '/student/payments', name: 'student_payments')]
    #[IsGranted('ROLE_STUDENT')]
    public function studentPayments(StudentRepository $studentRepository): Response
    {
        $user = $this->getUser();
        $student = $studentRepository->findOneBy(['email' => $user->getUserIdentifier()]);
        
        if (!$student) {
            throw $this->createAccessDeniedException('Student record not found');
        }

        $payments = $student->getPayments();

        return $this->render('example/student_payments.html.twig', [
            'student' => $student,
            'payments' => $payments,
        ]);
    }

    /**
     * Teacher dashboard - only accessible to teachers and admins.
     * Teachers can see their courses and students.
     */
    #[Route(path: '/teacher/dashboard', name: 'teacher_dashboard')]
    #[IsGranted('ROLE_TEACHER')]
    public function teacherDashboard(): Response
    {
        $user = $this->getUser();

        return $this->render('example/teacher_dashboard.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Admin dashboard - only accessible to admins.
     * Admins have full access to everything.
     */
    #[Route(path: '/admin/dashboard', name: 'admin_dashboard')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminDashboard(): Response
    {
        $user = $this->getUser();

        return $this->render('example/admin_dashboard.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Example: Create student with audit logging.
     * Only admins can create students.
     */
    #[Route(path: '/admin/student/create', name: 'admin_student_create', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function createStudent(
        Request $request,
        EntityManagerInterface $entityManager,
        AuditService $auditService
    ): Response {
        if ($request->isMethod('POST')) {
            $student = new Student();
            $student->setFullname($request->request->get('fullname'));
            $student->setEmail($request->request->get('email'));
            $student->setStatus($request->request->get('status', 'active'));
            $student->setGpa($request->request->get('gpa'));

            $entityManager->persist($student);
            $entityManager->flush();

            // Log the creation
            $newValues = $auditService->extractEntityValues($student);
            $auditService->logCreate($student, $newValues, 'Student created via admin panel');

            $this->addFlash('success', 'Student created successfully');

            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('example/create_student.html.twig');
    }

    /**
     * Example: Update student with audit logging.
     * Only admins can update students.
     */
    #[Route(path: '/admin/student/{id}/edit', name: 'admin_student_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function editStudent(
        int $id,
        Request $request,
        StudentRepository $studentRepository,
        EntityManagerInterface $entityManager,
        AuditService $auditService
    ): Response {
        $student = $studentRepository->find($id);

        if (!$student) {
            throw $this->createNotFoundException('Student not found');
        }

        if ($request->isMethod('POST')) {
            // Store old values for audit
            $oldValues = $auditService->extractEntityValues($student);

            // Update student
            $student->setFullname($request->request->get('fullname'));
            $student->setEmail($request->request->get('email'));
            $student->setStatus($request->request->get('status'));
            $student->setGpa($request->request->get('gpa'));

            $entityManager->flush();

            // Log the update
            $newValues = $auditService->extractEntityValues($student);
            $auditService->logUpdate($student, $oldValues, $newValues, 'Student updated via admin panel');

            $this->addFlash('success', 'Student updated successfully');

            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('example/edit_student.html.twig', [
            'student' => $student,
        ]);
    }

    /**
     * Example: Delete student with audit logging.
     * Only admins can delete students.
     */
    #[Route(path: '/admin/student/{id}/delete', name: 'admin_student_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteStudent(
        int $id,
        StudentRepository $studentRepository,
        EntityManagerInterface $entityManager,
        AuditService $auditService
    ): Response {
        $student = $studentRepository->find($id);

        if (!$student) {
            throw $this->createNotFoundException('Student not found');
        }

        // Store old values for audit before deletion
        $oldValues = $auditService->extractEntityValues($student);

        $entityManager->remove($student);
        $entityManager->flush();

        // Log the deletion
        $auditService->logDelete($student, $oldValues, 'Student deleted via admin panel');

        $this->addFlash('success', 'Student deleted successfully');

        return $this->redirectToRoute('admin_dashboard');
    }
}

