// Map preview for a captured GPS point on the web scan form: a pin, a circle
// for the accuracy the device reported, and a pin the farmer can drag to
// correct the point. OpenStreetMap tiles (tile.openstreetmap.org, allowed in
// the CSP img-src), with the attribution their usage policy requires.
// Loaded only on the scan page (@vite in diagnostics/scan.blade.php).
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const TILES = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
const ATTRIBUTION = '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors';

// A drawn pin instead of Leaflet's image icons (their paths break once bundled).
const pinIcon = L.divIcon({
    className: 'msas-pin',
    html: '<svg width="28" height="38" viewBox="0 0 28 38" aria-hidden="true"><path d="M14 0C6.3 0 0 6.2 0 13.9 0 24.3 14 38 14 38s14-13.7 14-24.1C28 6.2 21.7 0 14 0z" fill="#059669" stroke="#fff" stroke-width="2"/><circle cx="14" cy="14" r="5" fill="#fff"/></svg>',
    iconSize: [28, 38],
    iconAnchor: [14, 38],
});

/**
 * Shows (or moves) the map in `el`. onMove(lat, lon) is called when the
 * farmer drops the dragged pin. Returns { setPoint(lat, lon, accuracy), destroy() }.
 */
function show(el, { lat, lon, accuracy, onMove, dragLabel }) {
    if (el._msasMap) {
        el._msasMap.setPoint(lat, lon, accuracy);
        return el._msasMap;
    }
    const map = L.map(el, { zoomControl: true, attributionControl: true, scrollWheelZoom: false });
    L.tileLayer(TILES, { maxZoom: 19, attribution: ATTRIBUTION }).addTo(map);
    const circle = L.circle([lat, lon], { radius: accuracy || 0, color: '#059669', weight: 1, fillOpacity: 0.12 }).addTo(map);
    const marker = L.marker([lat, lon], { icon: pinIcon, draggable: true, keyboard: true, title: dragLabel || '' }).addTo(map);

    const fit = (la, lo, acc) => {
        // Close enough to see the field, wide enough to see the whole accuracy circle.
        const zoom = acc > 500 ? 13 : acc > 100 ? 15 : acc > 30 ? 17 : 18;
        map.setView([la, lo], zoom);
    };
    marker.on('dragend', () => {
        const p = marker.getLatLng();
        if (onMove) onMove(Number(p.lat.toFixed(7)), Number(p.lng.toFixed(7)));
    });

    const api = {
        setPoint(la, lo, acc) {
            marker.setLatLng([la, lo]);
            circle.setLatLng([la, lo]);
            if (acc != null) circle.setRadius(acc);
            fit(la, lo, acc ?? 0);
        },
        destroy() {
            map.remove();
            delete el._msasMap;
        },
    };
    fit(lat, lon, accuracy || 0);
    // The container is shown just before this call; let Leaflet measure it.
    setTimeout(() => map.invalidateSize(), 0);
    el._msasMap = api;
    return api;
}

window.MsasMap = { show };
