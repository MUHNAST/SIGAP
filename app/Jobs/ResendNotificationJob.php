<?php
namespace App\Jobs;
use App\Models\Employee;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
class ResendNotificationJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;
     public $tries = 3;

    public $timeout = 3600;
    public function __construct(
        public string $nip
    ) {}
    public function handle(NotificationService $notificationService): void
    {
        $employee = Employee::with('notifications')->find($this->nip);
        if (! $employee) {
            return;
        }
        $notificationService->resendPendingNotifications($employee);
    }
}
