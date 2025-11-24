<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomAllocation extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'room_allocations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'exam_id',
        'exam_date',
        'room_id',
        'faculty_id',
        'subject_code',
        'student_count',
        'invigilator_count',
        'invigilator_assignments',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'exam_id' => 'integer',
        'room_id' => 'integer',
        'faculty_id' => 'integer',
        'student_count' => 'integer',
        'invigilator_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
         'invigilator_assignments' => 'array',
    ];

    /**
     * Get the exam that owns the room allocation.
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Get the room that owns the room allocation.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get the faculty that owns the room allocation.
     */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    /**
     * Scope to filter by exam and date.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $examId
     * @param string $examDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForExamDate($query, int $examId, string $examDate)
    {
        return $query->where('exam_id', $examId)
                     ->where('exam_date', $examDate);
    }

    /**
     * Scope to filter by room.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $roomId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForRoom($query, int $roomId)
    {
        return $query->where('room_id', $roomId);
    }

    /**
     * Scope to filter by faculty and subject.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $facultyId
     * @param string $subjectCode
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForSubject($query, int $facultyId, string $subjectCode)
    {
        return $query->where('faculty_id', $facultyId)
                     ->where('subject_code', $subjectCode);
    }

    /**
     * Get the paper key (faculty_id|subject_code) for this allocation.
     *
     * @return string
     */
    public function getPaperKeyAttribute(): string
    {
        return $this->faculty_id . '|' . $this->subject_code;
    }

    /**
     * Get total allocations grouped by room for an exam date.
     *
     * @param int $examId
     * @param string $examDate
     * @return \Illuminate\Support\Collection
     */
    public static function getTotalsByRoom(int $examId, string $examDate)
    {
        return static::where('exam_id', $examId)
            ->where('exam_date', $examDate)
            ->groupBy('room_id')
            ->selectRaw('room_id, SUM(student_count) as total')
            ->pluck('total', 'room_id');
    }

    /**
     * Get total allocations grouped by subject (paper key) for an exam date.
     *
     * @param int $examId
     * @param string $examDate
     * @return \Illuminate\Support\Collection
     */
    public static function getTotalsByPaper(int $examId, string $examDate)
    {
        return static::where('exam_id', $examId)
            ->where('exam_date', $examDate)
            ->get()
            ->groupBy(fn($item) => $item->faculty_id . '|' . $item->subject_code)
            ->map(fn($group) => $group->sum('student_count'));
    }
}