<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $dealer = User::where('role', 'agro-dealer')->first();
        if (!$dealer) return;

        $products = [
            // ── Veterinary Medicines ──────────────────────────────────────────
            ['name'=>'Ivermectin 1% Injectable','category'=>'Veterinary Medicines','subcategory'=>'Antiparasitic Drugs',
             'brand'=>'Agverm','manufacturer'=>'Agvet Nigeria','unit'=>'50ml bottle',
             'selling_price'=>2500,'cost_price'=>1800,'quantity_in_stock'=>120,'low_stock_threshold'=>20,
             'description'=>'Broad-spectrum antiparasitic for cattle, sheep, goats and poultry. Effective against roundworms, lungworms, lice and mange mites.',
             'dosage_instructions'=>'Cattle: 1ml per 50kg body weight. Subcutaneous injection behind shoulder.',
             'storage_requirements'=>'Store below 25°C away from direct sunlight.',
             'tags'=>['worms','parasites','roundworm','lungworm','lice','mange']],

            ['name'=>'Amprolium 20% Solution (Amprolsol)','category'=>'Veterinary Medicines','subcategory'=>'Antiparasitic Drugs',
             'brand'=>'Vetmediq','manufacturer'=>'Vetmediq Ltd','unit'=>'100ml bottle',
             'selling_price'=>1800,'cost_price'=>1200,'quantity_in_stock'=>85,'low_stock_threshold'=>15,
             'description'=>'Treatment and prevention of coccidiosis in poultry and livestock.',
             'dosage_instructions'=>'Mix 0.5ml per litre of drinking water for 5-7 days.',
             'tags'=>['coccidiosis','diarrhea','bloody droppings','poultry']],

            ['name'=>'Oxytetracycline 20% LA Injection','category'=>'Veterinary Medicines','subcategory'=>'Antibiotics',
             'brand'=>'Terramycin LA','manufacturer'=>'Zoetis','unit'=>'100ml bottle',
             'selling_price'=>4500,'cost_price'=>3200,'quantity_in_stock'=>60,'low_stock_threshold'=>10,
             'description'=>'Long-acting broad-spectrum antibiotic for respiratory infections, foot-rot and other bacterial diseases.',
             'dosage_instructions'=>'20mg/kg body weight IM or SC injection. One injection lasts 3-4 days.',
             'tags'=>['respiratory','pneumonia','foot rot','bacteria','cough']],

            ['name'=>'ORS Animal Electrolyte Sachet','category'=>'Veterinary Medicines','subcategory'=>'Vitamins',
             'brand'=>'FarmCare','manufacturer'=>'FarmCare Nigeria','unit'=>'sachet (50g)',
             'selling_price'=>350,'cost_price'=>200,'quantity_in_stock'=>500,'low_stock_threshold'=>50,
             'description'=>'Oral rehydration salts for animals suffering from diarrhea, heat stress or dehydration.',
             'dosage_instructions'=>'Dissolve one sachet in 5 litres of clean water. Give as sole drinking water.',
             'tags'=>['diarrhea','dehydration','heat stress','electrolyte']],

            ['name'=>'Vitamin ADE Injectable','category'=>'Veterinary Medicines','subcategory'=>'Vitamins',
             'brand'=>'VitADE','manufacturer'=>'Kepro','unit'=>'100ml bottle',
             'selling_price'=>3200,'cost_price'=>2200,'quantity_in_stock'=>75,'low_stock_threshold'=>10,
             'description'=>'Fat-soluble vitamins A, D3 and E for improved growth, reproduction and immunity.',
             'dosage_instructions'=>'Cattle: 5-10ml IM. Poultry: 0.5ml per bird. Repeat monthly.',
             'tags'=>['vitamin deficiency','weak bones','poor growth','reproduction','immunity']],

            ['name'=>'Penicillin-Streptomycin Injection','category'=>'Veterinary Medicines','subcategory'=>'Antibiotics',
             'brand'=>'Penstrep','manufacturer'=>'Norbrook','unit'=>'100ml bottle',
             'selling_price'=>3800,'cost_price'=>2600,'quantity_in_stock'=>55,'low_stock_threshold'=>10,
             'description'=>'Combined antibiotic for treatment of respiratory, genital and enteric infections.',
             'dosage_instructions'=>'1ml per 20kg body weight IM once daily for 3-5 days.',
             'tags'=>['bacteria','infection','respiratory','mastitis','pneumonia']],

            // ── Vaccines ─────────────────────────────────────────────────────
            ['name'=>'Newcastle Disease Vaccine (La Sota)','category'=>'Vaccines','subcategory'=>'Poultry Vaccines',
             'brand'=>'Nobilis ND La Sota','manufacturer'=>'MSD Animal Health','unit'=>'1000 dose vial',
             'selling_price'=>1500,'cost_price'=>900,'quantity_in_stock'=>200,'low_stock_threshold'=>30,
             'description'=>'Live attenuated vaccine for prevention of Newcastle Disease in poultry.',
             'dosage_instructions'=>'Dissolve in drinking water or administer as eye/nostril drop. 1 dose per bird. Vaccinate at day 7 and day 21.',
             'storage_requirements'=>'Store at 2-8°C. Do not freeze.',
             'tags'=>['Newcastle disease','poultry','vaccine','respiratory','nervous signs']],

            ['name'=>'Gumboro Disease Vaccine (IBD)','category'=>'Vaccines','subcategory'=>'Poultry Vaccines',
             'brand'=>'Nobilis Gumboro D78','manufacturer'=>'MSD Animal Health','unit'=>'1000 dose vial',
             'selling_price'=>1800,'cost_price'=>1100,'quantity_in_stock'=>150,'low_stock_threshold'=>25,
             'description'=>'Protection against Infectious Bursal Disease (Gumboro) in broilers and layers.',
             'dosage_instructions'=>'Administer via drinking water at day 10-14 and day 21-24.',
             'storage_requirements'=>'Store at 2-8°C.',
             'tags'=>['Gumboro','IBD','poultry','immunosuppression']],

            ['name'=>'Foot and Mouth Disease Vaccine','category'=>'Vaccines','subcategory'=>'Cattle Vaccines',
             'brand'=>'FMD Bivalent','manufacturer'=>'Vom Vaccines','unit'=>'50ml (25 dose)',
             'selling_price'=>2200,'cost_price'=>1500,'quantity_in_stock'=>80,'low_stock_threshold'=>10,
             'description'=>'Inactivated bivalent vaccine against Foot and Mouth Disease in cattle.',
             'dosage_instructions'=>'2ml per animal SC. Revaccinate every 6 months.',
             'storage_requirements'=>'Store at 4-8°C. Do not freeze.',
             'tags'=>['FMD','foot and mouth','cattle','lameness','blisters']],

            // ── Livestock Feed ────────────────────────────────────────────────
            ['name'=>'Broiler Starter Feed 18% CP','category'=>'Livestock Feed','subcategory'=>'Starter Feed',
             'brand'=>'Animal Care','manufacturer'=>'Animal Care Services','unit'=>'25kg bag',
             'selling_price'=>13500,'cost_price'=>10000,'quantity_in_stock'=>200,'low_stock_threshold'=>20,
             'description'=>'High protein starter feed for broiler chicks from day 1 to day 28. Contains vitamins, minerals and coccidiostat.',
             'usage_instructions'=>'Feed ad libitum from day 1 to 28. Ensure clean water is always available.',
             'tags'=>['broiler','poultry feed','starter','chick']],

            ['name'=>'Layer Mash (16% CP)','category'=>'Livestock Feed','subcategory'=>'Layer Mash',
             'brand'=>'Grand Cereals','manufacturer'=>'Grand Cereals Ltd','unit'=>'25kg bag',
             'selling_price'=>12000,'cost_price'=>9000,'quantity_in_stock'=>300,'low_stock_threshold'=>30,
             'description'=>'Complete layer feed formulated for optimum egg production and shell quality.',
             'usage_instructions'=>'Feed 110-120g per bird per day. Provide at all times.',
             'tags'=>['layer','egg production','poultry','laying hen']],

            ['name'=>'Dairy Cattle Feed (18% CP)','category'=>'Livestock Feed','subcategory'=>'Dairy Feed',
             'brand'=>'WACOT','manufacturer'=>'WACOT Ltd','unit'=>'50kg bag',
             'selling_price'=>22000,'cost_price'=>17000,'quantity_in_stock'=>100,'low_stock_threshold'=>15,
             'description'=>'High-energy concentrate for dairy cows to support milk production and body condition.',
             'usage_instructions'=>'Feed 1kg per 2.5 litres of milk produced. Supplement with good quality roughage.',
             'tags'=>['dairy','cattle','milk production','cow']],

            ['name'=>'Goat & Sheep Pellets','category'=>'Livestock Feed','subcategory'=>'Goat Feed',
             'brand'=>'FarmFirst','manufacturer'=>'FarmFirst Feeds','unit'=>'25kg bag',
             'selling_price'=>11000,'cost_price'=>8000,'quantity_in_stock'=>150,'low_stock_threshold'=>20,
             'description'=>'Balanced pelleted feed for goats and sheep. Rich in protein, energy and minerals.',
             'tags'=>['goat','sheep','pellets','small ruminant']],

            ['name'=>'Floating Fish Feed 32% CP (2mm)','category'=>'Livestock Feed','subcategory'=>'Fish Feed',
             'brand'=>'Skretting','manufacturer'=>'Skretting Nigeria','unit'=>'25kg bag',
             'selling_price'=>28000,'cost_price'=>21000,'quantity_in_stock'=>80,'low_stock_threshold'=>10,
             'description'=>'High-protein floating pellets for catfish and tilapia fingerlings. 2mm pellet size.',
             'usage_instructions'=>'Feed 3-5% of body weight per day in 2-3 meals. Remove uneaten feed after 30 minutes.',
             'tags'=>['fish','catfish','tilapia','aquaculture','fingerling']],

            // ── Crop Protection ───────────────────────────────────────────────
            ['name'=>'Mancozeb 80% WP Fungicide','category'=>'Crop Protection','subcategory'=>'Fungicides',
             'brand'=>'Dithane M-45','manufacturer'=>'Indofil','unit'=>'200g pack',
             'selling_price'=>1500,'cost_price'=>900,'quantity_in_stock'=>300,'low_stock_threshold'=>30,
             'description'=>'Broad-spectrum protective fungicide for control of blight, downy mildew, leaf spot and rust in cereals, vegetables and field crops.',
             'dosage_instructions'=>'Mix 2g per litre of water. Spray every 7-14 days during season.',
             'tags'=>['fungicide','blight','leaf spot','rust','mildew','late blight','early blight']],

            ['name'=>'Cypermethrin 10% EC Insecticide','category'=>'Crop Protection','subcategory'=>'Insecticides',
             'brand'=>'Cyperforce','manufacturer'=>'Jubaili Agrotec','unit'=>'1 litre bottle',
             'selling_price'=>3500,'cost_price'=>2400,'quantity_in_stock'=>200,'low_stock_threshold'=>25,
             'description'=>'Broad-spectrum synthetic pyrethroid for control of aphids, pod borers, armyworms, locusts in maize, tomato and vegetables.',
             'dosage_instructions'=>'Mix 10-20ml per 15 litre knapsack sprayer. Spray in the morning or evening.',
             'tags'=>['insecticide','armyworm','pod borer','aphid','locust','pests']],

            ['name'=>'Glyphosate 36% SL Herbicide','category'=>'Crop Protection','subcategory'=>'Herbicides',
             'brand'=>'Roundup','manufacturer'=>'Bayer CropScience','unit'=>'1 litre bottle',
             'selling_price'=>2800,'cost_price'=>1900,'quantity_in_stock'=>250,'low_stock_threshold'=>25,
             'description'=>'Non-selective systemic herbicide for control of annual and perennial weeds before planting.',
             'dosage_instructions'=>'Mix 100ml per 15 litre sprayer. Apply when weeds are actively growing.',
             'tags'=>['herbicide','weed','pre-planting','grass control']],

            ['name'=>'Neem Oil Biopesticide 2000ppm','category'=>'Crop Protection','subcategory'=>'Bio-pesticides',
             'brand'=>'GreenVet Neem','manufacturer'=>'GreenVet Nigeria','unit'=>'500ml bottle',
             'selling_price'=>800,'cost_price'=>500,'quantity_in_stock'=>400,'low_stock_threshold'=>40,
             'description'=>'Organic biopesticide for control of sucking pests, whiteflies, mites. Safe for beneficial insects and approved for organic farming.',
             'dosage_instructions'=>'Mix 5-10ml per litre of water with a few drops of liquid soap. Spray every 7 days.',
             'tags'=>['organic','biopesticide','whitefly','mite','sucking pest','eco-friendly']],

            // ── Fertilizers ───────────────────────────────────────────────────
            ['name'=>'Urea Fertilizer 46% Nitrogen','category'=>'Fertilizers','subcategory'=>'Urea',
             'brand'=>'NOTORE','manufacturer'=>'NOTORE Chemical Industries','unit'=>'50kg bag',
             'selling_price'=>22000,'cost_price'=>17000,'quantity_in_stock'=>200,'low_stock_threshold'=>20,
             'description'=>'High analysis nitrogen fertilizer for top-dressing cereals (maize, millet, sorghum, rice) and vegetables.',
             'usage_instructions'=>'Apply 60-100kg per hectare as side dressing 3-4 weeks after emergence. Do not apply in heavy rain.',
             'tags'=>['fertilizer','nitrogen','maize','top dressing','yellow leaves','stunted growth']],

            ['name'=>'NPK 15-15-15 Compound Fertilizer','category'=>'Fertilizers','subcategory'=>'NPK Fertilizers',
             'brand'=>'Notore NPK','manufacturer'=>'NOTORE Chemical Industries','unit'=>'50kg bag',
             'selling_price'=>25000,'cost_price'=>19000,'quantity_in_stock'=>180,'low_stock_threshold'=>20,
             'description'=>'Balanced compound fertilizer supplying equal amounts of nitrogen, phosphorus and potassium. Ideal for basal application.',
             'usage_instructions'=>'Apply 150-200kg per hectare at planting. Incorporate into soil.',
             'tags'=>['fertilizer','NPK','basal dressing','planting','all crops']],

            ['name'=>'Organic Compost (Fortified)','category'=>'Fertilizers','subcategory'=>'Organic Fertilizers',
             'brand'=>'SoilMate','manufacturer'=>'SoilMate Organics','unit'=>'25kg bag',
             'selling_price'=>3500,'cost_price'=>2000,'quantity_in_stock'=>500,'low_stock_threshold'=>50,
             'description'=>'Fortified organic compost with added rock phosphate and neem cake. Improves soil structure, water retention and microbial activity.',
             'usage_instructions'=>'Apply 2-5 tonnes per hectare. Mix into topsoil before planting.',
             'tags'=>['organic','compost','soil health','vegetable garden','sustainable']],

            // ── Seeds ─────────────────────────────────────────────────────────
            ['name'=>'SAMMAZ 15 Maize Seed (OPV)','category'=>'Seeds','subcategory'=>'Maize',
             'brand'=>'IAR','manufacturer'=>'Institute for Agricultural Research','unit'=>'5kg bag',
             'selling_price'=>4500,'cost_price'=>3000,'quantity_in_stock'=>300,'low_stock_threshold'=>30,
             'description'=>'Open-pollinated drought-tolerant maize variety. Matures in 90-100 days. Yield potential 4-6 tonnes/ha.',
             'usage_instructions'=>'Sow 1-2 seeds per hole at 75×25cm spacing. Thin to 1 plant per stand.',
             'tags'=>['maize','seed','drought tolerant','OPV']],

            ['name'=>'Soybean Seed TGX 1951-3F','category'=>'Seeds','subcategory'=>'Soybean',
             'brand'=>'IAR','manufacturer'=>'Institute for Agricultural Research','unit'=>'25kg bag',
             'selling_price'=>12000,'cost_price'=>8500,'quantity_in_stock'=>150,'low_stock_threshold'=>15,
             'description'=>'High-yielding soybean variety. Matures in 95-100 days. Resistant to soybean mosaic virus.',
             'usage_instructions'=>'Sow 50-60kg/ha at 45×5cm. Inoculate seed with Rhizobium before planting.',
             'tags'=>['soybean','legume','protein','seed']],

            // ── Veterinary Equipment ──────────────────────────────────────────
            ['name'=>'Automatic Livestock Syringe 20ml','category'=>'Veterinary Equipment','subcategory'=>'Syringes',
             'brand'=>'Socorex','manufacturer'=>'Socorex Switzerland','unit'=>'piece',
             'selling_price'=>8500,'cost_price'=>6000,'quantity_in_stock'=>50,'low_stock_threshold'=>10,
             'description'=>'Self-refilling multi-dose syringe for rapid vaccination and injection of large herds. Adjustable dose from 1-20ml.',
             'tags'=>['syringe','injection','vaccination','equipment']],

            ['name'=>'Digital Livestock Thermometer','category'=>'Veterinary Equipment','subcategory'=>'Thermometers',
             'brand'=>'RobVet','manufacturer'=>'RobVet Germany','unit'=>'piece',
             'selling_price'=>4500,'cost_price'=>3000,'quantity_in_stock'=>30,'low_stock_threshold'=>5,
             'description'=>'Waterproof digital thermometer for measuring body temperature in cattle, sheep, goats and pigs. Fast 10-second reading.',
             'tags'=>['thermometer','fever','temperature','diagnosis','equipment']],

            // ── Farm Equipment ─────────────────────────────────────────────────
            ['name'=>'15-Litre Knapsack Sprayer','category'=>'Farming Equipment','subcategory'=>'Sprayers',
             'brand'=>'TwinStar','manufacturer'=>'TwinStar Agro','unit'=>'piece',
             'selling_price'=>12000,'cost_price'=>8500,'quantity_in_stock'=>40,'low_stock_threshold'=>5,
             'description'=>'Manual pump knapsack sprayer with adjustable nozzle. Pressure range 2-4 bar. Suitable for crop spraying, animal treatment and disinfection.',
             'tags'=>['sprayer','equipment','herbicide application','pesticide application']],
        ];

        foreach ($products as $data) {
            $data['dealer_id'] = $dealer->id;
            $data['is_approved'] = true;
            $data['status'] = 'active';
            Product::firstOrCreate(
                ['name' => $data['name'], 'dealer_id' => $dealer->id],
                $data
            );
        }
    }
}
