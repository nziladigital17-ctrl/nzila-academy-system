<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 — Academic Core Schema
 *
 * Drops and recreates: assessments, grades, attendance_records, attendance_items
 * with correct pedagogical schema (AC/PP/PT types, grade books, sessions, etc.)
 *
 * New tables:
 *   - assessment_settings       (pedagogical config per school/year)
 *   - attendance_policy_settings (attendance policy per school/year)
 *   - grade_books               (grade sheet with workflow states)
 *   - term_results              (calculated MAC/NF per student/subject/term)
 *   - annual_results            (calculated MFA per student/subject/year)
 *   - attendance_sessions       (class session for attendance)
 *   - academic_alerts           (academic & attendance alerts)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Drop old tables (wrong schema from Phase 1) ──────────────────────
        Schema::dropIfExists('attendance_items');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('grades');
        Schema::dropIfExists('assessments');

        // ── 1. Assessment Settings ────────────────────────────────────────────
        Schema::create('assessment_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();

            // Grade scale
            $table->decimal('min_grade', 5, 2)->default(0.00);
            $table->decimal('max_grade', 5, 2)->default(20.00);
            $table->decimal('passing_grade', 5, 2)->default(10.00);

            // Number of terms (fixed at 3 for Angola)
            $table->tinyInteger('num_terms')->default(3);

            // Active assessment components (JSON array: ["AC","PP","PT"])
            $table->json('active_components')->nullable();

            // NF formula stored as JSON-encoded expression
            // Default: (MAC + PP + PT) / 3
            $table->string('nf_formula')->default('(MAC + PP + PT) / 3');

            // MFA formula
            // Default: (NF1 + NF2 + NF3) / 3
            $table->string('mfa_formula')->default('(NF1 + NF2 + NF3) / 3');

            // Rounding mode: half_up (PHP_ROUND_HALF_UP)
            $table->string('rounding_mode')->default('half_up');

            // Whether PP component is active (internal practice, not mandatory by law)
            $table->boolean('pp_active')->default(true);

            $table->timestamps();

            $table->unique(['school_id', 'academic_year_id']);
        });

        // ── 2. Attendance Policy Settings ─────────────────────────────────────
        Schema::create('attendance_policy_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();

            // angola_por_disciplina | escola_propria
            $table->string('mode')->default('angola_por_disciplina');

            // Angola mode: limits per weekly_periods
            // Stored as JSON: {"1": 3, "2": 4, "3+": 5}
            $table->json('angola_limits')->nullable();

            // Custom mode settings (JSON)
            // e.g. {"global_limit": 15, "count_justified": false, "scope": "per_subject"}
            $table->json('custom_settings')->nullable();

            // Alert threshold as fraction (e.g. 0.80 = 80% of limit)
            $table->decimal('alert_threshold', 3, 2)->default(0.80);

            $table->timestamps();

            $table->unique(['school_id', 'academic_year_id']);
        });

        // ── 3. Assessments (recreated) ────────────────────────────────────────
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_assignment_id')->nullable()->constrained()->nullOnDelete();

            // AC | PP | PT
            $table->string('type', 10);

            // Optional label (e.g. "AC 1", "AC 2")
            $table->string('label')->nullable();

            $table->date('date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['school_id', 'term_id', 'class_id', 'subject_id', 'type']);
        });

        // ── 4. Grade Books ────────────────────────────────────────────────────
        Schema::create('grade_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_assignment_id')->nullable()->constrained()->nullOnDelete();

            // draft | submitted | published | locked
            $table->string('status')->default('draft');

            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();

            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();

            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();

            $table->foreignId('unlocked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('unlocked_at')->nullable();
            $table->text('unlock_reason')->nullable();

            $table->timestamps();

            $table->unique(
                ['school_id', 'academic_year_id', 'term_id', 'class_id', 'subject_id'],
                'grade_books_unique'
            );
        });

        // ── 5. Grades (recreated) ─────────────────────────────────────────────
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grade_book_id')->constrained()->cascadeOnDelete();

            // Raw score stored with decimals; rounding applied only at display/publish time
            $table->decimal('score', 5, 2);

            $table->text('remarks')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();

            // Annulment
            $table->boolean('is_annulled')->default(false);
            $table->text('annulled_reason')->nullable();
            $table->foreignId('annulled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('annulled_at')->nullable();

            $table->timestamps();

            // Only one active grade per assessment per student
            $table->unique(['assessment_id', 'enrollment_id']);
        });

        // ── 6. Term Results ───────────────────────────────────────────────────
        Schema::create('term_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grade_book_id')->nullable()->constrained()->nullOnDelete();

            // Calculated values (raw, with decimals)
            $table->decimal('mac', 5, 2)->nullable();  // Average of AC
            $table->decimal('pp', 5, 2)->nullable();   // Single PP score
            $table->decimal('pt', 5, 2)->nullable();   // Single PT score
            $table->decimal('nf', 5, 2)->nullable();   // Final term grade (NF)

            // Attendance counts
            $table->integer('presences')->default(0);
            $table->integer('absences_justified')->default(0);
            $table->integer('absences_unjustified')->default(0);
            $table->decimal('attendance_percentage', 5, 2)->nullable();

            // sem_notas | em_risco_academico | aprovado | reprovado_por_nota | em_risco_por_faltas | retido_por_faltas
            $table->string('situation')->default('sem_notas');

            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['enrollment_id', 'subject_id', 'term_id'],
                'term_results_unique'
            );
            $table->index(['school_id', 'term_id', 'subject_id']);
        });

        // ── 7. Annual Results ─────────────────────────────────────────────────
        Schema::create('annual_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();

            // Raw MFA with decimals
            $table->decimal('mfa', 5, 2)->nullable();

            // Attendance totals for the year
            $table->integer('total_presences')->default(0);
            $table->integer('total_absences_justified')->default(0);
            $table->integer('total_absences_unjustified')->default(0);

            // aprovado | reprovado_por_nota | retido_por_faltas
            $table->string('situation')->default('sem_notas');

            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['enrollment_id', 'subject_id', 'academic_year_id'],
                'annual_results_unique'
            );
            $table->index(['school_id', 'academic_year_id', 'subject_id']);
        });

        // ── 8. Attendance Sessions ────────────────────────────────────────────
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_assignment_id')->nullable()->constrained()->nullOnDelete();

            $table->date('date');

            // Weekly periods for this subject (used for Angola attendance policy)
            $table->tinyInteger('weekly_periods')->default(1);

            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // One session per class/subject/date
            $table->unique(['class_id', 'subject_id', 'date'], 'attendance_sessions_unique');
            $table->index(['school_id', 'term_id', 'class_id', 'subject_id', 'date'], 'att_session_lookup_idx');
        });

        // ── 9. Attendance Records (per student per session) ───────────────────
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();

            // P = Presente, F = Falta não justificada, J = Falta justificada
            $table->string('status', 1)->default('P');

            $table->text('justification')->nullable();
            $table->timestamps();

            $table->unique(['attendance_session_id', 'enrollment_id']);
        });

        // ── 10. Academic Alerts ───────────────────────────────────────────────
        Schema::create('academic_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('term_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();

            // academic_risk | attendance_risk
            $table->string('type');

            // Additional context (JSON)
            $table->json('context')->nullable();

            // Whether coordinator has acknowledged this alert
            $table->boolean('is_acknowledged')->default(false);
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();

            $table->timestamps();

            $table->index(['school_id', 'enrollment_id', 'term_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_alerts');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('attendance_sessions');
        Schema::dropIfExists('annual_results');
        Schema::dropIfExists('term_results');
        Schema::dropIfExists('grades');
        Schema::dropIfExists('grade_books');
        Schema::dropIfExists('assessments');
        Schema::dropIfExists('attendance_policy_settings');
        Schema::dropIfExists('assessment_settings');
    }
};
