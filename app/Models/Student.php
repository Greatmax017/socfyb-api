<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'matric_no',
        'name',
        'email',
        'phone',
        'department',
        'is_paid',
        'reference',
    ];

    protected $casts = [
        'is_paid' => 'boolean'
    ];

    
}
