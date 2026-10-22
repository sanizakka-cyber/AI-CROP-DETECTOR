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
