<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = [
        'subject_id', 
        'type', 
        'question_text', 
        'option_a', 
        'option_b', 
        'option_c', 
        'option_d', 
        'options',
        'option_images',
        'answer', 
        'explanation',
        'reference_note',
        'image'
    ];

    protected $casts = [
        'options' => 'array',
        'option_images' => 'array',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function exams()
    {
        return $this->belongsToMany(Exam::class, 'exam_questions');
    }
}
