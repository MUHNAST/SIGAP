<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\EducationLevel;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Position;
use App\Models\Pppk;
use App\Models\Rank;
use App\Models\User;
use App\Services\EmployeeService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeDocumentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function employee(string $nip = '199001012020011001'): Employee
    {
        Department::firstOrCreate(['kode' => 'DOC-WORKFLOW'], [
            'nama' => 'Unit Dokumen Workflow',
            'status' => true,
        ]);

        return Employee::create([
            'nip' => $nip,
            'nama' => 'Pegawai Dokumen',
            'kode_unit_kerja' => 'DOC-WORKFLOW',
            'status_pegawai' => 'PNS',
        ]);
    }

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => 'super_admin']);
    }

    private function updateDocument(
        Employee $employee,
        string $kind,
        UploadedFile $file
    ) {
        return $this->put(route('employee.update', $employee->nip), [
            'nip' => $employee->nip,
            'nama' => $employee->nama,
            'dokumen' => [$kind => $file],
        ]);
    }

    public function test_pdf_and_supported_image_types_upload_private_file_and_metadata(): void
    {
        Storage::fake('local');
        $employee = $this->employee();
        $admin = $this->superAdmin();
        $files = [
            ['SK_KGB', 'kgb.pdf', 'application/pdf'],
            ['SK_PANGKAT', 'pangkat.jpg', 'image/jpeg'],
            ['SK_JABATAN', 'jabatan.jpeg', 'image/jpeg'],
            ['KTP', 'ktp.png', 'image/png'],
        ];

        foreach ($files as [$kind, $filename, $mime]) {
            $response = $this->actingAs($admin)->updateDocument(
                $employee,
                $kind,
                UploadedFile::fake()->create($filename, 1, $mime)
            );
            $response->assertRedirect(route('employee.index'));
            $document = EmployeeDocument::query()
                ->where('nip', $employee->nip)
                ->where('jenis_dokumen', $kind)
                ->firstOrFail();
            $this->assertSame($filename, $document->nama_file_asli);
            $this->assertSame($admin->id, $document->id_user);
            $this->assertContains(
                $document->nama_file,
                Storage::disk('local')->allFiles()
            );
            Storage::disk('local')->assertExists($document->nama_file);
        }

        $this->assertSame(4, EmployeeDocument::query()
            ->where('nip', $employee->nip)
            ->count());
    }

    public function test_employee_creation_can_store_a_document_with_its_metadata(): void
    {
        Storage::fake('local');
        $department = Department::firstOrCreate(['kode' => 'DOC-WORKFLOW'], [
            'nama' => 'Unit Dokumen Workflow',
            'status' => true,
        ]);
        $education = EducationLevel::create([
            'kode' => 'DOC-S1',
            'nama' => 'Sarjana Dokumen',
            'status' => true,
            'pangkat_pns_minimum' => 1,
            'pangkat_pns_maksimum' => 20,
        ]);
        $rank = Rank::create([
            'kode' => 'DOC-III/A',
            'nama' => 'Pangkat Dokumen',
            'urutan_pangkat' => 1,
        ]);
        $pppk = Pppk::create([
            'kode' => 'DOC-0',
            'nama' => 'Bukan PPPK',
            'urutan_golongan' => 0,
        ]);
        $position = Position::create([
            'kode' => 'DOC-POS',
            'nama' => 'Jabatan Dokumen',
            'usia_pensiun' => 58,
        ]);
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)
            ->post(route('employee.store'), [
                'nip' => '199001012020011009',
                'nik' => '3375010101900009',
                'nama' => 'Pegawai Upload Saat Dibuat',
                'status_pegawai' => 'PNS',
                'id_pangkat' => $rank->id,
                'kode_pppk' => $pppk->kode,
                'tingkat_pendidikan' => $education->kode,
                'kode_unit_kerja' => $department->kode,
                'kode_jabatan' => $position->kode,
                'tanggal_lahir' => '1990-01-01',
                'tanggal_masuk' => '2018-01-01',
                'tmt_pangkat_lama' => '2020-01-01',
                'tmt_kgb_lama' => '2020-01-01',
                'status' => 'Aktif',
                'dokumen' => [
                    'SK_PANGKAT' => UploadedFile::fake()->create(
                        'sk-pangkat.pdf',
                        1,
                        'application/pdf'
                    ),
                ],
            ])
            ->assertRedirect(route('employee.index'));

        $document = EmployeeDocument::query()
            ->where('nip', '199001012020011009')
            ->firstOrFail();
        $this->assertSame('SK_PANGKAT', $document->jenis_dokumen);
        $this->assertSame($superAdmin->id, $document->id_user);
        Storage::disk('local')->assertExists($document->nama_file);
    }

    public function test_file_at_5_mb_is_accepted_and_larger_file_is_rejected(): void
    {
        Storage::fake('local');
        $employee = $this->employee();
        $admin = $this->superAdmin();

        $this->actingAs($admin)->updateDocument(
            $employee,
            'SK_KGB',
            UploadedFile::fake()->create(
                'maximum.pdf',
                5120,
                'application/pdf'
            )
        )->assertRedirect(route('employee.index'));
        $this->assertDatabaseHas('employee_documents', [
            'nip' => $employee->nip,
            'jenis_dokumen' => 'SK_KGB',
            'nama_file_asli' => 'maximum.pdf',
        ]);

        $this->from(route('employee.edit', $employee->nip))
            ->actingAs($admin)
            ->updateDocument(
                $employee,
                'KTP',
                UploadedFile::fake()->create(
                    'too-large.pdf',
                    5121,
                    'application/pdf'
                )
            )
            ->assertSessionHasErrors(['dokumen.KTP']);
        $this->assertDatabaseMissing('employee_documents', [
            'nip' => $employee->nip,
            'jenis_dokumen' => 'KTP',
        ]);
    }

    public function test_invalid_extension_mime_mismatch_and_empty_file_are_rejected(): void
    {
        Storage::fake('local');
        $employee = $this->employee();
        $admin = $this->superAdmin();
        $invalidFiles = [
            UploadedFile::fake()->create('document.txt', 1, 'text/plain'),
            UploadedFile::fake()->create('document.pdf', 1, 'text/plain'),
        ];

        foreach ($invalidFiles as $file) {
            $this->from(route('employee.edit', $employee->nip))
                ->actingAs($admin)
                ->updateDocument($employee, 'SK_KGB', $file)
                ->assertSessionHasErrors(['dokumen.SK_KGB']);
        }

        $this->assertDatabaseCount('employee_documents', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_empty_file_is_not_stored_as_a_document(): void
    {
        Storage::fake('local');
        $employee = $this->employee();
        $admin = $this->superAdmin();

        $this->from(route('employee.edit', $employee->nip))
            ->actingAs($admin)
            ->updateDocument(
                $employee,
                'SK_KGB',
                UploadedFile::fake()->create('empty.pdf', 0, 'application/pdf')
            )
            ->assertSessionHasErrors(['dokumen.SK_KGB']);

        $this->assertDatabaseCount('employee_documents', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_replacing_document_updates_metadata_and_removes_old_file(): void
    {
        Storage::fake('local');
        $employee = $this->employee();
        $admin = $this->superAdmin();

        $this->actingAs($admin)->updateDocument(
            $employee,
            'SK_KGB',
            UploadedFile::fake()->create('old.pdf', 1, 'application/pdf')
        )->assertRedirect(route('employee.index'));
        $oldDocument = EmployeeDocument::query()->firstOrFail();
        $oldPath = $oldDocument->nama_file;
        Storage::disk('local')->assertExists($oldPath);

        $this->actingAs($admin)
            ->put(route('employee.update', $employee->nip), [
                'nip' => $employee->nip,
                'nama' => $employee->nama,
            ])
            ->assertRedirect(route('employee.index'));
        $this->assertSame($oldPath, $oldDocument->fresh()->nama_file);
        Storage::disk('local')->assertExists($oldPath);

        $this->actingAs($admin)->updateDocument(
            $employee,
            'SK_KGB',
            UploadedFile::fake()->create('replacement.pdf', 1, 'application/pdf')
        )->assertRedirect(route('employee.index'));

        $newDocument = EmployeeDocument::query()->firstOrFail();
        $this->assertSame($oldDocument->id, $newDocument->id);
        $this->assertSame('replacement.pdf', $newDocument->nama_file_asli);
        $this->assertNotSame($oldPath, $newDocument->nama_file);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($newDocument->nama_file);
        $this->assertSame(1, EmployeeDocument::query()->count());
    }

    public function test_document_metadata_failure_rolls_back_record_and_cleans_uploaded_file(): void
    {
        Storage::fake('local');
        $employee = $this->employee();
        $this->actingAs($this->superAdmin());
        DB::statement(
            "CREATE TRIGGER reject_document_metadata BEFORE INSERT ON employee_documents "
            . "BEGIN SELECT RAISE(FAIL, 'metadata insert failed'); END"
        );

        try {
            app(EmployeeService::class)->update($employee, [
                'nip' => $employee->nip,
                'nama' => $employee->nama,
                'dokumen' => [
                    'SK_KGB' => UploadedFile::fake()->create(
                        'rollback.pdf',
                        1,
                        'application/pdf'
                    ),
                ],
            ]);
            $this->fail('The metadata insert trigger should reject the document.');
        } catch (QueryException) {
            $this->assertDatabaseCount('employee_documents', 0);
            $this->assertSame([], Storage::disk('local')->allFiles());
            $this->assertDatabaseHas('employees', ['nip' => $employee->nip]);
        }
    }

    public function test_metadata_without_file_is_shown_as_unavailable_not_available(): void
    {
        Storage::fake('local');
        $employee = $this->employee();
        $document = EmployeeDocument::create([
            'nip' => $employee->nip,
            'jenis_dokumen' => 'SK_KGB',
            'nama_file' => 'employee-documents/missing/sk-kgb.pdf',
            'nama_file_asli' => 'sk-kgb.pdf',
        ]);

        $response = $this->actingAs($this->superAdmin())
            ->get(route('employee.show', $employee->nip));

        $response->assertOk()
            ->assertSee('File tidak ditemukan')
            ->assertSee('File dokumen tidak ditemukan di penyimpanan.')
            ->assertDontSee(route('employee.documents.show', [
                $employee->nip,
                $document->id,
            ]));
        $editResponse = $this->actingAs($this->superAdmin())
            ->get(route('employee.edit', $employee->nip));
        $editResponse->assertOk()
            ->assertSee('Metadata tersedia, file tidak ditemukan.')
            ->assertSee('data-existing="false"', false);

        $user = User::factory()->create([
            'role' => 'user',
            'nip' => $employee->nip,
            'kode_unit_kerja' => $employee->kode_unit_kerja,
        ]);
        $settingsResponse = $this->actingAs($user)->get(route('setting.index'));
        $settingsResponse->assertOk()
            ->assertSee('Metadata tersedia, file tidak ditemukan.')
            ->assertSee('data-existing="false"', false);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_self_service_rejects_an_empty_document_upload(): void
    {
        Storage::fake('local');
        $employee = $this->employee();
        $user = User::factory()->create([
            'role' => 'user',
            'nip' => $employee->nip,
            'kode_unit_kerja' => $employee->kode_unit_kerja,
        ]);

        $this->actingAs($user)
            ->from(route('setting.index'))
            ->put(route('setting.employee.update'), [
                'dokumen' => [
                    'SK_KGB' => UploadedFile::fake()->create(
                        'empty.pdf',
                        0,
                        'application/pdf'
                    ),
                ],
            ])
            ->assertSessionHasErrorsIn('employee', ['dokumen.SK_KGB']);

        $this->assertDatabaseCount('employee_documents', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }
}
