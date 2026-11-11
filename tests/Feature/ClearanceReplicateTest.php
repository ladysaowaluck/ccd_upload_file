<?php

namespace Tests\Feature;

// Test suite for MISS-01 Record Replication (Duplicate CCD) workflow
use App\Models\Auth\AdminUser;
use App\Models\Business\CustomerClearanceData;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ClearanceReplicateTest extends TestCase
{
    use DatabaseTransactions;

    protected AdminUser $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = AdminUser::first() ?? AdminUser::factory()->create();
    }

    // Test replication page renders for authenticated user with prefilled sequence.
    
    public function test_replicate_form_can_be_rendered(): void
    {
        $record = CustomerClearanceData::first();

        $response = $this->actingAs($this->user)
            ->get(route('clearance.replicate.show', $record->id));

        $response->assertStatus(200);
        $response->assertSee('Duplicate Clearance Record');
        $response->assertSee($record->job_no);
    }

    // Test record replication executes cleanly, copying tariff fields and leaving files blank.
    public function test_record_can_be_replicated_to_new_job_number(): void
    {
        $source = CustomerClearanceData::whereNotNull('hs_code')->first();
        $newJobNo = 'CCD2611-' . rand(5000, 5999);

        $response = $this->actingAs($this->user)
            ->post(route('clearance.replicate.execute', $source->id), [
                'new_job_no' => $newJobNo,
                'new_product_code_1' => 'NEW-CODE-01',
                'new_product_code_2' => 'NEW-CODE-02',
            ]);

        $duplicated = CustomerClearanceData::where('job_no', $newJobNo)->first();
        $this->assertNotNull($duplicated, 'Replicated record was not found in database');

        $response->assertRedirect(route('clearance.edit', $duplicated->id));

        // Assert tariff classification is preserved
        $this->assertSame($source->hs_code, $duplicated->hs_code);
        $this->assertSame($source->customer_name, $duplicated->customer_name);
        $this->assertSame($source->master_customer_id, $duplicated->master_customer_id);
        $this->assertSame($source->product_description_en, $duplicated->product_description_en);

        // Assert new product codes were applied
        $this->assertSame('NEW-CODE-01', $duplicated->product_code_1);
        $this->assertSame('NEW-CODE-02', $duplicated->product_code_2);

        // Assert file attachment slots are clean (blank) on duplicated record
        $this->assertNull($duplicated->reference_file_1);
        $this->assertNull($duplicated->reference_file_2);
        $this->assertNull($duplicated->hs_evidence_file_1);
        $this->assertNull($duplicated->hs_evidence_file_2);
        $this->assertNull($duplicated->hs_evidence_file_3);
        $this->assertNull($duplicated->hs_evidence_file_4);
    }

    // Test validation rejects duplicate job numbers.
    
    public function test_replication_rejects_existing_job_number(): void
    {
        $source = CustomerClearanceData::first();

        $response = $this->actingAs($this->user)
            ->post(route('clearance.replicate.execute', $source->id), [
                'new_job_no' => $source->job_no, // already exists
            ]);

        $response->assertSessionHasErrors(['new_job_no']);
    }
}

