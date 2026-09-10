<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 — Schema adjustments for multi-school isolation and business rules.
 *
 * Changes:
 * 1. Add school_id FK to enrollments for BelongsToSchool global scope.
 * 2. Replace global unique on teachers.employee_number with per-school unique.
 * 3. Replace global unique on students.student_number with per-school unique.
 * 4. Add relationship column to student_guardians pivot.
 * 5. Add soft deletes to subjects and rooms.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Enrollments — add school_id
        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreignId('school_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();
        });

        // 2. Teachers — per-school unique employee_number
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropUnique(['employee_number']);
            $table->unique(['school_id', 'employee_number'], 'teachers_school_employee_unique');
        });

        // 3. Students — per-school unique student_number
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['student_number']);
            $table->unique(['school_id', 'student_number'], 'students_school_number_unique');
        });

        // 4. Student-guardians pivot — add relationship type
        Schema::table('student_guardians', function (Blueprint $table) {
            $table->string('relationship')->default('outro')->after('guardian_id');
        });

        // 5. Subjects — add soft deletes
        Schema::table('subjects', function (Blueprint $table) {
            $table->softDeletes();
        });

        // 6. Rooms — add soft deletes
        Schema::table('rooms', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('student_guardians', function (Blueprint $table) {
            $table->dropColumn('relationship');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique('students_school_number_unique');
            $table->unique('student_number');
        });

        Schema::table('teachers', function (Blueprint $table) {
            $table->dropUnique('teachers_school_employee_unique');
            $table->unique('employee_number');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->dropColumn('school_id');
        });
    }
};
