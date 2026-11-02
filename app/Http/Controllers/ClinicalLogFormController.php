<?php

namespace App\Http\Controllers;

use App\Enums\ClinicalHours\AgeGroup;
use App\Enums\ClinicalHours\ClinicMode;
use App\Enums\ClinicalHours\HoursType;
use App\Enums\ClinicalHours\PlacementType;
use App\Enums\ClinicalHours\PracticumType;
use App\Enums\ClinicalHours\SessionMode;
use App\Enums\ClinicalHours\SessionType;
use App\Enums\ClinicalHours\WeekDay;
use App\Http\Requests\ClinicalLogFormRequest;
use App\Models\ClinicalLogEntry;
use App\Models\ClinicalLogForm;
use App\Services\ClinicalHoursSummary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ClinicalLogFormController extends Controller
{
    /**
     * Show the student's clinical hours tracker and log forms.
     */
    public function index(Request $request, ClinicalHoursSummary $summary): Response
    {
        $forms = $request->user()
            ->clinicalLogForms()
            ->withCount('entries')
            ->withSum('entries', 'duration_minutes')
            ->orderByDesc('session_start_year')
            ->orderByDesc('semester')
            ->latest('id')
            ->get();

        return Inertia::render('clinical-hours/Index', [
            'summary' => $summary->summarize($forms),
            'forms' => $forms->map(fn (ClinicalLogForm $form): array => [
                'id' => $form->id,
                'mode' => $form->mode->value,
                'year' => $form->year,
                'semester' => $form->semester,
                'session' => $form->sessionLabel(),
                'placement_name' => $form->placement_name,
                'supervisor_name' => $form->supervisor_name,
                'practicum_type' => $form->practicum_type->label(),
                'hours_type' => $form->hours_type->value,
                'hours_type_label' => $form->hours_type->label(),
                'entries_count' => $form->entries_count,
                'total_minutes' => $form->totalMinutes(),
            ]),
        ]);
    }

    /**
     * Show the page for filling in a new log form.
     */
    public function create(): Response
    {
        return Inertia::render('clinical-hours/Create', [
            'options' => $this->options(),
        ]);
    }

    /**
     * Store a new log form with its sessions, or the hours keyed in on a simple form.
     */
    public function store(ClinicalLogFormRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $form = $request->user()->clinicalLogForms()->create($request->formAttributes());

            $form->entries()->createMany($request->entryAttributes());
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Log form saved.')]);

        return to_route('clinical-hours.index');
    }

    /**
     * Show the page for editing a log form.
     */
    public function edit(ClinicalLogForm $clinicalLogForm): Response
    {
        Gate::authorize('update', $clinicalLogForm);

        $clinicalLogForm->load('entries');

        return Inertia::render('clinical-hours/Edit', [
            'options' => $this->options(),
            'form' => [
                'id' => $clinicalLogForm->id,
                'mode' => $clinicalLogForm->mode->value,
                'year' => $clinicalLogForm->year,
                'semester' => $clinicalLogForm->semester,
                'session_start_year' => $clinicalLogForm->session_start_year,
                'days' => $clinicalLogForm->days ?? [],
                'placement' => $clinicalLogForm->placement?->value ?? '',
                'clinic_mode' => $clinicalLogForm->clinic_mode?->value ?? '',
                'placement_name' => $clinicalLogForm->placement_name ?? '',
                'supervisor_name' => $clinicalLogForm->supervisor_name ?? '',
                'practicum_type' => $clinicalLogForm->practicum_type->value,
                'hours_type' => $clinicalLogForm->hours_type->value,
                'hours' => intdiv((int) $clinicalLogForm->simple_minutes, 60),
                'minutes' => (int) $clinicalLogForm->simple_minutes % 60,
                'entries' => $clinicalLogForm->entries->map(fn (ClinicalLogEntry $entry): array => [
                    'date' => $entry->date->toDateString(),
                    'age_group' => $entry->age_group->value,
                    'rn_no' => $entry->rn_no ?? '',
                    'session_mode' => $entry->session_mode->value,
                    'session_type' => $entry->session_type->value,
                    'diagnosis' => $entry->diagnosis ?? '',
                    'hours' => intdiv($entry->duration_minutes, 60),
                    'minutes' => $entry->duration_minutes % 60,
                ]),
            ],
        ]);
    }

    /**
     * Update a log form and replace its sessions, clearing them when switched to simple mode.
     */
    public function update(ClinicalLogFormRequest $request, ClinicalLogForm $clinicalLogForm): RedirectResponse
    {
        Gate::authorize('update', $clinicalLogForm);

        DB::transaction(function () use ($request, $clinicalLogForm): void {
            $clinicalLogForm->update($request->formAttributes());

            $clinicalLogForm->entries()->delete();
            $clinicalLogForm->entries()->createMany($request->entryAttributes());
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Log form updated.')]);

        return to_route('clinical-hours.index');
    }

    /**
     * Delete a log form and its sessions.
     */
    public function destroy(ClinicalLogForm $clinicalLogForm): RedirectResponse
    {
        Gate::authorize('delete', $clinicalLogForm);

        $clinicalLogForm->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Log form deleted.')]);

        return to_route('clinical-hours.index');
    }

    /**
     * Get the choices offered on the log form.
     *
     * @return array<string, array<int, array{value: string, label: string}>>
     */
    private function options(): array
    {
        return [
            'days' => WeekDay::options(),
            'placements' => PlacementType::options(),
            'clinicModes' => ClinicMode::options(),
            'practicumTypes' => PracticumType::options(),
            'hoursTypes' => HoursType::options(),
            'ageGroups' => AgeGroup::options(),
            'sessionModes' => SessionMode::options(),
            'sessionTypes' => SessionType::options(),
        ];
    }
}
