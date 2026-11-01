@php
    use App\Enums\ClinicalHours\AgeGroup;
    use App\Enums\ClinicalHours\ClinicMode;
    use App\Enums\ClinicalHours\HoursType;
    use App\Enums\ClinicalHours\PlacementType;
    use App\Enums\ClinicalHours\PracticumType;
    use App\Enums\ClinicalHours\SessionMode;
    use App\Enums\ClinicalHours\SessionType;
    use App\Enums\ClinicalHours\WeekDay;

    $tick = fn (bool $checked): string => '<span class="box">'.($checked ? '&#10003;' : '&nbsp;').'</span>';
    $choice = fn (string $label, bool $selected): string => '<span class="choice'.($selected ? ' selected' : '').'">'.e($label).'</span>';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Clinical Log Hours Form</title>
    <style>
        @page { margin: 12pt 22pt 8pt; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 6.4pt; color: #000; margin: 0; }
        .page { page-break-after: always; position: relative; }
        .page:last-child { page-break-after: auto; }
        .doc-code { font-size: 4.5pt; color: #333; }
        .brand { text-align: center; font-size: 17pt; font-weight: bold; margin-top: 2pt; }
        .brand-bar { margin: 2pt auto 0; border-collapse: collapse; }
        .brand-bar td { width: 60pt; height: 3pt; padding: 0; }
        .faculty { text-align: center; font-weight: bold; font-size: 6pt; margin-top: 4pt; line-height: 1.25; }
        .rule { border-top: 1.6pt solid #000; border-bottom: 0.5pt solid #000; height: 2.2pt; margin-top: 3pt; }
        .title { text-align: center; font-weight: bold; font-size: 8.5pt; padding: 3pt 0 2pt; border-bottom: 1.6pt solid #000; }
        .layout { width: 100%; border-collapse: collapse; margin-top: 7pt; }
        .layout > tbody > tr > td { vertical-align: top; padding: 0; }
        .info { width: 100%; border-collapse: collapse; }
        .info th { background: #000; color: #fff; font-weight: bold; text-align: center; padding: 1.4pt 2pt; border: 0.5pt solid #000; }
        .info td { border: 0.5pt solid #000; padding: 1pt 3pt; height: 10pt; white-space: nowrap; }
        .info td.label { background: #bfbfbf; font-weight: bold; }
        .info td.center { text-align: center; font-weight: bold; }
        .info td.box-cell { width: 18pt; text-align: center; }
        .choice { padding: 0 1.5pt; }
        .choice.selected { border: 0.8pt solid #000; border-radius: 5pt; }
        .box { display: inline-block; width: 7.5pt; height: 7.5pt; border: 0.6pt solid #000; text-align: center; line-height: 7pt; font-size: 6.5pt; font-weight: bold; }
        .log { width: 100%; border-collapse: collapse; margin-top: 7pt; table-layout: fixed; }
        .log td.sizer { height: 0; padding: 0; border: none; }
        .log th { border: 0.5pt solid #000; font-weight: bold; text-align: center; padding: 1pt; }
        .log th.group { background: #000; color: #fff; }
        .log th.blank { border: none; }
        .log td { border: 0.5pt solid #000; height: 10.6pt; padding: 0 2pt; text-align: center; overflow: hidden; white-space: nowrap; }
        .log td.no { font-weight: bold; text-align: left; }
        .log td.left { text-align: left; }
        .log td.total-label { border: none; text-align: right; font-weight: bold; padding-right: 3pt; }
        .log td.total { background: #a6a6a6; font-weight: bold; }
        .log td.none { border: none; }
        .legend { font-style: italic; font-size: 5.6pt; line-height: 1.5; }
        .page-number { position: absolute; right: 0; top: 0; font-size: 5pt; color: #333; }
    </style>
</head>
<body>
@foreach ($pages as $pageIndex => $entries)
    <div class="page">
        <div class="doc-code">UKM-FSK-PPSR-SPEECH-02 I NO. SEMAKAN:3 I MARCH 2021</div>
        @if ($pages->count() > 1)
            <div class="page-number">Page {{ $pageIndex + 1 }} of {{ $pages->count() }}</div>
        @endif

        <div class="brand">SIHAT@UKM</div>
        <table class="brand-bar">
            <tr>
                <td style="background: #e8202a;"></td>
                <td style="background: #1ba1e2;"></td>
                <td style="background: #f6b919;"></td>
            </tr>
        </table>
        <div class="faculty">
            KLINIK SAINS PERTUTURAN, FAKULTI SAINS KESIHATAN<br>
            UNIVERSITI KEBANGSAAN MALAYSIA
        </div>
        <div class="rule"></div>
        <div class="title">CLINICAL LOG HOURS FORM</div>

        <table class="layout">
            <tr>
                <td style="width: 3%;"></td>
                <td style="width: 22%;">
                    <table class="info">
                        <tr><th colspan="2">STUDENT CLINICIAN</th></tr>
                        <tr><td class="label" style="width: 38%;">NAME</td><td style="white-space: normal;">{{ $student->name }}</td></tr>
                        <tr><td class="label">MATRIC NO.</td><td>{{ $student->matric_no }}</td></tr>
                        <tr>
                            <td class="label">YEAR</td>
                            <td class="center">
                                @foreach ([1, 2, 3, 4] as $year)
                                    {!! $choice((string) $year, $form->year === $year) !!}{{ $loop->last ? '' : '/' }}
                                @endforeach
                            </td>
                        </tr>
                        <tr>
                            <td class="label">SEMESTER</td>
                            <td class="center">
                                {!! $choice('1', $form->semester === 1) !!}/{!! $choice('2', $form->semester === 2) !!}
                            </td>
                        </tr>
                        <tr><td class="label">SESSION</td><td class="center">{{ $form->sessionLabel() }}</td></tr>
                    </table>
                </td>
                <td style="width: 4%;"></td>
                <td style="width: 29%;">
                    <table class="info">
                        <tr><th colspan="2">PLACEMENT</th></tr>
                        <tr>
                            <td class="label" style="width: 40%;">DAY</td>
                            <td class="center">
                                @foreach (WeekDay::cases() as $day)
                                    {!! $choice($day->label(), in_array($day->value, $form->days, true)) !!}{{ $loop->last ? '' : '/' }}
                                @endforeach
                            </td>
                        </tr>
                        <tr>
                            <td class="label">PLACEMENT</td>
                            <td class="center">
                                @foreach (PlacementType::cases() as $placement)
                                    {!! $choice($placement->label(), $form->placement === $placement) !!}{{ $loop->last ? '' : '/' }}
                                @endforeach
                            </td>
                        </tr>
                        <tr>
                            <td class="label">CLINIC MODE</td>
                            <td class="center">
                                @foreach (ClinicMode::cases() as $clinicMode)
                                    {!! $choice($clinicMode->label(), $form->clinic_mode === $clinicMode) !!}{{ $loop->last ? '' : '/' }}
                                @endforeach
                            </td>
                        </tr>
                        <tr><td class="label">NAME OF PLACEMENT</td><td>{{ $form->placement_name }}</td></tr>
                        <tr><td class="label">NAME OF SUPERVISOR</td><td>{{ $form->supervisor_name }}</td></tr>
                    </table>
                </td>
                <td style="width: 13%;"></td>
                <td style="width: 25%;">
                    <table class="info">
                        <tr><th colspan="3">CLINICAL HOURS INFORMATION</th></tr>
                        @foreach (PracticumType::cases() as $practicumType)
                            <tr>
                                @if ($loop->first)
                                    <td class="label center" rowspan="{{ count(PracticumType::cases()) }}" style="width: 42%; white-space: normal;">TYPE OF CLINICAL PRACTICUM</td>
                                @endif
                                <td class="box-cell">{!! $tick($form->practicum_type === $practicumType) !!}</td>
                                <td>{{ $practicumType->label() }}</td>
                            </tr>
                        @endforeach
                        @foreach (HoursType::cases() as $hoursType)
                            <tr>
                                @if ($loop->first)
                                    <td class="label center" rowspan="{{ count(HoursType::cases()) }}">HOURS TYPE</td>
                                @endif
                                <td class="box-cell">{!! $tick($form->hours_type === $hoursType) !!}</td>
                                <td>{{ $hoursType->label() }}</td>
                            </tr>
                        @endforeach
                    </table>
                </td>
                <td style="width: 3%;"></td>
            </tr>
        </table>

        <table class="log">
            <tr>
                <td class="sizer" style="width: 3.2%;"></td>
                <td class="sizer" style="width: 9.4%;"></td>
                <td class="sizer" style="width: 3.4%;"></td>
                <td class="sizer" style="width: 3.4%;"></td>
                <td class="sizer" style="width: 3.4%;"></td>
                <td class="sizer" style="width: 6.4%;"></td>
                <td class="sizer" style="width: 6.6%;"></td>
                <td class="sizer" style="width: 6.6%;"></td>
                <td class="sizer" style="width: 6.4%;"></td>
                <td class="sizer" style="width: 6.0%;"></td>
                <td class="sizer" style="width: 17.0%;"></td>
                <td class="sizer" style="width: 4.6%;"></td>
                <td class="sizer" style="width: 4.6%;"></td>
                <td class="sizer" style="width: 19.0%;"></td>
            </tr>
            <tr>
                <th class="blank" colspan="2"></th>
                <th class="group" colspan="3">AGE GROUP</th>
                <th class="blank"></th>
                <th class="group" colspan="2">MODE OF SESSION</th>
                <th class="group" colspan="2">TYPE OF SESSION</th>
                <th class="blank" colspan="4"></th>
            </tr>
            <tr>
                <th style="text-align: left;">NO.</th>
                <th>DATE</th>
                @foreach (AgeGroup::cases() as $ageGroup)
                    <th>{{ $ageGroup->label() }}</th>
                @endforeach
                <th>RN.No.</th>
                @foreach (SessionMode::cases() as $sessionMode)
                    <th>{{ $sessionMode->label() }}</th>
                @endforeach
                @foreach (SessionType::cases() as $sessionType)
                    <th>{{ Str::upper($sessionType->label()) }}</th>
                @endforeach
                <th>DISORDERS/DIAGNOSIS</th>
                <th>HOURS</th>
                <th>MINS</th>
                <th>SUPERVISOR&rsquo;S SIGNATURE</th>
            </tr>
            @for ($row = 0; $row < $rowsPerPage; $row++)
                @php($entry = $entries->values()->get($row))
                <tr>
                    <td class="no">{{ $pageIndex * $rowsPerPage + $row + 1 }}</td>
                    <td>{{ $entry?->date->format('d/m/Y') }}</td>
                    @foreach (AgeGroup::cases() as $ageGroup)
                        <td>{!! $tick($entry?->age_group === $ageGroup) !!}</td>
                    @endforeach
                    <td>{{ $entry?->rn_no }}</td>
                    @foreach (SessionMode::cases() as $sessionMode)
                        <td>{!! $tick($entry?->session_mode === $sessionMode) !!}</td>
                    @endforeach
                    @foreach (SessionType::cases() as $sessionType)
                        <td>{!! $tick($entry?->session_type === $sessionType) !!}</td>
                    @endforeach
                    <td class="left">{{ $entry?->diagnosis }}</td>
                    <td>{{ $entry ? intdiv($entry->duration_minutes, 60) : '' }}</td>
                    <td>{{ $entry ? $entry->duration_minutes % 60 : '' }}</td>
                    <td></td>
                </tr>
            @endfor
            @if ($loop->last)
                <tr>
                    <td class="none" colspan="2"></td>
                    <td class="none legend" colspan="8" style="text-align: left;">CA = Child or Adolescent (0-19 years old)</td>
                    <td class="total-label">TOTAL</td>
                    <td class="total">{{ intdiv($totalMinutes, 60) }}</td>
                    <td class="total">{{ $totalMinutes % 60 }}</td>
                    <td class="none"></td>
                </tr>
            @endif
        </table>

        <div class="legend" style="margin-left: 14%;">
            @unless ($loop->last)
                CA = Child or Adolescent (0-19 years old)<br>
            @endunless
            A = Adult (20 - 59 years old)<br>
            E = Elderly(60+)
        </div>
    </div>
@endforeach
</body>
</html>
