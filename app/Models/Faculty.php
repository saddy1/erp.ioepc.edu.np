<?php
// app/Models/Faculty.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Faculty extends Model {
  protected $fillable = ['name','code'];

    public function scopeCodeOrder($query)
{
    $order = ['BCE', 'BEL', 'BEI', 'BCT', 'BEX', 'BME', 'BAG', 'BAR'];

    $list = "'" . implode("','", $order) . "'";

    // Put codes in $order first, all other codes after them
    return $query->orderByRaw("FIELD(code, $list) = 0, FIELD(code, $list)");
}

  public function semesterSubjects()
{
    return $this->hasMany(FacultySemesterSubject::class); 
}
public function sections()
{
    return $this->hasMany(Section::class);
}
public function departments()
{
    return $this->belongsToMany(Department::class, 'department_faculty');
}


}