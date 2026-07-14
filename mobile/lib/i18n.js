import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';

const en = {
  translation: {
    welcome: 'Welcome to FarmAI', tagline: 'Smart health diagnostics for your crops & livestock',
    login: 'Login', register: 'Register', phone: 'Phone Number', password: 'Password',
    name: 'Full Name', submit: 'Submit', logout: 'Logout', language: 'English',
    home: 'Home', scan: 'Scan', records: 'Records', market: 'Market', profile: 'Profile',
    goodMorning: 'Good morning', quickActions: 'Quick Actions', scanCrop: 'Scan Crop',
    scanAnimal: 'Animal Check', myFarm: 'My Farm', alerts: 'Alerts', recentDiagnoses: 'Recent Diagnoses',
    outbreakAlerts: 'Outbreak Alerts', noAlerts: 'No alerts in your area', noDiagnoses: 'No scans yet — tap Scan to start',
    selectCrop: 'Select Crop', selectAnimal: 'Select Animal', takePhoto: 'Take Photo',
    fromGallery: 'Choose from Gallery', analyzing: 'Analysing image...', result: 'Diagnosis Result',
    confidence: 'Accuracy', severity: 'Severity', treatment: 'Treatment Plan', organic: 'Organic',
    chemical: 'Chemical', prevention: 'Prevention', vetAdvice: 'Call Expert', mild: 'Mild',
    moderate: 'Moderate', severe: 'Severe', emergency: 'EMERGENCY', processing: 'Processing your scan...',
    scanAnother: 'Scan Another', saveRecord: 'Save to Records', wasHelpful: 'Was this helpful?',
    myAnimals: 'My Animals', addAnimal: 'Add Animal', cattle: 'Cattle', goat: 'Goat', sheep: 'Sheep',
    poultry: 'Poultry', healthy: 'Healthy', sick: 'Sick', recovering: 'Recovering', products: 'Products',
    searchProducts: 'Search products...', addToCart: 'Add to Cart', orderNow: 'Order Now',
    inStock: 'In Stock', price: 'Price', error: 'Something went wrong', retry: 'Retry',
    offline: 'You are offline. Results saved for sync.'
  }
};

const ha = {
  translation: {
    welcome: 'Maraba da FarmAI', tagline: 'Binciken lafiya mai wayo ga amfanin gonan ku da dabbobi',
    login: 'Shiga', register: 'Yi Rajista', phone: 'Lambar Waya', password: 'Kalmar Wucewa',
    name: 'Cikakken Suna', submit: 'Tura', logout: 'Fita', language: 'Hausa',
    home: 'Gida', scan: 'Duba', records: 'Bayananku', market: 'Kasuwa', profile: 'Bayanin Kai',
    goodMorning: 'Ina kwana', quickActions: 'Ayyukan Gaggawa', scanCrop: 'Duba Amfanin Gona',
    scanAnimal: 'Duba Dabba', myFarm: 'Gonar ta', alerts: 'Gargadi', recentDiagnoses: 'Bincike na Kwanan Nan',
    outbreakAlerts: 'Gargadin Cututtuka', noAlerts: 'Babu gargadi a yankin ku', noDiagnoses: 'Babu bincike tukuna — danna Duba don farawa',
    selectCrop: 'Zaɓi Amfanin Gona', selectAnimal: 'Zaɓi Dabba', takePhoto: 'Ɗauki Hoto',
    fromGallery: 'Zaɓi daga Hotunan Wayar', analyzing: 'Ana bincika hoto...', result: 'Sakamakon Bincike',
    confidence: 'Daidaito', severity: 'Tsanani', treatment: 'Shirin Magani', organic: 'Na Halitta',
    chemical: 'Sinadarai', prevention: 'Kariya', vetAdvice: 'Kira Ƙwararren', mild: 'Kaɗan',
    moderate: 'Matsakaici', severe: 'Mai tsanani', emergency: 'GAGGAWA', processing: 'Ana sarrafa dubanka...',
    scanAnother: 'Sake Duba', saveRecord: 'Ajiye cikin Bayanai', wasHelpful: 'Shin ya taimaka?',
    myAnimals: 'Dabbobin na', addAnimal: 'Ƙara Dabba', cattle: 'Shanu', goat: 'Awaki', sheep: 'Tumaki',
    poultry: 'Kaji', healthy: 'Mai lafiya', sick: 'Marasa lafiya', recovering: 'Ana waraka', products: 'Kayayyaki',
    searchProducts: 'Nemo kayayyaki...', addToCart: 'Ƙara zuwa Kwandon', orderNow: 'Oda Yanzu',
    inStock: 'Akwai Hannun', price: 'Farashi', error: 'Wani abu ya faru', retry: 'Sake Gwadawa',
    offline: 'Ba ku da intanet. An adana sakamako don aika daga baya.'
  }
};

const ig = {
  translation: {
    welcome: 'Nnọọ na FarmAI', tagline: 'Nnyocha ahụike smart maka ihe ọkụkụ na anụ ụlọ gị',
    login: 'Banye', register: 'Debanye aha', phone: 'Nọmba ekwentị', password: 'Paswọdu',
    name: 'Aha zuru oke', submit: 'Nyefee', logout: 'Pụọ', language: 'Igbo',
    home: 'Ụlọ', scan: 'Nyochaa', records: 'Ndekọ', market: 'Ahịa', profile: 'Profaịlụ',
    goodMorning: 'Ụtụtụ ọma', quickActions: 'Ọrụ ọsọ ọsọ', scanCrop: 'Nyochaa ihe ọkụkụ',
    scanAnimal: 'Nyochaa anụmanụ', myFarm: 'Ugbo m', alerts: 'Ịdọ aka ná ntị', recentDiagnoses: 'Nchọpụta ọhụrụ',
    outbreakAlerts: 'Ịdọ aka ná ntị ntiwapụ', noAlerts: 'Enweghị ịdọ aka ná ntị n\'ógbè gị', noDiagnoses: 'Enweghị nyocha - pịa Nyochaa iji malite',
    selectCrop: 'Họrọ ihe ọkụkụ', selectAnimal: 'Họrọ anụmanụ', takePhoto: 'See foto',
    fromGallery: 'Họrọ na gallery', analyzing: 'Na-enyocha foto...', result: 'Nsonaazụ nchọpụta',
    confidence: 'Eziokwu', severity: 'Ike', treatment: 'Atụmatụ ọgwụgwọ', organic: 'Nke okike',
    chemical: 'Chemical', prevention: 'Mgbochi', vetAdvice: 'Kpọọ ọkachamara', mild: 'Adịghị ike',
    moderate: 'N\'etiti', severe: 'Dị ike', emergency: 'Mberede', processing: 'Na-ahazi nyocha gị...',
    scanAnother: 'Nyochaa ọzọ', saveRecord: 'Chekwa na ndekọ', wasHelpful: 'Nke a ọ nyeere aka?',
    myAnimals: 'Anụmanụ m', addAnimal: 'Tụkwasị anụmanụ', cattle: 'Ehi', goat: 'Ewu', sheep: 'Atụrụ',
    poultry: 'Anụ ufe', healthy: 'Ahụike', sick: 'Ọrịa', recovering: 'Na-agbake', products: 'Ngwaahịa',
    searchProducts: 'Chọọ ngwaahịa...', addToCart: 'Tinye na ụgbọ', orderNow: 'Nye iwu ugbu a',
    inStock: 'Na ngwaahịa', price: 'Ọnụahịa', error: 'Ihe mebiri emebi', retry: 'Gbalịa ọzọ',
    offline: 'Ị nọghị na ịntanetị. E chekwaara nsonaazụ.'
  }
};

const yo = {
  translation: {
    welcome: 'Kaabọ si FarmAI', tagline: 'Ṣiṣayẹwo ilera ọlọgbọn fun awọn irugbin & ẹran-ọsin rẹ',
    login: 'Wọle', register: 'Forukọsilẹ', phone: 'Nọmba foonu', password: 'Ọrọ igbaniwọle',
    name: 'Orukọ kikun', submit: 'Firanṣẹ', logout: 'Jade', language: 'Yoruba',
    home: 'Ile', scan: 'Ṣayẹwo', records: 'Awọn igbasilẹ', market: 'Ọja', profile: 'Ifihan',
    goodMorning: 'Ẹ kaarọ', quickActions: 'Awọn igbesẹ kiakia', scanCrop: 'Ṣayẹwo irugbin',
    scanAnimal: 'Ṣayẹwo ẹran', myFarm: 'Fermu mi', alerts: 'Awọn itaniji', recentDiagnoses: 'Ayẹwo to šẹšẹ',
    outbreakAlerts: 'Itaniji ibesile', noAlerts: 'Ko si itaniji ni agbegbe rẹ', noDiagnoses: 'Ko si ayẹwo - tẹ Ṣayẹwo lati bẹrẹ',
    selectCrop: 'Yan irugbin', selectAnimal: 'Yan ẹran', takePhoto: 'Ya fọto',
    fromGallery: 'Yan lati gallery', analyzing: 'Nṣayẹwo aworan...', result: 'Esi Ayẹwo',
    confidence: 'Dede', severity: 'Bi o ṣe le to', treatment: 'Eto itọju', organic: 'Adayeba',
    chemical: 'Kemikali', prevention: 'Idena', vetAdvice: 'Pe Amoye', mild: 'Kekere',
    moderate: 'Alabọde', severe: 'Pupo', emergency: 'PAJAWIRI', processing: 'Nṣiṣẹ lori ayẹwo rẹ...',
    scanAnother: 'Ṣayẹwo miiran', saveRecord: 'Fipamọ sinu igbasilẹ', wasHelpful: 'Ṣe eyi wulo?',
    myAnimals: 'Awọn ẹran mi', addAnimal: 'Fi ẹran kun', cattle: 'Malu', goat: 'Ewure', sheep: 'Agutan',
    poultry: 'Adie', healthy: 'Ni ilera', sick: 'Nṣaisan', recovering: 'Ngba iwosan', products: 'Awọn ọja',
    searchProducts: 'Wa awọn ọja...', addToCart: 'Fi sinu kẹkẹ', orderNow: 'Bere bayi',
    inStock: 'Wa ninu itaja', price: 'Iye', error: 'Nkan ti ko tọ', retry: 'Tun gbiyanju',
    offline: 'O ko ni ayelujara. Esi ti wa ni fipamọ.'
  }
};

const ff = {
  translation: {
    welcome: 'Bismillaahi to FarmAI', tagline: 'Kisa ndamu ngam gese e dabbaaji maa',
    login: 'Naatu', register: 'Winndito', phone: 'Lamba waya', password: 'Kodin sirri',
    name: 'Innde timmunde', submit: 'Neldu', logout: 'Yaltu', language: 'Fulfulde',
    home: 'Saare', scan: 'Liyyu', records: 'Binndi', market: 'Luumo', profile: 'Joomum',
    goodMorning: 'Jam waali', quickActions: 'Golle yaawɗe', scanCrop: 'Liyyu gawri',
    scanAnimal: 'Liyyu dabba', myFarm: 'Ngesa am', alerts: 'Ewndu', recentDiagnoses: 'Liyyi kumpital',
    outbreakAlerts: 'Ewndu rafi', noAlerts: 'Walaa ewndu e nokku maa', noDiagnoses: 'Walaa liyyi - Bappu Liyyu',
    selectCrop: 'Suɓo gawri', selectAnimal: 'Suɓo dabba', takePhoto: 'Hooƴu foto',
    fromGallery: 'Suɓo nder fotooji', analyzing: 'Ndon liyya foto...', result: 'Njamu kumpital',
    confidence: 'Goonɗingol', severity: 'Nawuɗum', treatment: 'Safaku', organic: 'Tagoore',
    chemical: 'Kemikal', prevention: 'Hada', vetAdvice: 'Noddu Likkitaajo', mild: 'Seɗɗa',
    moderate: 'Hakkunde', severe: 'Nawuɗum', emergency: 'JAWJAW', processing: 'Ndon liyya...',
    scanAnother: 'Liyyu fahin', saveRecord: 'Aynu nder binndi', wasHelpful: 'Ɗum walli?',
    myAnimals: 'Dabbaaji am', addAnimal: 'Ɓesdu dabba', cattle: 'Na\'i', goat: 'Bewa', sheep: 'Mbaalu',
    poultry: 'Gertooɗe', healthy: 'Jam', sick: 'Nawɗo', recovering: 'Hooƴi jam', products: 'Kujeeji',
    searchProducts: 'Tefu kujeeji...', addToCart: 'Ɓesdu', orderNow: 'Yamdu jooni',
    inStock: 'Ndon', price: 'Ceedey', error: 'Fella', retry: 'Pahin',
    offline: 'A walaa netwok.'
  }
};

const fr = {
  translation: {
    welcome: 'Bienvenue sur FarmAI', tagline: 'Diagnostics de santé intelligents pour vos cultures et votre bétail',
    login: 'Connexion', register: 'S\'inscrire', phone: 'Numéro de téléphone', password: 'Mot de passe',
    name: 'Nom complet', submit: 'Soumettre', logout: 'Déconnexion', language: 'Français',
    home: 'Accueil', scan: 'Scanner', records: 'Dossiers', market: 'Marché', profile: 'Profil',
    goodMorning: 'Bonjour', quickActions: 'Actions rapides', scanCrop: 'Scanner culture',
    scanAnimal: 'Vérifier animal', myFarm: 'Ma Ferme', alerts: 'Alertes', recentDiagnoses: 'Diagnostics récents',
    outbreakAlerts: 'Alertes d\'épidémie', noAlerts: 'Aucune alerte dans votre zone', noDiagnoses: 'Aucun scan — appuyez sur Scanner pour commencer',
    selectCrop: 'Sélectionner culture', selectAnimal: 'Sélectionner animal', takePhoto: 'Prendre une photo',
    fromGallery: 'Choisir depuis la galerie', analyzing: 'Analyse de l\'image...', result: 'Résultat du diagnostic',
    confidence: 'Précision', severity: 'Sévérité', treatment: 'Plan de traitement', organic: 'Biologique',
    chemical: 'Chimique', prevention: 'Prévention', vetAdvice: 'Appeler l\'Expert', mild: 'Léger',
    moderate: 'Modéré', severe: 'Sévère', emergency: 'URGENCE', processing: 'Traitement de votre scan...',
    scanAnother: 'Scanner un autre', saveRecord: 'Enregistrer le dossier', wasHelpful: 'Était-ce utile ?',
    myAnimals: 'Mes Animaux', addAnimal: 'Ajouter un animal', cattle: 'Bétail', goat: 'Chèvre', sheep: 'Mouton',
    poultry: 'Volaille', healthy: 'En bonne santé', sick: 'Malade', recovering: 'En convalescence', products: 'Produits',
    searchProducts: 'Rechercher des produits...', addToCart: 'Ajouter au panier', orderNow: 'Commander',
    inStock: 'En stock', price: 'Prix', error: 'Un problème est survenu', retry: 'Réessayer',
    offline: 'Vous êtes hors ligne. Résultats sauvegardés.'
  }
};

i18n
  .use(initReactI18next)
  .init({
    compatibilityJSON: 'v4',
    resources: { en, ha, ig, yo, ff, fr },
    lng: 'en',
    fallbackLng: 'en',
    interpolation: { escapeValue: false },
  });

export default i18n;
