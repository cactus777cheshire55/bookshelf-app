<?php

namespace Tests\Feature\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanReminderNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendReadingPlanRemindersCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 期日3日前リマインダー通知が送信されるか(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $targetPlan = ReadingPlan::factory()->create([
            'user_id'     => $user->id,
            'target_date' => Carbon::today()->addDays(3)->format('Y-m-d'),
            'status'      => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('app:send-reading-plan-reminders')->assertExitCode(0);

        Notification::assertSentTo(
            [$user],
            ReadingPlanReminderNotification::class,
            function ($notification) use ($targetPlan) {
                // ★ timing の値も正しく渡されているか検証
                return $notification->plan->id === $targetPlan->id 
                    && $notification->timing === 'three_days_before';
            }
        );
    }

    /** @test */
    public function 期日に当日リマインダー通知が送信されるか(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $targetPlan = ReadingPlan::factory()->create([
            'user_id'     => $user->id,
            'target_date' => Carbon::today()->format('Y-m-d'),
            'status'      => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('app:send-reading-plan-reminders')->assertExitCode(0);

        Notification::assertSentTo(
            [$user],
            ReadingPlanReminderNotification::class,
            function ($notification) use ($targetPlan) {
                return $notification->plan->id === $targetPlan->id 
                    && $notification->timing === 'on_due_date';
            }
        );
    }

    /** @test */
    public function 期日3日後に再エンゲージメント通知が送信されるか(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $targetPlan = ReadingPlan::factory()->create([
            'user_id'     => $user->id,
            'target_date' => Carbon::today()->subDays(3)->format('Y-m-d'),
            'status'      => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('app:send-reading-plan-reminders')->assertExitCode(0);

        Notification::assertSentTo(
            [$user],
            ReadingPlanReminderNotification::class,
            function ($notification) use ($targetPlan) {
                return $notification->plan->id === $targetPlan->id 
                    && $notification->timing === 'three_days_after';
            }
        );
    }

    /** @test */
    public function 通知対象外の日付の計画には通知が送信されないか(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        ReadingPlan::factory()->create([
            'user_id'     => $user->id,
            'target_date' => Carbon::today()->addDays(4)->format('Y-m-d'),
            'status'      => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('app:send-reading-plan-reminders')->assertExitCode(0);

        Notification::assertNothingSent();
    }

    /** @test */
    public function Completedの計画には通知が送信されないか(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        ReadingPlan::factory()->create([
            'user_id'     => $user->id,
            'target_date' => Carbon::today()->addDays(3)->format('Y-m-d'),
            'status'      => ReadingPlanStatus::Completed,
        ]);

        $this->artisan('app:send-reading-plan-reminders')->assertExitCode(0);

        Notification::assertNothingSent();
    }

    /** @test */
    public function 過去の目標期日はステータスがExpiredに自動更新されるか(): void
    {
        $expiredPlan = ReadingPlan::factory()->create([
            'target_date' => Carbon::today()->subDay()->format('Y-m-d'), 
            'status'      => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('app:send-reading-plan-reminders')->assertExitCode(0);

        $this->assertDatabaseHas('reading_plans', [
            'id'     => $expiredPlan->id,
            'status' => ReadingPlanStatus::Expired->value,
        ]);
    }
}