<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PYQQuestion extends Model
{
    protected $table = 'pyq_questions';

    public $timestamps = false;

    protected $fillable = [
        'subject', 'topic', 'year', 'type',
        'question', 'options', 'answer', 'solution', 'difficulty',
    ];

    protected $casts = [
        'options'    => 'array',
        'year'       => 'integer',
        'difficulty' => 'integer',
    ];

    public function attempts(): HasMany
    {
        return $this->hasMany(StudentPYQAttempt::class, 'pyq_id');
    }

    public static function topics(): array
    {
        return [
            'Physics' => [
                'Kinematics', 'Newton\'s Laws and Friction', 'Work Energy Power',
                'Rotational Motion', 'Simple Harmonic Motion', 'Waves and Sound',
                'Electrostatics', 'Current Electricity', 'Electromagnetic Induction',
                'Ray Optics', 'Modern Physics', 'Thermodynamics',
            ],
            'Chemistry' => [
                'Mole Concept', 'Chemical Equilibrium', 'Electrochemistry',
                'Atomic Structure', 'Chemical Bonding', 'Organic Reaction Mechanisms',
                'Aldehydes and Ketones', 'Coordination Compounds',
                'p-Block Elements', 'Chemical Kinetics',
            ],
            'Maths' => [
                'Limits and Derivatives', 'Definite Integration', 'Differential Equations',
                'Matrices and Determinants', 'Vectors and 3D Geometry', 'Probability',
                'Complex Numbers', 'Conic Sections',
                'Permutations and Combinations', 'Sequences and Series',
            ],
        ];
    }

    public static function allTopics(): array
    {
        return array_merge(...array_values(self::topics()));
    }
}
