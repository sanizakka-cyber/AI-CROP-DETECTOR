import { afterChunk, canStream, chunkAt, chunkProgress } from '../chunkPlayback';

const c = (i, d = 2) => ({ index: i, sentence: i, url: `u${i}`, durationSec: d });

test('streams only while the whole file is not ready and at least one sentence exists', () => {
  expect(canStream({ status: 'pending', chunks: [c(0)] })).toBe(true);
  expect(canStream({ status: 'pending', chunks: [] })).toBe(false);
  expect(canStream({ status: 'pending' })).toBe(false);
  expect(canStream({ status: 'ready', chunks: [c(0)] })).toBe(false); // whole file: play that instead
  expect(canStream(null)).toBe(false);
});

test('after a sentence: play the next, wait for it, or stop at the end', () => {
  expect(afterChunk([c(0), c(1)], 3, 0)).toEqual({ action: 'play', chunk: c(1) });
  expect(afterChunk([c(0), c(1)], 3, 1)).toEqual({ action: 'wait' });
  expect(afterChunk([c(0), c(1), c(2)], 3, 2)).toEqual({ action: 'done' });
  expect(chunkAt([c(0)], 5)).toBeNull();
});

test('progress estimates sentences not generated yet from the average length', () => {
  // 2 of 4 known, 2 s each -> whole ~8 s; halfway through sentence 2 (index 1) = 3 s.
  expect(chunkProgress([c(0), c(1)], 4, 1, 1)).toBeCloseTo(3 / 8);
  expect(chunkProgress([], 4, 0, 1)).toBe(0);
  expect(chunkProgress([c(0)], 1, 0, 5)).toBe(1);
});
