/**
 * Sentence-by-sentence playback for "Listen to your result".
 *
 * While the server is still generating audio it returns the sentence files
 * finished so far ({index, sentence, url, durationSec}) and how many there
 * will be (chunksTotal). The app plays them in order, starting as soon as
 * the first exists, and waits ("Audio is being prepared…") if it catches up
 * with the generator.
 */

/** True when sentence files can be played before the whole file is ready. */
export function canStream(payload) {
  return !!payload && payload.status !== 'ready' && Array.isArray(payload.chunks) && payload.chunks.length > 0;
}

/** The chunk with this index, or null if it is not ready yet. */
export function chunkAt(chunks, index) {
  return (chunks || []).find((c) => c.index === index) || null;
}

/**
 * What to do when chunk `index` has finished playing:
 *  - {action: 'play', chunk}  the next one is ready
 *  - {action: 'wait'}         more are coming but the next is not ready
 *  - {action: 'done'}         that was the last one
 */
export function afterChunk(chunks, total, index) {
  const next = index + 1;
  if (total && next >= total) return { action: 'done' };
  const chunk = chunkAt(chunks, next);
  return chunk ? { action: 'play', chunk } : { action: 'wait' };
}

/** 0..1 through the whole result, estimating sentences not yet generated. */
export function chunkProgress(chunks, total, index, currentTime) {
  const list = chunks || [];
  if (!list.length || index < 0) return 0;
  const known = list.reduce((s, c) => s + (c.durationSec || 0), 0);
  const avg = known / list.length;
  const whole = known + Math.max(0, (total || list.length) - list.length) * avg;
  const before = list.filter((c) => c.index < index).reduce((s, c) => s + (c.durationSec || 0), 0);
  return whole ? Math.min(1, (before + (currentTime || 0)) / whole) : 0;
}
