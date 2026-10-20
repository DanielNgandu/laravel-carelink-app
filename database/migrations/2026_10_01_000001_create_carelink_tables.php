<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
    Schema::create('facilities', function (Blueprint $t) { $t->string('id')->primary(); $t->string('name'); $t->string('district'); });
    Schema::create('patients', function (Blueprint $t) {
        $t->id(); $t->string('facility_id'); $t->string('patient_number'); $t->string('first_name'); $t->string('last_name');
        $t->date('date_of_birth'); $t->string('sex'); $t->string('phone_number')->nullable(); $t->timestamp('created_at')->useCurrent();
        $t->foreign('facility_id')->references('id')->on('facilities'); $t->unique(['facility_id','patient_number']);
        $t->index(['facility_id','id']);
    });
    Schema::create('visits', function (Blueprint $t) {
        $t->id(); $t->foreignId('patient_id')->constrained('patients'); $t->date('visit_date');
        $t->date('next_appointment_date')->nullable(); $t->string('visit_type'); $t->timestamp('created_at')->useCurrent();
        $t->index(['patient_id','visit_date','id']); $t->index('next_appointment_date');
    });
    Schema::create('contact_attempts', function (Blueprint $t) {
        $t->id(); $t->uuid('interaction_id')->unique(); $t->foreignId('patient_id')->constrained('patients');
        $t->dateTime('contacted_at'); $t->text('note'); $t->timestamp('created_at')->useCurrent();
        $t->index(['patient_id','contacted_at']);
    });
 }
 public function down(): void { Schema::dropIfExists('contact_attempts'); Schema::dropIfExists('visits'); Schema::dropIfExists('patients'); Schema::dropIfExists('facilities'); }
};
