<?php
namespace Modules\Contracts\DuplicateChecker\Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
class DuplicateCheckerFeatureTest extends TestCase {
    use RefreshDatabase;
    public function test_check_duplicate_email_found(): void {
        DB::table('customers')->insert(['id'=>\Illuminate\Support\Str::uuid(),'name'=>'Mario Rossi','email'=>'mario@test.it','customer_code'=>'CLI-001','deleted_at'=>null]);
        $this->actingAsAdmin()->getJson('/api/customers/check-duplicate?email=mario@test.it')->assertOk()->assertJsonPath('has_duplicates', true)->assertJsonCount(1, 'duplicates');
    }
    public function test_check_duplicate_email_not_found(): void {
        $this->actingAsAdmin()->getJson('/api/customers/check-duplicate?email=nobody@test.it')->assertOk()->assertJsonPath('has_duplicates', false)->assertJsonCount(0, 'duplicates');
    }
    public function test_exclude_id_removes_self_from_results(): void {
        $id = \Illuminate\Support\Str::uuid();
        DB::table('customers')->insert(['id'=>$id,'name'=>'Self','email'=>'self@test.it','customer_code'=>'CLI-001','deleted_at'=>null]);
        $this->actingAsAdmin()->getJson("/api/customers/check-duplicate?email=self@test.it&exclude_id={$id}")->assertOk()->assertJsonPath('has_duplicates', false);
    }
}
