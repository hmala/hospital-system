<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('nicu_milk_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable(); // طبيعي, صناعي للخدج, علاجي/تحسس, تغذية وريدية
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // بذر أنواع الحليب والتغذية الأساسية لقسم الخدج
        $defaults = [
            ['name' => 'حليب أم طبيعي (EBM)', 'category' => 'طبيعي', 'notes' => 'حليب الأم المعصور والمحفوظ سريرياً', 'is_active' => true, 'sort_order' => 1],
            ['name' => 'حليب خدج صناعي (Pre-term Formula)', 'category' => 'صناعي للخدج', 'notes' => 'تركيبة خاصة لحديثي الولادة والخدج منخفضي الوزن', 'is_active' => true, 'sort_order' => 2],
            ['name' => 'حليب سيميلاك نيوشور (Neosure)', 'category' => 'صناعي للخدج', 'notes' => 'تركيبة غنية بالسُعرات والمعادن لدعم النمو السريع', 'is_active' => true, 'sort_order' => 3],
            ['name' => 'حليب بري-نان للخدج (Pre-NAN)', 'category' => 'صناعي للخدج', 'notes' => 'حليب مخصص للأطفال ناقصي وزن الولادة', 'is_active' => true, 'sort_order' => 4],
            ['name' => 'حليب ببتي جونيور قليل التحسس', 'category' => 'علاجي / تحسس', 'notes' => 'تركيبة متحللة جزئياً لحالات اضطراب الامتصاص والتحسس', 'is_active' => true, 'sort_order' => 5],
            ['name' => 'حليب أبتاميل كمفورت (Aptamil Comfort)', 'category' => 'علاجي / تحسس', 'notes' => 'تركيبة سهلة الهضم للمغص والإمساك عند الخدج', 'is_active' => true, 'sort_order' => 6],
            ['name' => 'تغذية وريدية كاملة (TPN)', 'category' => 'تغذية وريدية', 'notes' => 'محاليل وريدية مركزة عبر القسطرة الوريدية المركزية أو الطرفية', 'is_active' => true, 'sort_order' => 7],
        ];

        $now = now();
        foreach ($defaults as $item) {
            DB::table('nicu_milk_types')->insert(array_merge($item, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nicu_milk_types');
    }
};
