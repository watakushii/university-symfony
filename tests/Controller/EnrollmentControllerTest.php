<?php

namespace App\Tests\Controller;

use App\Entity\Enrollment;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class EnrollmentControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $enrollmentRepository;
    private string $path = '/enrollment/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->enrollmentRepository = $this->manager->getRepository(Enrollment::class);

        foreach ($this->enrollmentRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Enrollment index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'enrollment[grade]' => 'Testing',
            'enrollment[enrolledAt]' => 'Testing',
            'enrollment[student]' => 'Testing',
            'enrollment[course]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->enrollmentRepository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Enrollment();
        $fixture->setGrade('My Title');
        $fixture->setEnrolledAt('My Title');
        $fixture->setStudent('My Title');
        $fixture->setCourse('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Enrollment');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Enrollment();
        $fixture->setGrade('Value');
        $fixture->setEnrolledAt('Value');
        $fixture->setStudent('Value');
        $fixture->setCourse('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'enrollment[grade]' => 'Something New',
            'enrollment[enrolledAt]' => 'Something New',
            'enrollment[student]' => 'Something New',
            'enrollment[course]' => 'Something New',
        ]);

        self::assertResponseRedirects('/enrollment/');

        $fixture = $this->enrollmentRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getGrade());
        self::assertSame('Something New', $fixture[0]->getEnrolledAt());
        self::assertSame('Something New', $fixture[0]->getStudent());
        self::assertSame('Something New', $fixture[0]->getCourse());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new Enrollment();
        $fixture->setGrade('Value');
        $fixture->setEnrolledAt('Value');
        $fixture->setStudent('Value');
        $fixture->setCourse('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/enrollment/');
        self::assertSame(0, $this->enrollmentRepository->count([]));
    }
}
