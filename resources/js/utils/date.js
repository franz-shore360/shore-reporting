const LOCALE = 'en-AU';
const TIMEZONE = 'Australia/Sydney';
/** Locale for "Mon DD, YYYY" display format (e.g. "Mar 17, 2025") */
const DISPLAY_LOCALE = 'en-US';

/**
 * Format a date for display in Australian time (Australia/Sydney).
 * Use this everywhere dates are shown so the app is consistent.
 *
 * @param {string|number|Date|null|undefined} value - ISO date string, timestamp, or Date
 * @returns {string} Formatted date string or '—' if invalid/empty
 */
export function formatDate(value) {
  if (value === null || value === undefined) return '—';
  const d = value instanceof Date ? value : new Date(value);
  if (Number.isNaN(d.getTime())) return typeof value === 'string' ? value : '—';
  return d.toLocaleString(LOCALE, { timeZone: TIMEZONE });
}

/**
 * Default date format for DataTable: "Mon DD, YYYY" (e.g. "Mar 17, 2025").
 * Use for date columns (no time).
 *
 * @param {string|number|Date|null|undefined} value - ISO date string, timestamp, or Date
 * @returns {string} Formatted date string or '—' if invalid/empty
 */
export function formatDateDisplay(value) {
  if (value === null || value === undefined) return '—';
  const d = value instanceof Date ? value : new Date(value);
  if (Number.isNaN(d.getTime())) return typeof value === 'string' ? value : '—';
  return d.toLocaleDateString(DISPLAY_LOCALE, {
    timeZone: TIMEZONE,
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
}

/**
 * Default datetime format for DataTable: "Mon DD, YYYY, h:mm am/pm".
 * Use for datetime columns.
 *
 * @param {string|number|Date|null|undefined} value - ISO date string, timestamp, or Date
 * @returns {string} Formatted datetime string or '—' if invalid/empty
 */
export function formatDateTimeDisplay(value) {
  if (value === null || value === undefined) return '—';
  const d = value instanceof Date ? value : new Date(value);
  if (Number.isNaN(d.getTime())) return typeof value === 'string' ? value : '—';
  const datePart = d.toLocaleDateString(DISPLAY_LOCALE, {
    timeZone: TIMEZONE,
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
  const timePart = d.toLocaleTimeString(DISPLAY_LOCALE, {
    timeZone: TIMEZONE,
    hour: 'numeric',
    minute: '2-digit',
    hour12: true,
  });
  return `${datePart}, ${timePart}`;
}

/**
 * Format a table cell value for date/datetime columns.
 * Use in DataTable cell templates when falling back to default display.
 * Column def should have meta: { type: 'date' } or meta: { type: 'datetime' }.
 *
 * @param {object} columnDef - Column definition (e.g. cell.column.columnDef)
 * @param {*} value - Cell value
 * @returns {*} Formatted display value or original value
 */
export function formatTableCellValue(columnDef, value) {
  const type = columnDef && columnDef.meta && columnDef.meta.type;
  if (type === 'date') return formatDateDisplay(value);
  if (type === 'datetime') return formatDateTimeDisplay(value);
  return value;
}
