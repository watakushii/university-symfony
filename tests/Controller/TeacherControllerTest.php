<?php

namespace App\Tests\Controller;

use App\Entity\Teacher;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class TeacherControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $teacherRepository;
    private string $path = '/teacher/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->teacherRepository = $this->manager->getRepository(Teacher::class);

        foreach ($this->teacherRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Teacher index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'teacher[fullname]' => 'Testing',
            'teacher[department]' => 'Testing',
            'teacher[position]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->teacherRepository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Teacher();
        $fixture->setFullname('My Title');
        $fixture->setDepartment('My Title');
        $fixture->setPosition('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Teacher');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Teacher();
        $fixture->setFullname('Value');
        $fixture->setDepartment('Value');
        $fixture->setPosition('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'teacher[fullname]' => 'Something New',
            'teacher[department]' => 'Something New',
            'teacher[position]' => 'Something New',
        ]);

        self::assertResponseRedirects('/teacher/');

        $fixture = $this->teacherRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getFullname());
        self::assertSame('Something New', $fixture[0]->getDepartment());
        self::assertSame('Something New', $fixture[0]->getPosition());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new Teacher();
        $fixture->setFullname('Value');
        $fixture->setDepartment('Value');
        $fixture->setPosition('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/teacher/');
        self::assertSame(0, $this->teacherRepository->count([]));
    }
}
