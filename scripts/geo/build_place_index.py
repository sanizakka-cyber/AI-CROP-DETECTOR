"""
Builds msas-system/resources/geo/places/: named schools, markets, mosques,
churches and health centres in Nigeria, for the Address/Landmark suggestion
(App\\Services\\NearbyPlaces). Read on our server only; nothing is sent out.

Source: OpenStreetMap, via the Geofabrik Nigeria extract. Data (c)
OpenStreetMap contributors, Open Database Licence (ODbL) 1.0: credit is shown
under the fields wherever a suggestion is shown; this index is a derived
database, so if it is ever shared outside MSAS it must be shared under ODbL.
Download the extract into a folder of its own first (downloaded data is
untrusted), then run with pyosmium installed:

    https://download.geofabrik.de/africa/nigeria-latest.osm.pbf

    py -I scripts/geo/build_place_index.py <nigeria-latest.osm.pbf> msas-system/resources/geo/places

Kinds kept (only places with a name):
  school   amenity = school, college, university, kindergarten
  market   amenity = marketplace
  mosque   amenity = place_of_worship and religion = muslim
  church   amenity = place_of_worship and religion = christian
  worship  amenity = place_of_worship, other or no religion
  health   amenity = clinic, hospital, doctors, health_post, or any healthcare=*

Nodes give their own position; ways (buildings, compounds) the average of their
nodes. Two passes keep memory low: the first keeps tagged nodes and the node
ids of tagged ways, the second looks those ids up.

Output: one JSON file per half-degree cell, named "<row>_<col>.json" with
row = floor(lat * 2) and col = floor(lon * 2); each holds
[[lat, lon, kind, name], ...], coordinates rounded to 5 decimals (about 1 m).
A manifest (index.json) records the source date, the counts and the licence.
"""
import json
import math
import os
import sys
from collections import defaultdict

import osmium

SCHOOL = {'school', 'college', 'university', 'kindergarten'}
HEALTH = {'clinic', 'hospital', 'doctors', 'health_post'}
DECIMALS = 5


def kind_of(tags):
    amenity = tags.get('amenity', '')
    if amenity in SCHOOL:
        return 'school'
    if amenity == 'marketplace':
        return 'market'
    if amenity == 'place_of_worship':
        religion = tags.get('religion', '')
        return {'muslim': 'mosque', 'christian': 'church'}.get(religion, 'worship')
    if amenity in HEALTH or 'healthcare' in tags:
        return 'health'
    return None


def name_of(tags):
    return (tags.get('name:en') or tags.get('name') or '').strip()


class FirstPass(osmium.SimpleHandler):
    def __init__(self):
        super().__init__()
        self.places = []            # [lat, lon, kind, name]
        self.ways = []              # (node ids, kind, name)
        self.needed = set()

    def node(self, n):
        k = kind_of(n.tags)
        name = name_of(n.tags)
        if k and name and n.location.valid():
            self.places.append([n.location.lat, n.location.lon, k, name])

    def way(self, w):
        k = kind_of(w.tags)
        name = name_of(w.tags)
        if k and name:
            refs = [r.ref for r in w.nodes]
            self.ways.append((refs, k, name))
            self.needed.update(refs)


class SecondPass(osmium.SimpleHandler):
    def __init__(self, needed):
        super().__init__()
        self.needed = needed
        self.coords = {}

    def node(self, n):
        if n.id in self.needed and n.location.valid():
            self.coords[n.id] = (n.location.lat, n.location.lon)


def main(pbf, out_dir):
    # Filtered in C++ before Python sees anything: the first pass only gets objects with an amenity or healthcare tag, the second only the nodes the kept ways need.
    first = FirstPass()
    first.apply_file(pbf, filters=[osmium.filter.KeyFilter('amenity', 'healthcare')])
    second = SecondPass(first.needed)
    second.apply_file(pbf, filters=[osmium.filter.EntityFilter(osmium.osm.NODE), osmium.filter.IdFilter(first.needed)])

    places = list(first.places)
    for refs, k, name in first.ways:
        pts = [second.coords[r] for r in refs if r in second.coords]
        if pts:
            places.append([sum(p[0] for p in pts) / len(pts), sum(p[1] for p in pts) / len(pts), k, name])

    cells = defaultdict(list)
    counts = defaultdict(int)
    seen = set()
    for lat, lon, k, name in places:
        lat, lon = round(lat, DECIMALS), round(lon, DECIMALS)
        key = (lat, lon, k, name)
        if key in seen:
            continue
        seen.add(key)
        cells[f'{math.floor(lat * 2)}_{math.floor(lon * 2)}'].append([lat, lon, k, name])
        counts[k] += 1

    os.makedirs(out_dir, exist_ok=True)
    for old in os.listdir(out_dir):
        if old.endswith('.json'):
            os.remove(os.path.join(out_dir, old))
    for cell, rows in cells.items():
        rows.sort()
        with open(os.path.join(out_dir, cell + '.json'), 'w', encoding='utf-8') as f:
            json.dump(rows, f, ensure_ascii=False, separators=(',', ':'))

    with open(os.path.join(out_dir, 'index.json'), 'w', encoding='utf-8') as f:
        json.dump({
            'source': 'OpenStreetMap via Geofabrik (nigeria-latest.osm.pbf)',
            'source_date': os.environ.get('OSM_SOURCE_DATE', ''),
            'licence': 'Open Database Licence (ODbL) 1.0',
            'attribution': '© OpenStreetMap contributors',
            'cell_degrees': 0.5,
            'counts': dict(sorted(counts.items())),
            'total': sum(counts.values()),
            'cells': len(cells),
        }, f, ensure_ascii=False, indent=2)
        f.write('\n')
    print(f'{sum(counts.values())} places in {len(cells)} cells: {dict(sorted(counts.items()))}')


if __name__ == '__main__':
    if len(sys.argv) != 3:
        sys.exit(__doc__)
    main(sys.argv[1], sys.argv[2])
