<?php

namespace Tests\Feature;

use App\Models\LabTest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LabTestResultEntryTypeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // ضمان وجود دور الأدمن
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    public function test_guest_is_redirected_from_entry_settings()
    {
        $response = $this->get(route('lab-tests.entry-settings'));
        $response->assertRedirect('/login');
    }

    public function test_non_admin_cannot_access_entry_settings()
    {
        $regularUser = User::factory()->create();

        $response = $this->actingAs($regularUser)->get(route('lab-tests.entry-settings'));
        $response->assertStatus(403);
    }

    public function test_admin_can_view_entry_settings_page()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('lab-tests.entry-settings'));
        $response->assertStatus(200);
        $response->assertViewIs('lab-tests.entry_settings');
        $response->assertSee('إعدادات أنماط إدخال نتائج الفحوصات المختبرية');
        $response->assertSee('قيمة مباشرة');
        $response->assertSee('قيم فرعية');
        $response->assertSee('مرفق جهاز');
        $response->assertSee('كلاهما (مرن)');
    }

    public function test_admin_can_update_single_test_entry_type_via_ajax()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $test = LabTest::firstOrCreate(
            ['name' => 'Test Entry Mode Check'],
            [
                'code' => 'TEM01',
                'subcategory' => 'Biochemistry',
                'price' => 15000,
                'result_entry_type' => LabTest::ENTRY_TYPE_SINGLE,
                'is_active' => true,
            ]
        );

        $response = $this->actingAs($admin)
            ->postJson(route('lab-tests.entry-settings.update'), [
                'lab_test_id' => $test->id,
                'result_entry_type' => LabTest::ENTRY_TYPE_FLEXIBLE,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'entry_type' => LabTest::ENTRY_TYPE_FLEXIBLE,
            'label' => 'كلاهما (مرن)',
        ]);

        $test->refresh();
        $this->assertEquals(LabTest::ENTRY_TYPE_FLEXIBLE, $test->result_entry_type);
        $this->assertTrue($test->isFlexible());
    }

    public function test_admin_can_bulk_update_tests_entry_type()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $test1 = LabTest::firstOrCreate(['name' => 'Bulk Test 1'], ['code' => 'BT01', 'price' => 10000, 'subcategory' => 'Hematology']);
        $test2 = LabTest::firstOrCreate(['name' => 'Bulk Test 2'], ['code' => 'BT02', 'price' => 12000, 'subcategory' => 'Hematology']);

        $response = $this->actingAs($admin)
            ->postJson(route('lab-tests.entry-settings.bulk-update'), [
                'test_ids' => [$test1->id, $test2->id],
                'result_entry_type' => LabTest::ENTRY_TYPE_SUBTESTS,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'updated_count' => 2,
            'entry_type' => LabTest::ENTRY_TYPE_SUBTESTS,
        ]);

        $test1->refresh();
        $test2->refresh();
        $this->assertEquals(LabTest::ENTRY_TYPE_SUBTESTS, $test1->result_entry_type);
        $this->assertEquals(LabTest::ENTRY_TYPE_SUBTESTS, $test2->result_entry_type);
        $this->assertTrue($test1->isSubTests());
        $this->assertTrue($test2->isSubTests());
    }

    public function test_lab_test_model_helper_methods_and_labels()
    {
        $test = new LabTest();

        $test->result_entry_type = LabTest::ENTRY_TYPE_SINGLE;
        $this->assertTrue($test->isSingleValue());
        $this->assertEquals('قيمة مباشرة مفردة', $test->result_entry_type_label);

        $test->result_entry_type = LabTest::ENTRY_TYPE_SUBTESTS;
        $this->assertTrue($test->isSubTests());
        $this->assertEquals('قيم فرعية مفصلة', $test->result_entry_type_label);

        $test->result_entry_type = LabTest::ENTRY_TYPE_ATTACHMENT;
        $this->assertTrue($test->isAttachmentOnly());
        $this->assertEquals('مرفق تقرير الجهاز', $test->result_entry_type_label);

        $test->result_entry_type = LabTest::ENTRY_TYPE_FLEXIBLE;
        $this->assertTrue($test->isFlexible());
        $this->assertEquals('كلاهما (مرن)', $test->result_entry_type_label);
    }
}
