<?php

namespace App\Http\Controllers;

use App\Models\ClinicalLogForm;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ClinicalLogFormPdfController extends Controller
{
    /**
     * The number of session rows printed on each page, matching the paper form.
     */
    private const int ROWS_PER_PAGE = 25;

    /**
     * Download the log form as a printable PDF for the supervisor to sign.
     */
    public function show(ClinicalLogForm $clinicalLogForm): Response
    {
        Gate::authorize('view', $clinicalLogForm);

        abort_if($clinicalLogForm->isSimple(), 404);

        $clinicalLogForm->load(['user', 'entries']);

        $pages = $clinicalLogForm->entries->chunk(self::ROWS_PER_PAGE)->values();

        return Pdf::loadView('pdf.clinical-log-form', [
            'form' => $clinicalLogForm,
            'student' => $clinicalLogForm->user,
            'pages' => $pages->isEmpty() ? collect([collect()]) : $pages,
            'rowsPerPage' => self::ROWS_PER_PAGE,
            'totalMinutes' => $clinicalLogForm->entries->sum('duration_minutes'),
        ])
            ->setPaper('a4', 'landscape')
            ->setOption('isFontSubsettingEnabled', true)
            ->download($this->filename($clinicalLogForm));
    }

    /**
     * Build the file name the faculty expects, e.g.
     * "KSP CLINICAL LOG HOURS FORM SEM 1 2122 - OBSERVATION-A123456-SARAH-YEAR 3.pdf".
     */
    private function filename(ClinicalLogForm $form): string
    {
        $session = substr((string) $form->session_start_year, -2).substr((string) ($form->session_start_year + 1), -2);

        $details = collect([
            $form->hours_type->label(),
            $form->user->matric_no,
            $form->user->name,
            "YEAR {$form->year}",
        ])->filter()->implode('-');

        $filename = "KSP CLINICAL LOG HOURS FORM SEM {$form->semester} {$session} - {$details}";

        return Str::upper(str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '', $filename)).'.pdf';
    }
}
