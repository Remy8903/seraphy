<?php

namespace App\Http\Controllers;

use App\Enums\Therapy\TrialResult;
use App\Models\Client;
use App\Models\PlaySession;
use App\Services\Therapy\ClientProgress;
use App\Services\Therapy\GoalEvaluator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ClientProgressReportController extends Controller
{
    /**
     * Download a client's progress over a date range as a PDF.
     *
     * The report identifies the client by reference code only.
     */
    public function __invoke(Request $request, Client $client, ClientProgress $progress, GoalEvaluator $goals): Response
    {
        Gate::authorize('view', $client);

        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        [$defaultFrom, $defaultTo] = $this->currentSemester();
        $from = isset($validated['from']) ? Carbon::parse($validated['from']) : $defaultFrom;
        $to = isset($validated['to']) ? Carbon::parse($validated['to']) : $defaultTo;

        $sessions = $client->playSessions()
            ->reorder()
            ->whereNotNull('ended_at')
            ->whereBetween('started_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->with('activity:id,title')
            ->withCount([
                'trials',
                'trials as correct_count' => fn ($query) => $query->where('result', TrialResult::Correct),
            ])
            ->orderBy('started_at')
            ->orderBy('id')
            ->get();

        return Pdf::loadView('pdf.client-progress-report', [
            'clinician' => $request->user()->name,
            'client' => [
                'reference_code' => $client->reference_code,
                'age_group' => $client->age_group->label(),
                'language' => $client->language->label(),
            ],
            'from' => $from,
            'to' => $to,
            'goals' => $goals->summarize($client),
            'progress' => $progress->summarize($client, $from, $to),
            'sessions' => $sessions->map(function (PlaySession $session): array {
                $total = (int) $session->getAttribute('trials_count');
                $correct = (int) $session->getAttribute('correct_count');

                return [
                    'date' => $session->started_at->format('d/m/Y'),
                    'activity' => $session->activity->title,
                    'total' => $total,
                    'accuracy' => $total > 0 ? round($correct / $total * 100, 1) : 0.0,
                    'notes' => $session->notes,
                ];
            })->all(),
        ])
            ->setPaper('a4')
            ->download($this->filename($client->reference_code, $from, $to));
    }

    /**
     * Get the first and last day of the current semester.
     *
     * Semester 1 runs from September to February and semester 2 from March to August.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function currentSemester(): array
    {
        $today = Carbon::today();

        if ($today->month >= 3 && $today->month <= 8) {
            return [$today->copy()->setDate($today->year, 3, 1), $today->copy()->setDate($today->year, 8, 31)];
        }

        $startYear = $today->month >= 9 ? $today->year : $today->year - 1;
        $start = $today->copy()->setDate($startYear, 9, 1);

        return [$start, $start->copy()->addMonths(6)->subDay()];
    }

    /**
     * Build the file name, e.g. "PROGRESS REPORT - C-01 - 2026-09-01 TO 2027-02-28.pdf".
     */
    private function filename(string $reference, Carbon $from, Carbon $to): string
    {
        $reference = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $reference);

        return Str::upper("PROGRESS REPORT - {$reference} - {$from->toDateString()} TO {$to->toDateString()}").'.pdf';
    }
}
