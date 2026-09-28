<?php

namespace Database\Seeders\Eye;

use App\Models\Eye\EyeStoreItem;
use App\Models\Location;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class EyeCenterSeeder extends Seeder
{
    public function run(): void
    {
        // 1. إنشاء موقع مخزن مركز العيون
        $eyeLocation = Location::firstOrCreate(
            ['name' => 'مخزن مركز العيون'],
            ['type' => 'sub']
        );

        // 2. صلاحيات قسم العيون
        $permissions = [
            'view eye center',
            'manage eye appointments',
            'manage eye cashier',
            'conduct eye examinations',
            'manage eye investigations',
            'manage eye store',
            'perform eye surgeries',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(['name' => $permName]);
        }

        // منح الصلاحيات للأدوار الافتراضية
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permissions);
        }

        $doctorRole = Role::where('name', 'doctor')->first();
        if ($doctorRole) {
            $doctorRole->givePermissionTo([
                'view eye center',
                'conduct eye examinations',
                'manage eye investigations',
                'perform eye surgeries',
            ]);
        }

        $receptionistRole = Role::where('name', 'receptionist')->first();
        if ($receptionistRole) {
            $receptionistRole->givePermissionTo([
                'view eye center',
                'manage eye appointments',
            ]);
        }

        $cashierRole = Role::where('name', 'cashier')->first();
        if ($cashierRole) {
            $cashierRole->givePermissionTo([
                'view eye center',
                'manage eye cashier',
            ]);
        }

        // 3. إنشاء قسم مركز وجراحة العيون كقسم رسمي في المستشفى
        $eyeDept = \App\Models\Department::firstOrCreate(
            ['name' => 'مركز وجراحة العيون'],
            [
                'hospital_id'          => 1,
                'type'                 => 'surgery',
                'room_number'          => 'EYE-101',
                'consultation_fee'     => 25000,
                'working_hours_start'  => '08:00:00',
                'working_hours_end'    => '20:00:00',
                'max_patients_per_day' => 50,
                'is_active'            => true,
            ]
        );

        // 4. إنشاء نخبة من استشاريي وأطباء العيون المتخصصين
        $eyeDoctors = [
            [
                'name'               => 'د. فراس حميد العبيدي',
                'email'              => 'firas.eye@hospital.com',
                'phone'              => '07701112233',
                'specialization'     => 'جراحة الشبكية والجسم الزجاجي',
                'qualification'      => 'استشاري جراحة الشبكية والعيون (FRCS)',
                'license_number'     => 'DOC-EYE-001',
                'consultation_fee'   => 35000,
                'working_days'       => ['السبت', 'الإثنين', 'الأربعاء'],
                'is_available_today' => true,
            ],
            [
                'name'               => 'د. عمار يوسف خليل',
                'email'              => 'ammar.eye@hospital.com',
                'phone'              => '07702223344',
                'specialization'     => 'جراحة الساد (الفاكو) وزراعة العدسات',
                'qualification'      => 'استشاري جراحة الساد وتصحيح البصر (ICO)',
                'license_number'     => 'DOC-EYE-002',
                'consultation_fee'   => 30000,
                'working_days'       => ['السبت', 'الأحد', 'الثلاثاء', 'الخميس'],
                'is_available_today' => true,
            ],
            [
                'name'               => 'د. رنا سعدون الجميلي',
                'email'              => 'rana.eye@hospital.com',
                'phone'              => '07703334455',
                'specialization'     => 'طب عيون الأطفال وجراحة الحول',
                'qualification'      => 'بورد عربي في طب وجراحة العيون',
                'license_number'     => 'DOC-EYE-003',
                'consultation_fee'   => 25000,
                'working_days'       => ['الأحد', 'الإثنين', 'الأربعاء'],
                'is_available_today' => true,
            ],
            [
                'name'               => 'د. مصطفى كمال التميمي',
                'email'              => 'mustafa.eye@hospital.com',
                'phone'              => '07704445566',
                'specialization'     => 'أمراض وجراحة القرنية والليزك',
                'qualification'      => 'زمالة كلية الجراحين الملكية البريطانية',
                'license_number'     => 'DOC-EYE-004',
                'consultation_fee'   => 30000,
                'working_days'       => ['السبت', 'الثلاثاء', 'الخميس'],
                'is_available_today' => true,
            ],
            [
                'name'               => 'د. زينب عبد الحسين',
                'email'              => 'zainab.eye@hospital.com',
                'phone'              => '07705556677',
                'specialization'     => 'تشخيص وعلاج الجلوكوما وضغط العين',
                'qualification'      => 'دكتوراه طب وجراحة العيون',
                'license_number'     => 'DOC-EYE-005',
                'consultation_fee'   => 25000,
                'working_days'       => ['الإثنين', 'الثلاثاء', 'الأربعاء'],
                'is_available_today' => false,
            ],
        ];

        foreach ($eyeDoctors as $docData) {
            $user = \App\Models\User::firstOrCreate(
                ['email' => $docData['email']],
                [
                    'name'              => $docData['name'],
                    'phone'             => $docData['phone'],
                    'password'          => \Illuminate\Support\Facades\Hash::make('password123'),
                    'email_verified_at' => now(),
                ]
            );

            if ($doctorRole && !$user->hasRole('doctor')) {
                $user->assignRole($doctorRole);
            }

            \App\Models\Doctor::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'phone'              => $docData['phone'],
                    'department_id'      => $eyeDept->id,
                    'specialization'     => $docData['specialization'],
                    'type'               => 'consultant',
                    'consultation_fee'   => $docData['consultation_fee'],
                    'working_days'       => $docData['working_days'],
                    'is_active'          => true,
                    'is_available_today' => $docData['is_available_today'],
                    'available_date'     => now()->toDateString(),
                ]
            );
        }

        // 5. بذر أصناف المخزن الافتتاحية التخصصية للعيون
        $items = [
            // عدسات IOL
            [
                'item_code'       => 'IOL-ALC-200',
                'name'            => 'Alcon AcrySof IQ Natural Monofocal IOL',
                'category'        => 'iol_lens',
                'diopter'         => 20.00,
                'model_number'    => 'SN60WF',
                'manufacturer'    => 'Alcon / USA',
                'unit'            => 'قطعة',
                'current_stock'   => 15,
                'min_stock_alert' => 5,
                'cost_price'      => 85000,
                'selling_price'   => 140000,
            ],
            [
                'item_code'       => 'IOL-ALC-215',
                'name'            => 'Alcon AcrySof IQ Natural Monofocal IOL',
                'category'        => 'iol_lens',
                'diopter'         => 21.50,
                'model_number'    => 'SN60WF',
                'manufacturer'    => 'Alcon / USA',
                'unit'            => 'قطعة',
                'current_stock'   => 12,
                'min_stock_alert' => 5,
                'cost_price'      => 85000,
                'selling_price'   => 140000,
            ],
            [
                'item_code'       => 'IOL-ZEISS-210',
                'name'            => 'Zeiss Asphina 409M Hydrophilic IOL',
                'category'        => 'iol_lens',
                'diopter'         => 21.00,
                'model_number'    => 'Asphina 409M',
                'manufacturer'    => 'Carl Zeiss / Germany',
                'unit'            => 'قطعة',
                'current_stock'   => 10,
                'min_stock_alert' => 4,
                'cost_price'      => 95000,
                'selling_price'   => 160000,
            ],
            // إبر حقن الشبكية
            [
                'item_code'       => 'INJ-EYLEA-01',
                'name'            => 'Eylea (Aflibercept) 40mg/ml Pre-filled Syringe',
                'category'        => 'retinal_injection',
                'diopter'         => null,
                'model_number'    => '0.05ml Dose',
                'manufacturer'    => 'Bayer / Regeneron',
                'unit'            => 'سرنجة جاهزة',
                'current_stock'   => 8,
                'min_stock_alert' => 3,
                'cost_price'      => 450000,
                'selling_price'   => 550000,
            ],
            [
                'item_code'       => 'INJ-LUCENTIS-01',
                'name'            => 'Lucentis (Ranibizumab) 10mg/ml Vial',
                'category'        => 'retinal_injection',
                'diopter'         => null,
                'model_number'    => '0.05ml Dose',
                'manufacturer'    => 'Novartis',
                'unit'            => 'فيال',
                'current_stock'   => 6,
                'min_stock_alert' => 2,
                'cost_price'      => 420000,
                'selling_price'   => 500000,
            ],
            // محاليل جراحية
            [
                'item_code'       => 'SOL-BSS-500',
                'name'            => 'BSS (Balanced Salt Solution) Sterile 500ml',
                'category'        => 'viscoelastic',
                'diopter'         => null,
                'model_number'    => '500ml Bottle',
                'manufacturer'    => 'Alcon',
                'unit'            => 'عبوة',
                'current_stock'   => 25,
                'min_stock_alert' => 8,
                'cost_price'      => 15000,
                'selling_price'   => 25000,
            ],
            [
                'item_code'       => 'VISC-VISCOAT',
                'name'            => 'Viscoat OVD (Sodium Chondroitin - Sodium Hyaluronate)',
                'category'        => 'viscoelastic',
                'diopter'         => null,
                'model_number'    => '0.75ml Syringe',
                'manufacturer'    => 'Alcon',
                'unit'            => 'سرنجة',
                'current_stock'   => 20,
                'min_stock_alert' => 5,
                'cost_price'      => 45000,
                'selling_price'   => 70000,
            ],
            // شفرات وخيوط جراحية
            [
                'item_code'       => 'BLD-KERATOME-22',
                'name'            => 'Clear Cornea Slit Keratome 2.2mm Angled',
                'category'        => 'surgical_blade',
                'diopter'         => null,
                'model_number'    => '2.2mm Slit',
                'manufacturer'    => 'Mani / Japan',
                'unit'            => 'شفرة معقمة',
                'current_stock'   => 30,
                'min_stock_alert' => 10,
                'cost_price'      => 8000,
                'selling_price'   => 15000,
            ],
            [
                'item_code'       => 'SUT-NYLON-100',
                'name'            => '10-0 Black Monofilament Nylon Suture with Spatula Needle',
                'category'        => 'suture',
                'diopter'         => null,
                'model_number'    => 'W2850',
                'manufacturer'    => 'Ethicon',
                'unit'            => 'خيط مع إبرة',
                'current_stock'   => 40,
                'min_stock_alert' => 15,
                'cost_price'      => 12000,
                'selling_price'   => 20000,
            ],
        ];

        foreach ($items as $itemData) {
            $itemData['location_id'] = $eyeLocation->id;
            EyeStoreItem::firstOrCreate(
                ['item_code' => $itemData['item_code']],
                $itemData
            );
        }
    }
}
