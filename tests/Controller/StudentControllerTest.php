<?php

namespace App\Tests\Controller;

use App\Entity\Student;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class StudentControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $studentRepository;
    private string $path = '/student/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->studentRepository = $this->manager->getRepository(Student::class);

        foreach ($this->studentRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Student index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'student[fullname]' => 'Testing',
            'student[email]' => 'Testing',
            'student[status]' => 'Testing',
            'student[gpa]' => 'Testing',
            'student[exams]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->studentRepository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Student();
        $fixture->setFullname('My Title');
        $fixture->setEmail('My Title');
        $fixture->setStatus('My Title');
        $fixture->setGpa('My Title');
        $fixture->setExams('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Student');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Student();
        $fixture->setFullname('Value');
        $fixture->setEmail('Value');
        $fixture->setStatus('Value');
        $fixture->setGpa('Value');
        $fixture->setExams('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'student[fullname]' => 'Something New',
            'student[email]' => 'Something New',
            'student[status]' => 'Something New',
            'student[gpa]' => 'Something New',
            'student[exams]' => 'Something New',
        ]);

        self::assertResponseRedirects('/student/');

        $fixture = $this->studentRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getFullname());
        self::assertSame('Something New', $fixture[0]->getEmail());
        self::assertSame('Something New', $fixture[0]->getStatus());
        self::assertSame('Something New', $fixture[0]->getGpa());
        self::assertSame('Something New', $fixture[0]->getExams());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new Student();
        $fixture->setFullname('Value');
        $fixture->setEmail('Value');
        $fixture->setStatus('Value');
        $fixture->setGpa('Value');
        $fixture->setExams('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/student/');
        self::assertSame(0, $this->studentRepository->count([]));
    }
}
