<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Mirrors app/Config/AuthGroups.php from the CI4 app: same 5 groups, same
 * permission matrix (superadmin's wildcard groups are expanded here since
 * Spatie permissions are flat, not wildcard-based). `audit.view` is new —
 * the old app's audit trail (Admin\Dashboard::auditLog()) had no permission
 * gate and no group ever needed one; granted here to the same roles that
 * already see attendance reports (admin, kepsek), per migration plan §5.2.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'dashboard.view-admin',
            'admin.access',
            'students.manage',
            'teachers.manage',
            'classes.manage',
            'attendance.edit',
            'attendance.view',
            'qr.generate',
            'petugas.manage',
            'settings.manage',
            'backup.manage',
            'teacher.access',
            'audit.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $matrix = [
            'superadmin' => $permissions,
            'admin' => [
                'dashboard.view-admin',
                'admin.access',
                'attendance.edit',
                'attendance.view',
                'qr.generate',
                'audit.view',
            ],
            'kepsek' => [
                'dashboard.view-admin',
                'admin.access',
                'attendance.view',
                'audit.view',
            ],
            'scanner' => [
                'admin.access',
                'attendance.view',
            ],
            'guru' => [
                'teacher.access',
                'attendance.edit',
                'attendance.view',
            ],
        ];

        foreach ($matrix as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePermissions);
        }
    }
}
