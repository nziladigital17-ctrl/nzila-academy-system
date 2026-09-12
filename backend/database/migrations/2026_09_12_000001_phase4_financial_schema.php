<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tuition_plans', function (Blueprint $table) {
            $table->string('code')->after('academic_year_id');
            $table->foreignId('class_id')->nullable()->after('grade_level')->constrained('classes')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->after('class_id')->constrained('students')->nullOnDelete();
            $table->string('periodicity')->default('monthly')->after('installments'); // enrollment, monthly, quarterly, annual, one_time
            $table->unsignedTinyInteger('due_day')->default(10)->after('periodicity');
            $table->boolean('is_active')->default(true)->after('due_day');
            
            $table->unique(['school_id', 'code']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('status');
            $table->dateTime('voided_at')->nullable()->after('notes');
            $table->foreignId('voided_by')->nullable()->after('voided_at')->constrained('users')->nullOnDelete();
            $table->string('void_reason')->nullable()->after('voided_by');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('pending','paid','partial','cancelled','overdue','voided') DEFAULT 'pending'");
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('school_id')->after('id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('confirmed')->after('paid_at'); // pending, confirmed, voided, refunded
            $table->dateTime('confirmed_at')->nullable()->after('status');
            $table->foreignId('confirmed_by')->nullable()->after('confirmed_at')->constrained('users')->nullOnDelete();
            $table->dateTime('voided_at')->nullable()->after('confirmed_by');
            $table->foreignId('voided_by')->nullable()->after('voided_at')->constrained('users')->nullOnDelete();
            $table->string('void_reason')->nullable()->after('voided_by');
        });

        Schema::table('receipts', function (Blueprint $table) {
            $table->string('status')->default('active')->after('issued_at'); // active, voided
            $table->dateTime('voided_at')->nullable()->after('status');
            $table->foreignId('voided_by')->nullable()->after('voided_at')->constrained('users')->nullOnDelete();
            $table->string('void_reason')->nullable()->after('voided_by');
        });

        // Create expense_categories BEFORE altering expenses (FK dependency)
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['school_id', 'name']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('expense_category_id')->nullable()->after('school_id')->constrained()->nullOnDelete();
            $table->string('status')->default('draft')->after('amount'); // draft, confirmed, voided
            $table->dateTime('confirmed_at')->nullable()->after('date');
            $table->foreignId('confirmed_by')->nullable()->after('confirmed_at')->constrained('users')->nullOnDelete();
            $table->dateTime('voided_at')->nullable()->after('confirmed_by');
            $table->foreignId('voided_by')->nullable()->after('voided_at')->constrained('users')->nullOnDelete();
            $table->string('void_reason')->nullable()->after('voided_by');
        });

        Schema::create('student_fee_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tuition_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_fixed', 15, 2)->default(0);
            $table->string('scholarship_type')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['student_id', 'tuition_plan_id', 'academic_year_id'], 'sfa_student_plan_year_unique');
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->timestamps();
        });

        Schema::create('financial_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // discount, scholarship, exemption, penalty, correction, refund
            $table->decimal('amount', 15, 2);
            $table->string('description');
            $table->foreignId('applied_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('financial_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('setting_key');
            $table->text('setting_value')->nullable();
            $table->timestamps();
            
            $table->unique(['school_id', 'academic_year_id', 'setting_key'], 'fs_school_year_key_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_settings');
        Schema::dropIfExists('financial_adjustments');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('student_fee_assignments');

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['expense_category_id']);
            $table->dropForeign(['confirmed_by']);
            $table->dropForeign(['voided_by']);
            $table->dropColumn([
                'expense_category_id', 'status', 'confirmed_at', 'confirmed_by', 'voided_at', 'voided_by', 'void_reason'
            ]);
        });

        Schema::table('receipts', function (Blueprint $table) {
            $table->dropForeign(['voided_by']);
            $table->dropColumn([
                'status', 'voided_at', 'voided_by', 'void_reason'
            ]);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->dropForeign(['confirmed_by']);
            $table->dropForeign(['voided_by']);
            $table->dropColumn([
                'school_id', 'status', 'confirmed_at', 'confirmed_by', 'voided_at', 'voided_by', 'void_reason'
            ]);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('pending','paid','partial','cancelled','overdue') DEFAULT 'pending'");
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['voided_by']);
            $table->dropColumn([
                'notes', 'voided_at', 'voided_by', 'void_reason'
            ]);
        });

        Schema::table('tuition_plans', function (Blueprint $table) {
            $table->dropUnique(['school_id', 'code']);
            $table->dropForeign(['class_id']);
            $table->dropForeign(['student_id']);
            $table->dropColumn([
                'code', 'class_id', 'student_id', 'periodicity', 'due_day', 'is_active'
            ]);
        });
    }
};
