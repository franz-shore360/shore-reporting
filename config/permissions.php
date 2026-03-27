<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Available permissions (grouped for UI)
    |--------------------------------------------------------------------------
    | Each group has a display name and an array of permissions.
    | Permission key is the Spatie permission name; label is shown in forms.
    */
    'user' => [
        'name' => 'User',
        'permissions' => [
            'user-list' => ['label' => 'View User List'],
            'user-create' => ['label' => 'Create User'],
            'user-edit' => ['label' => 'Edit User'],
            'user-delete' => ['label' => 'Delete User'],
        ],
    ],
    'department' => [
        'name' => 'Department',
        'permissions' => [
            'department-list' => ['label' => 'View Department List'],
            'department-create' => ['label' => 'Create Department'],
            'department-edit' => ['label' => 'Edit Department'],
            'department-delete' => ['label' => 'Delete Department'],
        ],
    ],
    'logs' => [
        'name' => 'Logs',
        'permissions' => [
            'email-log-list' => ['label' => 'View Email Logs'],
            'email-log-delete' => ['label' => 'Delete Email Logs'],
        ],
    ],
];
