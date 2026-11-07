<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EducationLevel;
use App\Models\Position;
use App\Models\PromotionHistory;
use App\Models\Pppk;
use App\Models\Rank;
use App\Models\Salary;
use App\Models\User;
use App\Services\EmployeeService;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_position_age_is_used_by_employee_creation_for_retirement_dates(): void
    {
        Position::create([
            'kode' => 'J-CAMAT',
            'nama' => 'Camat',
            'usia_pensiun' => 58,
        ]);
        Pppk::create([
            'kode' => '0',
            'nama' => 'Bukan PPPK',
            'urutan_golongan' => 0,
        ]);
        $user = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($user);

        $employee = app(EmployeeService::class)->create([
            'nip' => '199001012020011001',
            'nama' => 'Pegawai Uji',
            'status_pegawai' => 'PNS',
            'kode_jabatan' => 'J-CAMAT',
            'tanggal_lahir' => '1990-06-15',
        ]);

        $this->assertSame('2048-06-15', $employee->tanggal_pensiun->format('Y-m-d'));
        $this->assertSame('2048-07-01', $employee->tmt_pensiun->format('Y-m-d'));
        $this->assertSame('J-CAMAT', $employee->position->kode);
        $this->assertSame($employee->nip, Position::findOrFail('J-CAMAT')->employees->sole()->nip);
    }

    public function test_pns_rank_salary_and_pppk_grade_relations_remain_distinct(): void
    {
        $rank = Rank::create([
            'kode' => 'III/a',
            'nama' => 'Penata Muda',
            'urutan_pangkat' => 9,
        ]);
        $pppk = Pppk::create([
            'kode' => 'III',
            'nama' => 'Golongan III',
            'urutan_golongan' => 3,
        ]);
        $salary = Salary::create([
            'id_pangkat' => $rank->id,
            'tahun_masa_kerja' => 4,
            'gaji_pokok' => 2964000,
        ]);
        $pnsEmployee = Employee::create([
            'nip' => '199001012020011002',
            'nama' => 'Pegawai PNS',
            'status_pegawai' => 'PNS',
            'id_pangkat' => $rank->id,
        ]);
        $pppkEmployee = Employee::create([
            'nip' => '199001012020011003',
            'nama' => 'Pegawai PPPK',
            'status_pegawai' => 'PPPK',
            'kode_pppk' => $pppk->kode,
        ]);

        $this->assertSame($rank->id, $pnsEmployee->rank->id);
        $this->assertSame($salary->id, $rank->salaries->sole()->id);
        $this->assertSame($rank->id, $salary->rank->id);
        $this->assertSame($pnsEmployee->nip, $rank->employees->sole()->nip);
        $this->assertSame($pppk->kode, $pppkEmployee->pppk->kode);
        $this->assertSame($pppkEmployee->nip, $pppk->employees->sole()->nip);
        $this->assertSame(3, $pppk->refresh()->urutan_golongan);
        $this->assertFalse(method_exists(Salary::class, 'pppk'));
    }

    public function test_automatic_rank_promotion_can_write_its_history(): void
    {
        $oldRank = Rank::create([
            'kode' => 'III/a',
            'nama' => 'Penata Muda',
            'urutan_pangkat' => 9,
        ]);
        $newRank = Rank::create([
            'kode' => 'III/b',
            'nama' => 'Penata Muda Tingkat I',
            'urutan_pangkat' => 10,
        ]);
        EducationLevel::create([
            'kode' => 'S1',
            'nama' => 'Sarjana',
            'status' => true,
            'pangkat_pns_minimum' => 9,
            'pangkat_pns_maksimum' => 10,
        ]);
        Employee::create([
            'nip' => '199001012020011004',
            'nama' => 'Pegawai Promosi',
            'status_pegawai' => 'PNS',
            'id_pangkat' => $oldRank->id,
            'tingkat_pendidikan' => 'S1',
            'tmt_pangkat_baru' => now()->subYears(4)->toDateString(),
        ]);

        app(EmployeeService::class)->syncTmt();

        $this->assertDatabaseHas('promotion_histories', [
            'nip' => '199001012020011004',
            'id_pangkat_lama' => $oldRank->id,
            'id_pangkat_baru' => $newRank->id,
            'keterangan' => 'Kenaikan pangkat otomatis',
        ]);

        $history = PromotionHistory::where('nip', '199001012020011004')->sole();
        $this->assertSame($history->nip, $history->employee->nip);
        $this->assertSame($oldRank->id, $history->oldRank->id);
        $this->assertSame($newRank->id, $history->newRank->id);
        $this->assertSame($history->id, $oldRank->oldPromotionHistories->sole()->id);
        $this->assertSame($history->id, $newRank->newPromotionHistories->sole()->id);
    }

    public function test_sync_tmt_does_not_promote_pppk_employee(): void
    {
        $rank = Rank::create([
            'kode' => 'III/a',
            'nama' => 'Penata Muda',
            'urutan_pangkat' => 9,
        ]);
        Employee::create([
            'nip' => '199001012020011008',
            'nama' => 'Pegawai PPPK',
            'status_pegawai' => 'PPPK',
            'id_pangkat' => $rank->id,
            'tmt_pangkat_baru' => now()->subDay()->toDateString(),
        ]);

        app(EmployeeService::class)->syncTmt();

        $employee = Employee::findOrFail('199001012020011008');
        $this->assertSame($rank->id, $employee->id_pangkat);
        $this->assertSame(now()->subDay()->toDateString(), $employee->tmt_pangkat_baru->toDateString());
        $this->assertDatabaseMissing('promotion_histories', [
            'nip' => $employee->nip,
        ]);
    }

    public function test_promotion_history_rank_foreign_keys_reference_rank_records(): void
    {
        $oldRank = Rank::create([
            'kode' => 'III/a',
            'nama' => 'Penata Muda',
            'urutan_pangkat' => 9,
        ]);
        $newRank = Rank::create([
            'kode' => 'III/b',
            'nama' => 'Penata Muda Tingkat I',
            'urutan_pangkat' => 10,
        ]);
        $employee = Employee::create([
            'nip' => '199001012020011005',
            'nama' => 'Pegawai Riwayat',
            'status_pegawai' => 'PNS',
            'id_pangkat' => $newRank->id,
        ]);

        $history = PromotionHistory::create([
            'nip' => $employee->nip,
            'id_pangkat_lama' => $oldRank->id,
            'id_pangkat_baru' => $newRank->id,
            'tmt_pangkat' => now()->subYears(4)->toDateString(),
            'keterangan' => 'Kenaikan pangkat',
        ]);

        $this->assertDatabaseHas('promotion_histories', [
            'nip' => $employee->nip,
            'id_pangkat_lama' => $oldRank->id,
            'id_pangkat_baru' => $newRank->id,
        ]);
        $this->assertSame('Kenaikan pangkat', $history->refresh()->keterangan);
        $this->assertSame($employee->nip, $history->employee->nip);
        $this->assertSame($oldRank->id, $history->oldRank->id);
        $this->assertSame($newRank->id, $history->newRank->id);
    }

    public function test_kgb_salary_lookup_uses_pns_rank_and_nearest_service_year_not_above_current(): void
    {
        $rank = Rank::create([
            'kode' => 'III/a',
            'nama' => 'Penata Muda',
            'urutan_pangkat' => 9,
        ]);
        Salary::create([
            'id_pangkat' => $rank->id,
            'tahun_masa_kerja' => 2,
            'gaji_pokok' => 2873500,
        ]);
        Salary::create([
            'id_pangkat' => $rank->id,
            'tahun_masa_kerja' => 4,
            'gaji_pokok' => 2964000,
        ]);
        Employee::create([
            'nip' => '199001012020011006',
            'nama' => 'Pegawai PNS Gaji',
            'status_pegawai' => 'PNS',
            'id_pangkat' => $rank->id,
            'tahun_masa_kerja' => 3,
            'tmt_kgb_lama' => now()->subYears(2)->toDateString(),
            'tmt_kgb_baru' => now()->addMonth()->toDateString(),
        ]);
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));

        $surat = app(ReportService::class)->prepareSuratData(
            Employee::findOrFail('199001012020011006')
        );

        $this->assertSame(2873500, $surat->gaji_pokok_lama);
        $this->assertSame(2964000, $surat->gaji_pokok_baru);
        $this->assertSame(5, $surat->tahun_masa_kerja_baru);
    }

    public function test_pppk_kgb_salary_has_no_direct_grade_schedule_in_current_schema(): void
    {
        $defaultRank = Rank::create([
            'kode' => '0',
            'nama' => 'Bukan PNS',
            'urutan_pangkat' => 0,
        ]);
        Pppk::create([
            'kode' => 'III',
            'nama' => 'Golongan III',
            'urutan_golongan' => 3,
        ]);
        Employee::create([
            'nip' => '199001012020011007',
            'nama' => 'Pegawai PPPK Gaji',
            'status_pegawai' => 'PPPK',
            'id_pangkat' => $defaultRank->id,
            'kode_pppk' => 'III',
            'tahun_masa_kerja' => 4,
            'tmt_kgb_lama' => now()->subYears(2)->toDateString(),
            'tmt_kgb_baru' => now()->addMonth()->toDateString(),
        ]);
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));

        $surat = app(ReportService::class)->prepareSuratData(
            Employee::findOrFail('199001012020011007')
        );

        $this->assertSame(0, $surat->gaji_pokok_lama);
        $this->assertSame(0, $surat->gaji_pokok_baru);
    }
}
