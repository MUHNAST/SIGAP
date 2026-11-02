<?php
namespace App\Console\Commands;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Console\Command;
class UpdateTmtPeriodik extends Command
{
    protected $signature = 'employee:sync-tmt';
    protected $deskripsi = 'Update otomatis TMT KGB dan Pangkat';
    public function handle(): int
    {
        $today = Carbon::today();
        Employee::query()
            ->chunkById(
                100,
                function ($employees) use ($today) {
                    foreach ($employees as $employee) {
                        $changed = false;
                        /*
                        |--------------------------------------------------------------------------
                        | TMT KGB
                        |--------------------------------------------------------------------------
                        */
                        if ($employee->tmt_kgb_baru) {
                            $tmtKgb = Carbon::parse(
                                $employee->tmt_kgb_baru
                            );
                            $oldKgbTmt = $tmtKgb->copy();
                            while ($tmtKgb->lte($today)) {
                                $tmtKgb->addYears(2);
                            }
                            if (
                                !$tmtKgb->equalTo(
                                    $oldKgbTmt
                                )
                            ) {
                                $employee->tmt_kgb_lama =
                                    $tmtKgb->copy()->subYears(2);
                                $employee->tmt_kgb_baru =
                                    $tmtKgb;
                                $changed = true;
                            }
                        }
                        /*
                        |--------------------------------------------------------------------------
                        | TMT Pangkat
                        |--------------------------------------------------------------------------
                        */
                        if ($employee->tmt_pangkat_baru) {
                            $tmtPangkat = Carbon::parse(
                                $employee->tmt_pangkat_baru
                            );
                            $oldRankTmt =
                                $tmtPangkat->copy();
                            while (
                                $tmtPangkat->lte($today)
                            ) {
                                $tmtPangkat->addYears(4);
                            }
                            if (
                                !$tmtPangkat->equalTo(
                                    $oldRankTmt
                                )
                            ) {
                                $employee->tmt_pangkat_lama =
                                    $tmtPangkat
                                        ->copy()
                                        ->subYears(4);
                                $employee->tmt_pangkat_baru =
                                    $tmtPangkat;
                                $changed = true;
                            }
                        }
                        /*
                        |--------------------------------------------------------------------------
                        | Simpan hanya jika ada perubahan
                        |--------------------------------------------------------------------------
                        */
                        if ($changed) {
                            $employee->save();
                        }
                    }
                },
                'nip',
                'nip'
            );
        $this->info(
            'Update TMT selesai.'
        );
        return self::SUCCESS;
    }
}
