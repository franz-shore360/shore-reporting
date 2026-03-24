/**
 * Application display name from the server (window.Laravel.appName or meta application-name).
 */
export function getAppName() {
  if (typeof window === 'undefined') {
    return '';
  }
  return (
    window.Laravel?.appName ??
    document.querySelector('meta[name="application-name"]')?.getAttribute('content')?.trim() ??
    'Shore Reporting'
  );
}
