<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    protected $fillable = ['exam_id', 'user_id', 'score', 'computer_score', 'essay_score', 'answers', 'student_notes', 'teacher_notes', 'student_signature', 'teacher_signature', 'start_time', 'end_time', 'is_cheating', 'last_activity'];
    
    protected $casts = [
        'answers' => 'array',
        'student_notes' => 'array',
        'teacher_notes' => 'array',
        'is_cheating' => 'boolean',
        'score' => 'float',
        'computer_score' => 'float',
        'essay_score' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }
}
