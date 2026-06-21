<?php

namespace Database\Seeders;

use App\Models\PYQQuestion;
use Illuminate\Database\Seeder;

class PYQSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [

            // ── KINEMATICS ──
            ['subject' => 'Physics', 'topic' => 'Kinematics', 'year' => 2023, 'type' => 'mcq', 'difficulty' => 2,
             'question' => 'A ball is thrown vertically upward with a velocity of 20 m/s. The maximum height reached is (g = 10 m/s²):',
             'options' => ['A. 10 m', 'B. 20 m', 'C. 40 m', 'D. 5 m'], 'answer' => 'B',
             'solution' => 'Using v² = u² - 2gh. At max height, v = 0. So h = u²/2g = 400/20 = 20 m.'],

            ['subject' => 'Physics', 'topic' => 'Kinematics', 'year' => 2022, 'type' => 'mcq', 'difficulty' => 2,
             'question' => 'A particle moves along x-axis as x = 4t - t². Velocity at t = 2s is:',
             'options' => ['A. 0 m/s', 'B. 2 m/s', 'C. 4 m/s', 'D. -2 m/s'], 'answer' => 'A',
             'solution' => 'v = dx/dt = 4 - 2t. At t = 2: v = 4 - 4 = 0 m/s.'],

            ['subject' => 'Physics', 'topic' => 'Kinematics', 'year' => 2021, 'type' => 'numerical', 'difficulty' => 3,
             'question' => 'A projectile is launched at 45° with speed 20 m/s. Find the range (g = 10 m/s²).',
             'options' => null, 'answer' => '40 m',
             'solution' => 'R = u²sin(2θ)/g = 400 × sin(90°)/10 = 400/10 = 40 m.'],

            // ── NEWTON'S LAWS ──
            ['subject' => 'Physics', 'topic' => "Newton's Laws and Friction", 'year' => 2023, 'type' => 'mcq', 'difficulty' => 2,
             'question' => 'A block of mass 5 kg is placed on a surface with μ = 0.3. The friction force when a 10 N horizontal force is applied is (g = 10 m/s²):',
             'options' => ['A. 15 N', 'B. 10 N', 'C. 5 N', 'D. 0 N'], 'answer' => 'B',
             'solution' => 'Max static friction = μmg = 0.3 × 5 × 10 = 15 N. Applied force = 10 N < 15 N, so block stays at rest. Friction = 10 N (equals applied force).'],

            ['subject' => 'Physics', 'topic' => "Newton's Laws and Friction", 'year' => 2022, 'type' => 'mcq', 'difficulty' => 3,
             'question' => 'Two blocks A (2 kg) and B (3 kg) are connected by a string over a pulley. Acceleration of the system is (g = 10 m/s²):',
             'options' => ['A. 1 m/s²', 'B. 2 m/s²', 'C. 4 m/s²', 'D. 5 m/s²'], 'answer' => 'B',
             'solution' => 'a = (m₂ - m₁)g/(m₁ + m₂) = (3-2)×10/(2+3) = 10/5 = 2 m/s².'],

            // ── WORK ENERGY POWER ──
            ['subject' => 'Physics', 'topic' => 'Work Energy Power', 'year' => 2023, 'type' => 'mcq', 'difficulty' => 2,
             'question' => 'A body of mass 2 kg moving at 5 m/s is brought to rest. Work done by braking force is:',
             'options' => ['A. 25 J', 'B. -25 J', 'C. 10 J', 'D. -10 J'], 'answer' => 'B',
             'solution' => 'W = ΔKE = ½mv² - ½mu² = 0 - ½×2×25 = -25 J. Negative because force opposes motion.'],

            // ── ELECTROSTATICS ──
            ['subject' => 'Physics', 'topic' => 'Electrostatics', 'year' => 2023, 'type' => 'mcq', 'difficulty' => 2,
             'question' => 'Electric field inside a uniformly charged spherical shell is:',
             'options' => ['A. Zero', 'B. Non-zero constant', 'C. Varies with r', 'D. Infinite at center'], 'answer' => 'A',
             'solution' => 'By Gauss\'s law, E inside a shell = 0 because enclosed charge = 0.'],

            ['subject' => 'Physics', 'topic' => 'Electrostatics', 'year' => 2022, 'type' => 'numerical', 'difficulty' => 3,
             'question' => 'Two charges +2μC and -2μC are placed 0.1m apart. Force between them is (k = 9×10⁹ Nm²/C²):',
             'options' => null, 'answer' => '3.6 N',
             'solution' => 'F = kq₁q₂/r² = 9×10⁹ × 2×10⁻⁶ × 2×10⁻⁶ / (0.1)² = 9×10⁹ × 4×10⁻¹² / 0.01 = 3.6 N.'],

            // ── THERMODYNAMICS ──
            ['subject' => 'Physics', 'topic' => 'Thermodynamics', 'year' => 2023, 'type' => 'mcq', 'difficulty' => 2,
             'question' => 'In an isothermal process, which quantity remains constant?',
             'options' => ['A. Pressure', 'B. Volume', 'C. Temperature', 'D. Entropy'], 'answer' => 'C',
             'solution' => 'Isothermal = same temperature. Temperature remains constant. PV = nRT = constant (so P and V change inversely).'],

            ['subject' => 'Physics', 'topic' => 'Thermodynamics', 'year' => 2021, 'type' => 'mcq', 'difficulty' => 3,
             'question' => 'Efficiency of a Carnot engine operating between 400K and 300K is:',
             'options' => ['A. 25%', 'B. 33%', 'C. 50%', 'D. 75%'], 'answer' => 'A',
             'solution' => 'η = 1 - T_cold/T_hot = 1 - 300/400 = 1 - 0.75 = 0.25 = 25%.'],

            // ── MOLE CONCEPT ──
            ['subject' => 'Chemistry', 'topic' => 'Mole Concept', 'year' => 2023, 'type' => 'mcq', 'difficulty' => 1,
             'question' => 'Number of molecules in 36g of water (Molar mass = 18 g/mol) is:',
             'options' => ['A. 6.022×10²³', 'B. 1.2×10²⁴', 'C. 3.011×10²³', 'D. 18×10²³'], 'answer' => 'B',
             'solution' => 'Moles = 36/18 = 2 mol. Molecules = 2 × 6.022×10²³ = 1.2×10²⁴.'],

            ['subject' => 'Chemistry', 'topic' => 'Mole Concept', 'year' => 2022, 'type' => 'numerical', 'difficulty' => 2,
             'question' => 'What volume (in L) does 4g of oxygen gas occupy at STP? (Molar mass O₂ = 32 g/mol)',
             'options' => null, 'answer' => '2.8 L',
             'solution' => 'Moles of O₂ = 4/32 = 0.125 mol. Volume at STP = 0.125 × 22.4 = 2.8 L.'],

            // ── CHEMICAL EQUILIBRIUM ──
            ['subject' => 'Chemistry', 'topic' => 'Chemical Equilibrium', 'year' => 2023, 'type' => 'mcq', 'difficulty' => 2,
             'question' => 'For the reaction N₂ + 3H₂ ⇌ 2NH₃, increasing pressure will:',
             'options' => ['A. Shift equilibrium left', 'B. Shift equilibrium right', 'C. No effect', 'D. Stop reaction'], 'answer' => 'B',
             'solution' => 'Increasing pressure favors side with fewer moles of gas. Left has 4 moles, right has 2 moles. Equilibrium shifts right (toward NH₃ formation).'],

            // ── ORGANIC REACTION MECHANISMS ──
            ['subject' => 'Chemistry', 'topic' => 'Organic Reaction Mechanisms', 'year' => 2022, 'type' => 'mcq', 'difficulty' => 3,
             'question' => 'SN2 reaction proceeds with:',
             'options' => ['A. Retention of configuration', 'B. Inversion of configuration', 'C. Racemization', 'D. No stereochemical change'], 'answer' => 'B',
             'solution' => 'SN2 is a concerted backside attack. Nucleophile attacks from back, leaving group leaves from front — causing inversion (Walden inversion).'],

            // ── ATOMIC STRUCTURE ──
            ['subject' => 'Chemistry', 'topic' => 'Atomic Structure', 'year' => 2023, 'type' => 'mcq', 'difficulty' => 2,
             'question' => 'The maximum number of electrons in a subshell with l = 2 is:',
             'options' => ['A. 2', 'B. 6', 'C. 10', 'D. 14'], 'answer' => 'C',
             'solution' => 'For l = 2 (d subshell), m_l = -2,-1,0,+1,+2 → 5 orbitals × 2 electrons = 10 electrons.'],

            // ── LIMITS AND DERIVATIVES ──
            ['subject' => 'Maths', 'topic' => 'Limits and Derivatives', 'year' => 2023, 'type' => 'mcq', 'difficulty' => 2,
             'question' => 'lim(x→0) (sin x)/x equals:',
             'options' => ['A. 0', 'B. ∞', 'C. 1', 'D. π'], 'answer' => 'C',
             'solution' => 'This is a standard limit: lim(x→0) (sin x)/x = 1. Proof via L\'Hôpital or Taylor expansion: sin x ≈ x for small x.'],

            ['subject' => 'Maths', 'topic' => 'Limits and Derivatives', 'year' => 2022, 'type' => 'mcq', 'difficulty' => 2,
             'question' => 'Derivative of sin²x with respect to x is:',
             'options' => ['A. 2sinx', 'B. sin2x', 'C. 2cosx', 'D. cos2x'], 'answer' => 'B',
             'solution' => 'd/dx(sin²x) = 2sinx·cosx = sin2x (using double angle formula).'],

            // ── DEFINITE INTEGRATION ──
            ['subject' => 'Maths', 'topic' => 'Definite Integration', 'year' => 2023, 'type' => 'numerical', 'difficulty' => 2,
             'question' => 'Evaluate ∫₀^π sin x dx',
             'options' => null, 'answer' => '2',
             'solution' => '∫₀^π sin x dx = [-cos x]₀^π = -cos(π) + cos(0) = -(-1) + 1 = 2.'],

            ['subject' => 'Maths', 'topic' => 'Definite Integration', 'year' => 2022, 'type' => 'mcq', 'difficulty' => 3,
             'question' => '∫₀¹ x/(1+x²) dx equals:',
             'options' => ['A. ln2/2', 'B. ln2', 'C. 1/2', 'D. π/4'], 'answer' => 'A',
             'solution' => 'Let u = 1+x². du = 2x dx. Limits: x=0 → u=1, x=1 → u=2. Integral = ½∫₁² du/u = ½[ln u]₁² = ½ln2.'],

            // ── PROBABILITY ──
            ['subject' => 'Maths', 'topic' => 'Probability', 'year' => 2023, 'type' => 'mcq', 'difficulty' => 2,
             'question' => 'A dice is thrown twice. Probability of getting sum = 7 is:',
             'options' => ['A. 1/6', 'B. 7/36', 'C. 5/36', 'D. 1/3'], 'answer' => 'A',
             'solution' => 'Favorable outcomes for sum 7: (1,6),(2,5),(3,4),(4,3),(5,2),(6,1) = 6 outcomes. Total = 36. P = 6/36 = 1/6.'],

            // ── VECTORS ──
            ['subject' => 'Maths', 'topic' => 'Vectors and 3D Geometry', 'year' => 2022, 'type' => 'mcq', 'difficulty' => 2,
             'question' => 'If |a| = 3, |b| = 4 and a·b = 0, then |a + b| is:',
             'options' => ['A. 7', 'B. 5', 'C. 1', 'D. 25'], 'answer' => 'B',
             'solution' => '|a+b|² = |a|² + 2a·b + |b|² = 9 + 0 + 16 = 25. So |a+b| = 5. (a·b = 0 means perpendicular.)'],

            // ── COMPLEX NUMBERS ──
            ['subject' => 'Maths', 'topic' => 'Complex Numbers', 'year' => 2023, 'type' => 'mcq', 'difficulty' => 2,
             'question' => 'The modulus of the complex number (3 + 4i) is:',
             'options' => ['A. 3', 'B. 4', 'C. 5', 'D. 7'], 'answer' => 'C',
             'solution' => '|z| = √(3² + 4²) = √(9 + 16) = √25 = 5.'],

            // ── CONIC SECTIONS ──
            ['subject' => 'Maths', 'topic' => 'Conic Sections', 'year' => 2022, 'type' => 'mcq', 'difficulty' => 2,
             'question' => 'The eccentricity of a circle is:',
             'options' => ['A. 0', 'B. 1', 'C. > 1', 'D. < 0'], 'answer' => 'A',
             'solution' => 'Circle is a special case of ellipse with both foci at center. Eccentricity e = 0 for a circle.'],

            // ── RAY OPTICS ──
            ['subject' => 'Physics', 'topic' => 'Ray Optics', 'year' => 2022, 'type' => 'mcq', 'difficulty' => 2,
             'question' => 'A convex mirror always forms an image that is:',
             'options' => ['A. Real, inverted', 'B. Virtual, erect, diminished', 'C. Real, erect', 'D. Virtual, inverted'], 'answer' => 'B',
             'solution' => 'Convex mirrors always form virtual, erect, and diminished images regardless of object position. Used as rear-view mirrors.'],

            // ── MODERN PHYSICS ──
            ['subject' => 'Physics', 'topic' => 'Modern Physics', 'year' => 2023, 'type' => 'mcq', 'difficulty' => 3,
             'question' => 'The de Broglie wavelength of an electron accelerated through potential V is proportional to:',
             'options' => ['A. V', 'B. √V', 'C. 1/√V', 'D. 1/V'], 'answer' => 'C',
             'solution' => 'λ = h/p = h/√(2meV). So λ ∝ 1/√V.'],

            // ── SEQUENCES AND SERIES ──
            ['subject' => 'Maths', 'topic' => 'Sequences and Series', 'year' => 2022, 'type' => 'mcq', 'difficulty' => 2,
             'question' => 'Sum of first 10 terms of AP: 2, 5, 8, 11... is:',
             'options' => ['A. 145', 'B. 155', 'C. 135', 'D. 160'], 'answer' => 'B',
             'solution' => 'a = 2, d = 3, n = 10. Sₙ = n/2[2a + (n-1)d] = 10/2[4 + 27] = 5 × 31 = 155.'],

        ];

        foreach ($questions as $q) {
            PYQQuestion::firstOrCreate(
                ['question' => $q['question']],
                $q
            );
        }

        $this->command->info('PYQ seeder done — ' . count($questions) . ' questions loaded.');
    }
}
