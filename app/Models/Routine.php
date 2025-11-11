<?php
// app/Models/Routine.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Routine extends Model {
  protected $fillable = ['exam_date','start_time','end_time','semester','subject_code','is_common_subject','faculty_ids'];
  protected $casts = ['exam_date'=>'date','is_common_subject'=>'boolean','faculty_ids'=>'array'];
}