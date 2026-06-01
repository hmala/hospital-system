<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('resident_stations', function (Blueprint $table) {
            $table->text('chief_complaint')->nullable()->after('phase');
            $table->text('history_present_illness')->nullable()->after('chief_complaint');
            $table->text('past_medical_hx')->nullable()->after('history_present_illness');
            $table->text('past_surgical_hx')->nullable()->after('past_medical_hx');
            $table->text('drug_hx')->nullable()->after('past_surgical_hx');
            $table->text('drug_allergy')->nullable()->after('drug_hx');
            $table->text('clinical_examination')->nullable()->after('drug_allergy');
            $table->string('bp')->nullable()->after('clinical_examination');
            $table->string('temp')->nullable()->after('bp');
            $table->string('pr')->nullable()->after('temp');
            $table->string('rr')->nullable()->after('pr');
            $table->string('spo2')->nullable()->after('rr');
            $table->text('review_of_other_systems')->nullable()->after('spo2');
            $table->text('laboratory_investigation')->nullable()->after('review_of_other_systems');
            $table->text('imaging_tests')->nullable()->after('laboratory_investigation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resident_stations', function (Blueprint $table) {
            $table->dropColumn([
                'chief_complaint',
                'history_present_illness',
                'past_medical_hx',
                'past_surgical_hx',
                'drug_hx',
                'drug_allergy',
                'clinical_examination',
                'bp',
                'temp',
                'pr',
                'rr',
                'spo2',
                'review_of_other_systems',
                'laboratory_investigation',
                'imaging_tests',
            ]);
        });
    }
};
