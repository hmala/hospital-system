<?php

namespace Tests\Feature;

use App\Models\Incubator;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IncubatorSortingAndTypeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view incubator reservations', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create incubator reservations', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->room = Room::create([
            'room_number' => 'ط6',
            'room_type' => 'regular',
            'room_purpose' => 'incubators',
            'floor' => 'الطابق السادس',
            'daily_fee' => 0,
            'status' => 'available',
            'beds_count' => 16,
            'is_active' => true,
        ]);
    }

    /**
     * التحقق من أن ترتيب الحاضنات بالـ scope يتم تصاعدياً كأرقام حقيقية (1 إلى 16) وليس كأبجدية نصوص
     */
    public function test_incubator_order_by_number_scope_sorts_numerically(): void
    {
        // إنشاء حاضنات بأرقام غير مرتبة في الإدخال: 10، 2، 1، 16، 15، 3
        Incubator::create(['incubator_number' => '10', 'incubator_type' => 'normal', 'status' => 'available', 'room_id' => $this->room->id]);
        Incubator::create(['incubator_number' => '2', 'incubator_type' => 'normal', 'status' => 'available', 'room_id' => $this->room->id]);
        Incubator::create(['incubator_number' => '1', 'incubator_type' => 'normal', 'status' => 'available', 'room_id' => $this->room->id]);
        Incubator::create(['incubator_number' => '16', 'incubator_type' => 'capsule', 'status' => 'available', 'room_id' => $this->room->id]);
        Incubator::create(['incubator_number' => '15', 'incubator_type' => 'capsule', 'status' => 'available', 'room_id' => $this->room->id]);
        Incubator::create(['incubator_number' => '3', 'incubator_type' => 'normal', 'status' => 'available', 'room_id' => $this->room->id]);

        $sortedNumbers = Incubator::orderByNumber()->pluck('incubator_number')->toArray();

        // الترتيب الرقمي المتوقع: 1, 2, 3, 10, 15, 16 (وليس 1, 10, 15, 16, 2, 3)
        $this->assertEquals(['1', '2', '3', '10', '15', '16'], $sortedNumbers);
    }

    /**
     * التحقق من ترتيب الحاضنات وعرض الأنواع (حاضنة / علاج ضوئي) في شاشة NICU الرئيسية
     */
    public function test_nicu_dashboard_sorts_incubators_numerically_and_tracks_types(): void
    {
        // إنشاء 16 حاضنة تحاكي النظام الفعلي
        for ($i = 1; $i <= 14; $i++) {
            Incubator::create([
                'incubator_number' => (string) $i,
                'incubator_type' => 'normal',
                'status' => 'available',
                'room_id' => $this->room->id,
            ]);
        }
        Incubator::create([
            'incubator_number' => '15',
            'incubator_type' => 'capsule',
            'status' => 'available',
            'room_id' => $this->room->id,
        ]);
        Incubator::create([
            'incubator_number' => '16',
            'incubator_type' => 'capsule',
            'status' => 'available',
            'room_id' => $this->room->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('nicu.index'));
        $response->assertStatus(200);

        // التحقق من أن المتغير الممرض للواجهة مرتب رقمياً
        $incubators = $response->viewData('incubators');
        $this->assertEquals(
            ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16'],
            $incubators->pluck('incubator_number')->toArray()
        );

        // إحصائيات الكبسولات والعلاج الضوئي
        $stats = $response->viewData('stats');
        $this->assertEquals(14, $stats['normal']);
        $this->assertEquals(2, $stats['capsule']);
    }

    /**
     * التحقق من ترتيب الحاضنات في شاشة حجز واستقبال الخديج
     */
    public function test_incubator_reservation_create_page_sorts_numerically_and_supports_type_filtering(): void
    {
        Incubator::create(['incubator_number' => '12', 'incubator_type' => 'normal', 'status' => 'available', 'room_id' => $this->room->id]);
        Incubator::create(['incubator_number' => '1', 'incubator_type' => 'normal', 'status' => 'available', 'room_id' => $this->room->id]);
        Incubator::create(['incubator_number' => '16', 'incubator_type' => 'capsule', 'status' => 'available', 'room_id' => $this->room->id]);
        Incubator::create(['incubator_number' => '2', 'incubator_type' => 'normal', 'status' => 'available', 'room_id' => $this->room->id]);

        $response = $this->actingAs($this->admin)->get(route('incubator-reservations.create'));
        $response->assertStatus(200);

        $incubators = $response->viewData('incubators');
        $this->assertEquals(['1', '2', '12', '16'], $incubators->pluck('incubator_number')->toArray());

        // فلترة بالنوع كبسولة علاج ضوئي
        $capsuleResponse = $this->actingAs($this->admin)->get(route('incubator-reservations.create', ['incubator_type' => 'capsule']));
        $capsuleResponse->assertStatus(200);
        $capsuleIncubators = $capsuleResponse->viewData('incubators');
        $this->assertEquals(['16'], $capsuleIncubators->pluck('incubator_number')->toArray());
    }

    /**
     * التحقق من الترتيب والتبويبات في شاشة إدارة الحاضنات /incubators
     */
    public function test_incubators_index_page_sorts_numerically_and_separates_tabs(): void
    {
        Incubator::create(['incubator_number' => '11', 'incubator_type' => 'normal', 'status' => 'available', 'room_id' => $this->room->id]);
        Incubator::create(['incubator_number' => '2', 'incubator_type' => 'normal', 'status' => 'available', 'room_id' => $this->room->id]);
        Incubator::create(['incubator_number' => '15', 'incubator_type' => 'capsule', 'status' => 'available', 'room_id' => $this->room->id]);

        $response = $this->actingAs($this->admin)->get(route('incubators.index'));
        $response->assertStatus(200);

        $incubators = $response->viewData('incubators');
        $this->assertEquals(['2', '11', '15'], $incubators->pluck('incubator_number')->toArray());

        $stats = $response->viewData('stats');
        $this->assertEquals(2, $stats['normal']);
        $this->assertEquals(1, $stats['capsule']);
    }
}
