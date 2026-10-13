import { Image } from 'react-native';
import * as ImagePicker from 'expo-image-picker';
import * as DocumentPicker from 'expo-document-picker';

/**
 * One image-acquisition path for all four scan modes (Phase 11):
 * 'camera' (take picture), 'gallery' (system photo picker) and 'files'
 * (system document browser). Each returns assets in the same shape the
 * on-device quality gate (lib/imageValidator.js) already expects.
 *
 * Limits mirror the backend's real validation (DiagnoseApiController:
 * mimes:jpeg,jpg,png,webp, max:10240 KB) so a file is rejected here with a
 * clear reason instead of after a slow upload.
 */
export const ACCEPTED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
export const MAX_UPLOAD_BYTES = 10 * 1024 * 1024;

const getSize = (uri) => new Promise((resolve) => {
  Image.getSize(uri, (width, height) => resolve({ width, height }), () => resolve({ width: 0, height: 0 }));
});

function mimeFromName(name = '') {
  const ext = name.split('.').pop()?.toLowerCase();
  return { jpg: 'image/jpeg', jpeg: 'image/jpeg', png: 'image/png', webp: 'image/webp' }[ext] || null;
}

/**
 * @param {'camera'|'gallery'|'files'} source
 * @param {{ multiple?: boolean, quality?: number }} opts
 * @returns {Promise<{ canceled: boolean, assets: object[], error?: string }>}
 */
export async function acquireScanImages(source, { multiple = false, quality = 0.85 } = {}) {
  if (source === 'files') {
    const result = await DocumentPicker.getDocumentAsync({
      type: ACCEPTED_MIME_TYPES,
      multiple,
      copyToCacheDirectory: true,
    });
    if (result.canceled) return { canceled: true, assets: [] };

    const assets = [];
    for (const doc of result.assets || []) {
      const mimeType = doc.mimeType || mimeFromName(doc.name);
      if (!ACCEPTED_MIME_TYPES.includes(mimeType)) {
        return { canceled: false, assets: [], error: `"${doc.name}" is not a supported image. Choose a JPEG, PNG or WebP photo.` };
      }
      if (doc.size && doc.size > MAX_UPLOAD_BYTES) {
        return { canceled: false, assets: [], error: `"${doc.name}" is larger than 10 MB. Choose a smaller photo.` };
      }
      const { width, height } = await getSize(doc.uri);
      assets.push({ uri: doc.uri, width, height, fileSize: doc.size, fileName: doc.name, mimeType, _source: 'files' });
    }
    return { canceled: false, assets };
  }

  // Permission is requested only now, at the moment it is needed.
  const perms = source === 'camera'
    ? await ImagePicker.requestCameraPermissionsAsync()
    : await ImagePicker.requestMediaLibraryPermissionsAsync();
  if (!perms.granted) {
    return {
      canceled: false, assets: [],
      error: source === 'camera'
        ? 'Please allow camera access in your device settings.'
        : 'Please allow photo access in your device settings.',
    };
  }

  const result = source === 'camera'
    ? await ImagePicker.launchCameraAsync({ quality, exif: true })
    : await ImagePicker.launchImageLibraryAsync({ allowsMultipleSelection: multiple, quality, exif: true });
  if (result.canceled) return { canceled: true, assets: [] };

  const assets = [];
  for (const a of result.assets || []) {
    if (a.fileSize && a.fileSize > MAX_UPLOAD_BYTES) {
      return { canceled: false, assets: [], error: 'This photo is larger than 10 MB. Choose a smaller photo.' };
    }
    assets.push({ ...a, _source: source });
  }
  return { canceled: false, assets };
}

/** Stable per-submission id: the backend returns the existing scan when a retry repeats it. */
export function newClientScanId() {
  return `m-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 10)}`;
}
