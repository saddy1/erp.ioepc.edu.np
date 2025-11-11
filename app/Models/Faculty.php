<?php
// app/Models/Faculty.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Faculty extends Model {
  protected $fillable = ['name','code'];

  public function semesterSubjects()
{
    return $this->hasMany(FacultySemesterSubject::class); 
}}