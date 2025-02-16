<?php

namespace App\Tests\Support\Helper;

use Codeception\Module;

class ApiResponseHelper extends Module
{
    public function assertResponseIsApiError(array $response): void
    {
        $this->assertArrayHasKey('code', $response);
        $this->assertArrayHasKey('errors', $response);
    }
}