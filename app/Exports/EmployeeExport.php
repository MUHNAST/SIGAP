<?php
namespace App\Exports;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
class EmployeeExport extends StringValueBinder implements
    FromArray,
    ShouldAutoSize,
    WithColumnFormatting,
    WithCustomValueBinder,
    WithStyles
{
    private array $columns;
    private array $exportRows = [];
    private array $headerRows = [];
    private array $unitRows = [];
    private array $dataRows = [];
    private array $metadataRows = [];
    private int $columnCount;
    public function __construct( private Collection $employees, array $columns) {
        $availableColumns = self::availableColumns();
        $this->columns = array_values(array_unique(
            array_filter(
                $columns,
                fn ($column) => is_string($column) && array_key_exists($column, $availableColumns)
            )
        ));
        if (empty($this->columns)) {
            throw new \InvalidArgumentException(
                'Minimal satu kolom harus dipilih untuk export.'
            );
        }
        $this->columnCount = count($this->columns) + 1;
        $this->buildRows();
    }
    public static function availableColumns(): array
    {
        return [
            'nama' => 'Nama',
            'nik' => 'NIK',
            'nip' => 'NIP',
            'email' => 'Email',
            'no_hp' => 'no_hp',
            'status_pegawai' => 'Status Pegawai',
            'pangkat_pns' => 'Pangkat PNS',
            'golongan_pppk' => 'Golongan PPPK',
            'jabatan' => 'Jabatan',
            'unit_kerja' => 'Unit Kerja',
            'pendidikan' => 'Pendidikan',
            'tempat_lahir' => 'Tempat Lahir',
            'tanggal_lahir' => 'Tanggal Lahir',
            'jenis_kelamin' => 'Jenis Kelamin',
            'agama' => 'Agama',
            'status_nikah' => 'Status Pernikahan',
            'provinsi' => 'Provinsi',
            'kota' => 'Kota/Kabupaten',
            'kecamatan' => 'Kecamatan',
            'kelurahan' => 'Kelurahan/Desa',
            'alamat' => 'Alamat',
            'tanggal_masuk' => 'Tanggal Masuk',
            'masa_kerja' => 'Masa Kerja',
            'tmt_pangkat_lama' => 'TMT Pangkat Lama',
            'tmt_pangkat_baru' => 'TMT Pangkat Baru',
            'tmt_kgb_lama' => 'TMT KGB Lama',
            'tmt_kgb_baru' => 'TMT KGB Baru',
            'tanggal_pensiun' => 'Tanggal Pensiun',
            'tmt_pensiun' => 'TMT Pensiun',
            'status' => 'Status',
        ];
    }
    public function array(): array
    {
        return $this->exportRows;
    }
    public function headings(): array
    {
        $availableColumns = self::availableColumns();
        return array_merge(
            ['No.'],
            array_map(
                fn ($column) => $availableColumns[$column],
                $this->columns
            )
        );
    }
    public function map($employee): array
    {
        return $this->mapEmployee($employee);
    }
    private function buildRows(): void
    {
        $availableColumns = self::availableColumns();
        // Kelompokkan pegawai berdasarkan unit kerja.
        // Unit kerja tanpa pegawai tidak akan menghasilkan tabel.
        $groups = $this->employees
            ->filter(fn ($employee) => $employee->department !== null)
            ->groupBy(fn ($employee) => $employee->department->nama)
            ->sortKeys();
        foreach ($groups as $unitName => $employees) {
            if ($employees->isEmpty()) {
                continue;
            }
            // Judul unit kerja.
            $this->exportRows[] = array_merge(
                ["UNIT KERJA: {$unitName}"],
                array_fill(0, $this->columnCount - 1, '')
            );
            $this->unitRows[] = count($this->exportRows);
            // Header kolom untuk setiap unit kerja.
            $this->exportRows[] = array_merge(
                ['No.'],
                array_map(
                    fn ($column) => $availableColumns[$column],
                    $this->columns
                )
            );
            $this->headerRows[] = count($this->exportRows);
            // Data pegawai dengan nomor urut dimulai dari 1.
            foreach ($employees->values() as $index => $employee) {
                $this->exportRows[] = array_merge(
                    [$index + 1],
                    $this->mapEmployee($employee)
                );
                $this->dataRows[] = count($this->exportRows);
            }
            // Baris kosong sebagai pemisah antartabel.
            $this->exportRows[] = array_fill(
                0,
                $this->columnCount,
                ''
            );
        }
        // Informasi pembuat dan tanggal cetak hanya sekali,
        // di bagian paling bawah setelah seluruh tabel.
        $user = Auth::user();
        $namaPembuat = $user?->nama ?? 'Pengguna';
        $tanggalCetak = now('Asia/Jakarta')
            ->translatedFormat('d F Y, H:i') . ' WIB';
        $this->exportRows[] = [
            'Dibuat oleh: ' . $namaPembuat,
            ...array_fill(0, $this->columnCount - 1, ''),
        ];
        $this->metadataRows[] = count($this->exportRows);
        $this->exportRows[] = [
            'Tanggal cetak: ' . $tanggalCetak,
            ...array_fill(0, $this->columnCount - 1, ''),
        ];
        $this->metadataRows[] = count($this->exportRows);
    }
    private function mapEmployee($employee): array
    {
        $pangkatPns = null;
        if (
            $employee->status_pegawai === 'PNS'
            && $employee->rank
            && (int) $employee->rank->urutan_pangkat > 0
        ) {
            $pangkatPns = $employee->rank->nama
                . ' (' . $employee->rank->kode . ')';
        }
        $golonganPppk = null;
        if (
            $employee->status_pegawai === 'PPPK'
            && $employee->pppk
            && (int) $employee->pppk->urutan_golongan > 0
        ) {
            $golonganPppk = $employee->pppk->nama
                . ' (' . $employee->pppk->kode . ')';
        }
        $data = [
            'nama' => $employee->nama,
            'nik' => $employee->nik !== null
                ? (string) $employee->nik : null,
            'nip' => $employee->nip !== null
                ? (string) $employee->nip : null,
            'email' => $employee->email,
            'no_hp' => $employee->no_hp !== null
                ? (string) $employee->no_hp : null,
            'status_pegawai' => $employee->status_pegawai,
            'pangkat_pns' => $pangkatPns,
            'golongan_pppk' => $golonganPppk,
            'jabatan' => $employee->position?->nama,
            'unit_kerja' => $employee->department?->nama,
            'pendidikan' => $employee->educationLevel?->nama,
            'tempat_lahir' => $employee->birthplace?->nama,
            'tanggal_lahir' => $employee->tanggal_lahir?->format('Y-m-d'),
            'jenis_kelamin' => $employee->jenis_kelamin,
            'agama' => $employee->agama,
            'status_nikah' => $employee->status_nikah,
            'provinsi' => $employee->province?->nama,
            'kota' => $employee->city?->nama,
            'kecamatan' => $employee->district?->nama,
            'kelurahan' => $employee->urbanVillage?->nama,
            'alamat' => $employee->alamat,
            'tanggal_masuk' => $employee->tanggal_masuk?->format('Y-m-d'),
            'masa_kerja' => sprintf(
                '%d Tahun %02d Bulan',
                $employee->tahun_masa_kerja ?? 0,
                $employee->bulan_masa_kerja ?? 0
            ),
            'tmt_pangkat_lama' => $employee->tmt_pangkat_lama?->format('Y-m-d'),
            'tmt_pangkat_baru' => $employee->tmt_pangkat_baru?->format('Y-m-d'),
            'tmt_kgb_lama' => $employee->tmt_kgb_lama?->format('Y-m-d'),
            'tmt_kgb_baru' => $employee->tmt_kgb_baru?->format('Y-m-d'),
            'tanggal_pensiun' => $employee->tanggal_pensiun?->format('Y-m-d'),
            'tmt_pensiun' => $employee->tmt_pensiun?->format('Y-m-d'),
            'status' => $employee->status,
        ];
        return array_map(
            fn ($column) => $data[$column] ?? null,
            $this->columns
        );
    }
    public function bindValue(Cell $cell, mixed $value): bool
    {
        $columnIndex = Coordinate::columnIndexFromString(
            $cell->getColumn()
        ) - 1;
        // Baris judul, header, dan metadata tidak perlu
        // diproses sebagai kolom data pegawai.
        if (in_array(
            $cell->getRow(),
            array_merge(
                $this->headerRows,
                $this->unitRows,
                $this->metadataRows
            ),
            true
        )) {
            return parent::bindValue($cell, $value);
        }
        // Kolom A adalah nomor urut.
        // Kolom B dan seterusnya mengikuti kolom yang dipilih.
        $field = $this->columns[$columnIndex - 1] ?? null;
        if (in_array($field, ['nik', 'nip', 'email', 'no_hp'], true)) {
            $cell->setValueExplicit(
                $value !== null ? (string) $value : '',
                DataType::TYPE_STRING
            );
            return true;
        }
        return parent::bindValue($cell, $value);
    }
    public function columnFormats(): array
    {
        $formats = [];
        foreach ($this->columns as $index => $field) {
            if (in_array($field, ['nik', 'nip', 'email', 'no_hp'], true)) {
                $column = Coordinate::stringFromColumnIndex($index + 2);
                $formats[$column] = NumberFormat::FORMAT_TEXT;
            }
        }
        return $formats;
    }
    public function styles(Worksheet $sheet): array
    {
        $styles = [];
        // Warna judul unit kerja: biru muda.
        foreach ($this->unitRows as $row) {
            $styles[$row] = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '1F1F1F'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D9EAF7'],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ];
            $styles["A{$row}:{$this->lastColumn()}{$row}"] = [
                'borders' => [
                    'bottom' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '9EADBA'],
                    ],
                ],
            ];
        }
        // Header tabel: biru tua dengan teks putih.
        foreach ($this->headerRows as $row) {
            $styles[$row] = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1F4E78'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                ],
            ];
        }
        // Isi tabel: warna selang-seling putih dan biru muda,
        // dilengkapi garis pembatas setiap sel.
        foreach ($this->dataRows as $index => $row) {
            $warna = $index % 2 === 0 ? 'FFFFFF' : 'EAF3F8';
            $styles["A{$row}:{$this->lastColumn()}{$row}"] = [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $warna],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'D6E0E8'],
                    ],
                ],
            ];
        }
        // Informasi pembuat dan tanggal cetak di bagian bawah,
        // rata kiri dan hanya ditampilkan satu kali.
        foreach ($this->metadataRows as $row) {
            $styles[$row] = [
                'font' => [
                    'color' => ['rgb' => '404040'],
                    'size' => 10,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ];
        }
        return $styles;
    }
    private function lastColumn(): string
    {
        return Coordinate::stringFromColumnIndex($this->columnCount);
    }
}
