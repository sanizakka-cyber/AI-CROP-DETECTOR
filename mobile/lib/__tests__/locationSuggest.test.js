// Place-name suggestion rules, for the app (lib/locationSuggest.js) and the web forms
// (msas-system/public/js/location-suggest.js), against the same cases so the two
// can never fill fields differently.
import * as app from '../locationSuggest';

const web = require('../../../msas-system/public/js/location-suggest.js');

const empty = () => ({ community: { value: '', source: null }, landmark: { value: '', source: null }, postal_code: { value: '', source: null } });
const S = { community: 'Kofar Sauri', landmark: 'IBB Way, near Katsina Central Market', postal_code: '820101' };

describe.each([['app', app], ['web', web]])('%s', (_name, sug) => {
  test('fills empty fields and marks them as suggestions from the location', () => {
    expect(sug.apply(empty(), S)).toEqual({
      community: { value: 'Kofar Sauri', source: 'gps' },
      landmark: { value: 'IBB Way, near Katsina Central Market', source: 'gps' },
      postal_code: { value: '820101', source: 'gps' },
    });
  });

  test('never overwrites something the farmer typed', () => {
    const f = empty();
    f.community = { value: 'My village', source: 'user' };
    f.postal_code = sug.edited('820001');
    const out = sug.apply(f, S);
    expect(out.community).toEqual({ value: 'My village', source: 'user' });
    expect(out.postal_code).toEqual({ value: '820001', source: 'user' });
    expect(out.landmark.source).toBe('gps');
  });

  test('an edited suggestion becomes the farmer\'s and is kept on the next lookup', () => {
    let f = sug.apply(empty(), S);
    f = { ...f, community: sug.edited('Kofar Sauri ward 2') };
    const out = sug.apply(f, { community: 'Somewhere else', landmark: null, postal_code: null });
    expect(out.community).toEqual({ value: 'Kofar Sauri ward 2', source: 'user' });
  });

  test('a moved pin replaces or clears earlier suggestions, not typed values', () => {
    const f = sug.apply(empty(), S);
    const out = sug.apply(f, { community: 'Funtua', landmark: null, postal_code: null });
    expect(out.community).toEqual({ value: 'Funtua', source: 'gps' });
    expect(out.landmark).toEqual({ value: '', source: null });
    expect(out.postal_code).toEqual({ value: '', source: null });
  });

  test('no postal code is invented when the provider returns none', () => {
    expect(sug.apply(empty(), { community: 'Funtua', landmark: null, postal_code: null }).postal_code).toEqual({ value: '', source: null });
  });

  test('a failed lookup leaves the fields as they are (empty stays empty)', () => {
    expect(sug.apply(empty(), null)).toEqual(empty());
    const f = sug.apply(empty(), S);
    expect(sug.apply(f, null)).toEqual(f);   // nothing is cleared when the lookup failed
  });

  test('clearing a field leaves it empty, with no source', () => {
    expect(sug.edited('')).toEqual({ value: '', source: null });
  });

  test.each([[5, true], [999, true], [1000, true], [1001, false], [5000, false], [null, true], ['', true], [NaN, false]])(
    'a reading of ±%s m may fill fields: %s', (acc, ok) => {
      expect(sug.canAutoFill(acc)).toBe(ok);
    });
});
