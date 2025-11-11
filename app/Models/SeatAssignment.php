<?php

// app/Models/SeatAssignment.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SeatAssignment extends Model {
  protected $fillable = ['exam_id','room_id','col','row','side','student_id'];
}
