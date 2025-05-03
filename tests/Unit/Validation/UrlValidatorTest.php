<?php

namespace Tests\Unit\Validation;

use Tests\TestCase;
use App\Validation\UrlValidator;
use App\Exceptions\ValidationException;

class UrlValidatorTest extends TestCase
{
    private UrlValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new UrlValidator();
    }

    public function testValidUrlPasses(): void
    {
        $result = $this->validator->validate('https://example.com');
        $this->assertTrue($result);
    }

    public function testEmptyUrlThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validate('');
    }

    public function testInvalidUrlFormatThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validate('not-a-url');
    }

    public function testTooLongUrlThrowsException(): void
    {
        $longUrl = 'https://example.com/' . str_repeat('a', UrlValidator::MAX_URL_LENGTH);

        $this->expectException(ValidationException::class);
        $this->validator->validate($longUrl);
    }

    public function testNormalizeStripsPathAndQuery(): void
    {
        $url = 'https://example.com/path?query=string';
        $normalized = $this->validator->normalize($url);

        $this->assertEquals('https://example.com', $normalized);
    }

    public function testNormalizeKeepsPort(): void
    {
        $url = 'https://example.com:8080/path';
        $normalized = $this->validator->normalize($url);

        $this->assertEquals('https://example.com:8080', $normalized);
    }

    public function testNormalizeDefaultsToHttpScheme(): void
    {
        $url = '//example.com';
        $normalized = $this->validator->normalize($url);

        $this->assertEquals('http://example.com', $normalized);
    }

    public function testNormalizeThrowsExceptionOnInvalidUrl(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->normalize('not-parseable-as-url');
    }

    public function testValidateAndNormalizeSuccess(): void
    {
        $url = 'https://example.com/path?query=string';
        $result = $this->validator->validateAndNormalize($url);

        $this->assertEquals('https://example.com', $result);
    }

    public function testValidateAndNormalizeThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validateAndNormalize('invalid-url');
    }
}