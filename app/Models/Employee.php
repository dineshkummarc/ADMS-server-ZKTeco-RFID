<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'name',
    ];

    /**
     * Get the attendance records for the employee.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'id', 'employee_id');
    }
}