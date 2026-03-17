<?php

declare(strict_types=1);

namespace Modules\Provisioning\Tests\Unit;

use Modules\Provisioning\Data\LineStatusResult;
use PHPUnit\Framework\TestCase;

/**
 * Test specifici per la Line Testing API v2.3 OF.
 * NOVITÀ v2.3: L02 distingue timeout (retry) da ONT unreachable (ticket).
 */
class LinetestV23Test extends TestCase
{
    /** @test */
    public function ok_response_is_parsed_correctly(): void
    {
        $result = LineStatusResult::fromOfV23Response([
            'TestInstanceId'      => 1001,
            'Result'              => 'OK',
            'OntOperationalState' => 'UP',
            'Attenuation'         => '-12.5',
            'OpticalDistance'     => '850.0',
            'OntLanStatus'        => 'ENABLED',
        ]);

        $this->assertTrue($result->success);
        $this->assertEquals('OK', $result->result);
        $this->assertEquals('UP', $result->ontOperationalState);
        $this->assertEquals(-12.5, $result->attenuation);
        $this->assertEquals(850.0, $result->opticalDistance);
        $this->assertEquals('ENABLED', $result->ontLanStatus);
        $this->assertFalse($result->isRetryable);
        $this->assertFalse($result->requiresTicket);
    }

    /** @test */
    public function l01_service_not_available_is_retryable(): void
    {
        $result = LineStatusResult::fromOfV23Response([
            'Result'      => 'KO',
            'Code'        => 'L01',
            'Description' => 'Service not available',
        ]);

        $this->assertFalse($result->success);
        $this->assertTrue($result->isRetryable);
        $this->assertFalse($result->requiresTicket);
    }

    /**
     * @test
     * NOVITÀ v2.3: L02 con "timeout" → retry
     */
    public function l02_timeout_is_retryable(): void
    {
        $result = LineStatusResult::fromOfV23Response([
            'Result'      => 'KO',
            'Code'        => 'L02',
            'Description' => 'Temporary timeout, please try again later',
        ]);

        $this->assertFalse($result->success);
        $this->assertTrue($result->isRetryable, 'L02 timeout deve essere retryable (v2.3)');
        $this->assertFalse($result->requiresTicket);
    }

    /**
     * @test
     * NOVITÀ v2.3: L02 con "unreachable" → apri ticket assurance, NON retry
     */
    public function l02_unreachable_requires_ticket_not_retry(): void
    {
        $result = LineStatusResult::fromOfV23Response([
            'Result'      => 'KO',
            'Code'        => 'L02',
            'Description' => 'ONT unreachable',
        ]);

        $this->assertFalse($result->success);
        $this->assertFalse($result->isRetryable, 'L02 unreachable NON deve essere retryable (v2.3)');
        $this->assertTrue($result->requiresTicket, 'L02 unreachable deve richiedere ticket assurance');
    }

    /** @test */
    public function l03_bad_request_is_not_retryable(): void
    {
        $result = LineStatusResult::fromOfV23Response([
            'Result'      => 'KO',
            'Code'        => 'L03',
            'Description' => 'Bad formatted request',
        ]);

        $this->assertFalse($result->isRetryable);
        $this->assertFalse($result->requiresTicket);
    }

    /** @test */
    public function l07_massive_fault_is_not_retryable_and_no_ticket(): void
    {
        $result = LineStatusResult::fromOfV23Response([
            'Result'      => 'KO',
            'Code'        => 'L07',
            'Description' => 'Resource ID affected by massive fault',
        ]);

        $this->assertFalse($result->isRetryable, 'L07 MSO: NON retry singolo');
        $this->assertFalse($result->requiresTicket, 'L07 MSO: NON ticket singolo - alert speciale');
    }
}
