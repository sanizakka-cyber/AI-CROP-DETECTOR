// Scan error codes on the app side: one helper every scan screen uses
// instead of Alert.alert('Scan Failed', e.message).
//
// The server classifies the failure (error_code), gives a reference
// (SCN-XXXXX) and the next step (action). Staff roles also get
// `technical`; the server decides who sees it, the app only shows what it
// receives. Messages are shown in the app's language (6 languages; Fulfulde
// in English until a native translation exists). The quota and
// field-validation messages come from the server, which knows the numbers.
import { Alert, Share } from 'react-native';
import * as Clipboard from 'expo-clipboard';

const UNAVAILABLE = {
  en: 'Our analysis service is temporarily unavailable. Your scan was NOT counted. Please try again in a few minutes.',
  ha: "Sabis ɗin bincikenmu ba ya aiki na ɗan lokaci. BA a ƙirga binciken ka ba. Don Allah ka sake gwadawa nan da 'yan mintuna.",
  fr: "Notre service d'analyse est temporairement indisponible. Votre analyse n'a PAS été comptée. Veuillez réessayer dans quelques minutes.",
  yo: 'Iṣẹ́ àyẹ̀wò wa kò sí fún ìgbà díẹ̀. A KÒ ka àyẹ̀wò rẹ. Jọ̀wọ́ gbìyànjú lẹ́ẹ̀kan sí i lẹ́yìn ìṣẹ́jú díẹ̀.',
  ig: "Ọrụ nyocha anyị adịghị ugbu a. A GỤGHỊ nyocha gị. Biko nwaa ọzọ n'ime nkeji ole na ole.",
};

export const SCAN_ERROR_TEXT = {
  en: {
    title: 'Scan not completed',
    reference: 'Reference',
    retry: 'Try again', signIn: 'Sign in', upgrade: 'Upgrade plan', choosePhoto: 'Choose another photo', close: 'Close',
    technical: 'Technical details', copy: 'Copy details', copied: 'Copied', share: 'Share details',
    codes: {
      SESSION_EXPIRED: 'Your login has expired. Please sign in again.',
      UPLOAD_TOO_LARGE: 'This photo is too large (over 10 MB). Choose a smaller photo or take a new one.',
      UPLOAD_BAD_FORMAT: 'This file is not a photo we can read. Choose a JPG, PNG, WebP or HEIC photo.',
      UPLOAD_INTERRUPTED: 'The photo did not finish uploading. Check your connection and try again.',
      TOO_MANY_SCANS: 'You are sending scans too quickly. Wait a minute, then try again.',
      INTERNAL_ERROR: 'Something went wrong on our side. Your scan was NOT counted. Please try again, and contact support with the reference below if it keeps happening.',
    },
  },
  ha: {
    title: 'Binciken bai kammala ba',
    reference: 'Lambar shaida',
    retry: 'Sake gwadawa', signIn: 'Shiga', upgrade: 'Haɓaka tsari', choosePhoto: 'Zaɓi wani hoto', close: 'Rufe',
    technical: 'Bayanan fasaha', copy: 'Kwafa bayanai', copied: 'An kwafa', share: 'Raba bayanai',
    codes: {
      SESSION_EXPIRED: 'Lokacin shigarka ya ƙare. Don Allah ka sake shiga.',
      UPLOAD_TOO_LARGE: 'Wannan hoton ya yi girma da yawa (sama da 10 MB). Zaɓi ƙaramin hoto ko ka ɗauki sabo.',
      UPLOAD_BAD_FORMAT: 'Wannan fayil ba hoto ne da za mu iya karantawa ba. Zaɓi hoton JPG, PNG, WebP ko HEIC.',
      UPLOAD_INTERRUPTED: 'Hoton bai gama lodawa ba. Duba hanyar sadarwarka ka sake gwadawa.',
      TOO_MANY_SCANS: 'Kana aika bincike da sauri sosai. Jira minti ɗaya, sannan ka sake gwadawa.',
      INTERNAL_ERROR: 'Wani abu ya lalace a ɓangarenmu. BA a ƙirga binciken ka ba. Don Allah ka sake gwadawa, kuma ka tuntuɓi tallafi da lambar shaida da ke ƙasa idan ya ci gaba.',
    },
  },
  fr: {
    title: 'Analyse non terminée',
    reference: 'Référence',
    retry: 'Réessayer', signIn: 'Se connecter', upgrade: "Changer d'offre", choosePhoto: 'Choisir une autre photo', close: 'Fermer',
    technical: 'Détails techniques', copy: 'Copier les détails', copied: 'Copié', share: 'Partager les détails',
    codes: {
      SESSION_EXPIRED: 'Votre session a expiré. Veuillez vous reconnecter.',
      UPLOAD_TOO_LARGE: 'Cette photo est trop lourde (plus de 10 Mo). Choisissez une photo plus petite ou prenez-en une nouvelle.',
      UPLOAD_BAD_FORMAT: "Ce fichier n'est pas une photo lisible. Choisissez une photo JPG, PNG, WebP ou HEIC.",
      UPLOAD_INTERRUPTED: "La photo n'a pas fini de se télécharger. Vérifiez votre connexion et réessayez.",
      TOO_MANY_SCANS: 'Vous envoyez des analyses trop rapidement. Attendez une minute, puis réessayez.',
      INTERNAL_ERROR: "Un problème est survenu de notre côté. Votre analyse n'a PAS été comptée. Veuillez réessayer et contactez l'assistance avec la référence ci-dessous si le problème persiste.",
    },
  },
  yo: {
    title: 'Àyẹ̀wò kò parí',
    reference: 'Ìtọ́kasí',
    retry: 'Gbìyànjú lẹ́ẹ̀kan sí i', signIn: 'Wọlé', upgrade: 'Ṣe àfikún ètò', choosePhoto: 'Yan fọ́tò mìíràn', close: 'Pa á dé',
    technical: 'Àlàyé ìmọ̀ ẹ̀rọ', copy: 'Daakọ àlàyé', copied: 'A ti daakọ', share: 'Pín àlàyé',
    codes: {
      SESSION_EXPIRED: 'Ìwọlé rẹ ti parí. Jọ̀wọ́ wọlé lẹ́ẹ̀kan sí i.',
      UPLOAD_TOO_LARGE: 'Fọ́tò yìí ti tóbi jù (ju 10 MB lọ). Yan fọ́tò kékeré tàbí ya tuntun.',
      UPLOAD_BAD_FORMAT: 'Fáìlì yìí kì í ṣe fọ́tò tí a lè kà. Yan fọ́tò JPG, PNG, WebP tàbí HEIC.',
      UPLOAD_INTERRUPTED: 'Fọ́tò náà kò parí gbígbéjáde. Ṣàyẹ̀wò ìsopọ̀ rẹ kí o sì gbìyànjú lẹ́ẹ̀kan sí i.',
      TOO_MANY_SCANS: 'O ń fi àyẹ̀wò ránṣẹ́ ní kíákíá jù. Dúró fún ìṣẹ́jú kan, lẹ́yìn náà gbìyànjú lẹ́ẹ̀kan sí i.',
      INTERNAL_ERROR: 'Nǹkan kan ṣẹlẹ̀ ní ẹ̀gbẹ́ wa. A KÒ ka àyẹ̀wò rẹ. Jọ̀wọ́ gbìyànjú lẹ́ẹ̀kan sí i, kí o sì kàn sí ìrànlọ́wọ́ pẹ̀lú ìtọ́kasí ní ìsàlẹ̀ tí ó bá ń ṣẹlẹ̀ síbẹ̀.',
    },
  },
  ig: {
    title: 'Nyocha emechaghị',
    reference: 'Ntụaka',
    retry: 'Nwaa ọzọ', signIn: 'Banye', upgrade: 'Kwalite atụmatụ', choosePhoto: 'Họrọ foto ọzọ', close: 'Mechie',
    technical: 'Nkọwa nka', copy: 'Detuo nkọwa', copied: 'Edetuola', share: 'Kesaa nkọwa',
    codes: {
      SESSION_EXPIRED: 'Ntinye gị agwụla. Biko banye ọzọ.',
      UPLOAD_TOO_LARGE: 'Foto a buru oke ibu (karịa 10 MB). Họrọ foto pere mpe ma ọ bụ see nke ọhụrụ.',
      UPLOAD_BAD_FORMAT: 'Faịlụ a abụghị foto anyị nwere ike ịgụ. Họrọ foto JPG, PNG, WebP ma ọ bụ HEIC.',
      UPLOAD_INTERRUPTED: 'Foto ahụ ebugoghị nke ọma. Lelee njikọ gị ma nwaa ọzọ.',
      TOO_MANY_SCANS: 'Ị na-eziga nyocha ngwa ngwa. Chere otu nkeji, wee nwaa ọzọ.',
      INTERNAL_ERROR: "Ihe ọjọọ mere n'akụkụ anyị. A GỤGHỊ nyocha gị. Biko nwaa ọzọ, kpọtụrụ ndị nkwado jiri ntụaka dị n'okpuru ma ọ bụrụ na ọ na-eme ọzọ.",
    },
  },
};
// Every engine / Claude API failure tells the farmer the same thing.
for (const lang of Object.keys(SCAN_ERROR_TEXT)) {
  for (const code of ['ENGINE_UNREACHABLE', 'ENGINE_AUTH_FAILED', 'AI_PROVIDER_AUTH_FAILED', 'AI_PROVIDER_NO_CREDIT', 'AI_PROVIDER_RATE_LIMIT', 'AI_PROVIDER_ERROR']) {
    SCAN_ERROR_TEXT[lang].codes[code] = UNAVAILABLE[lang];
  }
}

const textFor = (lang) => SCAN_ERROR_TEXT[lang] || SCAN_ERROR_TEXT.en;

/** The farmer-facing message and reference line for an ApiError from a scan. Never technical text. */
export function formatScanError(err, lang = 'en') {
  const t = textFor(lang);
  const code = err?.code || null;
  // Quota (numbers, date) and field validation (which field) are worded by the server, in the user's language.
  const serverWorded = code === 'SCAN_QUOTA_EXCEEDED' || code === 'VALIDATION_FAILED';
  const message = (!serverWorded && code && t.codes[code]) || err?.message || t.codes.INTERNAL_ERROR;
  return {
    title: t.title,
    message,
    referenceLine: err?.reference ? `${t.reference}: ${err.reference}` : null,
  };
}

/** Plain-text technical details (staff only, as sent by the server) for copying or sharing. */
export function technicalText(err) {
  const d = err?.technical;
  if (!d) return null;
  return [
    `Reference: ${d.reference || err.reference || ''}`,
    `Code: ${d.code || err.code || ''}`,
    `HTTP status: ${d.http_status ?? ''}`,
    `Service: ${d.service_label || d.service || ''}`,
    `Time: ${d.timestamp || ''}`,
    `Message: ${d.message || ''}`,
  ].join('\n');
}

/**
 * Shows a failed scan: message, reference, the next step as a button, and
 * for staff a "Technical details" button that opens the details with a
 * Copy / share option. handlers: { onRetry, onSignIn, onUpgrade, onChoosePhoto }.
 */
export function showScanError(err, { lang = 'en', onRetry, onSignIn, onUpgrade, onChoosePhoto } = {}) {
  const t = textFor(lang);
  const { title, message, referenceLine } = formatScanError(err, lang);
  const body = referenceLine ? `${message}\n\n${referenceLine}` : message;
  const buttons = [];
  const action = err?.action;
  if (action === 'retry' && onRetry) buttons.push({ text: t.retry, onPress: onRetry });
  if (action === 'sign_in' && onSignIn) buttons.push({ text: t.signIn, onPress: onSignIn });
  if (action === 'upgrade' && onUpgrade) buttons.push({ text: t.upgrade, onPress: onUpgrade });
  if (action === 'choose_photo' && onChoosePhoto) buttons.push({ text: t.choosePhoto, onPress: onChoosePhoto });
  const tech = technicalText(err);
  if (tech) {
    buttons.push({
      text: t.technical,
      onPress: () => Alert.alert(t.technical, tech, [
        // A real copy (expo-clipboard, build 25+); Share is still offered for sending to support.
        { text: t.copy, onPress: () => Clipboard.setStringAsync(tech).then(() => Alert.alert(t.copied)).catch(() => Share.share({ message: tech }).catch(() => {})) },
        { text: t.share, onPress: () => Share.share({ message: tech }).catch(() => {}) },
        { text: t.close, style: 'cancel' },
      ]),
    });
  }
  buttons.push({ text: t.close, style: 'cancel' });
  Alert.alert(title, body, buttons);
}
