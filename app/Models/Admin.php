<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use Notifiable;

    protected $table = 'admins';

    protected $fillable = [
        'name',
        'email',
        'password',
        'contact',
        'is_super_admin',
        'is_hod',
        'department_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // 🔗 Department this admin is HOD of (or null)
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Faculties this admin can manage.
     *
     * - Super admin: all faculties
     * - HOD: faculties attached to their department
     * - Others: none
     */
    public function managedFacultyIds(): array
    {
        // Super admin → all faculties
        if ($this->is_super_admin) {
            return \App\Models\Faculty::pluck('id')->all();
        }

        // HOD with department → faculties from department_faculty
        if ($this->is_hod && $this->department_id && $this->department) {
            return $this->department
                ->faculties()
                ->pluck('faculties.id')
                ->unique()
                ->values()
                ->all();
        }

        // Default: no faculties
        return [];
    }

    /**
     * Quick helper: is this admin limited to department?
     */
    public function isDepartmentAdmin(): bool
    {
        return !$this->is_super_admin && $this->is_hod && $this->department_id !== null;
    }
}
