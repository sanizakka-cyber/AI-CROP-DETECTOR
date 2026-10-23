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

Community names cannot be filled from coordinates: there are no open
community boundaries.

## Fields not filled from GPS (checked 23 Oct 2026)

Each stays empty and editable; `collection_locations.field_sources` records
`user` when the farmer fills it.

- **Ward:** GRID3 NGA Operational Wards v3.0 (CIESIN, doi 10.7916/2pfj-hq78)
  is CC BY-SA 4.0 (share-alike: anything derived from it must carry the same
  licence), covers 24 states, and its notes say the boundaries are not yet
  validated by government. Using it is a licensing decision for the owner.
- **Community / settlement:** GRID3 NGA Settlement Names (Sept 2020) is listed
  on grid3.org without a stated licence; the GRID3 settlement extents are
  CC BY-SA and have no names. Ask info@grid3.org before using it.
- **Postal code:** NIPOST's NDAPS codes (from Oct 2026) have no open licence
  or bulk download; the alternatives found are commercial. Never guessed.
