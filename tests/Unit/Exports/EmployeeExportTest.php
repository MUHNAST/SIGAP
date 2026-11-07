<?php

namespace Tests\Unit\Exports;

use App\Exports\EmployeeExport;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Tests\TestCase;

class EmployeeExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_maps_selected_columns_grouped_by_department(): void
    {
        Department::create([
            'kode' => 'UNIT-01',
            'nama' => 'Unit A',
            'status' => true,
        ]);
        $employee = Employee::create([
            'nip' => '199001012020011001',
            'nama' => 'Budi Santoso',
            'kode_unit_kerja' => 'UNIT-01',
            'status_pegawai' => 'PNS',
            'tahun_masa_kerja' => 5,
            'bulan_masa_kerja' => 3,
        ]);
        $this->actingAs(User::factory()->create(['nama' => 'Penguji']));
        $export = new EmployeeExport(collect([$employee]), ['nip', 'nama']);

        $this->assertSame(['No.', 'NIP', 'Nama'], $export->headings());
        $this->assertSame('UNIT KERJA: Unit A', $export->array()[0][0]);
        $this->assertSame([1, '199001012020011001', 'Budi Santoso'], $export->array()[2]);
        $this->assertSame('Budi Santoso', $export->map($employee)[1]);
        $this->assertSame(3, count($export->array()[0]));
    }

    public function test_export_rejects_empty_column_selection(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new EmployeeExport(collect(), []);
    }

    public function test_export_binds_identifiers_as_text_and_provides_styles_and_formats(): void
    {
        Department::create([
            'kode' => 'UNIT-02',
            'nama' => 'Unit B',
            'status' => true,
        ]);
        $employee = Employee::create([
            'nip' => '199001012020011002',
            'nama' => 'Siti Aminah',
            'kode_unit_kerja' => 'UNIT-02',
        ]);
        $export = new EmployeeExport(collect([$employee]), ['nip', 'nama']);
        $cell = (new Worksheet())->getCell('B4');

        $this->assertTrue($export->bindValue($cell, '001234567890123456'));
        $this->assertSame(DataType::TYPE_STRING, $cell->getDataType());
        $this->assertInstanceOf(StringValueBinder::class, $export);
        $this->assertSame(['B' => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT], $export->columnFormats());
        $this->assertArrayHasKey(1, $export->styles(new Worksheet()));
    }

    public function test_csv_output_contains_selected_data_and_does_not_add_letterhead(): void
    {
        $department = Department::create([
            'kode' => 'UNIT-CSV',
            'nama' => 'Unit CSV',
            'status' => true,
        ]);
        $employee = Employee::create([
            'nip' => '001234567890123456',
            'nik' => '0012345678901234',
            'nama' => 'Pegawai CSV',
            'kode_unit_kerja' => $department->kode,
        ]);
        $this->actingAs(User::factory()->create(['nama' => 'Penguji CSV']));

        $contents = Excel::raw(
            new EmployeeExport(collect([$employee]), ['nip', 'nama', 'nik']),
            ExcelFormat::CSV
        );

        $this->assertStringContainsString('UNIT KERJA: Unit CSV', $contents);
        $this->assertStringContainsString('NIP', $contents);
        $this->assertStringContainsString($employee->nip, $contents);
        $this->assertStringContainsString($employee->nik, $contents);
        $this->assertStringNotContainsString('KECAMATAN PEKALONGAN TIMUR', $contents);
        $this->assertStringNotContainsString('Logo Pemerintah Kota Pekalongan', $contents);
    }

    public function test_xlsx_output_preserves_identifier_cells_as_text(): void
    {
        $department = Department::create([
            'kode' => 'UNIT-XLSX',
            'nama' => 'Unit XLSX',
            'status' => true,
        ]);
        $employee = Employee::create([
            'nip' => '001234567890123456',
            'nik' => '0012345678901234',
            'nama' => 'Pegawai XLSX',
            'kode_unit_kerja' => $department->kode,
        ]);
        $this->actingAs(User::factory()->create(['nama' => 'Penguji XLSX']));
        $contents = Excel::raw(
            new EmployeeExport(collect([$employee]), ['nip', 'nama', 'nik']),
            ExcelFormat::XLSX
        );
        $temporaryFile = tempnam(sys_get_temp_dir(), 'sigap-export-');

        try {
            file_put_contents($temporaryFile, $contents);
            $sheet = IOFactory::load($temporaryFile)->getActiveSheet();

            $this->assertSame('UNIT KERJA: Unit XLSX', $sheet->getCell('A1')->getValue());
            $this->assertSame('NIP', $sheet->getCell('B2')->getValue());
            $this->assertSame($employee->nip, $sheet->getCell('B3')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('B3')->getDataType());
            $this->assertSame($employee->nik, $sheet->getCell('D3')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('D3')->getDataType());
            $this->assertSame('Dibuat oleh: Penguji XLSX', $sheet->getCell('A5')->getValue());
        } finally {
            if (is_file($temporaryFile)) {
                unlink($temporaryFile);
            }
        }
    }
}
