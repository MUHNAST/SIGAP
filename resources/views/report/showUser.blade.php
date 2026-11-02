@extends('layouts.app')
@section('content')
    @php
        use Carbon\Carbon;
        $today = now()->startOfDay();
        $jadwalAktif = collect();
        $jadwalTerlewat = collect();
        foreach (collect($data ?? []) as $item) {
            $tmt = filled($item->tmt_kgb_baru ?? null) ? Carbon::parse($item->tmt_kgb_baru)->startOfDay() : null;
            if ($tmt && $today->greaterThan($tmt)) {
                $jadwalTerlewat->push($item);
            } else {
                $jadwalAktif->push($item);
            }
        }
    @endphp
    <div class="min-h-screen bg-slate-50 px-4 py-6 sm:px-6 lg:px-8">
        {{-- HEADER --}}
        <div class="mb-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-slate-800 sm:text-3xl">
                        {{ $title ?? 'Laporan Kenaikan Gaji Berkala' }}
                    </h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                        {{ $sub_title ?? 'Pantau jadwal kenaikan gaji berkala dan persiapkan laporan sesuai periode yang ditentukan.' }}
                    </p>
                </div>
                <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                        <i class="fa-solid fa-calendar-check text-lg"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Tanggal hari ini</p>
                        <p class="font-bold text-slate-800" id="tanggal-hari-ini">
                            {{ $today->translatedFormat('d F Y') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
        {{-- INFORMASI PERIODE --}}
        <div class="mb-6 flex gap-3 rounded-2xl border border-blue-100 bg-blue-50 p-4 sm:p-5">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-blue-600 shadow-sm">
                <i class="fa-solid fa-circle-info text-lg"></i>
            </div>
            <div>
                <h3 class="font-bold text-blue-900">
                    Ketentuan Preview Laporan
                </h3>
                <p class="mt-1 text-sm leading-6 text-blue-800">
                    Preview tersedia mulai dua bulan sebelum TMT KGB sampai dengan tanggal TMT KGB.
                    Hitung mundur dihitung berdasarkan tanggal hari ini dan TMT KGB berikutnya.
                    Jadwal yang telah melewati TMT ditampilkan pada keterangan terpisah.
                </p>
            </div>
        </div>
        {{-- DAFTAR JADWAL AKTIF --}}

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            {{-- HEADER JADWAL --}}
            <div class="flex flex-col gap-3 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-extrabold text-slate-800">
                        Jadwal Kenaikan Gaji Berkala
                    </h2>
                    <p class="mt-1 text-sm leading-6 text-slate-500">
                        Informasi jadwal KGB berdasarkan data kepegawaian Anda.
                    </p>
                </div>

                <div
                    class="inline-flex w-fit items-center gap-2 rounded-full bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700">
                    <i class="fa-solid fa-calendar-check"></i>
                    {{ $jadwalAktif->count() }} jadwal aktif
                </div>
            </div>

            @forelse ($jadwalAktif as $item)
                @php
                    $tmtLama = filled($item->tmt_kgb_lama ?? null)
                        ? Carbon::parse($item->tmt_kgb_lama)->startOfDay()
                        : null;

                    $tmtBaru = filled($item->tmt_kgb_baru ?? null)
                        ? Carbon::parse($item->tmt_kgb_baru)->startOfDay()
                        : null;

                    $awalPeriode = $tmtBaru ? $tmtBaru->copy()->subMonthsNoOverflow(2) : null;

                    $previewTersedia =
                        $tmtBaru &&
                        $awalPeriode &&
                        $today->greaterThanOrEqualTo($awalPeriode) &&
                        $today->lessThanOrEqualTo($tmtBaru);

                    $namaPegawai = $item->nama ?? ($item->nama_pegawai ?? ($user->nama ?? 'Pegawai'));

                    $nipPegawai = $item->nip ?? ($user->nip ?? '-');

                    $sisaTahun = 0;
                    $sisaBulan = 0;
                    $sisaHari = 0;

                    if ($tmtBaru && $today->lessThanOrEqualTo($tmtBaru)) {
                        $selisih = $today->diff($tmtBaru);
                        $sisaTahun = $selisih->y;
                        $sisaBulan = $selisih->m;
                        $sisaHari = $selisih->d;
                    }
                @endphp

                <div class="border-b border-slate-100 p-5 last:border-b-0 sm:p-6">

                    {{-- IDENTITAS PEGAWAI --}}
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex items-start gap-3">
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                                <i class="fa-solid fa-user-tie text-lg"></i>
                            </div>

                            <div class="min-w-0">
                                <h3 class="text-lg font-extrabold text-slate-800">
                                    {{ $namaPegawai }}
                                </h3>
                                <p class="mt-1 text-sm text-slate-500">
                                    NIP:
                                    <span class="font-semibold text-slate-700">
                                        {{ $nipPegawai }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        @if (!$tmtBaru)
                            <span
                                class="inline-flex w-fit items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600">
                                <i class="fa-solid fa-circle-info"></i>
                                TMT Belum Tersedia
                            </span>
                        @elseif ($previewTersedia)
                            <span
                                class="inline-flex w-fit items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700">
                                <i class="fa-solid fa-circle-check"></i>
                                Siap Preview
                            </span>
                        @else
                            <span
                                class="inline-flex w-fit items-center gap-2 rounded-full bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700">
                                <i class="fa-solid fa-hourglass-half"></i>
                                Belum Memasuki Periode
                            </span>
                        @endif
                    </div>

                    {{-- INFORMASI TANGGAL --}}
                    <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-center gap-2 text-slate-500">
                                <i class="fa-regular fa-calendar"></i>
                                <p class="text-xs font-semibold">TMT KGB Lama</p>
                            </div>
                            <p class="mt-3 text-sm font-extrabold text-slate-800">
                                {{ $tmtLama ? $tmtLama->translatedFormat('d F Y') : 'Belum tersedia' }}
                            </p>
                        </div>

                        <div class="rounded-xl border border-blue-100 bg-blue-50/60 p-4">
                            <div class="flex items-center gap-2 text-blue-700">
                                <i class="fa-solid fa-calendar-day"></i>
                                <p class="text-xs font-semibold">TMT KGB Berikutnya</p>
                            </div>
                            <p class="mt-3 text-sm font-extrabold text-slate-800">
                                {{ $tmtBaru ? $tmtBaru->translatedFormat('d F Y') : 'Belum tersedia' }}
                            </p>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-center gap-2 text-slate-500">
                                <i class="fa-regular fa-clock"></i>
                                <p class="text-xs font-semibold">Awal Periode Preview</p>
                            </div>
                            <p class="mt-3 text-sm font-extrabold text-slate-800">
                                {{ $awalPeriode ? $awalPeriode->translatedFormat('d F Y') : 'Belum tersedia' }}
                            </p>
                        </div>
                    </div>

                    {{-- HITUNG MUNDUR --}}
                    @if ($tmtBaru && $today->lessThanOrEqualTo($tmtBaru))
                        <div class="mt-5 overflow-hidden rounded-2xl border border-blue-100 bg-gradient-to-br from-blue-50 to-white"
                            data-kgb-countdown data-tmt="{{ $tmtBaru->toDateString() }}">
                            <div class="p-5 sm:p-6">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-sm font-extrabold text-blue-950">
                                            <i class="fa-solid fa-hourglass-half mr-2 text-blue-600"></i>
                                            Hitung Mundur TMT KGB
                                        </p>
                                        <p class="mt-1 text-xs leading-5 text-slate-500">
                                            Sisa waktu hingga jadwal kenaikan gaji berkala berikutnya.
                                        </p>
                                    </div>

                                    <span class="text-xs font-semibold text-blue-700">
                                        <i class="fa-regular fa-calendar mr-1"></i>
                                        {{ $tmtBaru->translatedFormat('d F Y') }}
                                    </span>
                                </div>

                                <div class="mt-5 grid grid-cols-3 gap-3 text-center" data-countdown-output>
                                    <div class="rounded-xl border border-blue-100 bg-white p-3 sm:p-5">
                                        <p class="text-3xl font-black tabular-nums text-blue-700 sm:text-4xl" data-years>
                                            {{ $sisaTahun }}
                                        </p>
                                        <p class="mt-1 text-xs font-semibold text-slate-500">Tahun</p>
                                    </div>

                                    <div class="rounded-xl border border-blue-100 bg-white p-3 sm:p-5">
                                        <p class="text-3xl font-black tabular-nums text-blue-700 sm:text-4xl" data-months>
                                            {{ $sisaBulan }}
                                        </p>
                                        <p class="mt-1 text-xs font-semibold text-slate-500">Bulan</p>
                                    </div>

                                    <div class="rounded-xl border border-blue-100 bg-white p-3 sm:p-5">
                                        <p class="text-3xl font-black tabular-nums text-blue-700 sm:text-4xl" data-days>
                                            {{ $sisaHari }}
                                        </p>
                                        <p class="mt-1 text-xs font-semibold text-slate-500">Hari</p>
                                    </div>
                                </div>

                                @if ($awalPeriode && $today->lt($awalPeriode))
                                    <div class="mt-4 flex items-start gap-2 rounded-xl bg-amber-50 p-3 text-amber-800">
                                        <i class="fa-solid fa-circle-info mt-0.5"></i>
                                        <p class="text-xs leading-5">
                                            Preview laporan mulai tersedia pada
                                            <strong>{{ $awalPeriode->translatedFormat('d F Y') }}</strong>.
                                        </p>
                                    </div>
                                @else
                                    <div class="mt-4 flex items-start gap-2 rounded-xl bg-emerald-50 p-3 text-emerald-800">
                                        <i class="fa-solid fa-circle-check mt-0.5"></i>
                                        <p class="text-xs leading-5">
                                            Periode preview telah dimulai. Anda dapat membuka preview laporan sesuai
                                            ketentuan sistem.
                                        </p>
                                    </div>
                                @endif

                                {{-- TOMBOL PREVIEW --}}
                                <div class="mt-5">
                                    @if ($previewTersedia)
                                        <a href="{{ route('report.kgb.user.preview', ['nip' => $item->nip]) }}"
                                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-blue-700 sm:w-auto">
                                            <i class="fa-solid fa-eye"></i>
                                            Preview Surat KGB
                                        </a>
                                    @else
                                        <button type="button" disabled
                                            class="inline-flex w-full cursor-not-allowed items-center justify-center gap-2 rounded-xl bg-slate-200 px-4 py-3 text-sm font-bold text-slate-500 sm:w-auto">
                                            <i class="fa-solid fa-lock"></i>
                                            Preview Belum Tersedia
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @elseif (!$tmtBaru)
                        <div class="mt-5 flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <i class="fa-solid fa-circle-info mt-0.5 text-slate-400"></i>
                            <div>
                                <p class="text-sm font-bold text-slate-700">
                                    Hitung Mundur Belum Tersedia
                                </p>
                                <p class="mt-1 text-xs leading-5 text-slate-500">
                                    TMT KGB berikutnya belum tersedia sehingga sisa waktu belum dapat dihitung.
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="px-6 py-16 text-center">
                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-blue-50 text-blue-500">
                        <i class="fa-solid fa-calendar-xmark text-3xl"></i>
                    </div>

                    <h3 class="mt-5 text-lg font-extrabold text-slate-800">
                        Belum Ada Jadwal KGB Aktif
                    </h3>

                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">
                        Saat ini tidak ada jadwal KGB aktif yang dapat ditampilkan.
                        Silakan periksa data kepegawaian atau hubungi pengelola kepegawaian.
                    </p>
                </div>
            @endforelse
        </div>

        {{-- KETERANGAN JADWAL TERLEWATI --}}
        @if ($jadwalTerlewat->isNotEmpty())
            <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5">
                <div class="flex items-start gap-3">
                    <i class="fa-solid fa-triangle-exclamation mt-1 text-amber-600"></i>
                    <div>
                        <h2 class="font-extrabold text-amber-900">
                            Periode KGB Telah Terlewati
                        </h2>
                        <p class="mt-1 text-sm leading-6 text-amber-800">
                            Jadwal berikut tidak lagi ditampilkan sebagai jadwal aktif karena tanggal TMT KGB telah
                            terlewati.
                            Silakan hubungi pengelola kepegawaian untuk memastikan tindak lanjut administrasinya.
                        </p>
                    </div>
                </div>
                <div class="mt-4 space-y-3">
                    @foreach ($jadwalTerlewat as $item)
                        @php
                            $tmtTerlewat = filled($item->tmt_kgb_baru ?? null)
                                ? Carbon::parse($item->tmt_kgb_baru)->startOfDay()
                                : null;
                            $hariTerlambat = $tmtTerlewat ? (int) $tmtTerlewat->diffInDays($today) : null;
                        @endphp
                        <div class="rounded-xl border border-amber-200 bg-white p-4">
                            <p class="font-bold text-slate-800">
                                {{ $item->nama ?? ($item->nama_pegawai ?? 'Pegawai') }}
                            </p>
                            <p class="mt-1 text-sm text-slate-600">
                                TMT KGB:
                                {{ $tmtTerlewat ? $tmtTerlewat->translatedFormat('d F Y') : 'Belum tersedia' }}
                            </p>
                            @if ($hariTerlambat !== null)
                                <p class="mt-1 text-sm font-semibold text-amber-700">
                                    Terlewati {{ $hariTerlambat }} hari.
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
        {{-- CATATAN --}}
        <div class="mt-5 flex items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4">
            <i class="fa-solid fa-shield-halved mt-0.5 text-slate-400"></i>
            <p class="text-xs leading-5 text-slate-500">
                Informasi jadwal dihitung berdasarkan data kepegawaian yang tersimpan dalam SIGAP.
                Preview hanya tersedia pada periode yang ditentukan sistem.
            </p>
        </div>
    </div>
    {{-- HITUNG MUNDUR OTOMATIS --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('[data-kgb-countdown]');
            const DAY_IN_MS = 24 * 60 * 60 * 1000;

            function parseLocalDate(value) {
                if (!value) return null;
                const parts = value.split('-').map(Number);
                if (parts.length !== 3 || parts.some(Number.isNaN)) {
                    return null;
                }
                const [year, month, day] = parts;
                const date = new Date(year, month - 1, day);
                if (
                    date.getFullYear() !== year ||
                    date.getMonth() !== month - 1 ||
                    date.getDate() !== day
                ) {
                    return null;
                }
                return date;
            }

            function subtractMonthsNoOverflow(date, months) {
                const result = new Date(
                    date.getFullYear(),
                    date.getMonth(),
                    1
                );
                result.setMonth(result.getMonth() - months);
                const lastDay = new Date(
                    result.getFullYear(),
                    result.getMonth() + 1,
                    0
                ).getDate();
                result.setDate(Math.min(date.getDate(), lastDay));
                return result;
            }

            function getCalendarDifference(start, end) {
                let years = end.getFullYear() - start.getFullYear();
                let months = end.getMonth() - start.getMonth();
                let days = end.getDate() - start.getDate();
                if (days < 0) {
                    months--;
                    const previousMonthDays = new Date(
                        end.getFullYear(),
                        end.getMonth(),
                        0
                    ).getDate();
                    days += previousMonthDays;
                }
                if (months < 0) {
                    years--;
                    months += 12;
                }
                return {
                    years: Math.max(0, years),
                    months: Math.max(0, months),
                    days: Math.max(0, days)
                };
            }

            function updateCountdown() {
                const now = new Date();
                const today = new Date(
                    now.getFullYear(),
                    now.getMonth(),
                    now.getDate()
                );
                cards.forEach(card => {
                    const target = parseLocalDate(card.dataset.tmt);
                    if (!target) {
                        card.hidden = true;
                        return;
                    }
                    // Hilangkan hitung mundur jika TMT sudah terlewati.
                    if (target < today) {
                        card.hidden = true;
                        return;
                    }
                    card.hidden = false;
                    const yearsElement = card.querySelector('[data-years]');
                    const monthsElement = card.querySelector('[data-months]');
                    const daysElement = card.querySelector('[data-days]');
                    const difference = getCalendarDifference(today, target);
                    if (yearsElement) {
                        yearsElement.textContent = difference.years;
                    }
                    if (monthsElement) {
                        monthsElement.textContent = difference.months;
                    }
                    if (daysElement) {
                        daysElement.textContent = difference.days;
                    }
                });
            }
            updateCountdown();
            setInterval(updateCountdown, 60000);
        });
    </script>
@endsection
