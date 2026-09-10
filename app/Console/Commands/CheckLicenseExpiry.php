<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\TenantApplication;
use App\Models\User;
use App\Notifications\LicenseExpired;
use App\Notifications\LicenseExpiringSoon;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;

final class CheckLicenseExpiry extends Command
{
    protected $signature = 'licenses:check-expiry';

    protected $description = 'Send database notifications for licenses expiring within seven days or already expired';

    public function handle(): int
    {
        $now = now();
        $windowDate = $now->toDateString();
        $superadmins = User::query()
            ->where('role', User::ROLE_SUPERADMIN)
            ->where('is_active', true)
            ->get();
        $expiringCount = 0;
        $expiredCount = 0;
        $sentCount = 0;

        TenantApplication::query()
            ->where('is_active', true)
            ->whereNotNull('expired_at')
            ->with(['application', 'tenant'])
            ->orderBy('expired_at')
            ->get()
            ->each(function (TenantApplication $license) use ($now, $windowDate, $superadmins, &$expiringCount, &$expiredCount, &$sentCount): void {
                $expiredAt = $license->expired_at;

                if ($expiredAt->isPast()) {
                    $expiredCount++;

                    if ($this->hasNotification($license, LicenseExpired::class)) {
                        return;
                    }

                    $notification = new LicenseExpired($this->payload($license, $expiredAt, $expiredAt->toDateString()));
                    $this->send($superadmins, $notification);
                    $sentCount++;

                    return;
                }

                if ($expiredAt->greaterThan($now->copy()->addDays(7))) {
                    return;
                }

                $expiringCount++;

                if ($this->hasNotification($license, LicenseExpiringSoon::class, $windowDate)) {
                    return;
                }

                $notification = new LicenseExpiringSoon($this->payload($license, $expiredAt, $windowDate));
                $this->send($superadmins, $notification);
                $sentCount++;
            });

        $this->info("Lisensi mendekati kadaluarsa: {$expiringCount}.");
        $this->info("Lisensi kadaluarsa: {$expiredCount}.");
        $this->info("Notifikasi terkirim: {$sentCount}.");

        return self::SUCCESS;
    }

    /**
     * @param  iterable<int, User>  $users
     */
    private function send(iterable $users, object $notification): void
    {
        foreach ($users as $user) {
            $user->notify($notification);
        }
    }

    private function hasNotification(TenantApplication $license, string $type, ?string $windowDate = null): bool
    {
        return User::query()
            ->where('role', User::ROLE_SUPERADMIN)
            ->whereHas('notifications', fn ($query) => $query
                ->where('type', $type)
                ->where('data->tenant_application_id', $license->id)
                ->when($windowDate !== null, fn ($scoped) => $scoped->where('data->window_date', $windowDate)))
            ->exists();
    }

    private function payload(TenantApplication $license, CarbonInterface $expiredAt, string $windowDate): array
    {
        return [
            'tenant_application_id' => $license->id,
            'tenant_id' => $license->tenant_id,
            'tenant_name' => $license->tenant?->name,
            'application_name' => $license->application?->name,
            'label' => $license->label,
            'expired_at' => $expiredAt->toIso8601String(),
            'window_date' => $windowDate,
        ];
    }
}
