import * as Speech from 'expo-speech';
import { LANGUAGES } from '../context/LanguageContext';

// TTS language code for the current app language
function ttsCode(langCode) {
  const lang = LANGUAGES.find(l => l.code === langCode);
  return lang?.ttsCode || 'en-NG';
}

export const VoiceService = {
  // True if the device has an installed TTS voice matching this app
  // language (e.g. "ha" matches a voice tagged "ha-NG" or "ha_NG"). Some
  // Android devices ship with no Hausa/Yoruba/Igbo voice at all, and
  // Fulfulde has no TTS language code anywhere — in both cases Speech.speak
  // silently substitutes a different-language voice instead of failing.
  async isVoiceAvailable(langCode = 'en') {
    if (langCode === 'ff') return false; // no TTS code exists for Fulfulde at all
    const code = ttsCode(langCode);
    try {
      const voices = await Speech.getAvailableVoicesAsync();
      return voices.some(v => v.language && (v.language === code || v.language.split(/[-_]/)[0] === code.split('-')[0]));
    } catch {
      // Can't enumerate voices on this platform/device — assume available
      // rather than blocking narration outright.
      return true;
    }
  },

  /**
   * options.onUnavailableVoice, if given, fires (before speaking) when the
   * device has no voice for langCode — narration still plays in whatever
   * voice the OS substitutes, but the caller can now surface that instead
   * of the user silently hearing the wrong language.
   */
  async speak(text, langCode = 'en', options = {}) {
    Speech.stop();
    if (options.onUnavailableVoice && !(await this.isVoiceAvailable(langCode))) {
      options.onUnavailableVoice(langCode);
    }
    Speech.speak(text, {
      language: ttsCode(langCode),
      rate:    options.rate    ?? 0.88,
      pitch:   options.pitch   ?? 1.0,
      volume:  options.volume  ?? 1.0,
      onDone:  options.onDone  ?? undefined,
      onError: options.onError ?? undefined,
    });
  },

  stop() { Speech.stop(); },

  async isSpeaking() { return Speech.isSpeakingAsync(); },

  // Build the narration sentence for a crop diagnosis
  buildCropNarration(result, plan, langCode) {
    const name   = result.primaryDiagnosis || 'Unknown Disease';
    const sev    = result.severity         || 'mild';
    const causes = (result.likelyCauses    || []).slice(0, 2).join(', ') || 'fungal or bacterial infection';
    const treat  = (plan.immediateActions  || []).map(a => a.action).slice(0, 2).join('. ') || 'consult an agronomist';
    const chem   = (plan.chemicalTreatments|| []).map(c => c.product).slice(0, 2).join(' or ') || '';
    const prev   = (plan.prevention        || []).map(p => p.measure).slice(0, 1).join('. ') || '';

    const texts = {
      en: `Crop diagnosis complete. The disease identified is ${name}. Severity is ${sev}. Likely caused by ${causes}. ${treat}. ${chem ? `Recommended product: ${chem}. ` : ''}${prev}. Consult an agronomist if symptoms persist.`,
      ha: `An gama bincike. An gano cuta mai suna ${name}. Tsananin cuta: ${sev}. Sababi: ${causes}. ${treat}. ${chem ? `Maganin da ake ba da shawarar amfani da shi: ${chem}. ` : ''}Ka tuntuɓi ƙwararren noma idan cuta ta cigaba.`,
      yo: `Àyẹ̀wò irugbin ti pari. Àrùn tí a ṣàyẹ̀wò ni ${name}. Bi o ṣe le to: ${sev}. Idi: ${causes}. ${treat}. ${chem ? `Ọja tí a dábàá: ${chem}. ` : ''}Kan si amoye ogbin ti awọn aami aisan ba tẹsiwaju.`,
      ig: `Nchọpụta ihe ọkụkụ emechara. Ọrịa achọtara bụ ${name}. Ike ya: ${sev}. Ihe kpatara ya: ${causes}. ${treat}. ${chem ? `Ọgwụ a tụpụtara: ${chem}. ` : ''}Kpọtụrụ ọkachamara ọrụ ugbo ma ọrịa ahụ gachaa.`,
      ff: `Liyyi gese maayi. Rafi ngondiri ko ${name}. Nawuɗum: ${sev}. Dow to ummii: ${causes}. ${treat}. ${chem ? `Safarol tafodiraangu: ${chem}. ` : ''}Noddu jangirde nder ngesa si rafi ngondi caali.`,
    };

    return texts[langCode] || texts.en;
  },

  // Build the narration sentence for a livestock diagnosis
  buildLivestockNarration(result, plan, langCode) {
    const name   = result.primaryDiagnosis || 'Unknown Condition';
    const sev    = result.severity         || 'mild';
    const animal = result.animalType       || 'animal';
    const meds   = (plan.chemicalTreatments|| []).map(c => c.product).slice(0, 2).join(' or ') || '';
    const action = (plan.immediateActions  || []).map(a => a.action).slice(0, 1).join('. ') || 'consult a veterinarian';

    const texts = {
      en: `Livestock scan complete. You scanned a ${animal}. The condition identified is ${name}. Severity is ${sev}. ${action}. ${meds ? `Recommended medication: ${meds}. ` : ''}Isolate affected animals and seek veterinary advice immediately if the condition is severe.`,
      ha: `An gama duba dabba. Ka duba ${animal}. An gano cuta mai suna ${name}. Tsanani: ${sev}. ${action}. ${meds ? `Magani: ${meds}. ` : ''}Ka raba dabbobin da suka kamu kuma ka tuntuɓi likitan dabbobi nan da nan idan cuta ta yi tsanani.`,
      yo: `Ṣayẹwo ẹran ti pari. O ṣayẹwo ${animal}. Àrùn tí a rí ni ${name}. Bi o ṣe le to: ${sev}. ${action}. ${meds ? `Oogun tí a dábàá: ${meds}. ` : ''}Ya awọn ẹran tí ó ṣaisan sọtọ ki o si pe oniwosan ẹranko lẹsẹkẹsẹ.`,
      ig: `Nyocha anụmanụ emechara. Ị nyochara ${animal}. Ọrịa achọtara bụ ${name}. Ike ya: ${sev}. ${action}. ${meds ? `Ọgwụ a tụpụtara: ${meds}. ` : ''}Kapụọ anụmanụ ndị ọrịa na akụkụ, wee kpọtụrụ dọkịta anụmanụ ozugbo.`,
      ff: `Liyyi dabba maayi. A liyyii ${animal}. Rafi ngondiri ko ${name}. Nawuɗum: ${sev}. ${action}. ${meds ? `Safarol: ${meds}. ` : ''}Sosnu dabbaaji nawngooji, ndoddu likkitaajo dabbaaji jooni.`,
    };

    return texts[langCode] || texts.en;
  },

  // Build the narration sentence for a soil sample assessment.
  // English-only for now (crop/livestock narration above has full 5-language
  // coverage from earlier work; soil/pest are new scan types this pass adds
  // — translating narration text accurately requires native-speaker review
  // rather than a machine guess, so this falls back to English everywhere
  // until that translation work happens, same as the existing texts.en fallback).
  buildSoilNarration(result, plan, langCode) {
    const soilType = result.primaryDiagnosis || result.subjectName || 'your soil sample';
    const health    = result.healthStatus    || 'assessed';
    const action    = plan.preventiveMeasures || plan.fertilizerRecommendation || 'consult an extension officer for a lab soil test';

    const text = `Soil assessment complete. Soil type identified as ${soilType}. Condition: ${health}. ${action}. This is a visual AI estimate, not a laboratory-confirmed result — a lab test gives the most accurate reading.`;
    return text;
  },

  // Build the narration sentence for a pest identification result.
  buildPestNarration(result, plan, langCode) {
    const pestName = result.primaryDiagnosis || 'the pest';
    const sev       = result.severity || 'moderate';
    const action    = plan.immediateActions?.map(a => a.action).slice(0, 1).join('. ') || 'consult an agronomist';

    const text = `Pest identification complete. Identified as ${pestName}. Severity is ${sev}. ${action}. Contact an extension officer if the infestation spreads.`;
    return text;
  },
};
