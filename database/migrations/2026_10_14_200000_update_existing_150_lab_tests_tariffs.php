<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Updates cash prices (Al-Kafaat) and health insurance tariffs (HI 2025)
     * for the existing 150 laboratory tests in-place preserving all subtests and normal ranges.
     */
    public function up(): void
    {
        $tariffs = [
            1 => ['price' => 20000.00, 'hi_price' => 8000.00, 'is_hi_active' => 1],
            2 => ['price' => 15000.00, 'hi_price' => 8000.00, 'is_hi_active' => 1],
            3 => ['price' => 50000.00, 'hi_price' => 20000.00, 'is_hi_active' => 1],
            4 => ['price' => 15000.00, 'hi_price' => 8000.00, 'is_hi_active' => 1],
            5 => ['price' => 25000.00, 'hi_price' => 8500.00, 'is_hi_active' => 1],
            6 => ['price' => 10000.00, 'hi_price' => 4000.00, 'is_hi_active' => 1],
            7 => ['price' => 10000.00, 'hi_price' => 4500.00, 'is_hi_active' => 1],
            8 => ['price' => 10000.00, 'hi_price' => 4000.00, 'is_hi_active' => 1],
            9 => ['price' => 60000.00, 'hi_price' => 0.00, 'is_hi_active' => 0],
            10 => ['price' => 20000.00, 'hi_price' => 4500.00, 'is_hi_active' => 1],
            11 => ['price' => 25000.00, 'hi_price' => 10000.00, 'is_hi_active' => 1],
            12 => ['price' => 100000.00, 'hi_price' => 10000.00, 'is_hi_active' => 1],
            13 => ['price' => 80000.00, 'hi_price' => 0.00, 'is_hi_active' => 0],
            14 => ['price' => 25000.00, 'hi_price' => 7000.00, 'is_hi_active' => 1],
            15 => ['price' => 25000.00, 'hi_price' => 7000.00, 'is_hi_active' => 1],
            16 => ['price' => 20000.00, 'hi_price' => 7000.00, 'is_hi_active' => 1],
            17 => ['price' => 20000.00, 'hi_price' => 7000.00, 'is_hi_active' => 1],
            18 => ['price' => 40000.00, 'hi_price' => 18000.00, 'is_hi_active' => 1],
            19 => ['price' => 60000.00, 'hi_price' => 24000.00, 'is_hi_active' => 1],
            20 => ['price' => 45000.00, 'hi_price' => 12000.00, 'is_hi_active' => 1],
            21 => ['price' => 30000.00, 'hi_price' => 12000.00, 'is_hi_active' => 1],
            22 => ['price' => 10000.00, 'hi_price' => 4500.00, 'is_hi_active' => 1],
            23 => ['price' => 10000.00, 'hi_price' => 4000.00, 'is_hi_active' => 1],
            24 => ['price' => 20000.00, 'hi_price' => 8000.00, 'is_hi_active' => 1],
            25 => ['price' => 10000.00, 'hi_price' => 4000.00, 'is_hi_active' => 1],
            26 => ['price' => 10000.00, 'hi_price' => 4000.00, 'is_hi_active' => 1],
            27 => ['price' => 10000.00, 'hi_price' => 4000.00, 'is_hi_active' => 1],
            28 => ['price' => 5000.00, 'hi_price' => 4000.00, 'is_hi_active' => 1],
            29 => ['price' => 35000.00, 'hi_price' => 20000.00, 'is_hi_active' => 1],
            30 => ['price' => 5000.00, 'hi_price' => 4500.00, 'is_hi_active' => 1],
            31 => ['price' => 60000.00, 'hi_price' => 0.00, 'is_hi_active' => 0],
            32 => ['price' => 10000.00, 'hi_price' => 4500.00, 'is_hi_active' => 1],
            33 => ['price' => 10000.00, 'hi_price' => 4500.00, 'is_hi_active' => 1],
            34 => ['price' => 10000.00, 'hi_price' => 4500.00, 'is_hi_active' => 1],
            35 => ['price' => 25000.00, 'hi_price' => 16000.00, 'is_hi_active' => 1],
            36 => ['price' => 25000.00, 'hi_price' => 16000.00, 'is_hi_active' => 1],
            37 => ['price' => 25000.00, 'hi_price' => 16000.00, 'is_hi_active' => 1],
            38 => ['price' => 40000.00, 'hi_price' => 20000.00, 'is_hi_active' => 1],
            39 => ['price' => 10000.00, 'hi_price' => 4500.00, 'is_hi_active' => 1],
            40 => ['price' => 45000.00, 'hi_price' => 17000.00, 'is_hi_active' => 1],
            41 => ['price' => 30000.00, 'hi_price' => 20000.00, 'is_hi_active' => 1],
            42 => ['price' => 20000.00, 'hi_price' => 7000.00, 'is_hi_active' => 1],
            43 => ['price' => 15000.00, 'hi_price' => 8500.00, 'is_hi_active' => 1],
            44 => ['price' => 15000.00, 'hi_price' => 8500.00, 'is_hi_active' => 1],
            45 => ['price' => 25000.00, 'hi_price' => 16000.00, 'is_hi_active' => 1],
            46 => ['price' => 25000.00, 'hi_price' => 12000.00, 'is_hi_active' => 1],
            47 => ['price' => 10000.00, 'hi_price' => 4500.00, 'is_hi_active' => 1],
            48 => ['price' => 10000.00, 'hi_price' => 4000.00, 'is_hi_active' => 1],
            49 => ['price' => 25000.00, 'hi_price' => 7000.00, 'is_hi_active' => 1],
            50 => ['price' => 20000.00, 'hi_price' => 8500.00, 'is_hi_active' => 1],
            51 => ['price' => 20000.00, 'hi_price' => 8500.00, 'is_hi_active' => 1],
            52 => ['price' => 25000.00, 'hi_price' => 10000.00, 'is_hi_active' => 1],
            53 => ['price' => 25000.00, 'hi_price' => 10000.00, 'is_hi_active' => 1],
            54 => ['price' => 25000.00, 'hi_price' => 12000.00, 'is_hi_active' => 1],
            55 => ['price' => 20000.00, 'hi_price' => 8500.00, 'is_hi_active' => 1],
            56 => ['price' => 30000.00, 'hi_price' => 0.00, 'is_hi_active' => 0],
            57 => ['price' => 35000.00, 'hi_price' => 20000.00, 'is_hi_active' => 1],
            58 => ['price' => 10000.00, 'hi_price' => 4500.00, 'is_hi_active' => 1],
            59 => ['price' => 20000.00, 'hi_price' => 8500.00, 'is_hi_active' => 1],
            60 => ['price' => 10000.00, 'hi_price' => 4000.00, 'is_hi_active' => 1],
            61 => ['price' => 10000.00, 'hi_price' => 4500.00, 'is_hi_active' => 1],
            62 => ['price' => 35000.00, 'hi_price' => 16000.00, 'is_hi_active' => 1],
            63 => ['price' => 30000.00, 'hi_price' => 20000.00, 'is_hi_active' => 1],
            64 => ['price' => 5000.00, 'hi_price' => 4500.00, 'is_hi_active' => 1],
            65 => ['price' => 20000.00, 'hi_price' => 8000.00, 'is_hi_active' => 1],
            66 => ['price' => 5000.00, 'hi_price' => 4000.00, 'is_hi_active' => 1],
            67 => ['price' => 15000.00, 'hi_price' => 8500.00, 'is_hi_active' => 1],
            68 => ['price' => 15000.00, 'hi_price' => 4500.00, 'is_hi_active' => 1],
            69 => ['price' => 30000.00, 'hi_price' => 20000.00, 'is_hi_active' => 1],
            70 => ['price' => 20000.00, 'hi_price' => 8000.00, 'is_hi_active' => 1],
            71 => ['price' => 20000.00, 'hi_price' => 8000.00, 'is_hi_active' => 1],
            72 => ['price' => 5000.00, 'hi_price' => 4500.00, 'is_hi_active' => 1],
            73 => ['price' => 5000.00, 'hi_price' => 4000.00, 'is_hi_active' => 1],
            74 => ['price' => 10000.00, 'hi_price' => 4000.00, 'is_hi_active' => 1],
            75 => ['price' => 15000.00, 'hi_price' => 4500.00, 'is_hi_active' => 1],
            76 => ['price' => 15000.00, 'hi_price' => 4500.00, 'is_hi_active' => 1],
            77 => ['price' => 15000.00, 'hi_price' => 9500.00, 'is_hi_active' => 1],
            78 => ['price' => 20000.00, 'hi_price' => 8000.00, 'is_hi_active' => 1],
            79 => ['price' => 20000.00, 'hi_price' => 8000.00, 'is_hi_active' => 1],
            80 => ['price' => 20000.00, 'hi_price' => 11000.00, 'is_hi_active' => 1],
            81 => ['price' => 15000.00, 'hi_price' => 6000.00, 'is_hi_active' => 1],
            82 => ['price' => 30000.00, 'hi_price' => 8500.00, 'is_hi_active' => 1],
            83 => ['price' => 15000.00, 'hi_price' => 6000.00, 'is_hi_active' => 1],
            84 => ['price' => 10000.00, 'hi_price' => 4000.00, 'is_hi_active' => 1],
            85 => ['price' => 5000.00, 'hi_price' => 4500.00, 'is_hi_active' => 1],
            86 => ['price' => 20000.00, 'hi_price' => 8500.00, 'is_hi_active' => 1],
            87 => ['price' => 20000.00, 'hi_price' => 8500.00, 'is_hi_active' => 1],
            88 => ['price' => 20000.00, 'hi_price' => 8500.00, 'is_hi_active' => 1],
            89 => ['price' => 20000.00, 'hi_price' => 8500.00, 'is_hi_active' => 1],
            90 => ['price' => 15000.00, 'hi_price' => 6000.00, 'is_hi_active' => 1],
            91 => ['price' => 50000.00, 'hi_price' => 20000.00, 'is_hi_active' => 1],
            92 => ['price' => 15000.00, 'hi_price' => 8000.00, 'is_hi_active' => 1],
            93 => ['price' => 25000.00, 'hi_price' => 16000.00, 'is_hi_active' => 1],
            94 => ['price' => 10000.00, 'hi_price' => 4500.00, 'is_hi_active' => 1],
            95 => ['price' => 10000.00, 'hi_price' => 4500.00, 'is_hi_active' => 1],
            96 => ['price' => 15000.00, 'hi_price' => 4500.00, 'is_hi_active' => 1],
            97 => ['price' => 10000.00, 'hi_price' => 4000.00, 'is_hi_active' => 1],
            98 => ['price' => 20000.00, 'hi_price' => 8000.00, 'is_hi_active' => 1],
            99 => ['price' => 20000.00, 'hi_price' => 4500.00, 'is_hi_active' => 1],
            100 => ['price' => 40000.00, 'hi_price' => 30000.00, 'is_hi_active' => 1],
            101 => ['price' => 30000.00, 'hi_price' => 16000.00, 'is_hi_active' => 1],
            102 => ['price' => 20000.00, 'hi_price' => 10000.00, 'is_hi_active' => 1],
            103 => ['price' => 35000.00, 'hi_price' => 20000.00, 'is_hi_active' => 1],
            104 => ['price' => 5000.00, 'hi_price' => 4500.00, 'is_hi_active' => 1],
            105 => ['price' => 10000.00, 'hi_price' => 4500.00, 'is_hi_active' => 1],
            106 => ['price' => 10000.00, 'hi_price' => 4500.00, 'is_hi_active' => 1],
            107 => ['price' => 5000.00, 'hi_price' => 4000.00, 'is_hi_active' => 1],
            108 => ['price' => 80000.00, 'hi_price' => 0.00, 'is_hi_active' => 0],
            109 => ['price' => 45000.00, 'hi_price' => 21000.00, 'is_hi_active' => 1],
            110 => ['price' => 20000.00, 'hi_price' => 8000.00, 'is_hi_active' => 1],
            111 => ['price' => 20000.00, 'hi_price' => 8000.00, 'is_hi_active' => 1],
            112 => ['price' => 15000.00, 'hi_price' => 8000.00, 'is_hi_active' => 1],
            113 => ['price' => 5000.00, 'hi_price' => 4000.00, 'is_hi_active' => 1],
            114 => ['price' => 10000.00, 'hi_price' => 4500.00, 'is_hi_active' => 1],
            115 => ['price' => 15000.00, 'hi_price' => 4500.00, 'is_hi_active' => 1],
            116 => ['price' => 20000.00, 'hi_price' => 8500.00, 'is_hi_active' => 1],
            117 => ['price' => 20000.00, 'hi_price' => 8500.00, 'is_hi_active' => 1],
            118 => ['price' => 15000.00, 'hi_price' => 8000.00, 'is_hi_active' => 1],
            119 => ['price' => 10000.00, 'hi_price' => 4500.00, 'is_hi_active' => 1],
            120 => ['price' => 15000.00, 'hi_price' => 4500.00, 'is_hi_active' => 1],
            121 => ['price' => 30000.00, 'hi_price' => 13000.00, 'is_hi_active' => 1],
            122 => ['price' => 15000.00, 'hi_price' => 6000.00, 'is_hi_active' => 1],
            123 => ['price' => 20000.00, 'hi_price' => 8000.00, 'is_hi_active' => 1],
            124 => ['price' => 25000.00, 'hi_price' => 10000.00, 'is_hi_active' => 1],
            125 => ['price' => 30000.00, 'hi_price' => 12000.00, 'is_hi_active' => 1],
            126 => ['price' => 15000.00, 'hi_price' => 8000.00, 'is_hi_active' => 1],
            127 => ['price' => 25000.00, 'hi_price' => 7000.00, 'is_hi_active' => 1],
            128 => ['price' => 25000.00, 'hi_price' => 7000.00, 'is_hi_active' => 1],
            129 => ['price' => 25000.00, 'hi_price' => 15000.00, 'is_hi_active' => 1],
            130 => ['price' => 15000.00, 'hi_price' => 4500.00, 'is_hi_active' => 1],
            131 => ['price' => 10000.00, 'hi_price' => 4000.00, 'is_hi_active' => 1],
            132 => ['price' => 20000.00, 'hi_price' => 8500.00, 'is_hi_active' => 1],
            133 => ['price' => 20000.00, 'hi_price' => 8500.00, 'is_hi_active' => 1],
            134 => ['price' => 20000.00, 'hi_price' => 8500.00, 'is_hi_active' => 1],
            135 => ['price' => 25000.00, 'hi_price' => 20000.00, 'is_hi_active' => 1],
            136 => ['price' => 10000.00, 'hi_price' => 4000.00, 'is_hi_active' => 1],
            137 => ['price' => 15000.00, 'hi_price' => 8000.00, 'is_hi_active' => 1],
            138 => ['price' => 15000.00, 'hi_price' => 8000.00, 'is_hi_active' => 1],
            139 => ['price' => 15000.00, 'hi_price' => 8000.00, 'is_hi_active' => 1],
            140 => ['price' => 15000.00, 'hi_price' => 8000.00, 'is_hi_active' => 1],
            141 => ['price' => 10000.00, 'hi_price' => 4000.00, 'is_hi_active' => 1],
            142 => ['price' => 45000.00, 'hi_price' => 0.00, 'is_hi_active' => 0],
            143 => ['price' => 10000.00, 'hi_price' => 4000.00, 'is_hi_active' => 1],
            144 => ['price' => 25000.00, 'hi_price' => 13000.00, 'is_hi_active' => 1],
            145 => ['price' => 50000.00, 'hi_price' => 0.00, 'is_hi_active' => 0],
            146 => ['price' => 25000.00, 'hi_price' => 12000.00, 'is_hi_active' => 1],
            147 => ['price' => 30000.00, 'hi_price' => 12000.00, 'is_hi_active' => 1],
            148 => ['price' => 10000.00, 'hi_price' => 4000.00, 'is_hi_active' => 1],
            149 => ['price' => 35000.00, 'hi_price' => 12000.00, 'is_hi_active' => 1],
            152 => ['price' => 15000.00, 'hi_price' => 8000.00, 'is_hi_active' => 1],
        ];

        foreach ($tariffs as $id => $data) {
            DB::table('lab_tests')->where('id', $id)->update([
                'price' => $data['price'],
                'hi_price' => $data['hi_price'],
                'is_hi_active' => $data['is_hi_active'],
                'is_moi_active' => 0,
                'moi_price' => 0.00,
                'updated_at' => now(),
            ]);
        }

        // Also ensure any non-HI radiology types have is_moi_active = 0 as cash only
        if (\Illuminate\Support\Facades\Schema::hasTable('radiology_types')) {
            DB::table('radiology_types')
                ->where('is_hi_active', 0)
                ->update([
                    'is_moi_active' => 0,
                    'moi_price' => 0.00,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // In-place tariff updates do not require reversal
    }
};
