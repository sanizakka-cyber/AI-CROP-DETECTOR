import * as Speech from 'expo-speech';
import { LANGUAGES } from '../context/LanguageContext';

// TTS language code for the current app language
function ttsCode(langCode) {
  const lang = LANGUAGES.find(l => l.code === langCode);
  return lang?.ttsCode || 'en-NG';
}

export const VoiceService = {
  speak(text, langCode = 'en', options = {}) {
    Speech.stop();
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
};
