<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Repositories\CardRepository;
use App\Services\CardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CardWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createDepartment(string $code): Department
    {
        return Department::create([
            'kode' => $code,
            'nama' => 'Unit '.$code,
            'status' => true,
        ]);
    }

    private function createEmployee(
        string $nip,
        string $name,
        ?string $unit,
        array $attributes = []
    ): Employee {
        return Employee::create(array_merge([
            'nip' => $nip,
            'nama' => $name,
            'kode_unit_kerja' => $unit,
            'status' => 'Aktif',
            'status_pegawai' => 'PNS',
        ], $attributes));
    }

    public function test_upcoming_agendas_apply_their_date_status_and_employee_rules(): void
    {
        Carbon::setTestNow('2026-11-04 10:00:00');
        $today = Carbon::today();
        $this->createEmployee('199001012020011101', 'PNS Pangkat', null, [
            'tmt_pangkat_baru' => $today->copy()->addMonth()->toDateString(),
            'tmt_kgb_baru' => $today->copy()->addMonths(3)->toDateString(),
            'tmt_pensiun' => $today->copy()->addMonths(8)->toDateString(),
        ]);
        $this->createEmployee('199001012020011102', 'PNS Pangkat Di Luar Jendela', null, [
            'tmt_pangkat_baru' => $today->copy()->addMonths(3)->toDateString(),
        ]);
        $this->createEmployee('199001012020011103', 'PPPK Dengan TMT Pangkat', null, [
            'status_pegawai' => 'PPPK',
            'tmt_pangkat_baru' => $today->copy()->addMonth()->toDateString(),
        ]);
        $this->createEmployee('199001012020011104', 'PNS Tidak Aktif', null, [
            'status' => 'Nonaktif',
            'tmt_pangkat_baru' => $today->copy()->addMonth()->toDateString(),
            'tmt_kgb_baru' => $today->copy()->addMonth()->toDateString(),
            'tmt_pensiun' => $today->copy()->addMonths(5)->toDateString(),
        ]);
        $this->createEmployee('199001012020011105', 'Pegawai KGB', null, [
            'tmt_kgb_baru' => $today->copy()->addMonths(2)->toDateString(),
            'tmt_pangkat_baru' => $today->copy()->addMonths(4)->toDateString(),
            'tmt_pensiun' => $today->copy()->addMonths(8)->toDateString(),
        ]);
        $this->createEmployee('199001012020011106', 'Pegawai KGB Di Luar Jendela', null, [
            'tmt_kgb_baru' => $today->copy()->addMonths(3)->toDateString(),
        ]);
        $this->createEmployee('199001012020011107', 'Pegawai Pensiun', null, [
            'tmt_pensiun' => $today->copy()->addMonths(6)->toDateString(),
            'tmt_pangkat_baru' => $today->copy()->addMonths(4)->toDateString(),
            'tmt_kgb_baru' => $today->copy()->addMonths(4)->toDateString(),
        ]);
        $this->createEmployee('199001012020011108', 'Pegawai Pensiun Di Luar Jendela', null, [
            'tmt_pensiun' => $today->copy()->addMonths(7)->toDateString(),
        ]);

        $repository = new CardRepository();

        $this->assertSame(
            ['199001012020011101'],
            $repository->getUpcoming('kp')->pluck('nip')->all()
        );
        $this->assertSame(
            ['199001012020011105'],
            $repository->getUpcoming('kgb')->pluck('nip')->all()
        );
        $this->assertSame(
            ['199001012020011107'],
            $repository->getUpcoming('mp')->pluck('nip')->all()
        );
        $this->assertCount(0, $repository->getUpcoming('unknown'));
    }

    public function test_card_service_returns_type_metadata_and_empty_data_without_error(): void
    {
        $result = (new CardService(new CardRepository()))->getUpcoming('kp');

        $this->assertSame('Kenaikan Pangkat', $result['title']);
        $this->assertSame('tmt_pangkat_baru', $result['dateField']);
        $this->assertSame(0, $result['total']);
        $this->assertCount(0, $result['data']);

        $unknown = (new CardService(new CardRepository()))->getUpcoming('unknown');
        $this->assertSame('Data Tidak Ditemukan', $unknown['title']);
        $this->assertNull($unknown['dateField']);
        $this->assertSame(0, $unknown['total']);
    }

    public function test_card_controller_scopes_admin_and_super_admin_and_renders_scheduled_data(): void
    {
        Carbon::setTestNow('2026-11-04 10:00:00');
        $first = $this->createDepartment('CARD-A');
        $second = $this->createDepartment('CARD-B');
        $scheduledDate = Carbon::today()->addMonth()->toDateString();
        $firstEmployee = $this->createEmployee(
            '199001012020011109',
            'Pegawai Unit A',
            $first->kode,
            ['tmt_pangkat_baru' => $scheduledDate]
        );
        $secondEmployee = $this->createEmployee(
            '199001012020011110',
            'Pegawai Unit B',
            $second->kode,
            ['tmt_pangkat_baru' => $scheduledDate]
        );
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => $first->kode,
        ]);
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->get(route('card.index', 'kp'))
            ->assertOk()
            ->assertViewIs('employee.card-index')
            ->assertViewHas('data', function (array $data) use ($firstEmployee) {
                return $data['total'] === 1
                    && $data['data']->sole()->nip === $firstEmployee->nip;
            })
            ->assertSee($firstEmployee->nama)
            ->assertDontSee($secondEmployee->nama);

        $this->actingAs($superAdmin)
            ->get(route('card.index', 'kp'))
            ->assertOk()
            ->assertViewHas('data', function (array $data) use ($firstEmployee, $secondEmployee) {
                return $data['total'] === 2
                    && $data['data']->pluck('nip')->all() === [
                        $firstEmployee->nip,
                        $secondEmployee->nip,
                    ];
            });
    }

    public function test_card_controller_denies_users_and_unknown_agenda_types(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('card.index', 'kp'))
            ->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => 'super_admin']))
            ->get('/card/unknown')
            ->assertNotFound();
    }

    public function test_admin_without_unit_is_denied_access_to_the_card_route(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('card.index', 'kp'))
            ->assertForbidden();
    }
}
