<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Url;

class UrlTest extends TestCase
{
    public function testUrlConstruction(): void
    {
        $url = new Url('https://example.com');

        $this->assertEquals('https://example.com', $url->getName());
        $this->assertNull($url->getId());
        $this->assertNull($url->getCreatedAt());
    }

    public function testGettersAndSetters(): void
    {
        $url = new Url('https://example.com');

        $url->setId(1)
            ->setCreatedAt('2023-01-01 12:00:00');

        $this->assertEquals(1, $url->getId());
        $this->assertEquals('https://example.com', $url->getName());
        $this->assertEquals('2023-01-01 12:00:00', $url->getCreatedAt());
    }

    public function testFormattedCreatedAt(): void
    {
        $url = new Url('https://example.com');
        $url->setCreatedAt('2023-01-01 12:00:00');

        $this->assertEquals('2023-01-01 12:00:00', $url->getFormattedCreatedAt());
        $this->assertEquals('01/01/2023', $url->getFormattedCreatedAt('d/m/Y'));
    }

    public function testFromArray(): void
    {
        $data = [
            'id' => 1,
            'name' => 'https://example.com',
            'created_at' => '2023-01-01 12:00:00'
        ];

        $url = Url::fromArray($data);

        $this->assertEquals(1, $url->getId());
        $this->assertEquals('https://example.com', $url->getName());
        $this->assertEquals('2023-01-01 12:00:00', $url->getCreatedAt());
    }

    public function testToArray(): void
    {
        $url = new Url('https://example.com');
        $url->setId(1)
            ->setCreatedAt('2023-01-01 12:00:00');

        $array = $url->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(1, $array['id']);
        $this->assertEquals('https://example.com', $array['name']);
        $this->assertEquals('2023-01-01 12:00:00', $array['created_at']);
    }

    public function testValidationSuccess(): void
    {
        $url = new Url('https://example.com');

        $this->assertTrue($url->validate());
        $this->assertEmpty($url->getValidationErrors());
    }

    public function testValidationFailureEmptyUrl(): void
    {
        $url = new Url('');

        $this->assertFalse($url->validate());
        $this->assertNotEmpty($url->getValidationErrors());
        $this->assertArrayHasKey('name', $url->getValidationErrors());
    }

    public function testValidationFailureInvalidFormat(): void
    {
        $url = new Url('invalid-url');

        $this->assertFalse($url->validate());
        $this->assertNotEmpty($url->getValidationErrors());
        $this->assertArrayHasKey('name', $url->getValidationErrors());
    }

    public function testValidationFailureTooLong(): void
    {
        // Create a URL that's too long (over 255 chars)
        $longUrl = 'https://example.com/' . str_repeat('a', 255);
        $url = new Url($longUrl);

        $this->assertFalse($url->validate());
        $this->assertNotEmpty($url->getValidationErrors());
        $this->assertArrayHasKey('name', $url->getValidationErrors());
    }
}