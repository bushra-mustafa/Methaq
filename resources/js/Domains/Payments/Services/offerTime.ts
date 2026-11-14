const MINUTE = 60_000;
const HOUR = 60 * MINUTE;
const DAY = 24 * HOUR;

/** Arabic count wording: one, two, 3 to 10 (plural), 11 and more (singular accusative). */
function unit(count: number, one: string, two: string, few: string, many: string): string {
    if (count === 1) return one;
    if (count === 2) return two;
    return `${count} ${count <= 10 ? few : many}`;
}

/** «يومين وساعة»: whole days and hours, then hours and minutes, then minutes in the last hour. Empty once the time has passed. */
export function remainingLabel(endsAt: string, now: number): string {
    const left = new Date(endsAt).getTime() - now;
    if (!Number.isFinite(left) || left <= 0) return '';

    const days = Math.floor(left / DAY);
    const hours = Math.floor((left % DAY) / HOUR);
    const minutes = Math.max(1, Math.ceil((left % HOUR) / MINUTE));
    const dayText = unit(days, 'يوم', 'يومين', 'أيام', 'يوماً');
    const hourText = unit(hours, 'ساعة', 'ساعتين', 'ساعات', 'ساعة');
    const minuteText = unit(minutes, 'دقيقة', 'دقيقتين', 'دقائق', 'دقيقة');

    if (days > 0) return hours > 0 ? `${dayText} و${hourText}` : dayText;
    if (hours > 0) return `${hourText} و${minuteText}`;
    return minuteText;
}

/** True while the offer's end is still ahead. */
export function isOpen(endsAt: string, now: number): boolean {
    return new Date(endsAt).getTime() > now;
}
