<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ICD10Code extends Model
{
    protected $table = 'icd10_codes';
    protected $fillable = ['code', 'description', 'description_ar', 'category'];
}
