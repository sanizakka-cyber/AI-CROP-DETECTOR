"""
Canonical condition labels: the fixed list each analysis picks from, so the
repeat-pass vote (consensus.py) compares conditions, not wording.

Why. A real banana scan got three phrasings of one finding from its three
analyses ("natural leaf sheath senescence", "natural drying and senescence
of old leaf sheaths", "natural drying and shedding of old outer leaf
sheaths"). Name matching saw three different answers, so the result was
"inconclusive" although every analysis agreed. Now each analysis also
returns `condition_label` from the list below, and the vote is on that
label. Free text stays for the explanation only.

`condition_label` is a plain string in the structured-output schema, not an
enum: the API has an unpublished grammar-size limit ("Schema is too complex
for compilation"), and a schema rejection sends every scan to the legacy
prompt. The list is in the prompt instead, and normalize() maps anything
off the list to "unknown", which falls back to name matching in the vote.

Each label has a category. The backend uses it for the farmer's health line
and the "What to do now" steps, so those follow the condition, not wording.
"""
from typing import Dict, List, Optional, Tuple

UNKNOWN = "unknown"
HEALTHY = "healthy"
NORMAL_AGEING = "normal_ageing"

# Categories the backend understands (Laravel App\Support\ConditionCategory).
CATEGORIES = [
    "healthy", "normal_ageing", "fungal_disease", "bacterial_disease", "viral_disease",
    "pest_damage", "nutrient_problem", "water_or_weather_stress", "physical_damage",
    "chemical_injury", "parasite", "animal_illness", "soil_property", "soil_problem", "unknown",
]

# (label, plain English name, category). Plain names avoid Latin and jargon:
# they are what a farmer reads when the model's own plain name is missing.
_GENERIC_CROP: List[Tuple[str, str, str]] = [
    (HEALTHY, "Healthy plant", "healthy"),
    (NORMAL_AGEING, "Normal ageing (old leaves or outer layers drying naturally)", "normal_ageing"),
    ("fungal_leaf_spot", "Leaf spot disease (fungus)", "fungal_disease"),
    ("rot", "Rot (soft, rotting tissue)", "fungal_disease"),
    ("bacterial_disease", "Bacterial disease", "bacterial_disease"),
    ("viral_disease", "Virus disease", "viral_disease"),
    ("insect_damage", "Insect damage", "pest_damage"),
    ("nitrogen_deficiency", "Not enough nitrogen (yellow older leaves)", "nutrient_problem"),
    ("potassium_deficiency", "Not enough potassium (leaf edges drying)", "nutrient_problem"),
    ("other_nutrient_deficiency", "Plant food shortage", "nutrient_problem"),
    ("water_stress", "Too little or too much water", "water_or_weather_stress"),
    ("sun_or_heat_scorch", "Sun or heat burn", "water_or_weather_stress"),
    ("physical_damage", "Physical damage (wind, tools, animals)", "physical_damage"),
    ("chemical_injury", "Damage from a chemical spray", "chemical_injury"),
    (UNKNOWN, "Not clear from this photo", "unknown"),
]

_CROP_SPECIFIC: Dict[str, List[Tuple[str, str, str]]] = {
    "banana/plantain": [
        ("banana_black_sigatoka", "Black leaf streak (black sigatoka)", "fungal_disease"),
        ("banana_yellow_sigatoka", "Yellow leaf spot (yellow sigatoka)", "fungal_disease"),
        ("banana_fusarium_wilt", "Fusarium wilt (Panama disease)", "fungal_disease"),
        ("banana_xanthomonas_wilt", "Banana bacterial wilt", "bacterial_disease"),
        ("banana_bunchy_top", "Banana bunchy top virus", "viral_disease"),
        ("banana_weevil", "Banana weevil damage", "pest_damage"),
        ("banana_nematodes", "Root worm (nematode) damage", "pest_damage"),
        ("banana_cigar_end_rot", "Cigar end rot of the fruit", "fungal_disease"),
    ],
    "maize": [
        ("maize_northern_leaf_blight", "Northern leaf blight", "fungal_disease"),
        ("maize_grey_leaf_spot", "Grey leaf spot", "fungal_disease"),
        ("maize_rust", "Maize rust", "fungal_disease"),
        ("maize_streak_virus", "Maize streak virus", "viral_disease"),
        ("maize_lethal_necrosis", "Maize lethal necrosis", "viral_disease"),
        ("maize_fall_armyworm", "Fall armyworm damage", "pest_damage"),
        ("maize_stem_borer", "Stem borer damage", "pest_damage"),
        ("maize_stalk_rot", "Stalk rot", "fungal_disease"),
    ],
    "tomato": [
        ("tomato_early_blight", "Early blight", "fungal_disease"),
        ("tomato_late_blight", "Late blight", "fungal_disease"),
        ("tomato_septoria_leaf_spot", "Septoria leaf spot", "fungal_disease"),
        ("tomato_fusarium_wilt", "Fusarium wilt", "fungal_disease"),
        ("tomato_bacterial_wilt", "Bacterial wilt", "bacterial_disease"),
        ("tomato_yellow_leaf_curl", "Tomato yellow leaf curl virus", "viral_disease"),
        ("tomato_tuta_absoluta", "Tomato leaf miner (Tuta absoluta)", "pest_damage"),
        ("tomato_blossom_end_rot", "Blossom end rot (calcium shortage)", "nutrient_problem"),
    ],
    "cassava": [
        ("cassava_mosaic", "Cassava mosaic disease", "viral_disease"),
        ("cassava_brown_streak", "Cassava brown streak disease", "viral_disease"),
        ("cassava_bacterial_blight", "Cassava bacterial blight", "bacterial_disease"),
        ("cassava_anthracnose", "Cassava anthracnose", "fungal_disease"),
        ("cassava_green_mite", "Cassava green mite", "pest_damage"),
        ("cassava_mealybug", "Cassava mealybug", "pest_damage"),
    ],
    "rice": [
        ("rice_blast", "Rice blast", "fungal_disease"),
        ("rice_brown_spot", "Brown spot", "fungal_disease"),
        ("rice_bacterial_leaf_blight", "Bacterial leaf blight", "bacterial_disease"),
        ("rice_yellow_mottle", "Rice yellow mottle virus", "viral_disease"),
    ],
    "cowpea/beans": [
        ("cowpea_mosaic", "Cowpea mosaic virus", "viral_disease"),
        ("cowpea_bacterial_blight", "Bacterial blight", "bacterial_disease"),
        ("cowpea_aphids", "Aphid damage", "pest_damage"),
        ("cowpea_pod_borer", "Pod borer (Maruca) damage", "pest_damage"),
        ("cowpea_thrips", "Flower thrips damage", "pest_damage"),
    ],
    "groundnut": [
        ("groundnut_rosette", "Groundnut rosette virus", "viral_disease"),
        ("groundnut_leaf_spot", "Groundnut leaf spot", "fungal_disease"),
        ("groundnut_rust", "Groundnut rust", "fungal_disease"),
    ],
    "sorghum/millet": [
        ("sorghum_anthracnose", "Sorghum anthracnose", "fungal_disease"),
        ("millet_downy_mildew", "Downy mildew", "fungal_disease"),
        ("sorghum_smut", "Smut (black powder in the head)", "fungal_disease"),
        ("striga", "Witchweed (striga)", "pest_damage"),
    ],
    "yam": [
        ("yam_anthracnose", "Yam anthracnose", "fungal_disease"),
        ("yam_mosaic", "Yam mosaic virus", "viral_disease"),
        ("yam_nematodes", "Root worm (nematode) damage", "pest_damage"),
    ],
    "pepper": [
        ("pepper_anthracnose", "Pepper anthracnose", "fungal_disease"),
        ("pepper_bacterial_spot", "Bacterial spot", "bacterial_disease"),
        ("pepper_mosaic", "Pepper mosaic virus", "viral_disease"),
    ],
    "onion": [
        ("onion_purple_blotch", "Purple blotch", "fungal_disease"),
        ("onion_thrips", "Onion thrips damage", "pest_damage"),
    ],
}

_GENERIC_LIVESTOCK: List[Tuple[str, str, str]] = [
    (HEALTHY, "Healthy animal", "healthy"),
    ("normal_variation", "Normal for this animal (no sign of illness)", "healthy"),
    ("wound_or_injury", "Wound or injury", "physical_damage"),
    ("skin_infection", "Skin infection", "animal_illness"),
    ("external_parasites", "Ticks, lice or mites", "parasite"),
    ("internal_parasites", "Worms (internal parasites)", "parasite"),
    ("diarrhoea", "Diarrhoea (running stomach)", "animal_illness"),
    ("respiratory_disease", "Breathing illness", "animal_illness"),
    ("eye_infection", "Eye infection", "animal_illness"),
    ("lameness_or_foot_problem", "Lameness or foot problem", "animal_illness"),
    ("malnutrition", "Poor feeding (thin, weak)", "animal_illness"),
    (UNKNOWN, "Not clear from this photo", "unknown"),
]

_LIVESTOCK_SPECIFIC: Dict[str, List[Tuple[str, str, str]]] = {
    "cattle": [
        ("cattle_lumpy_skin_disease", "Lumpy skin disease", "animal_illness"),
        ("cattle_foot_and_mouth", "Foot and mouth disease", "animal_illness"),
        ("cattle_cbpp", "Lung sickness (CBPP)", "animal_illness"),
        ("cattle_trypanosomiasis", "Sleeping sickness (trypanosomiasis)", "animal_illness"),
        ("cattle_dermatophilosis", "Skin crust disease (dermatophilosis)", "animal_illness"),
        ("cattle_mastitis", "Udder infection (mastitis)", "animal_illness"),
    ],
    "goat/sheep": [
        ("small_ruminant_ppr", "Goat plague (PPR)", "animal_illness"),
        ("small_ruminant_orf", "Mouth scabs (orf)", "animal_illness"),
        ("small_ruminant_mange", "Mange", "parasite"),
        ("small_ruminant_pneumonia", "Pneumonia", "animal_illness"),
        ("small_ruminant_foot_rot", "Foot rot", "animal_illness"),
    ],
    "poultry": [
        ("poultry_newcastle", "Newcastle disease", "animal_illness"),
        ("poultry_fowl_pox", "Fowl pox", "animal_illness"),
        ("poultry_coccidiosis", "Coccidiosis (bloody droppings)", "animal_illness"),
        ("poultry_gumboro", "Gumboro disease", "animal_illness"),
        ("poultry_respiratory", "Chronic breathing disease", "animal_illness"),
        ("poultry_bird_flu_suspected", "Possible bird flu (report it)", "animal_illness"),
    ],
    "pig": [
        ("pig_african_swine_fever", "African swine fever", "animal_illness"),
        ("pig_mange", "Pig mange", "parasite"),
    ],
}

_PEST: List[Tuple[str, str, str]] = [
    ("fall_armyworm", "Fall armyworm", "pest_damage"),
    ("stem_borer", "Stem borer", "pest_damage"),
    ("aphids", "Aphids", "pest_damage"),
    ("whiteflies", "Whiteflies", "pest_damage"),
    ("thrips", "Thrips", "pest_damage"),
    ("spider_mites", "Spider mites", "pest_damage"),
    ("mealybugs", "Mealybugs", "pest_damage"),
    ("locusts_or_grasshoppers", "Locusts or grasshoppers", "pest_damage"),
    ("termites", "Termites", "pest_damage"),
    ("tomato_leaf_miner", "Tomato leaf miner", "pest_damage"),
    ("pod_borer", "Pod borer", "pest_damage"),
    ("weevils", "Weevils", "pest_damage"),
    ("other_caterpillar", "Caterpillar", "pest_damage"),
    ("other_beetle", "Beetle", "pest_damage"),
    ("other_insect", "Other insect", "pest_damage"),
    ("striga", "Witchweed (striga)", "pest_damage"),
    ("other_weed", "Weed", "pest_damage"),
    ("rodents", "Rats or other rodents", "pest_damage"),
    ("birds", "Birds", "pest_damage"),
    ("disease_not_pest", "Disease damage, not a pest", "fungal_disease"),
    ("pest_damage_unidentified", "Pest damage (pest not seen)", "pest_damage"),
    ("no_pest", "No pest found", "healthy"),
    (UNKNOWN, "Not clear from this photo", "unknown"),
]

_SOIL: List[Tuple[str, str, str]] = [
    ("sandy_soil", "Sandy soil", "soil_property"),
    ("loamy_soil", "Loamy soil (good mixed soil)", "soil_property"),
    ("clay_soil", "Clay soil", "soil_property"),
    ("silty_soil", "Silty soil", "soil_property"),
    ("laterite_soil", "Red laterite soil", "soil_property"),
    ("organic_rich_soil", "Dark soil rich in organic matter", "soil_property"),
    ("waterlogged_soil", "Waterlogged soil", "soil_problem"),
    ("eroded_soil", "Eroded soil", "soil_problem"),
    ("compacted_or_crusted_soil", "Hard, crusted soil", "soil_problem"),
    ("salty_soil", "Salty soil (white crust)", "soil_problem"),
    (UNKNOWN, "Not clear from this photo", "unknown"),
]


def _table(mode: str) -> Dict[str, Tuple[str, str]]:
    rows: List[Tuple[str, str, str]]
    if mode == "crop":
        rows = _GENERIC_CROP + [r for group in _CROP_SPECIFIC.values() for r in group]
    elif mode == "livestock":
        rows = _GENERIC_LIVESTOCK + [r for group in _LIVESTOCK_SPECIFIC.values() for r in group]
    elif mode == "pest":
        rows = _PEST
    elif mode == "soil":
        rows = _SOIL
    else:
        raise ValueError(f"unknown mode {mode}")
    return {label: (name, cat) for label, name, cat in rows}


TABLES = {m: _table(m) for m in ("crop", "livestock", "soil", "pest")}


def labels_for(mode: str) -> List[str]:
    return list(TABLES[mode].keys())


def normalize(mode: str, raw) -> Optional[str]:
    """A label on the list, "unknown" for anything off it, None when absent."""
    if not isinstance(raw, str) or not raw.strip():
        return None
    key = raw.strip().lower().replace("-", "_").replace(" ", "_")
    return key if key in TABLES[mode] else UNKNOWN


def category(mode: str, label: Optional[str]) -> Optional[str]:
    return TABLES[mode][label][1] if label in TABLES[mode] else None


def plain_name(mode: str, label: Optional[str]) -> Optional[str]:
    return TABLES[mode][label][0] if label in TABLES[mode] else None


def prompt_block(mode: str) -> str:
    """The list as the model sees it, grouped the way a farmer's subject is."""
    def line(rows):
        return "\n".join(f"  {label}: {name}" for label, name, _ in rows)

    if mode == "crop":
        parts = ["Any crop:\n" + line(_GENERIC_CROP)]
        parts += [f"{subject.capitalize()}:\n" + line(rows) for subject, rows in _CROP_SPECIFIC.items()]
    elif mode == "livestock":
        parts = ["Any animal:\n" + line(_GENERIC_LIVESTOCK)]
        parts += [f"{subject.capitalize()}:\n" + line(rows) for subject, rows in _LIVESTOCK_SPECIFIC.items()]
    else:
        parts = [line(_PEST if mode == "pest" else _SOIL)]
    return "\n".join(parts)
