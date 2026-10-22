"""
Builds msas-system/resources/geo/nga-lgas.json: one polygon set per Nigerian
LGA, named exactly as in App\\Data\\NigeriaLocations (the State/LGA dropdowns),
so a GPS point can fill State and LGA with names the dropdowns accept.

Source: GRID3 Nigeria State and LGA boundaries (2022), published by
geoBoundaries (wmgeolab) under CC BY 4.0. Download the two simplified files
into a folder of their own first (downloaded data is untrusted):

    https://github.com/wmgeolab/geoBoundaries/raw/9469f09/releaseData/gbOpen/NGA/ADM1/geoBoundaries-NGA-ADM1_simplified.geojson
    https://github.com/wmgeolab/geoBoundaries/raw/9469f09/releaseData/gbOpen/NGA/ADM2/geoBoundaries-NGA-ADM2_simplified.geojson

    py -I scripts/geo/build_lga_index.py <adm1.geojson> <adm2.geojson> msas-system/app/Data/NigeriaLocations.php msas-system/resources/geo/nga-lgas.json

Each LGA polygon is assigned to the state whose polygon contains one of its
interior points. Names are matched exactly, then after ignoring case,
spaces and punctuation, then through ALIASES (boundary spelling -> our
dropdown spelling). The build fails if any LGA, or any dropdown LGA, is left
unmatched, so the file always covers all 774 LGAs.
"""
import json
import re
import sys

# GRID3/geoBoundaries spelling -> App\Data\NigeriaLocations spelling (reviewed by hand).
ALIASES = {
    'Obi Nwga': 'Obi Ngwa', 'Osisioma Ngwa': 'Osisioma', 'Municipal Area Council': 'Abuja Municipal Area Council',
    'Yenegoa': 'Yenagoa', 'Oturkpo': 'Otukpo', 'Iguegben': 'Igueben', 'Mbatoli': 'Mbaitoli',
    'Nassarawa': 'Nasarawa', 'Garun Malam': 'Garun Mallam', 'Bagudu': 'Bagudo', 'Ifako/Ijaye': 'Ifako-Ijaiye',
    'Yewa South': 'Egbado South', 'Yewa North': 'Egbado North', 'Shagamu': 'Sagamu',
    'Ayedade': 'Aiyedade', 'Ayedire': 'Aiyedire', 'Ilesha East': 'Ilesa East', 'Ilesha West': 'Ilesa West',
    'Omumma': 'Omuma', 'Wamakko': 'Wamako',
}
STATE_ALIASES = {'Abuja Federal Capital Territory': 'Federal Capital Territory'}
DECIMALS = 4  # about 11 m: finer than the simplified source geometry


def our_states(php_path):
    src = open(php_path, encoding='utf-8').read()
    out = {}
    for m in re.finditer(r"\['name' => '([^']+)',\s*'lgas' => \[(.*?)\]\]", src):
        out[m.group(1)] = [x.replace("\\'", "'") for x in re.findall(r"'((?:[^'\\]|\\.)*)'", m.group(2))]
    return out


def polys(geom):
    return geom['coordinates'] if geom['type'] == 'MultiPolygon' else [geom['coordinates']]


def in_ring(pt, ring):
    x, y = pt
    c = False
    j = len(ring) - 1
    for i in range(len(ring)):
        xi, yi = ring[i]
        xj, yj = ring[j]
        if (yi > y) != (yj > y) and x < (xj - xi) * (y - yi) / (yj - yi) + xi:
            c = not c
        j = i
    return c


def inside(pt, ps):
    return any(in_ring(pt, p[0]) and not any(in_ring(pt, h) for h in p[1:]) for p in ps)


def interior_point(ps):
    ring = max((p[0] for p in ps), key=len)
    cx = sum(p[0] for p in ring) / len(ring)
    cy = sum(p[1] for p in ring) / len(ring)
    if inside((cx, cy), ps):
        return cx, cy
    for k in range(1, 40):
        for px, py in ring[::max(1, len(ring) // 40)]:
            q = (px + (cx - px) * k / 40, py + (cy - py) * k / 40)
            if inside(q, ps):
                return q
    raise ValueError('no interior point')


def norm(s):
    return re.sub(r'[^a-z]', '', s.lower())


def main(adm1_path, adm2_path, php_path, out_path):
    ours = our_states(php_path)
    adm1 = json.load(open(adm1_path, encoding='utf-8'))['features']
    adm2 = json.load(open(adm2_path, encoding='utf-8'))['features']
    states = []
    for f in adm1:
        name = STATE_ALIASES.get(f['properties']['shapeName'], f['properties']['shapeName'])
        if name not in ours:
            sys.exit(f'state not in dropdowns: {name}')
        states.append((name, polys(f['geometry'])))

    features, unmatched, used = [], [], set()
    for f in adm2:
        ps = polys(f['geometry'])
        state = next((s for s, p in states if inside(interior_point(ps), p)), None)
        raw = f['properties']['shapeName']
        lgas = ours.get(state, [])
        by_norm = {norm(l): l for l in lgas}
        name = raw if raw in lgas else by_norm.get(norm(raw)) or (ALIASES.get(raw) if ALIASES.get(raw) in lgas else None)
        if name is None:
            unmatched.append((state, raw))
            continue
        used.add((state, name))
        rounded = [[[[round(x, DECIMALS), round(y, DECIMALS)] for x, y in ring] for ring in p] for p in ps]
        xs = [pt[0] for p in rounded for pt in p[0]]
        ys = [pt[1] for p in rounded for pt in p[0]]
        features.append({'state': state, 'lga': name, 'bbox': [min(xs), min(ys), max(xs), max(ys)], 'polygons': rounded})

    missing = [(s, l) for s, ls in ours.items() for l in ls if (s, l) not in used]
    if unmatched or missing:
        sys.exit(f'unmatched boundary names: {unmatched}\ndropdown LGAs with no boundary: {missing}')

    features.sort(key=lambda f: (f['state'], f['lga']))
    out = {
        'source': 'GRID3 Nigeria State and LGA boundaries (2022), via geoBoundaries gbOpen NGA ADM1/ADM2 (wmgeolab, build 9469f09)',
        'licence': 'CC BY 4.0 (https://creativecommons.org/licenses/by/4.0/)',
        'attribution': 'Boundaries: GRID3 (Geo-Referenced Infrastructure and Demographic Data for Development), via geoBoundaries.',
        'version': 'grid3-2022-geoboundaries-9469f09',
        'precision_decimals': DECIMALS,
        'features': features,
    }
    with open(out_path, 'w', encoding='utf-8', newline='\n') as fh:
        json.dump(out, fh, ensure_ascii=False, separators=(',', ':'))
    print(f'{len(features)} LGAs written to {out_path}')


if __name__ == '__main__':
    main(*sys.argv[1:5])
