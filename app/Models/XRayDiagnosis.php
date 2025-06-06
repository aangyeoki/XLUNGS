<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class XRayDiagnosis extends Model
{
    use HasFactory;

    protected $table = 'xray_diagnoses';

    protected $fillable = [
        'user_id',
        'nama',
        'image_path',
        'ai_result',
        'diagnosis',
        'confidence',
        'explanation',
        'notes'
    ];

    protected $casts = [
        'ai_result' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function getImageUrlAttribute()
    {
        return asset('storage/' . $this->image_path);
    }

    public function getFormattedDateAttribute()
    {
        return $this->created_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i');
    }


    public function getConfidenceColorAttribute()
    {
        if ($this->confidence >= 80) {
            return 'success';
        } elseif ($this->confidence >= 60) {
            return 'warning';
        } else {
            return 'danger';
        }
    }

    public function getDiagnosisTypeAttribute()
    {
        switch (strtolower($this->diagnosis)) {
            case 'normal':
                return ['label' => 'Normal', 'color' => 'success', 'icon' => 'fa-check-circle'];
            case 'pneumonia':
                return ['label' => 'Pneumonia', 'color' => 'warning', 'icon' => 'fa-exclamation-triangle'];
            case 'covid-19':
                return ['label' => 'COVID-19', 'color' => 'danger', 'icon' => 'fa-virus'];
            case 'tuberculosis':
                return ['label' => 'Tuberculosis', 'color' => 'danger', 'icon' => 'fa-lungs'];
            case 'fibrosis':
                return ['label' => 'Fibrosis', 'color' => 'warning', 'icon' => 'fa-microscope'];
            default:
                return ['label' => 'Unknown', 'color' => 'secondary', 'icon' => 'fa-question'];
        }
    }
}
