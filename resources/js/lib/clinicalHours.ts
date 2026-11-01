/**
 * Format a number of minutes as hours and minutes, e.g. 150 -> "2h 30m".
 */
export function formatDuration(totalMinutes: number): string {
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;

    return minutes === 0 ? `${hours}h` : `${hours}h ${minutes}m`;
}
