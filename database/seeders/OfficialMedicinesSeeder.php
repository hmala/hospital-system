<?php

namespace Database\Seeders;

use App\Models\Medicine;
use App\Models\MedicineAlternative;
use App\Models\MedicineBatch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OfficialMedicinesSeeder extends Seeder
{
    public function run(): void
    {
        $csvText = <<<'CSV'
01-AA0-004,digoxin 250mcg tab (Accord),digoxin 0.25mg/tab,حبوب,250mcg,,علبة,شريط,2,0,5500,2750,5500,1
01-AA0-004,Lanoxin 0.25mg tab (Aspen),digoxin 0.25mg/tab,حبوب,0.25mg,,علبة,شريط,4,0,7250,1812.5,7250,1
01-AA0-006,digoxin (Anafarm),digoxin amp,أمبول,,,علبة,أمبولة,6,0,5500,916.67,5500,1
01-B00-005,lasiphar 20mg solu. (pharonia pharmaceutical),furosemide 20mg/2ml,أمبول,20mg,,علبة,أمبولة,3,0,2000,666.67,2000,1
01-B00-005,hydroflux sol.for inj. (Intermed),furosemide 20mg/2ml,أمبول,20mg,,علبة,أمبولة,5,0,4000,800,4000,1
01-B00-005,Furosemide inj. IV&IM (Alborz Darou),furosemide 20mg/2ml,أمبول,20mg,,علبة,أمبولة,10,0,4500,450,4500,1
01-B00-005,Furosemide inj. (Gland pharma),furosemide 20mg/2ml,أمبول,20mg,,علبة,أمبولة,10,0,4500,450,4500,1
01-B00-005,Diusemide 20mg/2ml (amp) (APM),furosemide 20mg/2ml,أمبول,20mg,,علبة,أمبولة,5,0,4500,900,4500,1
01-B00-005,piozex 20mg/2ml amp (Pioneer),furosemide 20mg/2ml,أمبول,20mg,,علبة,أمبولة,5,0,5000,1000,5000,1
01-B00-005,Lasix amp (Delpharm Dijon),furosemide 20mg/2ml,أمبول,20mg,,علبة,أمبولة,5,0,6750,1350,6750,1
01-B00-007,lasimex - 40mg tab (SDI),furosemide 40MG,حبوب,40mg,,علبة,شريط,1,0,500,500,500,1
01-B00-007,kinemide 40mg (Al kindi co.),furosemide 40MG,حبوب,40mg,,علبة,شريط,2,0,1000,500,1000,1
01-B00-007,Lasexam 40mg tab (Al-Anaam),furosemide 40MG,حبوب,40mg,,علبة,شريط,3,0,1250,416.67,1250,1
01-B00-007,Salurin - 40mg tablet (Julphar),furosemide 40MG,حبوب,40mg,,علبة,شريط,1,0,1500,1500,1500,1
01-B00-007,furosemide-MDI tab,furosemide 40MG,حبوب,40MG,,علبة,شريط,3,0,1500,500,1500,1
01-B00-007,Lasix 40mg (Opella healthcare),furosemide 40MG,حبوب,40mg,,علبة,شريط,2,0,2750,1375,2750,1
01-B00-007,furosemide 40mg tab (Accord),furosemide 40MG,حبوب,40mg,,علبة,شريط,2,0,3000,1500,3000,1
01-B00-007,desal 40mg tab (Biofarma),furosemide 40MG,حبوب,40mg,,علبة,شريط,2,0,5000,2500,5000,1
01-B00-013,diuzid tab (al safa),Hydrochlorothiazide 50mg,حبوب,50mg,,علبة,شريط,3,0,1750,583.33,1750,1
01-B00-013,urozide 50mg tab (Pioneer),Hydrochlorothiazide 50mg,حبوب,50mg,,علبة,شريط,3,0,3000,1000,3000,1
01-B00-013,Hydrochlorothiazide-50 (Advanced pharma industries),Hydrochlorothiazide 50mg,حبوب,50mg,,علبة,شريط,5,0,3750,750,3750,1
01-B00-016,Rolactone 25mg tab (Sama Al fayhaa),Spironolactone 25mg tab,حبوب,25mg,,علبة,شريط,3,0,4000,1333.33,4000,1
01-B00-016,SPIRONOLACTONE - 25 MG TAB (Accord health care),Spironolactone 25mg tab,حبوب,25 MG,,علبة,شريط,2,0,7750,3875,7750,1
01-B00-016,spironolactone 25mg tab (Bristol lab),Spironolactone 25mg tab,حبوب,25mg,,علبة,شريط,2,0,8750,4375,8750,1
01-B00-020,Rolactone 50mg tab (Al fayhaa),Spironolactone 50mg tab,حبوب,50mg,,علبة,شريط,3,0,5750,1916.67,5750,1
01-B00-017,SPIRONOLACTONE (accord),Spironolactone 100mg tab,حبوب,100mg,,علبة,شريط,2,0,10000,5000,10000,1
01-C00-008,Alphabeta - 6.25 mg tablet (Ajanta),Carvedilol 6.25mg,حبوب,6.25 mg,,علبة,شريط,2,0,1750,875,1750,1
01-C00-008,Karvedol 6.25mg (Al-Kindi co.),Carvedilol 6.25mg,حبوب,6.25mg,,علبة,شريط,3,0,3000,1000,3000,1
01-C00-008,Awacarvedol 6.25 mg (Awamedica),Carvedilol 6.25mg,حبوب,6.25 mg,,علبة,شريط,3,0,4250,1416.67,4250,1
01-C00-008,Coronis 6.25 mg tab. (bilim),Carvedilol 6.25mg,حبوب,6.25 mg,,علبة,شريط,2,0,5500,2750,5500,1
01-C00-008,Carvidol - 6.25mg tab (Pharma international),Carvedilol 6.25mg,حبوب,6.25mg,,علبة,شريط,3,0,5500,1833.33,5500,1
01-C00-008,Unidil 6.25 mg tab (UPM),Carvedilol 6.25mg,حبوب,6.25 mg,,علبة,شريط,3,0,5500,1833.33,5500,1
01-C00-008,Megacard 6.25mg tab (Pioneer),Carvedilol 6.25mg,حبوب,6.25mg,,علبة,شريط,3,0,5750,1916.67,5750,1
01-C00-008,Carvedilol Hexal 6.25mg tab (Salutuspharma GMBH),Carvedilol 6.25mg,حبوب,6.25mg,,علبة,شريط,3,0,8250,2750,8250,1
01-C00-008,carvedi-Denk 6.25 (Denk pharma),Carvedilol 6.25mg,حبوب,6.25mg,,علبة,شريط,3,0,8500,2833.33,8500,1
01-C00-025,becardin - 40mg tab (SDI),Propranolol hydrochloride 40mg,حبوب,40mg,,علبة,شريط,1,0,750,750,750,1
01-C00-025,procard 40 (Pioneer),Propranolol hydrochloride 40mg,حبوب,40mg,,علبة,شريط,3,0,2500,833.33,2500,1
01-C00-025,Pranol Awa - 40mg tablet (Awamedica),Propranolol hydrochloride 40mg,حبوب,40mg,,علبة,شريط,3,0,2500,833.33,2500,1
01-C00-025,Indicardin - 40mg Tab (APM),Propranolol hydrochloride 40mg,حبوب,40mg,,علبة,شريط,5,0,3500,700,3500,1
01-C00-012,labetalol solu. For inj (Omega laboratories LTD),Labetalol hydrochloride 5mg/ml (20ml Ampoule) or Vial,فيال,5mg,,علبة,فيال,1,0,38000,38000,38000,1
01-C00-017,Metoral 5mg/5ml (Alborz Darou),Metoprolol tartrate 1mg/1ml (5ml Ampoule) I.V,أمبول,5mg,,علبة,أمبولة,5,0,5000,1000,5000,1
01-C00-017,Betaloc liquid 1mg/1ml amp (Cenexi),Metoprolol tartrate 1mg/1ml (5ml Ampoule) I.V,أمبول,1mg,,علبة,أمبولة,5,0,21750,4350,21750,1
01-C00-019,Betablok SDK - 50mg CR film coated tablet (Ilko),Metoprolol Succinate 47.5mg (Eq. Metoprolol tartrate 50mg),حبوب,50mg,,علبة,شريط,2,0,3000,1500,3000,1
01-C00-019,metracin - 50mg retard tab (Acino),Metoprolol Succinate 47.5mg (Eq. Metoprolol tartrate 50mg),حبوب,50mg,,علبة,شريط,3,0,9500,3166.67,9500,1
01-C00-015,Metoprolol Awa - 50mg (Awamedica),Metoprolol tartrate 50mg Tablet,حبوب,50mg,,علبة,شريط,3,0,3000,1000,3000,1
01-C00-015,Met xl - 50mg tab (Ajanta),Metoprolol tartrate 50mg Tablet,حبوب,50mg,,علبة,شريط,3,0,3250,1083.33,3250,1
01-C00-015,Metoprolol-Kindi - 50mg tab. (Al kindi co.),Metoprolol tartrate 50mg Tablet,حبوب,50mg,,علبة,شريط,3,0,3750,1250,3750,1
01-C00-015,betablok SDK 50MG C.R. F.C.tab (Ilko ilac),Metoprolol tartrate 50mg Tablet,حبوب,50MG,,علبة,شريط,3,0,4250,1416.67,4250,1
01-C00-015,Metolok 50 - tablet (Sama Al fayhaa),Metoprolol tartrate 50mg Tablet,حبوب,50mg,,علبة,شريط,3,0,4500,1500,4500,1
01-C00-015,Metohexal 50 mg tab.,Metoprolol tartrate 50mg Tablet,حبوب,50 mg,,علبة,شريط,5,0,8000,1600,8000,1
01-C00-015,Betaloc Zok - 50 mg Tablet. (Astra zeneca),Metoprolol tartrate 50mg Tablet,حبوب,50 mg,,علبة,شريط,1,0,13000,13000,13000,1
01-C00-038,Met xl - 100mg tab (ajanta),Metoprolol succinate 95mg (Eq.Metoprolol tartrate 100mg),حبوب,100mg,,علبة,شريط,3,0,5000,1666.67,5000,1
01-C00-038,betablok SDK - 100MG C.R. F.C.tab (ilko),Metoprolol succinate 95mg (Eq.Metoprolol tartrate 100mg),حبوب,100MG,,علبة,شريط,2,0,7000,3500,7000,1
01-C00-038,metracin - 100mg retard tab (Acino),Metoprolol succinate 95mg (Eq.Metoprolol tartrate 100mg),حبوب,100mg,,علبة,شريط,3,0,13250,4416.67,13250,1
01-C00-038,Betaloc Zok 100mg (Astra zeneca),Metoprolol succinate 95mg (Eq.Metoprolol tartrate 100mg),حبوب,100mg,,علبة,شريط,1,0,17250,17250,17250,1
01-C00-006,Bisoprolol 5mg tab (Chanelle medical),Bisoprolol fumarate 5mg,حبوب,5mg,,علبة,شريط,2,0,3000,1500,3000,1
01-C00-006,bisodac 5mg tab (Aurobindo),Bisoprolol fumarate 5mg,حبوب,5mg,,علبة,شريط,2,0,3500,1750,3500,1
01-C00-006,BISOPROLOL - 5 MG F.C TAB (Accord),Bisoprolol fumarate 5mg,حبوب,5 MG,,علبة,شريط,2,0,4500,2250,4500,1
01-C00-006,cardiosafe 5 (The Jordanian pharmaceutical),Bisoprolol fumarate 5mg,حبوب,5mg,,علبة,شريط,3,0,5000,1666.67,5000,1
01-C00-006,Rizoprol - 5mg film coated tablet (World medicine ilac),Bisoprolol fumarate 5mg,حبوب,5mg,,علبة,شريط,3,0,5000,1666.67,5000,1
01-C00-006,B-Cor - 5mg tab (Joswe),Bisoprolol fumarate 5mg,حبوب,5mg,,علبة,شريط,2,0,5000,2500,5000,1
01-C00-006,Cardex - 5 mg film coated tablet (Tabuk),Bisoprolol fumarate 5mg,حبوب,5 mg,,علبة,شريط,3,0,6000,2000,6000,1
01-C00-006,Concor - 5 mg Film-coated tablets (Merck serono),Bisoprolol fumarate 5mg,حبوب,5 mg,,علبة,شريط,2,0,11000,5500,11000,1
01-C00-041,B-Cor - 2.5mg tab (Joswe),Bisoprolol fumarate 2.5mg,حبوب,2.5mg,,علبة,شريط,3,0,3500,1166.67,3500,1
01-C00-041,Cardex 2.5 mg (Tabuk pharma),Bisoprolol fumarate 2.5mg,حبوب,2.5 mg,,علبة,شريط,3,0,4250,1416.67,4250,1
01-C00-041,Concor cor - 2.5 mg Film coated tablet (Merck),Bisoprolol fumarate 2.5mg,حبوب,2.5 mg,,علبة,شريط,2,0,7500,3750,7500,1
01-C00-010,carvedisam 25mg (SDI),Carvedilol 25mg Tablet,حبوب,25mg,,علبة,شريط,2,0,3000,1500,3000,1
01-C00-010,Karvedol 25mg (Al kindi co.),Carvedilol 25mg Tablet,حبوب,25mg,,علبة,شريط,3,0,4000,1333.33,4000,1
01-C00-010,Awacarvedol - 25mg tab (Awamedica),Carvedilol 25mg Tablet,حبوب,25mg,,علبة,شريط,3,0,4250,1416.67,4250,1
01-C00-010,Carvidol - 25mg tab. (Pharma international),Carvedilol 25mg Tablet,حبوب,25mg,,علبة,شريط,3,0,6500,2166.67,6500,1
01-C00-010,Megacard 25mg tab (Pioneer),Carvedilol 25mg Tablet,حبوب,25mg,,علبة,شريط,3,0,8500,2833.33,8500,1
01-C00-010,Carvedilol Hexal 25mg (Salutuspharma GMBH),Carvedilol 25mg Tablet,حبوب,25mg,,علبة,شريط,3,0,12250,4083.33,12250,1
01-D00-027,adozin 3mg/ml amp (Vem ilac),Adenosine 3mg/ml (2ml) Vial or Ampoule,أمبول,3mg,,علبة,أمبولة,1,0,3500,3500,3500,1
01-D00-027,EMERGCINE - ampoule (Pioneer),Adenosine 3mg/ml (2ml) Vial or Ampoule,أمبول,3mg,,علبة,أمبولة,5,0,17250,3450,17250,1
01-D00-027,xoria 6mg/2ml solu. For inj. (Hikma),Adenosine 3mg/ml (2ml) Vial or Ampoule,أمبول,6mg,,علبة,أمبولة,6,0,18500,3083.33,18500,1
01-D00-001,amiocord 150mg/3ml amp (Pioneer),Amiodarone hydrochloride 50mg/ml (3ml) Ampoule,أمبول,150mg,,علبة,أمبولة,5,0,10500,2100,10500,1
01-D00-002,Amiocard tab (Sama Alfayhaa),Amiodarone hydrochloride 200mg Tablet,حبوب,200mg,,علبة,شريط,3,0,4000,1333.33,4000,1
01-D00-002,amiocord 200mg tab (Pioneer),Amiodarone hydrochloride 200mg Tablet,حبوب,200mg,,علبة,شريط,3,0,6500,2166.67,6500,1
01-D00-002,Cordarone - 200mg scored tab (Sanofi Aventis),Amiodarone hydrochloride 200mg Tablet,حبوب,200mg,,علبة,شريط,3,0,13500,4500,13500,1
01-D00-024,Isopamil 80mg tab (Sama Al fayhaa),Verapamil Hydrochloride 80mg,حبوب,80mg,,علبة,شريط,3,0,3000,1000,3000,1
01-D00-024,Isoptin 80mg tab. (Abbvie),Verapamil Hydrochloride 80mg,حبوب,80mg,,علبة,شريط,2,0,7500,3750,7500,1
01-D00-034,LIDOCAINE 2% - 2% inj. (Pioneer),Lidocaine hydrochloride 2% 20mg /ml 2ml Ampoule,أمبول,2%,,علبة,أمبولة,5,0,2000,400,2000,1
01-E00-003,CAPTOPRIL 25-KINDI - 25 mg tab (Al kindi co.),Captopril 25mg Tablet,حبوب,25 mg,,علبة,شريط,2,0,1000,500,1000,1
01-E00-003,Captosam 25 - 25 mg tablet (SDI),Captopril 25mg Tablet,حبوب,25 mg,,علبة,شريط,3,0,1000,333.33,1000,1
01-E00-003,Capotril 25mg tab (Eipico),Captopril 25mg Tablet,حبوب,25mg,,علبة,شريط,2,0,1500,750,1500,1
01-E00-003,Rilcapton 25 mg (Medochemie),Captopril 25mg Tablet,حبوب,25 mg,,علبة,شريط,2,0,2250,1125,2250,1
01-E00-003,Captoneer 25mg (Pioneer),Captopril 25mg Tablet,حبوب,25mg,,علبة,شريط,3,0,2500,833.33,2500,1
01-E00-004,Capotril 50mg tab (Eipico),Captopril 50mg,حبوب,50mg,,علبة,شريط,2,0,1250,625,1250,1
01-E00-004,CAPTOPRIL 50-KINDI - 50mg tab. (Al kindi co.),Captopril 50mg,حبوب,50mg,,علبة,شريط,2,0,1500,750,1500,1
01-E00-004,Captoneer 50mg (Pioneer),Captopril 50mg,حبوب,50mg,,علبة,شريط,3,0,3750,1250,3750,1
01-E00-011,Safapril 5mg (Al safa),Enalapril maleate 5mg,حبوب,5mg,,علبة,شريط,1,0,750,750,750,1
01-E00-012,Enalapril 10mg (Middle east),Enalapril maleate 10mg,حبوب,10mg,,علبة,شريط,3,0,1750,583.33,1750,1
01-E00-013,enalapril - 20mg (Middle east),Enalapril maleate 20mg,حبوب,20mg,,علبة,شريط,3,0,2250,750,2250,1
01-E00-024,Aldosam tab - (SDI),Methyldopa 250mgTablet,حبوب,250mg,,علبة,شريط,1,0,1000,1000,1000,1
01-E00-024,alphamet film coated tab (AL mansor),Methyldopa 250mgTablet,حبوب,250mg,,علبة,شريط,3,0,3500,1166.67,3500,1
01-E00-020,myotan 50mg tab (Unique),Losartan potassium 50mg Tablet,حبوب,50mg,,علبة,شريط,3,0,4500,1500,4500,1
01-E00-020,L-Sartan 50mg (Al kindi co.),Losartan potassium 50mg Tablet,حبوب,50mg,,علبة,شريط,3,0,5750,1916.67,5750,1
01-E00-020,Losart 50mg (Pioneer),Losartan potassium 50mg Tablet,حبوب,50mg,,علبة,شريط,3,0,7500,2500,7500,1
01-E00-020,Cozaar 50mg tab (Merck sharp & Dohme),Losartan potassium 50mg Tablet,حبوب,50mg,,علبة,شريط,2,0,15250,7625,15250,1
01-E00-018,LISNOP-10 - 10 mg tab (Ajanta pharma),Lisinopril 10mg tablet,حبوب,10 mg,,علبة,شريط,2,0,3500,1750,3500,1
01-E00-018,Dapril 10mg tab (Medochemie),Lisinopril 10mg tablet,حبوب,10mg,,علبة,شريط,3,0,5750,1916.67,5750,1
01-E00-018,Zestril - 10mg tab (Rovi),Lisinopril 10mg tablet,حبوب,10mg,,علبة,شريط,2,0,12750,6375,12750,1
01-E00-019,Linopril 20mg (Pharma International),Lisinopril 20mg,حبوب,20mg,,علبة,شريط,2,0,5750,2875,5750,1
01-E00-019,Zestril 20 mg tab. (Rovi),Lisinopril 20mg,حبوب,20 mg,,علبة,شريط,2,0,14250,7125,14250,1
01-E00-067,lastavin - 80mg fct (Ajanta),Valsartan 80mg,حبوب,80mg,,علبة,شريط,3,0,5750,1916.67,5750,1
01-E00-067,Valsart 80mg (Pioneer),Valsartan 80mg,حبوب,80mg,,علبة,شريط,2,0,9250,4625,9250,1
01-E00-067,Vapress 80 f.c.t (Medochemie),Valsartan 80mg,حبوب,80mg,,علبة,شريط,3,0,11500,3833.33,11500,1
01-E00-068,Diovasam 160mg tab (SDI),Valsartan 160mg,حبوب,160mg,,علبة,شريط,2,0,5500,2750,5500,1
01-E00-068,lastavin 160mg f.c. tab (Ajanta),Valsartan 160mg,حبوب,160mg,,علبة,شريط,3,0,7250,2416.67,7250,1
01-E00-068,valsartan Awa 160 (Awamedica),Valsartan 160mg,حبوب,160mg,,علبة,شريط,3,0,9750,3250,9750,1
01-E00-068,Vapress 160 mg tab (Medochemie),Valsartan 160mg,حبوب,160 mg,,علبة,شريط,3,0,13500,4500,13500,1
01-F00-075,Amloval 5/160 (Pioneer),Amlodipine 5 mg + valsartan 160 mg,حبوب,5 mg,,علبة,شريط,2,0,13750,6875,13750,1
01-F00-075,COMBISAR - 5mg/160mg f.c.t (Bilim),Amlodipine 5 mg + valsartan 160 mg,حبوب,5mg,,علبة,شريط,2,0,16500,8250,16500,1
01-F00-076,wamlox - 10/160mg Tablet (Krka),Amlodipine 10mg + valsartan 160 mg,حبوب,160mg,,علبة,شريط,3,0,16500,5500,16500,1
01-E00-078,angizasam 100mg tab (SDI),Losartan potassium 100mg,حبوب,100mg,,علبة,شريط,2,0,6250,3125,6250,1
01-E00-078,Losart 100mg (Pioneer),Losartan potassium 100mg,حبوب,100mg,,علبة,شريط,3,0,9750,3250,9750,1
01-E00-078,Cozaar - 100mg tab (Merck sharp & Dohme),Losartan potassium 100mg,حبوب,100mg,,علبة,شريط,3,0,21750,7250,21750,1
01-E00-114,Vasac 100 mg - (49+51)mg tab (Hilton),Sacubitril 49mg + valsartan 51mg,حبوب,100 mg,,علبة,شريط,3,0,31750,10583.33,31750,1
01-E00-114,entresto - 100 MG TABLET (Novartis),Sacubitril 49mg + valsartan 51mg,حبوب,100 MG,,علبة,شريط,2,0,89000,44500,89000,1
01-E00-115,entresto 200 mg (Novartis pharma),Sacubitril 97mg + valsartan 103mg,حبوب,200 mg,,علبة,شريط,4,0,170000,42500,170000,1
01-E00-058,Telmiclar - 40mg tab (Ajanta),Telmisartan 40mg,حبوب,40mg,,علبة,شريط,3,0,6000,2000,6000,1
01-E00-058,Micardis 40mg tab (Rottendorf),Telmisartan 40mg,حبوب,40mg,,علبة,شريط,4,0,17500,4375,17500,1
01-E00-059,Telmikindin - 80mg tab (Ajanta),Telmisartan 80mg,حبوب,80mg,,علبة,شريط,3,0,8500,2833.33,8500,1
01-E00-059,Micardis 80mg tab (Boehringer Ingelheim),Telmisartan 80mg,حبوب,80mg,,علبة,شريط,4,0,22000,5500,22000,1
01-E00-006,KANDESAT 8 - 8mg tab (Al kindi co.),Candesartan cilexetil 8mg,حبوب,8mg,,علبة,شريط,3,0,3750,1250,3750,1
01-E00-006,CANDASART - 8mg tab (Pioneer),Candesartan cilexetil 8mg,حبوب,8mg,,علبة,شريط,3,0,8000,2666.67,8000,1
01-E00-006,Atacand - 8mg tab (astrazenica),Candesartan cilexetil 8mg,حبوب,8mg,,علبة,شريط,2,0,16500,8250,16500,1
01-E00-060,KANDESAT 16 - 16mg tab (Al kindi co.),Candesartan cilexetil 16mg Tablet,حبوب,16mg,,علبة,شريط,2,0,7500,3750,7500,1
01-E00-060,CANDASART - 16mg tab (Pioneer),Candesartan cilexetil 16mg Tablet,حبوب,16mg,,علبة,شريط,3,0,11750,3916.67,11750,1
01-E00-060,Atacand 16mg (Klocke),Candesartan cilexetil 16mg Tablet,حبوب,16mg,,علبة,شريط,2,0,16500,8250,16500,1
01-F00-002,Amlosaf 5mg (Al-Safa co.),Amlodipine 5 mg,حبوب,5mg,,علبة,شريط,1,0,750,750,750,1
01-F00-002,Amaday 5mg tab (Ajanta),Amlodipine 5 mg,حبوب,5mg,,علبة,شريط,2,0,1500,750,1500,1
01-F00-002,Samadipine-5mg tab (SDI),Amlodipine 5 mg,حبوب,5mg,,علبة,شريط,3,0,3000,1000,3000,1
01-F00-002,Amloneer 5mg (Pioneer),Amlodipine 5 mg,كبسول,5mg,,علبة,شريط,3,0,7000,2333.33,7000,1
01-F00-002,Norvasc 5mg (Pfizer),Amlodipine 5 mg,حبوب,5mg,,علبة,شريط,3,0,14000,4666.67,14000,1
01-F00-003,Amaday-10 - 10 mg tab. (Ajanta),Amlodipine 10mg,حبوب,10 mg,,علبة,شريط,2,0,1500,750,1500,1
01-F00-003,Awalodipin 10mg (Awamedica),Amlodipine 10mg,حبوب,10mg,,علبة,شريط,3,0,3750,1250,3750,1
01-F00-003,Norvasc 10mg (Pfizer),Amlodipine 10mg,حبوب,10mg,,علبة,شريط,3,0,19500,6500,19500,1
01-F00-024,glyceryl trinitrate 500mcg (Accord),Glyceryl trinitrate 0.5mg sublingual,حبوب,500mcg,,علبة,شريط,1,0,13250,13250,13250,1
01-F00-078,Adalat LA 30mg tab (Bayer pharma),Nifedipine 30mg,حبوب,30mg,,علبة,شريط,3,0,19250,6416.67,19250,1
01-G00-002,cardivive 40mg/ml (Pioneer),Dopamine hydrochloride 40mg/ml (5ml),أمبول,40mg,,علبة,أمبولة,5,0,11500,2300,11500,1
01-G00-014,norphed 4mg/4mlsolu. For i.v infusion (Tabuk),Noradrenaline 2mg/ml (4ml),أمبول,4mg,,علبة,أمبولة,10,0,25500,2550,25500,1
02-B00-008,Spasmodain syrup (Wadi-Alrafidain),Hyoscine butylbromide 1mg/ml Syrup,شراب,1mg,,عبوة,عبوة,1,0,2000,2000,2000,1
02-B00-008,Scopinal - 20mg /ml Ampoule (Julphar),Hyoscine butylbromide 20mg/ml Amp,أمبول,20mg,,علبة,أمبولة,5,0,5750,1150,5750,1
02-B00-007,spasmo AS tab (Aswar Alkhaleej),Hyoscine butylbromide 10mg Tablet,حبوب,10mg,,علبة,شريط,1,0,1000,1000,1000,1
02-B00-007,Piocine 10mg (Pioneer),Hyoscine butylbromide 10mg Tablet,حبوب,10mg,,علبة,شريط,2,0,3750,1875,3750,1
02-B00-007,Buscopan 10mg (Delpharm),Hyoscine butylbromide 10mg Tablet,حبوب,10mg,,علبة,شريط,2,0,7000,3500,7000,1
02-C00-035,omyl - injection 40mg (Lyka),Omeprazole 40mg vial for IV,فيال,40mg,,علبة,فيال,1,0,2250,2250,2250,1
02-C00-035,Omeprazole 40mg (Gland pharma),Omeprazole 40mg vial for IV,فيال,40mg,,علبة,فيال,1,0,4500,4500,4500,1
02-C00-035,Risek - 40mg vial IV (julphar),Omeprazole 40mg vial for IV,فيال,40mg,,علبة,فيال,1,0,5750,5750,5750,1
02-C00-038,Esoblok - 40mg IV vial (Deva),Esomeprazole 40mg vial IV,فيال,40mg,,علبة,فيال,1,0,6000,6000,6000,1
02-C00-038,Nexus 40mg vial (Hikma),Esomeprazole 40mg vial IV,فيال,40mg,,علبة,فيال,1,0,8750,8750,8750,1
02-C00-038,Nexium - 40mg vial (astrazenica),Esomeprazole 40mg vial IV,فيال,40mg,,علبة,فيال,10,0,91000,9100,91000,1
02-C00-015,oprazon - 20tab (Al kindi),Omeprazole 20mg Capsule,حبوب,20mg,,علبة,شريط,2,0,1500,750,1500,1
02-C00-015,Pioprazole - 20mg cap. (Pioneer),Omeprazole 20mg Capsule,كبسول,20mg,,علبة,شريط,2,0,3250,1625,3250,1
02-C00-015,Risek 20mg cap (Julphar),Omeprazole 20mg Capsule,كبسول,20mg,,علبة,شريط,1,0,5750,5750,5750,1
02-C00-015,Gasec 20mg - cap (Acino),Omeprazole 20mg Capsule,كبسول,20mg,,علبة,شريط,1,0,13250,13250,13250,1
02-C00-022,pantodar 40mg E.C.T (DAD),Pantoprazole 40mg tablet,حبوب,40mg,,علبة,شريط,1,0,5500,5500,5500,1
02-C00-022,Panto-Denk 40mg tab (Allphamed),Pantoprazole 40mg tablet,حبوب,40mg,,علبة,شريط,1,0,8000,8000,8000,1
02-C00-022,Controloc 40mg G.R.tab (Takeda),Pantoprazole 40mg tablet,حبوب,40mg,,علبة,شريط,1,0,12750,12750,12750,1
02-D00-002,Entero-Stop (SDI),Diphenoxylate 2.5mg + Atropine 25mcg,حبوب,2.5mg,,علبة,شريط,1,0,500,500,500,1
02-D00-002,Lomo-stop tab (Al-Kindi co.),Diphenoxylate 2.5mg + Atropine 25mcg,حبوب,2.5mg,,علبة,شريط,1,0,500,500,500,1
02-F00-014,Ezilax - SYRUP (Tabuk),Lactulose 3.7g/5ml Syrup,شراب,3.7g,,عبوة,عبوة,1,0,2750,2750,2750,1
02-F00-014,Piolac - syrup (Pioneer),Lactulose 3.7g/5ml Syrup,شراب,3.7g,,عبوة,عبوة,1,0,3000,3000,3000,1
02-F00-014,Duphalac - 667 gm/l oral solution (Abbott),Lactulose 3.7g/5ml Syrup,شراب,667 gm,,عبوة,عبوة,1,0,9750,9750,9750,1
02-K00-002,Samapatalin - tablet (SDI),Mebeverine hydrochloride 135mg,حبوب,135mg,,علبة,شريط,1,0,1000,1000,1000,1
02-K00-002,PiOVERIN 135 - 135mg (Pioneer),Mebeverine hydrochloride 135mg,حبوب,135mg,,علبة,شريط,5,0,7000,1400,7000,1
02-K00-002,Duspatalin 135mg (Abbott),Mebeverine hydrochloride 135mg,حبوب,135mg,,علبة,شريط,3,0,12500,4166.67,12500,1
02-L00-001,Motidon 10mg (Pioneer),Domperidone 10mg,حبوب,10mg,,علبة,شريط,2,0,3000,1500,3000,1
02-L00-001,Motilium 10 mg tab. (Lusomedica),Domperidone 10mg,حبوب,10 mg,,علبة,شريط,1,0,8500,8500,8500,1
02-L00-008,Meclodin Amp (SDI),Metoclopramide 5mg/ml (2ml) Amp,أمبول,5mg,,علبة,أمبولة,1,0,500,500,500,1
02-L00-008,METOCOL - 10mg/2ml amp. (Pioneer),Metoclopramide 5mg/ml (2ml) Amp,أمبول,10mg,,علبة,أمبولة,5,0,4500,900,4500,1
02-L00-008,Plasil 10mg/2ml (Sanofi),Metoclopramide 5mg/ml (2ml) Amp,أمبول,10mg,,علبة,أمبولة,5,0,6500,1300,6500,1
03-A00-025,Butadin syrup (SDI),Salbutamol 2mg/5ml Syrup,شراب,2mg,,عبوة,عبوة,1,0,1500,1500,1500,1
03-A00-044,Asthalin 100mcg inhaler (Cipla),Salbutamol inhaler 100mcg,بخاخ / منشقة,100mcg,,عبوة,عبوة,1,0,3000,3000,3000,1
03-A00-044,ventolin evohaler 100mcg (GSK),Salbutamol inhaler 100mcg,بخاخ / منشقة,100mcg,,عبوة,عبوة,1,0,8750,8750,8750,1
03-B00-019,Symbicort 160/4.5mcg turbuhaler (Astra zeneca),Budesonide 160mcg + Formoterol 4.5mcg,بخاخ / منشقة,4.5mcg,,عبوة,عبوة,1,0,33500,33500,33500,1
03-B00-020,Pulmicort 0.5mg/ml (Astra zeneca),Budesonide 500mcg/ml respules,أمبول,500mcg,,علبة,أمبولة,20,0,39000,1950,39000,1
03-D00-004,Histadin tab 4mg (SDI),Chlorpheniramine maleate 4mg,حبوب,4mg,,علبة,شريط,1,0,500,500,500,1
03-D00-022,lorasam 10mg (SDI),Loratadine 10mg,حبوب,10mg,,علبة,شريط,1,0,500,500,500,1
03-D00-022,Claritine tab 10mg (Bayer),Loratadine 10mg,حبوب,10mg,,علبة,شريط,3,0,19750,6583.33,19750,1
03-J00-003,Montix 10mg (Pioneer),Montelukast 10mg,حبوب,10mg,,علبة,شريط,2,0,13250,6625,13250,1
03-J00-003,Singulair 10mg (MSD),Montelukast 10mg,حبوب,10mg,,علبة,شريط,4,0,29750,7437.5,29750,1
04-F00-019,NO-VOMIT 8mg/4ml (Pioneer),Ondansetron 8mg/4ml Amp,أمبول,8mg,,علبة,أمبولة,5,0,14250,2850,14250,1
04-F00-019,Zofran 8mg/4ml (Novartis),Ondansetron 8mg/4ml Amp,أمبول,8mg,,علبة,أمبولة,5,0,38000,7600,38000,1
04-G00-036,Paracetamol IV 1000mg/100ml (Pioneer),Paracetamol 10mg/ml (100ml) IV,فيال,1000mg,,علبة,فيال,1,0,3750,3750,3750,1
04-G00-036,Perfalgan 10mg/ml 100ml (Bristol),Paracetamol 10mg/ml (100ml) IV,فيال,1000mg,,علبة,فيال,1,0,8500,8500,8500,1
04-G00-027,piodol 500mg tab (Pioneer),Paracetamol 500mg,حبوب,500mg,,علبة,شريط,2,0,1000,500,1000,1
04-G00-027,Panadol advance tab (GSK),Paracetamol 500mg,حبوب,500mg,,علبة,شريط,2,0,3500,1750,3500,1
04-G00-002,Aspirin protect 100mg (Bayer),Acetylsalicylic acid 100mg EC,حبوب,100mg,,علبة,شريط,2,0,3250,1625,3250,1
04-H00-012,piomadol 100mg/2ml (Pioneer),Tramadol 100mg/2ml Amp,أمبول,100mg,,علبة,أمبولة,5,0,6500,1300,6500,1
04-H00-012,Tramal 100mg/2ml (Grunenthal),Tramadol 100mg/2ml Amp,أمبول,100mg,,علبة,أمبولة,5,0,15000,3000,15000,1
04-J00-063,Pregafix 75mg (Pioneer),Pregabalin 75mg,كبسول,75mg,,علبة,شريط,3,0,19000,6333.33,19000,1
04-J00-063,Lyrica 75mg (Pfizer),Pregabalin 75mg,كبسول,75mg,,علبة,شريط,4,0,52000,13000,52000,1
05-AA0-002,Amoxycillin 500mg (SDI),Amoxycillin 500mg,كبسول,500mg,,علبة,شريط,2,0,3000,1500,3000,1
05-AA0-002,Amoxil 500mg (GSK),Amoxycillin 500mg,كبسول,500mg,,علبة,شريط,2,0,6500,3250,6500,1
05-AA0-029,Julmentin forte 625mg (Julphar),Amoxicillin 500mg + Clavulanate 125mg,حبوب,625mg,,علبة,شريط,2,0,10000,5000,10000,1
05-AA0-029,Augmentin 625mg (GSK),Amoxicillin 500mg + Clavulanate 125mg,حبوب,625mg,,علبة,شريط,2,0,15500,7750,15500,1
05-AA0-082,Julmentin 1g tab (Julphar),Amoxicillin 875mg + Clavulanate 125mg,حبوب,1000mg,,علبة,شريط,2,0,11500,5750,11500,1
05-AA0-082,Augmentin 1g (GSK),Amoxicillin 875mg + Clavulanate 125mg,حبوب,1000mg,,علبة,شريط,2,0,18500,9250,18500,1
05-AB0-031,PIOXONE 1g (Pioneer),Ceftriaxone 1g IV,فيال,1g,,علبة,فيال,1,0,3750,3750,3750,1
05-AB0-031,Rocephin 1g IV (Roche),Ceftriaxone 1g IV,فيال,1g,,علبة,فيال,1,0,14500,14500,14500,1
05-AG0-005,CIPRONEER 500mg (Pioneer),Ciprofloxacin 500mg,حبوب,500mg,,علبة,شريط,1,0,3750,3750,3750,1
05-AG0-005,Ciproxin 500mg (Bayer),Ciprofloxacin 500mg,حبوب,500mg,,علبة,شريط,1,0,12500,12500,12500,1
05-AG0-059,PIOPENEM 1000mg (Pioneer),Meropenem 1000mg IV,فيال,1000mg,,علبة,فيال,1,0,13750,13750,13750,1
05-AG0-059,Meronem 1g (AstraZeneca),Meropenem 1000mg IV,فيال,1g,,علبة,فيال,1,0,28000,28000,28000,1
05-AG0-049,ZITRONEER 500mg (Pioneer),Azithromycin 500mg,حبوب,500mg,,علبة,شريط,1,0,4000,4000,4000,1
05-AG0-049,Zithromax 500mg (Pfizer),Azithromycin 500mg,حبوب,500mg,,علبة,شريط,1,0,9250,9250,9250,1
05-D00-012,Midagyl 500mg (Pioneer),Metronidazole 500mg,حبوب,500mg,,علبة,شريط,2,0,2500,1250,2500,1
05-D00-012,Flagyl 500mg (Sanofi),Metronidazole 500mg,حبوب,500mg,,علبة,شريط,2,0,6500,3250,6500,1
05-D00-013,Midagyl 500mg/100ml IV (Pioneer),Metronidazole 500mg/100ml IV,فيال,500mg,,علبة,فيال,1,0,2500,2500,2500,1
06-E00-009,Dexacure 8mg/2ml (Pioneer),Dexamethasone 8mg/2ml,أمبول,8mg,,علبة,أمبولة,5,0,3000,600,3000,1
06-E00-009,Decadron 8mg/2ml (MSD),Dexamethasone 8mg/2ml,أمبول,8mg,,علبة,أمبولة,5,0,7500,1500,7500,1
06-E00-018,Hydrocortisone 100mg (Eipico),Hydrocortisone 100mg IV,فيال,100mg,,علبة,فيال,1,0,3000,3000,3000,1
06-E00-018,Solu-Cortef 100mg (Pfizer),Hydrocortisone 100mg IV,فيال,100mg,,علبة,فيال,1,0,8500,8500,8500,1
08-E00-005,PLAVINEER 75mg (Pioneer),Clopidogrel 75mg,حبوب,75mg,,علبة,شريط,2,0,13750,6875,13750,1
08-E00-005,Plavix 75mg (Sanofi),Clopidogrel 75mg,حبوب,75mg,,علبة,شريط,2,0,23500,11750,23500,1
10-AA0-005,VOLTEX 75mg/3ml (Pioneer),Diclofenac sodium 75mg/3ml,أمبول,25mg,,علبة,أمبولة,5,0,4750,950,4750,1
10-AA0-005,Voltaren 75mg/3ml (Novartis),Diclofenac sodium 75mg/3ml,أمبول,25mg,,علبة,أمبولة,5,0,8500,1700,8500,1
10-AA0-011,Piofen 200mg (Pioneer),Ibuprofen 200mg,حبوب,200mg,,علبة,شريط,2,0,1500,750,1500,1
10-AA0-011,Brufen 200mg (Abbott),Ibuprofen 200mg,حبوب,200mg,,علبة,شريط,2,0,3500,1750,3500,1
CSV;

        $lines = explode("\n", trim($csvText));
        $header = str_getcsv(array_shift($lines));

        $count = 0;
        $createdMedIds = [];

        DB::beginTransaction();
        try {
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                $row = str_getcsv($line);
                if (count($row) < 14) continue;

                $nationalCode = trim($row[0]);
                $tradeName = trim($row[1]);
                $genericName = trim($row[2]);
                $dosageForm = trim($row[3]);
                $strength = trim($row[4]);
                $barcode = trim($row[5]) ?: null;
                $mainUnit = trim($row[6]) ?: 'علبة';
                $subUnit = trim($row[7]) ?: 'شريط';
                $subUnitsCount = (int) $row[8] ?: 1;
                $costPrice = (float) $row[9];
                $salePrice = (float) $row[10];
                $subUnitSalePrice = (float) $row[11];
                $hiPrice = (float) $row[12];
                $isCovered = (bool) $row[13];

                if (empty($barcode)) {
                    $barcode = 'MED' . str_pad(mt_rand(100000, 999999), 8, '0', STR_PAD_LEFT);
                }

                $med = Medicine::create([
                    'national_code' => $nationalCode,
                    'name' => $tradeName,
                    'generic_name' => $genericName,
                    'dosage_form' => $dosageForm,
                    'strength' => $strength,
                    'barcode' => $barcode,
                    'main_unit' => $mainUnit,
                    'sub_unit' => $subUnit,
                    'sub_units_count' => $subUnitsCount,
                    'cost_price' => $costPrice,
                    'sale_price' => $salePrice,
                    'sub_unit_sale_price' => $subUnitSalePrice,
                    'hi_price' => $hiPrice,
                    'is_insurance_covered' => $isCovered,
                    'is_active' => true,
                ]);

                $createdMedIds[] = $med->id;

                // إنشاء شحنة افتتاحية أولية في المخزون
                MedicineBatch::create([
                    'medicine_id' => $med->id,
                    'batch_number' => 'INIT-' . strtoupper(Str::random(5)),
                    'expiry_date' => now()->addMonths(mt_rand(12, 36))->toDateString(),
                    'initial_quantity' => 100,
                    'current_quantity' => 100,
                    'current_sub_units' => 0,
                    'purchase_price' => $costPrice ?: ($salePrice * 0.7),
                    'supplier_name' => 'المذخر المركزي / التجهيز المعتمد',
                    'received_at' => now()->toDateString(),
                    'status' => 'active',
                ]);

                $count++;
            }

            // 2. بناء شبكة البدائل التلقائية عبر الرمز الوطني والاسم العلمي المشترك
            $groups = Medicine::select('id', 'national_code', 'generic_name')
                ->whereIn('id', $createdMedIds)
                ->get()
                ->groupBy(function($item) {
                    return $item->national_code ?: Str::slug($item->generic_name);
                });

            foreach ($groups as $group) {
                if ($group->count() > 1) {
                    $ids = $group->pluck('id')->toArray();
                    foreach ($ids as $medId) {
                        foreach ($ids as $altId) {
                            if ($medId !== $altId) {
                                MedicineAlternative::firstOrCreate([
                                    'medicine_id' => $medId,
                                    'alternative_medicine_id' => $altId,
                                ], [
                                    'notes' => 'بديل مكافئ علمياً يحمل نفس الرمز الوطني والتركيز',
                                ]);
                            }
                        }
                    }
                }
            }

            DB::commit();
            $this->command->info("تم استيراد {$count} دواء بنجاح مع شحناتهم الافتتاحية وشبكة البدائل!");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("حدث خطأ أثناء الاستيراد: " . $e->getMessage());
        }
    }
}
