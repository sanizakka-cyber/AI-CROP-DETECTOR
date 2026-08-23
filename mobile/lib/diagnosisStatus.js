/**
 * Real diagnosis status values are: pending, needs_review, reviewed,
 * confirmed — there is no 'failed' value anywhere in the schema.
 * needs_review means the AI genuinely produced no result (timeout/
 * unavailable); reviewed/confirmed mean the AI succeeded. Mobile used to
 * default anything other than 'processed'/'pending' to "❌ Failed", which
 * mislabeled every successful scan. Prefer the backend's own
 * `status_label` (Diagnosis::getStatusLabelAttribute()) when present —
 * this mirrors it for older cached payloads that predate that field.
 */
export function statusMeta(status, statusLabel) {
  const label = statusLabel || {
    pending: 'Pending Review',
    needs_review: 'Failed',
    reviewed: 'Completed',
    confirmed: 'Completed',
    processed: 'Completed',
  }[status] || (status ? status.replace(/_/g, ' ') : 'Processing');

  if (label === 'Failed') return { label, icon: '❌', colorKey: 'danger' };
  if (label === 'Pending Review' || label === 'Processing') return { label, icon: '⏳', colorKey: 'warning' };
  if (label === 'Low Confidence') return { label, icon: '⚠️', colorKey: 'warning' };
  return { label, icon: '✅', colorKey: 'success' };
}
