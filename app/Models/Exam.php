<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    protected $fillable = [
        'subject_id', 
        'title', 
        'description', 
        'duration_minutes', 
        'random_order', 
        'pg_weight', 
        'essay_weight',
        'start_time',
        'is_active'
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'random_order' => 'boolean',
        'pg_weight' => 'integer',
        'essay_weight' => 'integer',
        'is_active' => 'boolean',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'exam_questions');
    }

    public function results()
    {
        return $this->hasMany(Result::class);
    }
}
