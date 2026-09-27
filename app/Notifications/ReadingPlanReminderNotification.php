<?php 

namespace App\Notifications;

use App\Models\ReadingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReadingPlanReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ReadingPlan $plan,
        public string $timing
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $bookTitle = $this->plan->book->title;

        [$title, $body] = match ($this->timing) {
            'three_days_before' => [
                '読書計画リマインダー（3日前）',
                "『{$bookTitle}』の目標期日まであと3日です。"
            ],
            'on_due_date' => [
                '読書計画リマインダー（本日）',
                "本日が『{$bookTitle}』の読書目標期日です！"
            ],
            'three_days_after' => [
                '読書計画再エンゲージメント（3日経過）',
                "『{$bookTitle}』の期日から3日が経過しました。読書状況を更新してみませんか？"
            ],
            default => [
                '読書計画リマインダー',
                "『{$bookTitle}』の読書計画をご確認ください。"
            ],
        };

        return [
            'title'           => $title,
            'body'            => $body,
            'timing'          => $this->timing,
            'reading_plan_id' => $this->plan->id,
            'book_id'         => $this->plan->book_id,
            'book_title'      => $bookTitle,
        ];
    }
}