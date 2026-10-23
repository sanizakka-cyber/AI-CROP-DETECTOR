// Map preview of a captured GPS point: a pin, a circle for the accuracy the
// device reported, and a pin the farmer can drag to correct the point
// (onMove(lat, lon)). Leaflet (unpkg, pinned with SRI) with OpenStreetMap
// tiles and the attribution their usage policy requires, in a WebView —
// react-native-maps would need a Google Maps key. Needs internet: offline,
// the coordinates are still saved and shown above it.
import React, { useMemo, useState } from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { WebView } from 'react-native-webview';
import { Colors, Spacing, Radius, Typography } from '../constants/Theme';

export function mapHtml(lat, lon, accuracy) {
  const zoom = accuracy > 500 ? 13 : accuracy > 100 ? 15 : accuracy > 30 ? 17 : 18;
  return `<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<style>html,body,#m{margin:0;height:100%;}</style></head><body><div id="m"></div><script>
var send=function(o){window.ReactNativeWebView&&window.ReactNativeWebView.postMessage(JSON.stringify(o));};
if(!window.L){send({type:'error'});}else{
var map=L.map('m',{zoomControl:true}).setView([${lat},${lon}],${zoom});
L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'&copy; OpenStreetMap contributors'}).on('tileerror',function(){send({type:'tileerror'});}).addTo(map);
L.circle([${lat},${lon}],{radius:${Number(accuracy) || 0},color:'#059669',weight:1,fillOpacity:0.12}).addTo(map);
var pin=L.divIcon({className:'',html:'<svg width="28" height="38" viewBox="0 0 28 38"><path d="M14 0C6.3 0 0 6.2 0 13.9 0 24.3 14 38 14 38s14-13.7 14-24.1C28 6.2 21.7 0 14 0z" fill="#059669" stroke="#fff" stroke-width="2"/><circle cx="14" cy="14" r="5" fill="#fff"/></svg>',iconSize:[28,38],iconAnchor:[14,38]});
var mk=L.marker([${lat},${lon}],{icon:pin,draggable:true}).addTo(map);
mk.on('dragend',function(){var p=mk.getLatLng();send({type:'moved',lat:+p.lat.toFixed(7),lon:+p.lng.toFixed(7)});});
send({type:'ready'});}
</script></body></html>`;
}

export default function LocationMapPreview({ fix, onMove, t }) {
  const [failed, setFailed] = useState(false);
  // Built once per captured fix: dragging the pin must not reload the map.
  const html = useMemo(() => mapHtml(fix.latitude, fix.longitude, fix.accuracy), [fix.latitude, fix.longitude, fix.accuracy]);

  if (failed) {
    return <Text style={styles.offline} testID="loc-map-offline">{t('loc_map_offline')}</Text>;
  }
  return (
    <View>
      <View style={styles.wrap} testID="loc-map">
        <WebView
          originWhitelist={['*']}
          source={{ html, baseUrl: 'https://www.msasagro.com/' }}
          onMessage={(e) => {
            let msg = null;
            try { msg = JSON.parse(e.nativeEvent.data); } catch { return; }
            if (msg.type === 'moved') onMove(msg.lat, msg.lon);
            if (msg.type === 'error') setFailed(true);
          }}
          onError={() => setFailed(true)}
          scrollEnabled={false}
          nestedScrollEnabled
          style={styles.web}
        />
      </View>
      <Text style={styles.hint}>{t('loc_drag_pin')}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { height: 200, borderRadius: Radius.sm, overflow: 'hidden', borderWidth: 1, borderColor: Colors.border, marginTop: Spacing.xs },
  web: { flex: 1, backgroundColor: Colors.background },
  hint: { ...Typography.tiny, color: Colors.textSecondary, marginTop: 4 },
  offline: { ...Typography.tiny, color: Colors.textSecondary, marginTop: Spacing.xs },
});
