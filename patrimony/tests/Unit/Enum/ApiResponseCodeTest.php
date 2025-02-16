<?php

namespace Unit\Enum;

use App\Enum\ApiResponseCode;
use Codeception\Test\Unit;

class ApiResponseCodeTest extends Unit
{
    public function testApiFromStatusCode(): void
    {
        $code = ApiResponseCode::fromStatusCode(400);
        $this->assertEquals(ApiResponseCode::ERR_BAD_REQUEST, $code);

        $code = ApiResponseCode::fromStatusCode(500);
        $this->assertEquals(ApiResponseCode::ERR_INTERNAL, $code);

        $code = ApiResponseCode::fromStatusCode(301);
        $this->assertEquals(ApiResponseCode::ERR_GENERIC, $code);
    }
}