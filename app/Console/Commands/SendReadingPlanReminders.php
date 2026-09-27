<?php 

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendReadingPlanReminders extends Command
{
    protected $signature = 'app:send-reading-plan-reminders';

    protected $description = '読書計画のリマインダー通知を対象ユーザーに送信する';

    public function handle(): void
    {
        $today = Carbon::today();

        $threeDaysLaterPlans = ReadingPlan::with(['user', 'book'])
            ->where('status', ReadingPlanStatus::InProgress)
            ->whereDate('target_date', $today->copy()->addDays(3))
            ->get();

        foreach ($threeDaysLaterPlans as $plan) {
            $plan->user->notify(new ReadingPlanReminderNotification($plan, 'three_days_before'));
        }

        $todayPlans = ReadingPlan::with(['user', 'book'])
            ->where('status', ReadingPlanStatus::InProgress)
            ->whereDate('target_date', $today)
            ->get();

        foreach ($todayPlans as $plan) {
            $plan->user->notify(new ReadingPlanReminderNotification($plan, 'on_due_date'));
        }

        $expiredPlans = ReadingPlan::with(['user', 'book'])
            ->where('status', ReadingPlanStatus::InProgress)
            ->whereDate('target_date', '<', $today)
            ->get();

        foreach ($expiredPlans as $plan) {
            $plan->update(['status' => ReadingPlanStatus::Expired]);

            if ($plan->target_date->format('Y-m-d') === $today->copy()->subDays(3)->format('Y-m-d')) {
                $plan->user->notify(new ReadingPlanReminderNotification($plan, 'three_days_after'));
            }
        }

        $this->info('読書計画リマインダーの送信処理が完了しました。');
    }
}