import { Image } from 'react-native';
import * as ImagePicker from 'expo-image-picker';
import * as DocumentPicker from 'expo-document-picker';
import { ImageManipulator, SaveFormat } from 'expo-image-manipulator';

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
// HEIC/HEIF (iPhone and some Android cameras) is not accepted by the server,
// so it is converted to JPEG on the phone before upload.
const CONVERT_MIME_TYPES = ['image/heic', 'image/heif'];
export const MAX_UPLOAD_BYTES = 10 * 1024 * 1024;

const getSize = (uri) => new Promise((resolve) => {
  Image.getSize(uri, (width, height) => resolve({ width, height }), () => resolve({ width: 0, height: 0 }));
});

function mimeFromName(name = '') {
  const ext = name.split('.').pop()?.toLowerCase();
  return { jpg: 'image/jpeg', jpeg: 'image/jpeg', png: 'image/png', webp: 'image/webp', heic: 'image/heic', heif: 'image/heif' }[ext] || null;
}

function baseName(name = '') {
  const i = name.lastIndexOf('.');
  return i > 0 ? name.slice(0, i) : (name || 'photo');
}

/** Re-encode an image (e.g. HEIC) as JPEG on the device. */
export async function convertToJpeg(uri) {
  const rendered = await ImageManipulator.manipulate(uri).renderAsync();
  const saved = await rendered.saveAsync({ format: SaveFormat.JPEG, compress: 0.9 });
  return { uri: saved.uri, width: saved.width, height: saved.height };
}

/**
 * The camera/gallery picker re-encodes when quality < 1 (HEIC comes back as
 * JPEG), but the asset can still report the SOURCE file's type and name
 * ("image/heic", "IMG_1.HEIC"). Describe the file actually being uploaded.
 */
function describeOutput(asset) {
  const outType = mimeFromName(asset.uri) || asset.mimeType || 'image/jpeg';
  const ext = { 'image/png': 'png', 'image/webp': 'webp' }[outType] || 'jpg';
  return { mimeType: outType, fileName: `${baseName(asset.fileName || '')}.${ext}` };
}

/**
 * @param {'camera'|'gallery'|'files'} source
 * @param {{ multiple?: boolean, quality?: number }} opts
 * @returns {Promise<{ canceled: boolean, assets: object[], error?: string }>}
 */
export async function acquireScanImages(source, { multiple = false, quality = 0.85 } = {}) {
  if (source === 'files') {
    const result = await DocumentPicker.getDocumentAsync({
      type: [...ACCEPTED_MIME_TYPES, ...CONVERT_MIME_TYPES],
      multiple,
      copyToCacheDirectory: true,
    });
    if (result.canceled) return { canceled: true, assets: [] };

    const assets = [];
    for (const doc of result.assets || []) {
      const mimeType = doc.mimeType || mimeFromName(doc.name);
      if (CONVERT_MIME_TYPES.includes(mimeType) || CONVERT_MIME_TYPES.includes(mimeFromName(doc.name))) {
        let jpeg;
        try {
          jpeg = await convertToJpeg(doc.uri);
        } catch {
          return { canceled: false, assets: [], error: `"${doc.name}" could not be converted. Choose a JPEG or PNG photo instead.` };
        }
        assets.push({ ...jpeg, fileName: `${baseName(doc.name)}.jpg`, mimeType: 'image/jpeg', _source: 'files', _convertedFrom: 'heic' });
        continue;
      }
      if (!ACCEPTED_MIME_TYPES.includes(mimeType)) {
        return { canceled: false, assets: [], error: `"${doc.name}" is not a supported image. Choose a JPEG, PNG, WebP or HEIC photo.` };
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
    assets.push({ ...a, ...describeOutput(a), _source: source });
  }
  return { canceled: false, assets };
}

/** Stable per-submission id: the backend returns the existing scan when a retry repeats it. */
export function newClientScanId() {
  return `m-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 10)}`;
}
