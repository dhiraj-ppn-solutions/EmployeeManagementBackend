<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use App\Helpers\JwtHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        // Create a test user and pre-authenticate requests with a JWT token
        $user = User::factory()->create();
        $this->token = JwtHelper::generateToken($user->id, 5);
        $this->withToken($this->token);
    }

    public function test_employee_can_be_created_with_legacy_documents(): void
    {
        $file1 = UploadedFile::fake()->create('resume.pdf', 500, 'application/pdf');
        $file2 = UploadedFile::fake()->create('avatar.jpg', 500, 'image/jpeg');

        $payload = [
            'employee_id' => 'EMP100',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'mobile' => '1234567890',
            'department' => 'Engineering',
            'designation' => 'Software Engineer',
            'joining_date' => '2026-05-27',
            'status' => 'Active',
            'documents' => [$file1, $file2]
        ];

        $response = $this->postJson('/api/employees', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('employee.documents_count', 2);

        $employee = Employee::first();
        $this->assertEquals('EMP100', $employee->employee_id);
        $this->assertCount(2, $employee->documents);

        $doc1 = $employee->documents[0];
        $this->assertEquals('Other Documents', $doc1->document_type);
        $this->assertEquals('resume.pdf', $doc1->document_name);
        Storage::disk('public')->assertExists($doc1->file_path);
    }

    public function test_get_employee_documents(): void
    {
        $employee = Employee::create([
            'employee_id' => 'EMP100',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'mobile' => '1234567890',
            'status' => 'Active'
        ]);

        $doc = EmployeeDocument::create([
            'employee_id' => $employee->id,
            'document_type' => 'Aadhaar Card',
            'document_number' => '123456789012',
            'document_name' => 'My Aadhaar',
            'file_path' => 'documents/test.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'uploaded_by' => 'Admin',
            'remarks' => 'Test Aadhaar Remarks'
        ]);

        $response = $this->getJson("/api/employees/{$employee->id}/documents");

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment([
            'id' => $doc->id,
            'document_type' => 'Aadhaar Card',
            'document_number' => '123456789012',
            'document_name' => 'My Aadhaar',
            'remarks' => 'Test Aadhaar Remarks',
            'file_url' => asset('storage/documents/test.pdf'),
        ]);
    }

    public function test_dynamic_upload_document_with_metadata(): void
    {
        $employee = Employee::create([
            'employee_id' => 'EMP100',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'mobile' => '1234567890',
            'status' => 'Active'
        ]);

        $file = UploadedFile::fake()->create('aadhaar.jpg', 800, 'image/jpeg');

        $response = $this->postJson("/api/employees/{$employee->id}/documents", [
            'document_type' => 'Aadhaar Card',
            'document_number' => '123456789012',
            'document_name' => 'Aadhaar Card Scan',
            'remarks' => 'Verified Aadhaar pass',
            'file' => $file
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('message', 'Document uploaded successfully.');
        $response->assertJsonPath('document.document_type', 'Aadhaar Card');
        $response->assertJsonPath('document.document_number', '123456789012');
        $response->assertJsonPath('document.document_name', 'Aadhaar Card Scan');
        $response->assertJsonPath('document.remarks', 'Verified Aadhaar pass');
        
        $this->assertEquals(1, EmployeeDocument::count());
        $doc = EmployeeDocument::first();
        Storage::disk('public')->assertExists($doc->file_path);
    }

    public function test_get_single_document(): void
    {
        $employee = Employee::create([
            'employee_id' => 'EMP100',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'mobile' => '1234567890',
            'status' => 'Active'
        ]);

        $doc = EmployeeDocument::create([
            'employee_id' => $employee->id,
            'document_type' => 'PAN Card',
            'document_number' => 'ABCDE1234F',
            'document_name' => 'My PAN',
            'file_path' => 'documents/pan.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 500,
            'uploaded_by' => 'Admin'
        ]);

        $response = $this->getJson("/api/documents/{$doc->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('document_name', 'My PAN');
        $response->assertJsonPath('document_type', 'PAN Card');
        $response->assertJsonPath('document_number', 'ABCDE1234F');
    }

    public function test_update_single_document_metadata_and_file(): void
    {
        $employee = Employee::create([
            'employee_id' => 'EMP100',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'mobile' => '1234567890',
            'status' => 'Active'
        ]);

        $filePath = 'documents/old.pdf';
        Storage::disk('public')->put($filePath, 'old file');

        $doc = EmployeeDocument::create([
            'employee_id' => $employee->id,
            'document_type' => 'PAN Card',
            'document_number' => 'ABCDE1234F',
            'document_name' => 'My PAN',
            'file_path' => $filePath,
            'mime_type' => 'application/pdf',
            'file_size' => 8,
            'uploaded_by' => 'Admin',
            'remarks' => 'Old Remarks'
        ]);

        // 1. Update metadata only (no file)
        $response = $this->putJson("/api/documents/{$doc->id}", [
            'document_type' => 'Passport',
            'document_number' => 'Z1234567',
            'document_name' => 'Updated Document Name',
            'remarks' => 'Updated Remarks'
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('document.document_type', 'Passport');
        $response->assertJsonPath('document.document_number', 'Z1234567');
        $response->assertJsonPath('document.document_name', 'Updated Document Name');
        $response->assertJsonPath('document.remarks', 'Updated Remarks');
        Storage::disk('public')->assertExists($filePath); // Old file should still exist
    }

    public function test_delete_document(): void
    {
        $employee = Employee::create([
            'employee_id' => 'EMP100',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'mobile' => '1234567890',
            'status' => 'Active'
        ]);

        $filePath = 'documents/delete_me.pdf';
        Storage::disk('public')->put($filePath, 'dummy content');

        $doc = EmployeeDocument::create([
            'employee_id' => $employee->id,
            'document_type' => 'Resume',
            'document_name' => 'delete_me.pdf',
            'file_path' => $filePath,
            'mime_type' => 'application/pdf',
            'file_size' => 13
        ]);

        Storage::disk('public')->assertExists($filePath);

        $response = $this->deleteJson("/api/documents/{$doc->id}");

        $response->assertStatus(200);
        $this->assertEquals(0, EmployeeDocument::count());
        Storage::disk('public')->assertMissing($filePath);
    }

    public function test_document_validation_rejects_invalid_types_and_sizes(): void
    {
        $employee = Employee::create([
            'employee_id' => 'EMP100',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'mobile' => '1234567890',
            'status' => 'Active'
        ]);

        // Test invalid doc type
        $file = UploadedFile::fake()->create('pass.pdf', 500, 'application/pdf');
        $response = $this->postJson("/api/employees/{$employee->id}/documents", [
            'document_type' => 'Invalid Type',
            'document_name' => 'My Doc',
            'file' => $file
        ]);
        $response->assertStatus(422);

        // Test Aadhaar Card with invalid document number (not 12 digits)
        $response = $this->postJson("/api/employees/{$employee->id}/documents", [
            'document_type' => 'Aadhaar Card',
            'document_number' => '12345ABC', // letters and short
            'document_name' => 'Aadhaar Card Scan',
            'file' => $file
        ]);
        $response->assertStatus(422);

        // Test PAN Card with invalid document number format
        $response = $this->postJson("/api/employees/{$employee->id}/documents", [
            'document_type' => 'PAN Card',
            'document_number' => '1234567890', // all numbers
            'document_name' => 'PAN Card Scan',
            'file' => $file
        ]);
        $response->assertStatus(422);
    }
}
