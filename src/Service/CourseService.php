<?php

namespace App\Service;

use App\Entity\Course;
use App\Repository\CourseRepository;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class CourseService
{
    private const CACHE_KEY = 'courses_with_teachers';
    private const CACHE_EXPIRATION = 3600; // 1 hour in seconds

    public function __construct(
        private readonly CourseRepository $courseRepository,
        #[Autowire('cache.app')]
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * Get all courses with their associated teachers.
     * Results are cached for 1 hour.
     *
     * @return Course[]
     */
    public function getCoursesWithTeachers(): array
    {
        return $this->cache->get(
            self::CACHE_KEY,
            function (ItemInterface $item): array {
                $item->expiresAfter(self::CACHE_EXPIRATION);
                
                return $this->courseRepository->createQueryBuilder('c')
                    ->leftJoin('c.teacher', 't')
                    ->addSelect('t')
                    ->orderBy('c.id', 'ASC')
                    ->getQuery()
                    ->getResult();
            }
        );
    }
}

