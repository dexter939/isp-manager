<?php
namespace Modules\Contracts\DuplicateChecker\Tests\Unit;
use Tests\TestCase;
use Modules\Contracts\DuplicateChecker\Services\CustomerDuplicateChecker;
class CustomerDuplicateCheckerTest extends TestCase {
    private CustomerDuplicateChecker $checker;
    protected function setUp(): void { parent::setUp(); $this->checker = new CustomerDuplicateChecker(); }
    public function test_normalize_phone_removes_country_code(): void {
        $this->assertEquals('3331234567', $this->checker->normalizePhone('+393331234567'));
        $this->assertEquals('3331234567', $this->checker->normalizePhone('393331234567'));
    }
    public function test_normalize_phone_removes_spaces(): void {
        $this->assertEquals('3331234567', $this->checker->normalizePhone('333 123 4567'));
    }
    public function test_normalize_phone_handles_leading_zero(): void {
        $this->assertEquals('334567890', $this->checker->normalizePhone('0334567890'));
    }
    public function test_normalize_phone_handles_landline(): void {
        $this->assertEquals('0612345678', $this->checker->normalizePhone('06 1234 5678'));
    }
}
