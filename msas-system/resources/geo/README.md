# Nigeria State and LGA boundaries

`nga-lgas.json` holds one polygon set per LGA (774), named exactly as the
State/LGA dropdowns (`app/Data/NigeriaLocations.php`). `App\Services\LgaLookup`
uses it to fill State and LGA from a GPS point, server side.

- **Source:** GRID3 Nigeria State and LGA boundaries (2022), published by
  geoBoundaries (wmgeolab), gbOpen NGA ADM1 and ADM2, build `9469f09`.
- **Licence:** Creative Commons Attribution 4.0 (CC BY 4.0).
  Attribution: "Boundaries: GRID3 (Geo-Referenced Infrastructure and
  Demographic Data for Development), via geoBoundaries."
- **Built by:** `scripts/geo/build_lga_index.py` (simplified geometry,
  coordinates rounded to 4 decimals, about 11 m). The script lists the
  20 boundary spellings mapped onto our dropdown names, and fails if any
  LGA is left unmatched.

State and LGA always come from these boundaries; nothing below changes that.

## Suggestions from the location (decision 7 Nov 2026)

**Decision (owner, 7 Nov 2026), replacing the 23 Oct decision: Community and
Address/Landmark are filled from the location as suggestions; Postal Code is
filled only when the provider returns one for that point. Ward stays manual.**

`App\Services\ReverseGeocoder` (settings in `config/geocoding.php`) asks
OpenStreetMap, from our server only, never from the browser or the app:

- **Nominatim**: the point's address and postcode (reverse, zoom 18), its
  settlement with coordinates (reverse, zoom 14), and, when needed, a bounded
  settlement search to place a settlement named in the address. Policy: at
  most 1 request per second for the whole app (enforced with a cache lock),
  an identifying User-Agent, results cached (30 days, by the point rounded to
  3 decimals, about 110 m). Only the rounded point is sent.
- **Our own copy of named places** (`places/`, Landmark only, owner decision
  7 Nov 2026): schools, markets, mosques, churches and health centres with a
  name, from the Geofabrik Nigeria extract, built by
  `scripts/geo/build_place_index.py` (one file per half-degree cell; see
  `places/index.json` for the date and counts). `App\Services\NearbyPlaces`
  reads it on our server; nothing is sent out. The road name comes from
  Nominatim's answer for the point, with its shape, so the 200 m check is
  exact. Rebuild every few months to pick up new places. The public Overpass
  servers were tried first and never answered from Render (7 Nov 2026).
  ODbL: this folder is a derived database; if it is ever shared outside MSAS
  it must be shared under ODbL.
- Suggestions are asked for in a second call (`suggest=1`) after State/LGA
  are shown, so the State/LGA answer stays fast; app builds that never ask
  are unaffected.
- **Licence and credit:** OpenStreetMap data, ODbL. "© OpenStreetMap
  contributors" is shown under the fields whenever a suggestion is shown.
- **Off switch:** `REVERSE_GEOCODING_ENABLED=false` stops every lookup at once
  (required by the OSMF policy); the fields then stay empty.

Rules:

- **Community / Town / Village:** Nominatim's settlement for the point when it
  is a village, hamlet, town, suburb, neighbourhood or city; otherwise the
  first of those names in the point's address (in that order), located with a
  bounded search. Only when that settlement lies in the same LGA as the point
  (placed with these boundaries). Otherwise empty. Names that are plainly a
  facility or a street are never suggested: OpenStreetMap names the city of
  Katsina "Muhammadu Dikko Stadium" (relation 3711502, 7 Nov 2026), which
  any mapper can correct.
- **Postal codes in OpenStreetMap are rare and not always right** (7 Nov 2026:
  none for Katsina city, Funtua or a Kankara village; Abuja CBD returned
  223140, unlike the 900xxx codes Abuja uses). The rule fills whatever 6-digit
  code is returned, as a suggestion for the farmer to check.
- **Address / Landmark:** the nearest named school, market, mosque, church or
  health centre within about 500 m, written "Road, near Place" when a named
  road is within 200 m, else "Near Place". No such place: empty.
- **Postal Code:** only a 6-digit code Nominatim returns for the point. Never
  guessed, never taken from the LGA or State.
- Only from the GPS point or a pin the farmer placed, never from IP. A device
  reading worse than 1 km (a laptop estimating from Wi-Fi) fills nothing: the
  farmer drags the pin, which runs the same lookups.
- Shown as "Suggested from your location – please check"; the farmer can edit
  or clear each field, and nothing they typed is overwritten (shared rules:
  `public/js/location-suggest.js`, `mobile/lib/locationSuggest.js`).
- `collection_locations.field_sources` records `gps` for a suggestion kept as
  is and `user` for anything typed or edited.
- A failed lookup or no network leaves the three fields empty for the farmer;
  State and LGA still come from the boundaries (offline scans included, when
  they sync).

## Earlier findings (23 Oct 2026)

The 23 Oct decision kept Ward and Community manual and Postal Code blank. The
datasets checked then still do not fit:

- **Ward:** GRID3 NGA Operational Wards v3.0 (CIESIN, doi 10.7916/2pfj-hq78)
  is CC BY-SA 4.0 (share-alike: anything derived from it must carry the same
  licence), covers 24 states, and its notes say the boundaries are not yet
  validated by government. Not used (owner decision above).
- **Community / settlement:** GRID3 NGA Settlement Names (Sept 2020) is listed
  on grid3.org without a stated licence; the GRID3 settlement extents are
  CC BY-SA and have no names. Ask info@grid3.org before using it.
- **Postal code:** NIPOST's NDAPS codes (from Oct 2026) have no open licence
  or bulk download; the alternatives found are commercial. Never guessed.
