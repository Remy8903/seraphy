<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Progress Report</title>
    <style>
        @page { margin: 36pt 36pt 48pt; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.5pt; color: #111; margin: 0; }
        h1 { font-size: 15pt; margin: 0 0 2pt; }
        h2 { font-size: 10.5pt; margin: 16pt 0 5pt; padding-bottom: 2pt; border-bottom: 1pt solid #111; }
        h3 { font-size: 9pt; margin: 10pt 0 4pt; }
        .muted { color: #555; }
        .details { width: 100%; border-collapse: collapse; margin-top: 8pt; }
        .details td { padding: 2pt 6pt 2pt 0; vertical-align: top; }
        .details td.label { color: #555; width: 80pt; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th { text-align: left; font-weight: bold; border-bottom: 0.8pt solid #111; padding: 3pt 4pt; background: #f0f0f0; }
        table.data td { border-bottom: 0.4pt solid #ccc; padding: 3pt 4pt; vertical-align: top; }
        .num { text-align: right; white-space: nowrap; }
        .status { font-weight: bold; }
        .status-achieved { color: #047857; }
        .status-discontinued { color: #777; }
        .met { color: #047857; font-weight: bold; }
        .empty { color: #555; font-style: italic; }
        .footer { position: fixed; bottom: -30pt; left: 0; right: 0; text-align: center; font-size: 7pt; color: #555; border-top: 0.4pt solid #ccc; padding-top: 4pt; }
    </style>
</head>
<body>
    <div class="footer">Reference only — contains no identifying patient information.</div>

    <h1>Therapy Progress Report</h1>
    <div class="muted">{{ $from->format('d/m/Y') }} – {{ $to->format('d/m/Y') }}</div>

    <table class="details">
        <tr>
            <td class="label">Client reference</td>
            <td><strong>{{ $client['reference_code'] }}</strong></td>
            <td class="label">Clinician</td>
            <td>{{ $clinician }}</td>
        </tr>
        <tr>
            <td class="label">Age group</td>
            <td>{{ $client['age_group'] }}</td>
            <td class="label">Language</td>
            <td>{{ $client['language'] }}</td>
        </tr>
    </table>

    <h2>Goals</h2>
    @if (count($goals) === 0)
        <p class="empty">No goals have been set.</p>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th>Goal</th>
                    <th>Target</th>
                    <th>Progress</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($goals as $goal)
                    <tr>
                        <td>
                            {{ $goal['description'] }}
                            @php
                                $criteria = array_filter([
                                    $goal['target_sound'] ? '/'.$goal['target_sound'].'/' : null,
                                    $goal['word_position_label'],
                                    $goal['language_label'],
                                    $goal['game_type_label'],
                                ]);
                            @endphp
                            @if ($criteria)
                                <div class="muted">{{ implode(' · ', $criteria) }}</div>
                            @endif
                        </td>
                        <td>&ge; {{ $goal['target_accuracy'] }}% in {{ $goal['consecutive_sessions'] }} {{ $goal['consecutive_sessions'] === 1 ? 'session' : 'sessions in a row' }}</td>
                        <td class="num">{{ $goal['streak'] }} / {{ $goal['consecutive_sessions'] }}</td>
                        <td class="status status-{{ $goal['status'] }}">
                            {{ $goal['status_label'] }}
                            @if ($goal['achieved_at'])
                                <div class="muted">{{ \Illuminate\Support\Carbon::parse($goal['achieved_at'])->format('d/m/Y') }}</div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Accuracy by activity</h2>
    @if (count($progress['activities']) === 0)
        <p class="empty">No finished sessions in this period.</p>
    @else
        @foreach ($progress['activities'] as $activity)
            <h3>{{ $activity['title'] }}</h3>
            <table class="data">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th class="num">Correct</th>
                        <th class="num">With prompt</th>
                        <th class="num">Incorrect</th>
                        <th class="num">Answers</th>
                        <th class="num">Accuracy</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($activity['sessions'] as $session)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($session['date'])->format('d/m/Y') }}</td>
                            <td class="num">{{ $session['correct'] }}</td>
                            <td class="num">{{ $session['prompted'] }}</td>
                            <td class="num">{{ $session['incorrect'] }}</td>
                            <td class="num">{{ $session['total'] }}</td>
                            <td class="num"><strong>{{ $session['accuracy'] }}%</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    @endif

    <h2>Accuracy by target sound</h2>
    @if (count($progress['sounds']) === 0)
        <p class="empty">No answers on words with a target sound in this period.</p>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th>Sound</th>
                    <th>Position</th>
                    <th class="num">Answers</th>
                    <th class="num">With prompt</th>
                    <th class="num">Accuracy</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($progress['sounds'] as $sound)
                    <tr>
                        <td>/{{ $sound['target_sound'] }}/</td>
                        <td>{{ $sound['word_position_label'] ?? '—' }}</td>
                        <td class="num">{{ $sound['total'] }}</td>
                        <td class="num">{{ $sound['prompted'] }}</td>
                        <td class="num"><strong>{{ $sound['accuracy'] }}%</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if (count($progress['categories']) > 0)
        <h2>Accuracy by word group (Sort it)</h2>
        <table class="data">
            <thead>
                <tr>
                    <th>Group</th>
                    <th class="num">Answers</th>
                    <th class="num">With prompt</th>
                    <th class="num">Accuracy</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($progress['categories'] as $group)
                    <tr>
                        <td>{{ ucfirst($group['category']) }}</td>
                        <td class="num">{{ $group['total'] }}</td>
                        <td class="num">{{ $group['prompted'] }}</td>
                        <td class="num"><strong>{{ $group['accuracy'] }}%</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if (count($progress['steps']) > 0)
        <h2>Accuracy by number of steps (Follow directions)</h2>
        <table class="data">
            <thead>
                <tr>
                    <th>Steps</th>
                    <th class="num">Answers</th>
                    <th class="num">With prompt</th>
                    <th class="num">Accuracy</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($progress['steps'] as $step)
                    <tr>
                        <td>{{ $step['steps'] }}-step</td>
                        <td class="num">{{ $step['total'] }}</td>
                        <td class="num">{{ $step['prompted'] }}</td>
                        <td class="num"><strong>{{ $step['accuracy'] }}%</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Sessions</h2>
    @if (count($sessions) === 0)
        <p class="empty">No finished sessions in this period.</p>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th style="width: 58pt;">Date</th>
                    <th>Activity</th>
                    <th class="num">Answers</th>
                    <th class="num">Accuracy</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sessions as $session)
                    <tr>
                        <td>{{ $session['date'] }}</td>
                        <td>{{ $session['activity'] }}</td>
                        <td class="num">{{ $session['total'] }}</td>
                        <td class="num">{{ $session['accuracy'] }}%</td>
                        <td>{{ $session['notes'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
