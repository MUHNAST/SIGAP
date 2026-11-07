<?php

namespace Tests\Unit\Models;

use App\Models\Notification;
use Tests\TestCase;

class NotificationIconTest extends TestCase
{
    public function test_pangkat_title_uses_rank_icon(): void
    {
        $notification = new Notification(['judul' => 'Kenaikan Pangkat']);

        $this->assertSame([
            'icon' => 'fa-chart-line',
            'bg' => 'bg-orange-100',
            'text' => 'text-orange-600',
        ], $notification->icon);
    }

    public function test_kgb_title_uses_salary_icon(): void
    {
        $notification = new Notification(['judul' => 'Pengingat KGB']);

        $this->assertSame([
            'icon' => 'fa-wallet',
            'bg' => 'bg-blue-100',
            'text' => 'text-blue-600',
        ], $notification->icon);
    }

    public function test_pensiun_title_uses_retirement_icon(): void
    {
        $notification = new Notification(['judul' => 'Masa Pensiun']);

        $this->assertSame([
            'icon' => 'fa-user-clock',
            'bg' => 'bg-green-100',
            'text' => 'text-green-600',
        ], $notification->icon);
    }

    public function test_other_title_uses_default_icon(): void
    {
        $notification = new Notification(['judul' => 'Informasi Umum']);

        $this->assertSame([
            'icon' => 'fa-bell',
            'bg' => 'bg-slate-100',
            'text' => 'text-slate-600',
        ], $notification->icon);
    }
}
