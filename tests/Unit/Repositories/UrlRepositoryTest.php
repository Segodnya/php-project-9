<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Repositories\UrlRepository;
use App\Models\Url;
use App\PDO;
use PDO as StandardPDO;

class UrlRepositoryTest extends TestCase
{
    private UrlRepository $repository;
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an in-memory SQLite database for testing
        $this->pdo = $this->createTestDatabase();

        // Create the repository instance
        $this->repository = new UrlRepository($this->pdo);
    }

    public function testCreate(): void
    {
        $data = ['name' => 'https://example.com'];
        $id = $this->repository->create($data);

        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);

        // Check if the URL was actually created
        $stmt = $this->pdo->prepare("SELECT * FROM urls WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(StandardPDO::FETCH_ASSOC);

        $this->assertIsArray($result);
        $this->assertEquals('https://example.com', $result['name']);
    }

    public function testCreateWithInvalidData(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->repository->create(['name' => '']);
    }

    public function testFindById(): void
    {
        // First create a URL
        $data = ['name' => 'https://example.com'];
        $id = $this->repository->create($data);

        // Then find it by ID
        $result = $this->repository->findById($id);

        $this->assertIsArray($result);
        $this->assertEquals($id, $result['id']);
        $this->assertEquals('https://example.com', $result['name']);
    }

    public function testFindByIdNotFound(): void
    {
        $result = $this->repository->findById(9999);
        $this->assertNull($result);
    }

    public function testFindByName(): void
    {
        // First create a URL
        $data = ['name' => 'https://example.com'];
        $id = $this->repository->create($data);

        // Then find it by name
        $result = $this->repository->findByName('https://example.com');

        $this->assertIsArray($result);
        $this->assertEquals($id, $result['id']);
        $this->assertEquals('https://example.com', $result['name']);
    }

    public function testFindByNameNotFound(): void
    {
        $result = $this->repository->findByName('https://nonexistent.com');
        $this->assertNull($result);
    }

    public function testFindAll(): void
    {
        // Create multiple URLs
        $this->repository->create(['name' => 'https://example1.com']);
        $this->repository->create(['name' => 'https://example2.com']);

        // Get all URLs
        $results = $this->repository->findAll();

        $this->assertIsArray($results);
        $this->assertGreaterThanOrEqual(2, count($results));
    }

    public function testCreateFromEntity(): void
    {
        $url = new Url('https://example.com');
        $id = $this->repository->createFromEntity($url);

        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
    }

    public function testCreateFromInvalidEntity(): void
    {
        $url = new Url('invalid-url');

        $this->expectException(\InvalidArgumentException::class);
        $this->repository->createFromEntity($url);
    }

    /**
     * Test finding an entity by ID and returning it as an object
     */
    public function testFindByIdAsEntity(): void
    {
        // First create a URL
        $data = ['name' => 'https://example.com'];
        $id = $this->repository->create($data);

        // Then find it by ID as entity
        /** @var Url $entity */
        $entity = $this->repository->findByIdAsEntity($id);

        $this->assertInstanceOf(Url::class, $entity);
        $this->assertEquals($id, $entity->getId());
        $this->assertEquals('https://example.com', $entity->getName());
    }

    /**
     * Test finding all entities and returning them as objects
     */
    public function testFindAllAsEntities(): void
    {
        // Create multiple URLs
        $this->repository->create(['name' => 'https://example1.com']);
        $this->repository->create(['name' => 'https://example2.com']);

        // Get all URLs as entities
        $entities = $this->repository->findAllAsEntities();

        $this->assertIsArray($entities);
        $this->assertGreaterThanOrEqual(2, count($entities));
        $this->assertInstanceOf(Url::class, $entities[0]);
    }
}