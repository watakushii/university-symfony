<?php

namespace App\Tests\Controller;

use App\Entity\Exam;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ExamControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $examRepository;
    private string $path = '/exam/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->examRepository = $this->manager->getRepository(Exam::class);

        foreach ($this->examRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Exam index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'exam[examDate]' => 'Testing',
            'exam[grades]' => 'Testing',
            'exam[course]' => 'Testing',
            'exam[students]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->examRepository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Exam();
        $fixture->setExamDate('My Title');
        $fixture->setGrades('My Title');
        $fixture->setCourse('My Title');
        $fixture->setStudents('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Exam');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Exam();
        $fixture->setExamDate('Value');
        $fixture->setGrades('Value');
        $fixture->setCourse('Value');
        $fixture->setStudents('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'exam[examDate]' => 'Something New',
            'exam[grades]' => 'Something New',
            'exam[course]' => 'Something New',
            'exam[students]' => 'Something New',
        ]);

        self::assertResponseRedirects('/exam/');

        $fixture = $this->examRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getExamDate());
        self::assertSame('Something New', $fixture[0]->getGrades());
        self::assertSame('Something New', $fixture[0]->getCourse());
        self::assertSame('Something New', $fixture[0]->getStudents());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new Exam();
        $fixture->setExamDate('Value');
        $fixture->setGrades('Value');
        $fixture->setCourse('Value');
        $fixture->setStudents('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/exam/');
        self::assertSame(0, $this->examRepository->count([]));
    }
}
