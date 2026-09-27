<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReadingPlanController extends Controller
{
    public function index(Request $request): View
        {
            $user = $request->user();

            $user->readingPlans()
            ->where('status', ReadingPlanStatus::InProgress)
            ->where('target_date', '<', Carbon::today())
            ->update(['status' => ReadingPlanStatus::Expired]);

            $query = $request->user()
                ->readingPlans()
                ->with('book');

            $status = $request->input('status');
            if ($status) {
                $query->where('status', $status);
            }

            $readingPlans = $query->orderBy('target_date', 'asc')->get();

            return view('reading-plans.index', [
                'readingPlans' => $readingPlans,
                'currentStatus' => $status,
            ]);
    }

    public function create(): View
    {
        $books = Book::orderBy('title', 'asc')->get();
        return view('reading-plans.create', compact('books'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'book_id'     => ['required', 'exists:books,id'],
            'target_date' => ['required', 'date', 'after_or_equal:today'],
        ], [
            'target_date.after_or_equal' => '目標期日は本日以降の日付を指定してください。',
        ]);

        $user = $request->user();

        $existsInProgress = $user->readingPlans()
            ->where('book_id', $validated['book_id'])
            ->where('status', ReadingPlanStatus::InProgress)
            ->exists();

        if ($existsInProgress) {
            return back()
                ->withInput()
                ->withErrors(['book_id' => 'この書籍は既に進行中の読書計画が存在します。']);
        }

        $user->readingPlans()->create([
            'book_id'     => $validated['book_id'],
            'target_date' => $validated['target_date'],
            'status'      => ReadingPlanStatus::InProgress,
        ]);

        return redirect()->route('reading-plans.index')
            ->with('success', '読書計画を作成しました。');
    }

    public function edit(ReadingPlan $readingPlan): View
    {
        $this->authorize('update', $readingPlan);

        return view('reading-plans.edit', compact('readingPlan'));
    }

    public function update(Request $request, ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('update', $readingPlan);

        $validated = $request->validate([
            'target_date' => ['required', 'date'],
        ]);

        $readingPlan->update([
            'target_date' => $validated['target_date'],
        ]);

        return redirect()->route('reading-plans.index')
            ->with('success', '読書計画を変更しました。');
    }

    public function destroy(ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('delete', $readingPlan);

        $readingPlan->delete();

        return redirect()->route('reading-plans.index')
            ->with('success', '読書計画を削除しました。');
    }

    public function complete(ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('update', $readingPlan);

        $readingPlan->update([
            'status'       => ReadingPlanStatus::Completed,
            'completed_at' => Carbon::today(),
        ]);

        return redirect()->route('reading-plans.index')
            ->with('success', '読書完了！お疲れ様でした。');
    }
}