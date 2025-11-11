<?php

// app/Models/Room.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Room extends Model {
  protected $fillable = ['room_no','total_benches','rows_col1','rows_col2','rows_col3','faculties_per_room'];
  protected $appends = ['computed_total_benches','computed_total_seats'];

  public function getComputedTotalBenchesAttribute(){
    return (int)($this->rows_col1 + $this->rows_col2 + $this->rows_col3);
  }
  public function getComputedTotalSeatsAttribute(){
    return $this->computed_total_benches * 2;
  }
}
