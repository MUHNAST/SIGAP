<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use App\Http\Middleware\CheckRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeDocumentControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createEmployee(string $nip = '199001012020011001'): Employee
    {
        Department::firstOrCreate(['kode' => 'DOC-01'], [
            'nama' => 'Unit Dokumen',
            'status' => true,
        ]);

        return Employee::create([
            'nip' => $nip,
            'nama' => 'Pegawai Dokumen',
            'kode_unit_kerja' => 'DOC-01',
            'status_pegawai' => 'PNS',
        ]);
    }

    private function createOtherDepartment(): void
    {
        Department::create([
            'kode' => 'DOC-02',
            'nama' => 'Unit Lain',
            'status' => true,
        ]);
    }

    private function createDocument(
        Employee $employee,
        string $path = 'employee-documents/test/document.pdf',
        string $originalName = 'surat.pdf'
    ): EmployeeDocument {
        return EmployeeDocument::create([
            'nip' => $employee->nip,
            'jenis_dokumen' => 'SK_KGB',
            'nama_file' => $path,
            'nama_file_asli' => $originalName,
        ]);
    }

    public function test_super_admin_can_view_an_employee_document(): void
    {
        Storage::fake('local');
        $employee = $this->createEmployee();
        $document = $this->createDocument($employee);
        Storage::disk('local')->put($document->nama_file, 'PDF content');
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin)->get(route(
            'employee.documents.show',
            [$employee->nip, $document->id]
        ));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_admin_cannot_view_document_from_another_department(): void
    {
        Storage::fake('local');
        $employee = $this->createEmployee();
        $document = $this->createDocument($employee);
        $this->createOtherDepartment();
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => 'DOC-02',
        ]);

        $response = $this->actingAs($admin)->get(route(
            'employee.documents.show',
            [$employee->nip, $document->id]
        ));

        $response->assertForbidden();
    }

    public function test_admin_can_view_document_from_their_department(): void
    {
        Storage::fake('local');
        $employee = $this->createEmployee();
        $document = $this->createDocument($employee);
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => 'DOC-01',
        ]);
        Storage::disk('local')->put($document->nama_file, 'PDF content');

        $response = $this->actingAs($admin)->get(route(
            'employee.documents.show',
            [$employee->nip, $document->id]
        ));

        $response->assertOk();
    }

    public function test_user_can_view_only_their_own_document(): void
    {
        Storage::fake('local');
        $employee = $this->createEmployee();
        $document = $this->createDocument($employee);
        Storage::disk('local')->put($document->nama_file, 'PDF content');
        $user = User::factory()->create([
            'role' => 'user',
            'nip' => $employee->nip,
        ]);

        $response = $this->actingAs($user)->get(route(
            'employee.documents.show',
            [$employee->nip, $document->id]
        ));

        $response->assertOk();
    }

    public function test_user_cannot_view_another_employees_document(): void
    {
        Storage::fake('local');
        $employee = $this->createEmployee();
        $otherEmployee = $this->createEmployee('199001012020011002');
        $document = $this->createDocument($employee);
        $user = User::factory()->create([
            'role' => 'user',
            'nip' => $otherEmployee->nip,
        ]);

        $response = $this->actingAs($user)->get(route(
            'employee.documents.show',
            [$employee->nip, $document->id]
        ));

        $response->assertForbidden();
    }

    public function test_controller_rejects_an_unsupported_role(): void
    {
        Storage::fake('local');
        $employee = $this->createEmployee();
        $document = $this->createDocument($employee);
        $unsupportedUser = User::factory()->create(['role' => 'guest']);

        $response = $this->withoutMiddleware(CheckRole::class)
            ->actingAs($unsupportedUser)
            ->get(route(
                'employee.documents.show',
                [$employee->nip, $document->id]
            ));

        $response->assertForbidden();
    }

    public function test_document_with_path_traversal_is_rejected(): void
    {
        Storage::fake('local');
        $employee = $this->createEmployee();
        $document = $this->createDocument(
            $employee,
            '../outside.pdf'
        );
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin)->get(route(
            'employee.documents.show',
            [$employee->nip, $document->id]
        ));

        $response->assertForbidden();
    }

    public function test_missing_document_file_returns_not_found(): void
    {
        Storage::fake('local');
        $employee = $this->createEmployee();
        $document = $this->createDocument($employee);
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin)->get(route(
            'employee.documents.show',
            [$employee->nip, $document->id]
        ));

        $response->assertNotFound();
    }

    public function test_document_extension_outside_allowlist_is_rejected(): void
    {
        Storage::fake('local');
        $employee = $this->createEmployee();
        $document = $this->createDocument(
            $employee,
            'employee-documents/test/document.txt',
            'surat.txt'
        );
        Storage::disk('local')->put($document->nama_file, 'plain text');
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin)->get(route(
            'employee.documents.show',
            [$employee->nip, $document->id]
        ));

        $response->assertForbidden();
    }

    public function test_all_allowed_document_extensions_return_the_expected_mime_type(): void
    {
        Storage::fake('local');
        $employee = $this->createEmployee();
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $extensions = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
        ];

        foreach ($extensions as $extension => $mimeType) {
            $document = $this->createDocument(
                $employee,
                "employee-documents/test/document.{$extension}",
                "surat.{$extension}"
            );
            Storage::disk('local')->put($document->nama_file, 'image content');

            $response = $this->actingAs($superAdmin)->get(route(
                'employee.documents.show',
                [$employee->nip, $document->id]
            ));

            $response->assertOk();
            $response->assertHeader('Content-Type', $mimeType);
        }
    }
}
