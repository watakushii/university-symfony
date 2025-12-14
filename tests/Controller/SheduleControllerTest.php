<?php

namespace App\Tests\Controller;

use App\Entity\Shedule;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SheduleControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $sheduleRepository;
    private string $path = '/shedule/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->sheduleRepository = $this->manager->getRepository(Shedule::class);

        foreach ($this->sheduleRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Shedule index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'shedule[room]' => 'Testing',
            'shedule[time]' => 'Testing',
            'shedule[course]' => 'Testing',
            'shedule[teacher]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->sheduleRepository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Shedule();
        $fixture->setRoom('My Title');
        $fixture->setTime('My Title');
        $fixture->setCourse('My Title');
        $fixture->setTeacher('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Shedule');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Shedule();
        $fixture->setRoom('Value');
        $fixture->setTime('Value');
        $fixture->setCourse('Value');
        $fixture->setTeacher('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'shedule[room]' => 'Something New',
            'shedule[time]' => 'Something New',
            'shedule[course]' => 'Something New',
            'shedule[teacher]' => 'Something New',
        ]);

        self::assertResponseRedirects('/shedule/');

        $fixture = $this->sheduleRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getRoom());
        self::assertSame('Something New', $fixture[0]->getTime());
        self::assertSame('Something New', $fixture[0]->getCourse());
        self::assertSame('Something New', $fixture[0]->getTeacher());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new Shedule();
        $fixture->setRoom('Value');
        $fixture->setTime('Value');
        $fixture->setCourse('Value');
        $fixture->setTeacher('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/shedule/');
        self::assertSame(0, $this->sheduleRepository->count([]));
    }
}
