<?php

namespace Tests\Feature\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
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
            'user_id' => $user->id,
            'target_date' => Carbon::today()->addDays(3)->format('Y-m-d'),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('app:send-reading-plan-reminders')->assertExitCode(0);

        Notification::assertSentTo(
            [$user],
            ReadingPlanReminderNotification::class,
            function ($notification) use ($targetPlan) {

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
            'user_id' => $user->id,
            'target_date' => Carbon::today()->format('Y-m-d'),
            'status' => ReadingPlanStatus::InProgress,
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
            'user_id' => $user->id,
            'target_date' => Carbon::today()->subDays(3)->format('Y-m-d'),
            'status' => ReadingPlanStatus::InProgress,
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
            'user_id' => $user->id,
            'target_date' => Carbon::today()->addDays(4)->format('Y-m-d'),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('app:send-reading-plan-reminders')->assertExitCode(0);

        Notification::assertNothingSent();
    }

    /** @test */
    public function completedの計画には通知が送信されないか(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => Carbon::today()->addDays(3)->format('Y-m-d'),
            'status' => ReadingPlanStatus::Completed,
        ]);

        $this->artisan('app:send-reading-plan-reminders')->assertExitCode(0);

        Notification::assertNothingSent();
    }

    /** @test */
    public function 過去の目標期日はステータスが_expiredに自動更新されるか(): void
    {
        $expiredPlan = ReadingPlan::factory()->create([
            'target_date' => Carbon::today()->subDay()->format('Y-m-d'),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('app:send-reading-plan-reminders')->assertExitCode(0);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $expiredPlan->id,
            'status' => ReadingPlanStatus::Expired->value,
        ]);
    }

    /** @test */
    public function ログインユーザーは読書計画一覧を取得できステータス絞り込みができるか(): void
    {
        $user = User::factory()->create();
        $planInProgress = ReadingPlan::factory()->for($user)->create(['status' => ReadingPlanStatus::InProgress]);
        $planCompleted = ReadingPlan::factory()->for($user)->completed()->create();

        $response = $this->actingAs($user)->get('/reading-plans?status=in_progress');

        $response->assertStatus(200);
        $response->assertSee($planInProgress->book->title);
        $response->assertDontSee($planCompleted->book->title);
    }

    /** @test */
    public function 本日以降の日付で新規読書計画を作成できるか(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post('/reading-plans', [
            'book_id' => $book->id,
            'target_date' => Carbon::today()->addDays(7)->format('Y-m-d'),
        ]);

        $response->assertRedirect('/reading-plans');
        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }

    /** @test */
    public function 過去の日付で読書計画を作成しようとするとバリデーションエラーになるか(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post('/reading-plans', [
            'book_id' => $book->id,
            'target_date' => Carbon::yesterday()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors(['target_date']);
    }

    /** @test */
    public function 読了処理を実行するとステータスがcompletedに更新されるか(): void
    {
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->for($user)->create(['status' => ReadingPlanStatus::InProgress]);

        $response = $this->actingAs($user)->post("/reading-plans/{$plan->id}/complete");

        $response->assertRedirect('/reading-plans');
        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'status' => ReadingPlanStatus::Completed->value,
        ]);
    }

    /** @test */
    public function 他人の読書計画を編集しようとすると403エラーになるか(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $plan = ReadingPlan::factory()->for($owner)->create();

        $response = $this->actingAs($otherUser)->get("/reading-plans/{$plan->id}/edit");

        $response->assertStatus(403);
    }

    /** @test */
    public function 所有者は読書計画編集画面を表示できるか(): void
    {
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->for($user)->create([
            'target_date' => Carbon::today()->addDays(5)->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user)->get("/reading-plans/{$plan->id}/edit");

        $response->assertStatus(200);
        $response->assertSee($plan->book->title);
    }

    /** @test */
    public function 本日以降の新しい期日に変更できるか(): void
    {
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->for($user)->create([
            'target_date' => Carbon::today()->addDays(3)->format('Y-m-d'),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $newTargetDate = Carbon::today()->addDays(10);

        $response = $this->actingAs($user)->put("/reading-plans/{$plan->id}", [
            'target_date' => $newTargetDate->format('Y-m-d'),
        ]);

        $response->assertRedirect('/reading-plans');
        $response->assertSessionHas('success', '読書計画を変更しました。');

        $plan->refresh();

        $targetDateAsCarbon = $plan->target_date instanceof Carbon ? $plan->target_date : Carbon::parse($plan->target_date);

        $this->assertTrue($targetDateAsCarbon->isSameDay($newTargetDate));
        $this->assertEquals(ReadingPlanStatus::InProgress, $plan->status);
    }
}
