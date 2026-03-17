<?php

declare(strict_types=1);

namespace Modules\Contracts\Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Modules\Contracts\Enums\ContractStatus;
use Modules\Contracts\Models\Contract;
use Modules\Contracts\Models\ContractSignature;
use Modules\Contracts\Services\DocumentStorageService;
use Modules\Contracts\Services\FEAService;
use Modules\Contracts\Services\NotificationService;
use Modules\Contracts\Services\PdfGeneratorService;
use Tests\TestCase;

class FEASignatureTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    private FEAService $feaService;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');

        $this->feaService = new FEAService(
            pdfGenerator: $this->mockPdfGenerator(),
            storage: $this->mockDocumentStorage(),
            notifier: $this->mockNotifier(),
        );
    }

    /** @test */
    public function it_sends_otp_and_creates_signature_record(): void
    {
        $contract = Contract::factory()
            ->pendingSignature()
            ->withPdfPath('contracts/2024/1/test.pdf')
            ->create();

        $signature = $this->feaService->sendOtp($contract, 'sms');

        $this->assertEquals('pending', $signature->status);
        $this->assertNotNull($signature->otp_hash);
        $this->assertNotNull($signature->otp_expires_at);
        $this->assertNotNull($signature->pdf_hash_pre_firma);
    }

    /** @test */
    public function it_expires_previous_otp_when_resending(): void
    {
        $contract = Contract::factory()
            ->pendingSignature()
            ->withPdfPath('contracts/test.pdf')
            ->create();

        $sig1 = $this->feaService->sendOtp($contract, 'sms');
        $sig2 = $this->feaService->sendOtp($contract, 'sms');

        $this->assertEquals('expired', $sig1->fresh()->status);
        $this->assertEquals('pending', $sig2->status);
    }

    /** @test */
    public function it_throws_if_no_pdf_path(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/PDF non ancora generato/');

        $contract = Contract::factory()->pendingSignature()->create(['pdf_path' => null]);
        $this->feaService->sendOtp($contract, 'sms');
    }

    /** @test */
    public function it_verifies_correct_otp_and_activates_contract(): void
    {
        $contract  = Contract::factory()->pendingSignature()->withPdfPath('contracts/test.pdf')->create();
        $clearOtp  = '123456';

        ContractSignature::factory()->create([
            'contract_id'    => $contract->id,
            'otp_hash'       => Hash::make($clearOtp),
            'otp_expires_at' => now()->addHours(24),
            'status'         => 'pending',
            'pdf_hash_pre_firma' => hash('sha256', 'fake-pdf-content'),
        ]);

        $result = $this->feaService->verifyAndSign(
            contract: $contract,
            otp: $clearOtp,
            clientIp: '192.168.1.1',
            userAgent: 'Mozilla/5.0',
        );

        $this->assertTrue($result->success);
        $this->assertNotEmpty($result->pdfHashSha256);
        $this->assertEquals(ContractStatus::Active, $contract->fresh()->status);
    }

    /** @test */
    public function it_rejects_wrong_otp_and_increments_attempts(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/non corretto/');

        $contract = Contract::factory()->pendingSignature()->withPdfPath('contracts/test.pdf')->create();

        ContractSignature::factory()->create([
            'contract_id'    => $contract->id,
            'otp_hash'       => Hash::make('999999'),
            'otp_expires_at' => now()->addHours(24),
            'status'         => 'pending',
        ]);

        $this->feaService->verifyAndSign($contract, '000000', '127.0.0.1', 'test');

        $this->assertEquals(1, ContractSignature::latest()->first()->failed_attempts);
    }

    /** @test */
    public function it_rejects_expired_otp(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/scaduto/i');

        $contract = Contract::factory()->pendingSignature()->withPdfPath('contracts/test.pdf')->create();

        ContractSignature::factory()->create([
            'contract_id'    => $contract->id,
            'otp_hash'       => Hash::make('123456'),
            'otp_expires_at' => now()->subMinute(), // già scaduto
            'status'         => 'pending',
        ]);

        $this->feaService->verifyAndSign($contract, '123456', '127.0.0.1', 'test');
    }

    /** @test */
    public function it_blocks_after_max_attempts(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Numero massimo/');

        $contract = Contract::factory()->pendingSignature()->withPdfPath('contracts/test.pdf')->create();

        ContractSignature::factory()->create([
            'contract_id'     => $contract->id,
            'otp_hash'        => Hash::make('999999'),
            'otp_expires_at'  => now()->addHours(24),
            'status'          => 'pending',
            'failed_attempts' => 3, // già al limite
        ]);

        $this->feaService->verifyAndSign($contract, '000000', '127.0.0.1', 'test');
    }

    // ---- Mocks ----

    private function mockPdfGenerator(): PdfGeneratorService
    {
        $mock = $this->createMock(PdfGeneratorService::class);
        $mock->method('addSignaturePage')->willReturn('fake-signed-pdf-content');
        return $mock;
    }

    private function mockDocumentStorage(): DocumentStorageService
    {
        $mock = $this->createMock(DocumentStorageService::class);
        $mock->method('getContent')->willReturn('fake-pdf-content');
        $mock->method('replaceWithSignedPdf')->willReturn([
            'path'   => 'contracts/test_signed.pdf',
            'sha256' => hash('sha256', 'fake-signed-pdf-content'),
        ]);
        return $mock;
    }

    private function mockNotifier(): NotificationService
    {
        $mock = $this->createMock(NotificationService::class);
        $mock->method('sendOtp');
        return $mock;
    }
}
