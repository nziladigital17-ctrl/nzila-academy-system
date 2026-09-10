<?php

namespace App\Enums;

enum PermissionEnum: string
{
    // Schools
    case SCHOOLS_VIEW = 'schools.view';
    case SCHOOLS_CREATE = 'schools.create';
    case SCHOOLS_UPDATE = 'schools.update';
    case SCHOOLS_DELETE = 'schools.delete';

    // Users
    case USERS_VIEW = 'users.view';
    case USERS_CREATE = 'users.create';
    case USERS_UPDATE = 'users.update';
    case USERS_DELETE = 'users.delete';
    case USERS_ASSIGN_ROLES = 'users.assign_roles';

    // Students
    case STUDENTS_VIEW = 'students.view';
    case STUDENTS_CREATE = 'students.create';
    case STUDENTS_UPDATE = 'students.update';
    case STUDENTS_DELETE = 'students.delete';

    // Guardians
    case GUARDIANS_VIEW = 'guardians.view';
    case GUARDIANS_CREATE = 'guardians.create';
    case GUARDIANS_UPDATE = 'guardians.update';
    case GUARDIANS_DELETE = 'guardians.delete';

    // Teachers
    case TEACHERS_VIEW = 'teachers.view';
    case TEACHERS_CREATE = 'teachers.create';
    case TEACHERS_UPDATE = 'teachers.update';
    case TEACHERS_DELETE = 'teachers.delete';

    // Classes
    case CLASSES_VIEW = 'classes.view';
    case CLASSES_CREATE = 'classes.create';
    case CLASSES_UPDATE = 'classes.update';
    case CLASSES_DELETE = 'classes.delete';
    case CLASSES_ENROLL = 'classes.enroll';
    case CLASSES_ASSIGN_TEACHER = 'classes.assign_teacher';

    // Subjects
    case SUBJECTS_VIEW = 'subjects.view';
    case SUBJECTS_CREATE = 'subjects.create';
    case SUBJECTS_UPDATE = 'subjects.update';
    case SUBJECTS_DELETE = 'subjects.delete';

    // Rooms
    case ROOMS_VIEW = 'rooms.view';
    case ROOMS_CREATE = 'rooms.create';
    case ROOMS_UPDATE = 'rooms.update';
    case ROOMS_DELETE = 'rooms.delete';

    // Academic Years
    case ACADEMIC_YEARS_VIEW = 'academic_years.view';
    case ACADEMIC_YEARS_CREATE = 'academic_years.create';
    case ACADEMIC_YEARS_UPDATE = 'academic_years.update';
    case ACADEMIC_YEARS_DELETE = 'academic_years.delete';

    // Terms
    case TERMS_VIEW = 'terms.view';
    case TERMS_CREATE = 'terms.create';
    case TERMS_UPDATE = 'terms.update';
    case TERMS_DELETE = 'terms.delete';

    // Enrollments
    case ENROLLMENTS_VIEW = 'enrollments.view';
    case ENROLLMENTS_CREATE = 'enrollments.create';
    case ENROLLMENTS_UPDATE = 'enrollments.update';
    case ENROLLMENTS_DELETE = 'enrollments.delete';

    // Teaching Assignments
    case TEACHING_ASSIGNMENTS_VIEW = 'teaching_assignments.view';
    case TEACHING_ASSIGNMENTS_CREATE = 'teaching_assignments.create';
    case TEACHING_ASSIGNMENTS_UPDATE = 'teaching_assignments.update';
    case TEACHING_ASSIGNMENTS_DELETE = 'teaching_assignments.delete';

    // Grades
    case GRADES_VIEW = 'grades.view';
    case GRADES_CREATE = 'grades.create';
    case GRADES_UPDATE = 'grades.update';

    // Attendance
    case ATTENDANCE_VIEW = 'attendance.view';
    case ATTENDANCE_CREATE = 'attendance.create';
    case ATTENDANCE_UPDATE = 'attendance.update';

    // Finance
    case FINANCE_VIEW = 'finance.view';
    case FINANCE_CREATE = 'finance.create';
    case FINANCE_UPDATE = 'finance.update';
    case FINANCE_APPROVE = 'finance.approve';
    case FINANCE_DELETE = 'finance.delete';

    // Messages
    case MESSAGES_SEND = 'messages.send';
    case MESSAGES_VIEW = 'messages.view';

    // Audit
    case AUDIT_VIEW = 'audit.view';

    // Reports
    case REPORTS_VIEW = 'reports.view';
    case REPORTS_EXPORT = 'reports.export';

    /**
     * Get the permission group name.
     */
    public function group(): string
    {
        return explode('.', $this->value)[0];
    }

    /**
     * Get human-readable display name.
     */
    public function displayName(): string
    {
        $parts = explode('.', $this->value);
        $group = ucfirst(str_replace('_', ' ', $parts[0]));
        $action = ucfirst($parts[1]);

        return "{$group} - {$action}";
    }

    /**
     * Get all permission values as array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get permissions grouped by their group name.
     */
    public static function grouped(): array
    {
        $grouped = [];
        foreach (self::cases() as $permission) {
            $grouped[$permission->group()][] = $permission;
        }
        return $grouped;
    }

    /**
     * Get permissions for a given role.
     */
    public static function forRole(RoleEnum $role): array
    {
        return match ($role) {
            RoleEnum::SUPER_ADMIN => self::cases(),
            RoleEnum::SCHOOL_ADMIN => array_filter(self::cases(), fn($p) => !in_array($p, [
                self::SCHOOLS_CREATE,
                self::SCHOOLS_DELETE,
            ])),
            RoleEnum::DIRECTOR => array_filter(self::cases(), fn($p) => in_array($p->group(), [
                'users', 'students', 'guardians', 'teachers', 'classes',
                'subjects', 'rooms', 'academic_years', 'terms', 'grades',
                'attendance', 'finance', 'messages', 'reports',
                'enrollments', 'teaching_assignments',
            ]) || $p === self::SCHOOLS_VIEW || $p === self::AUDIT_VIEW),
            RoleEnum::PEDAGOGIC_COORDINATOR => array_filter(self::cases(), fn($p) => in_array($p->group(), [
                'students', 'guardians', 'teachers', 'classes',
                'subjects', 'rooms', 'academic_years', 'terms', 'grades',
                'attendance', 'messages', 'reports',
                'enrollments', 'teaching_assignments',
            ])),
            RoleEnum::FINANCIAL => array_filter(self::cases(), fn($p) => in_array($p->group(), [
                'finance', 'reports',
            ]) || $p === self::STUDENTS_VIEW || $p === self::MESSAGES_SEND || $p === self::MESSAGES_VIEW),
            RoleEnum::SECRETARY => array_filter(self::cases(), fn($p) => in_array($p->group(), [
                'students', 'guardians', 'messages', 'enrollments',
            ]) || in_array($p, [
                self::USERS_VIEW, self::CLASSES_VIEW, self::CLASSES_ENROLL,
                self::ACADEMIC_YEARS_VIEW, self::TERMS_VIEW, self::ROOMS_VIEW,
                self::TEACHING_ASSIGNMENTS_VIEW,
            ])),
            RoleEnum::TEACHER => [
                self::CLASSES_VIEW, self::STUDENTS_VIEW,
                self::GRADES_VIEW, self::GRADES_CREATE, self::GRADES_UPDATE,
                self::ATTENDANCE_VIEW, self::ATTENDANCE_CREATE, self::ATTENDANCE_UPDATE,
                self::MESSAGES_SEND, self::MESSAGES_VIEW,
                self::SUBJECTS_VIEW, self::ACADEMIC_YEARS_VIEW, self::TERMS_VIEW,
                self::TEACHING_ASSIGNMENTS_VIEW, self::ENROLLMENTS_VIEW,
            ],
            RoleEnum::STUDENT => [
                self::GRADES_VIEW, self::ATTENDANCE_VIEW,
                self::MESSAGES_VIEW, self::MESSAGES_SEND,
            ],
            RoleEnum::GUARDIAN => [
                self::GRADES_VIEW, self::ATTENDANCE_VIEW,
                self::FINANCE_VIEW, self::MESSAGES_VIEW, self::MESSAGES_SEND,
            ],
        };
    }
}
