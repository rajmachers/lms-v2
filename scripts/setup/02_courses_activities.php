<?php
// Part 2: Create 6 Physics courses with sections, activities (page, quiz, forum, assignment)
define('CLI_SCRIPT', true);
require('/var/www/html/moodle/config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/mod/quiz/lib.php');
require_once($CFG->dirroot . '/mod/forum/lib.php');
require_once($CFG->dirroot . '/mod/assign/lib.php');
require_once($CFG->dirroot . '/mod/page/lib.php');
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/engine/bank.php');

echo "===== PART 2: CREATE 6 PHYSICS COURSES =====\n\n";

// Load IDs from Part 1
$ids = json_decode(file_get_contents('/tmp/moodle_setup_ids.json'), true);
$catids = $ids['categories'];

// Course definitions
$courses = [
    [
        'shortname' => 'PHY101',
        'fullname' => 'Classical Mechanics & Special Relativity',
        'category' => $catids['year1'],
        'summary' => 'A first-year module covering Newtonian mechanics, Lagrangian and Hamiltonian formulations, oscillations, and an introduction to Einstein\'s special theory of relativity.',
        'sections' => [
            ['name' => 'Newton\'s Laws & Kinematics', 'pages' => [
                ['Newton\'s Three Laws of Motion', '<h3>Newton\'s Laws</h3><p>Sir Isaac Newton formulated three laws of motion that form the foundation of classical mechanics.</p><p><strong>First Law (Inertia):</strong> An object at rest stays at rest, and an object in motion stays in motion at constant velocity, unless acted upon by a net external force.</p><p><strong>Second Law:</strong> The acceleration of an object is directly proportional to the net force acting on it: F = ma.</p><p><strong>Third Law:</strong> For every action, there is an equal and opposite reaction.</p><p>These laws, published in <em>Principia Mathematica</em> (1687), revolutionised our understanding of motion and remain accurate for everyday speeds and scales.</p>'],
                ['Kinematics in One and Two Dimensions', '<h3>Kinematics</h3><p>Kinematics describes the motion of objects without considering the forces that cause them.</p><p><strong>Key equations of motion:</strong></p><ul><li>v = u + at</li><li>s = ut + ½at²</li><li>v² = u² + 2as</li></ul><p>In two dimensions, projectile motion can be analysed by decomposing into independent horizontal and vertical components. The trajectory follows a parabolic path when air resistance is negligible.</p>'],
            ], 'quiz' => [
                'name' => 'Quiz: Newton\'s Laws',
                'questions' => [
                    ['What is the SI unit of force?', ['Newton', 'Joule', 'Watt', 'Pascal'], 0],
                    ['If F = ma, what is the acceleration when F = 10N and m = 2kg?', ['2 m/s²', '5 m/s²', '10 m/s²', '20 m/s²'], 1],
                    ['Newton\'s Third Law states that forces come in:', ['Singles', 'Pairs', 'Triples', 'Quadruples'], 1],
                ],
            ], 'forum' => 'Discussion: Real-world applications of Newton\'s Laws',
               'assign' => 'Problem Set 1: Kinematics & Newton\'s Laws'],
            ['name' => 'Work, Energy & Momentum', 'pages' => [
                ['Work-Energy Theorem', '<h3>Work and Energy</h3><p>Work is done when a force moves an object through a displacement: W = F·d·cos(θ).</p><p>The <strong>Work-Energy Theorem</strong> states that the net work done on an object equals its change in kinetic energy: W_net = ΔKE = ½mv₂² - ½mv₁².</p><p><strong>Conservation of Energy:</strong> In an isolated system, the total mechanical energy (KE + PE) remains constant. Energy can be transformed between kinetic and potential forms but never created or destroyed.</p>'],
                ['Linear Momentum & Collisions', '<h3>Momentum</h3><p>Linear momentum p = mv is a vector quantity conserved in all collisions.</p><p><strong>Elastic collisions:</strong> Both momentum and kinetic energy are conserved.</p><p><strong>Inelastic collisions:</strong> Momentum is conserved but kinetic energy is not. In a perfectly inelastic collision, objects stick together.</p><p>The impulse-momentum theorem: FΔt = Δp connects force applied over time to change in momentum.</p>'],
            ], 'quiz' => [
                'name' => 'Quiz: Energy & Momentum',
                'questions' => [
                    ['What is the kinetic energy of a 3kg object moving at 4 m/s?', ['6 J', '12 J', '24 J', '48 J'], 2],
                    ['In which type of collision is kinetic energy conserved?', ['Inelastic', 'Perfectly inelastic', 'Elastic', 'Explosive'], 2],
                    ['What is the unit of momentum?', ['kg·m/s', 'N·m', 'J/s', 'kg·m/s²'], 0],
                ],
            ], 'forum' => 'Discussion: Conservation Laws in everyday life',
               'assign' => 'Problem Set 2: Energy & Momentum Calculations'],
            ['name' => 'Rotational Mechanics', 'pages' => [
                ['Torque and Angular Momentum', '<h3>Rotational Dynamics</h3><p>Torque τ = r × F is the rotational analogue of force. It measures the tendency of a force to cause rotation about an axis.</p><p>Angular momentum L = Iω, where I is the moment of inertia and ω is angular velocity. Angular momentum is conserved when no external torque acts on the system — this explains why ice skaters spin faster when they pull in their arms.</p>'],
                ['Moment of Inertia & Rotational Energy', '<h3>Moment of Inertia</h3><p>The moment of inertia I depends on mass distribution relative to the axis of rotation.</p><p>Common values: Solid sphere I = 2/5 MR², Hollow sphere I = 2/3 MR², Solid cylinder I = 1/2 MR², Rod about centre I = 1/12 ML².</p><p>Rotational kinetic energy: KE_rot = ½Iω². Total kinetic energy of a rolling object includes both translational and rotational components.</p>'],
            ], 'quiz' => [
                'name' => 'Quiz: Rotational Mechanics',
                'questions' => [
                    ['Torque is measured in:', ['Newtons', 'Newton-metres', 'Joules per second', 'Kilogram-metres'], 1],
                    ['What happens to angular velocity when moment of inertia decreases (no external torque)?', ['Decreases', 'Stays same', 'Increases', 'Becomes zero'], 2],
                    ['The moment of inertia of a solid sphere is:', ['MR²', '2/3 MR²', '2/5 MR²', '1/2 MR²'], 2],
                ],
            ], 'forum' => 'Discussion: Angular momentum in astrophysics',
               'assign' => 'Problem Set 3: Rotational Dynamics'],
            ['name' => 'Special Relativity', 'pages' => [
                ['Postulates and Time Dilation', '<h3>Einstein\'s Special Relativity</h3><p>Published in 1905, special relativity rests on two postulates:</p><ol><li>The laws of physics are the same in all inertial reference frames.</li><li>The speed of light in vacuum is constant (c ≈ 3×10⁸ m/s) for all observers.</li></ol><p><strong>Time dilation:</strong> Moving clocks run slower. Δt = γΔt₀ where γ = 1/√(1-v²/c²) is the Lorentz factor. This has been confirmed by measuring muon lifetimes and with atomic clocks on aircraft.</p>'],
                ['Length Contraction and E=mc²', '<h3>Further Consequences</h3><p><strong>Length contraction:</strong> Objects moving at relativistic speeds appear shorter in the direction of motion: L = L₀/γ.</p><p><strong>Mass-energy equivalence:</strong> E = mc² demonstrates that mass and energy are interchangeable. A small amount of mass can release enormous energy, as seen in nuclear reactions.</p><p>The relativistic momentum p = γmv ensures momentum is conserved at all speeds. At low speeds (v << c), γ ≈ 1 and classical mechanics is recovered.</p>'],
            ], 'quiz' => [
                'name' => 'Quiz: Special Relativity',
                'questions' => [
                    ['What is the speed of light in vacuum?', ['3×10⁶ m/s', '3×10⁸ m/s', '3×10¹⁰ m/s', '3×10¹² m/s'], 1],
                    ['Time dilation means moving clocks run:', ['Faster', 'Slower', 'Same speed', 'Backwards'], 1],
                    ['In E=mc², c represents:', ['Capacitance', 'Coulomb constant', 'Speed of light', 'Specific heat'], 2],
                ],
            ], 'forum' => 'Discussion: Paradoxes in special relativity',
               'assign' => 'Essay: The twin paradox explained'],
        ],
    ],
    [
        'shortname' => 'PHY102',
        'fullname' => 'Electromagnetism & Optics',
        'category' => $catids['year1'],
        'summary' => 'A comprehensive first-year module covering electrostatics, magnetism, Maxwell\'s equations, electromagnetic waves, and the principles of geometrical and physical optics.',
        'sections' => [
            ['name' => 'Electrostatics', 'pages' => [
                ['Coulomb\'s Law and Electric Fields', '<h3>Electrostatics</h3><p>Coulomb\'s Law describes the force between two point charges: F = kq₁q₂/r². The electric field E at a point is the force per unit positive charge: E = F/q.</p><p>Electric field lines radiate outward from positive charges and inward toward negative charges. The principle of superposition allows us to calculate fields from multiple charges.</p>'],
                ['Gauss\'s Law and Electric Potential', '<h3>Gauss\'s Law</h3><p>Gauss\'s Law: ∮E·dA = Q_enc/ε₀. This powerful law relates the electric flux through a closed surface to the enclosed charge.</p><p>Electric potential V = kQ/r is the potential energy per unit charge. The potential difference drives current in circuits. Equipotential surfaces are perpendicular to electric field lines.</p>'],
            ], 'quiz' => [
                'name' => 'Quiz: Electrostatics',
                'questions' => [
                    ['Coulomb\'s constant k has the approximate value:', ['6.67×10⁻¹¹', '8.99×10⁹', '1.60×10⁻¹⁹', '9.81'], 1],
                    ['Electric field lines point:', ['From negative to positive', 'From positive to negative', 'In circles', 'Randomly'], 1],
                    ['The SI unit of electric potential is:', ['Ampere', 'Ohm', 'Volt', 'Farad'], 2],
                ],
            ], 'forum' => 'Discussion: Applications of electrostatics',
               'assign' => 'Problem Set 1: Electric fields and potentials'],
            ['name' => 'Magnetism & Electromagnetic Induction', 'pages' => [
                ['Magnetic Fields and Forces', '<h3>Magnetism</h3><p>Moving charges create magnetic fields. The magnetic force on a moving charge: F = qv × B (cross product). The force on a current-carrying wire: F = IL × B.</p><p>The Biot-Savart law and Ampère\'s law allow calculation of magnetic fields from current distributions. A solenoid produces a uniform field B = μ₀nI inside.</p>'],
                ['Faraday\'s Law and Lenz\'s Law', '<h3>Electromagnetic Induction</h3><p>Faraday\'s Law: EMF = -dΦ_B/dt. A changing magnetic flux induces an electromotive force. Lenz\'s Law states the induced current opposes the change that produced it.</p><p>Applications include electric generators, transformers, and induction cooktops. Self-inductance L relates the induced EMF to the rate of change of current: EMF = -L(dI/dt).</p>'],
            ], 'quiz' => [
                'name' => 'Quiz: Magnetism',
                'questions' => [
                    ['The SI unit of magnetic flux density is:', ['Weber', 'Tesla', 'Henry', 'Gauss'], 1],
                    ['Faraday\'s Law relates EMF to changing:', ['Electric field', 'Magnetic flux', 'Current', 'Resistance'], 1],
                    ['Lenz\'s Law is a consequence of conservation of:', ['Charge', 'Momentum', 'Energy', 'Mass'], 2],
                ],
            ], 'forum' => 'Discussion: Electromagnetic induction in technology',
               'assign' => 'Problem Set 2: Magnetic fields and induction'],
            ['name' => 'Maxwell\'s Equations & EM Waves', 'pages' => [
                ['Maxwell\'s Four Equations', '<h3>Maxwell\'s Equations</h3><p>James Clerk Maxwell unified electricity and magnetism into four elegant equations:</p><ol><li>∇·E = ρ/ε₀ (Gauss\'s law for electricity)</li><li>∇·B = 0 (No magnetic monopoles)</li><li>∇×E = -∂B/∂t (Faraday\'s law)</li><li>∇×B = μ₀J + μ₀ε₀∂E/∂t (Ampère-Maxwell law)</li></ol><p>The displacement current term μ₀ε₀∂E/∂t was Maxwell\'s key addition, predicting electromagnetic waves.</p>'],
                ['Electromagnetic Wave Properties', '<h3>EM Waves</h3><p>Electromagnetic waves propagate at c = 1/√(μ₀ε₀) ≈ 3×10⁸ m/s. They consist of oscillating electric and magnetic fields perpendicular to each other and to the direction of propagation.</p><p>The electromagnetic spectrum: radio waves, microwaves, infrared, visible light, ultraviolet, X-rays, gamma rays. All travel at c in vacuum but differ in frequency and wavelength: c = fλ.</p>'],
            ], 'quiz' => [
                'name' => 'Quiz: Maxwell\'s Equations',
                'questions' => [
                    ['How many equations are in Maxwell\'s set?', ['Two', 'Three', 'Four', 'Five'], 2],
                    ['EM waves travel at what speed in vacuum?', ['Speed of sound', 'Speed of light', 'Infinite speed', 'Variable speed'], 1],
                    ['Which part of the EM spectrum has the shortest wavelength?', ['Radio waves', 'Visible light', 'X-rays', 'Gamma rays'], 3],
                ],
            ], 'forum' => 'Discussion: Maxwell\'s legacy in modern physics',
               'assign' => 'Essay: How Maxwell unified electricity and magnetism'],
            ['name' => 'Optics', 'pages' => [
                ['Geometrical Optics: Reflection & Refraction', '<h3>Geometrical Optics</h3><p>Light can be modelled as rays that travel in straight lines. Reflection: θ_i = θ_r. Refraction follows Snell\'s Law: n₁sinθ₁ = n₂sinθ₂.</p><p>Total internal reflection occurs when light travels from a denser to a less dense medium at angles greater than the critical angle: θ_c = sin⁻¹(n₂/n₁). This principle is used in optical fibres.</p>'],
                ['Wave Optics: Interference & Diffraction', '<h3>Wave Optics</h3><p>Young\'s double-slit experiment demonstrates the wave nature of light through interference patterns. Bright fringes occur when dsinθ = mλ.</p><p>Single-slit diffraction produces a central maximum with subsidiary maxima. The resolving power of optical instruments is limited by diffraction (Rayleigh criterion).</p>'],
            ], 'quiz' => [
                'name' => 'Quiz: Optics',
                'questions' => [
                    ['Snell\'s law relates:', ['Reflection angles', 'Refraction angles', 'Diffraction angles', 'Polarisation angles'], 1],
                    ['Total internal reflection requires light moving from:', ['Less dense to denser medium', 'Denser to less dense medium', 'Same density media', 'Vacuum to any medium'], 1],
                    ['Young\'s double-slit experiment demonstrates:', ['Particle nature of light', 'Wave nature of light', 'Speed of light', 'Colour of light'], 1],
                ],
            ], 'forum' => 'Discussion: Fibre optics and telecommunications',
               'assign' => 'Problem Set: Optics calculations'],
        ],
    ],
    [
        'shortname' => 'PHY201',
        'fullname' => 'Quantum Mechanics',
        'category' => $catids['year2'],
        'summary' => 'A second-year module introducing the formalism of quantum mechanics: wave-particle duality, the Schrödinger equation, observables, and applications to atomic and molecular systems.',
        'sections' => [
            ['name' => 'Wave-Particle Duality', 'pages' => [
                ['The Photoelectric Effect & Blackbody Radiation', '<h3>Origins of Quantum Theory</h3><p>Blackbody radiation could not be explained by classical physics (ultraviolet catastrophe). Planck proposed that energy is quantised: E = nhf.</p><p>Einstein explained the photoelectric effect by proposing light consists of photons with energy E = hf. The maximum kinetic energy of ejected electrons: KE_max = hf - φ, where φ is the work function.</p>'],
                ['de Broglie Waves & Wave Functions', '<h3>Matter Waves</h3><p>Louis de Broglie proposed that particles have wave-like properties with wavelength λ = h/p. This was confirmed by electron diffraction experiments (Davisson-Germer, 1927).</p><p>The wave function ψ(x,t) contains all information about a quantum system. The probability of finding a particle in region dx is |ψ(x)|²dx. The wave function must be normalised: ∫|ψ|²dx = 1.</p>'],
            ], 'quiz' => [
                'name' => 'Quiz: Wave-Particle Duality',
                'questions' => [
                    ['Who explained the photoelectric effect?', ['Newton', 'Maxwell', 'Einstein', 'Bohr'], 2],
                    ['The de Broglie wavelength is λ = h/p. What is h?', ['Boltzmann constant', 'Planck constant', 'Hubble constant', 'Coulomb constant'], 1],
                    ['|ψ|² represents:', ['Energy', 'Momentum', 'Probability density', 'Force'], 2],
                ],
            ], 'forum' => 'Discussion: Is light a wave or a particle?',
               'assign' => 'Problem Set 1: Photons and matter waves'],
            ['name' => 'The Schrödinger Equation', 'pages' => [
                ['Time-Independent Schrödinger Equation', '<h3>Schrödinger Equation</h3><p>The time-independent Schrödinger equation: Ĥψ = Eψ, where Ĥ = -ℏ²/2m · d²/dx² + V(x) is the Hamiltonian operator.</p><p>For a particle in a 1D infinite potential well of width L: ψ_n(x) = √(2/L)sin(nπx/L) with E_n = n²π²ℏ²/(2mL²). Energy is quantised — only specific values are allowed.</p>'],
                ['Quantum Tunnelling', '<h3>Tunnelling</h3><p>A quantum particle can penetrate a potential barrier even when its energy is less than the barrier height. The wave function decays exponentially inside the barrier but has non-zero amplitude beyond it.</p><p>Tunnelling probability depends on barrier height, width, and particle mass. Applications: scanning tunnelling microscope (STM), nuclear fusion in stars, tunnel diodes.</p>'],
            ], 'quiz' => [
                'name' => 'Quiz: Schrödinger Equation',
                'questions' => [
                    ['In the infinite potential well, energy levels are:', ['Continuous', 'Quantised', 'Zero', 'Negative'], 1],
                    ['Quantum tunnelling is impossible in:', ['Quantum mechanics', 'Classical mechanics', 'Nuclear physics', 'Solid-state physics'], 1],
                    ['The Hamiltonian operator represents:', ['Position', 'Momentum', 'Total energy', 'Angular momentum'], 2],
                ],
            ], 'forum' => 'Discussion: Quantum tunnelling applications',
               'assign' => 'Problem Set 2: Solving the Schrödinger equation'],
            ['name' => 'Operators, Observables & Uncertainty', 'pages' => [
                ['Hermitian Operators and Measurement', '<h3>Quantum Observables</h3><p>Physical observables are represented by Hermitian operators. The eigenvalues of these operators are the possible measurement outcomes.</p><p>Position operator: x̂ψ = xψ. Momentum operator: p̂ = -iℏd/dx. The expectation value ⟨A⟩ = ∫ψ*Âψdx gives the average of many measurements.</p>'],
                ['Heisenberg Uncertainty Principle', '<h3>Uncertainty Principle</h3><p>Heisenberg\'s uncertainty principle: ΔxΔp ≥ ℏ/2. It is fundamentally impossible to simultaneously know both the exact position and momentum of a particle.</p><p>This is not a limitation of measurement apparatus but a fundamental property of nature. The energy-time uncertainty relation: ΔEΔt ≥ ℏ/2 explains virtual particles and natural linewidths.</p>'],
            ], 'quiz' => [
                'name' => 'Quiz: Uncertainty Principle',
                'questions' => [
                    ['The uncertainty principle was formulated by:', ['Bohr', 'Schrödinger', 'Heisenberg', 'Dirac'], 2],
                    ['ΔxΔp ≥ ℏ/2 means we cannot simultaneously know:', ['Mass and charge', 'Position and momentum', 'Energy and force', 'Spin and colour'], 1],
                    ['Hermitian operators have:', ['Complex eigenvalues', 'Real eigenvalues', 'Zero eigenvalues', 'Imaginary eigenvalues'], 1],
                ],
            ], 'forum' => 'Discussion: Interpretations of quantum mechanics',
               'assign' => 'Essay: The measurement problem in quantum mechanics'],
            ['name' => 'Hydrogen Atom & Angular Momentum', 'pages' => [
                ['Quantum Numbers and Atomic Orbitals', '<h3>The Hydrogen Atom</h3><p>The hydrogen atom in quantum mechanics yields three quantum numbers: n (principal), l (angular momentum), m_l (magnetic). The energy levels E_n = -13.6/n² eV match the Bohr model but with a deeper understanding.</p><p>Orbital shapes: s (l=0, spherical), p (l=1, dumbbell), d (l=2, cloverleaf). Each orbital can hold 2 electrons (spin up and down).</p>'],
                ['Spin and the Stern-Gerlach Experiment', '<h3>Intrinsic Spin</h3><p>Electrons possess intrinsic angular momentum (spin) with quantum number s = 1/2. The Stern-Gerlach experiment (1922) demonstrated spin quantisation: silver atoms split into two beams in a non-uniform magnetic field.</p><p>Spin-1/2 particles have two states: |↑⟩ (m_s = +1/2) and |↓⟩ (m_s = -1/2). The Pauli exclusion principle: no two fermions can occupy the same quantum state.</p>'],
            ], 'quiz' => [
                'name' => 'Quiz: Hydrogen Atom',
                'questions' => [
                    ['How many quantum numbers describe the hydrogen atom electron?', ['Two', 'Three', 'Four', 'Five'], 2],
                    ['The s orbital has angular momentum quantum number l =', ['0', '1', '2', '3'], 0],
                    ['Electron spin quantum number is:', ['0', '1/2', '1', '3/2'], 1],
                ],
            ], 'forum' => 'Discussion: Why quantum numbers matter',
               'assign' => 'Problem Set 3: Hydrogen atom calculations'],
        ],
    ],
    [
        'shortname' => 'PHY202',
        'fullname' => 'Thermodynamics & Statistical Physics',
        'category' => $catids['year2'],
        'summary' => 'A second-year module covering the laws of thermodynamics, entropy, statistical mechanics, and applications to ideal gases, phase transitions, and quantum statistics.',
        'sections' => [
            ['name' => 'Laws of Thermodynamics', 'pages' => [
                ['The Four Laws', '<h3>Thermodynamic Laws</h3><p><strong>Zeroth Law:</strong> If A is in thermal equilibrium with B, and B with C, then A is in equilibrium with C — this defines temperature.</p><p><strong>First Law:</strong> ΔU = Q - W. Energy is conserved; heat added minus work done equals change in internal energy.</p><p><strong>Second Law:</strong> Entropy of an isolated system never decreases. Heat flows spontaneously from hot to cold. No engine can be 100% efficient.</p><p><strong>Third Law:</strong> As T → 0 K, entropy approaches a minimum. Absolute zero cannot be reached in a finite number of steps.</p>'],
                ['Thermodynamic Processes and Cycles', '<h3>Processes</h3><p>Isothermal (constant T), isobaric (constant P), isochoric (constant V), adiabatic (Q = 0). PV diagrams show work as the area under the curve.</p><p>The Carnot cycle (two isothermals + two adiabatics) gives maximum efficiency: η = 1 - T_cold/T_hot. Real engines always have lower efficiency.</p>'],
            ], 'quiz' => [
                'name' => 'Quiz: Laws of Thermodynamics',
                'questions' => [
                    ['The first law of thermodynamics is about conservation of:', ['Momentum', 'Energy', 'Charge', 'Mass'], 1],
                    ['The Carnot efficiency depends on:', ['Pressure only', 'Volume only', 'Temperature of reservoirs', 'Type of gas'], 2],
                    ['Entropy of an isolated system:', ['Always decreases', 'Never decreases', 'Stays constant', 'Oscillates'], 1],
                ],
            ], 'forum' => 'Discussion: Entropy and the arrow of time',
               'assign' => 'Problem Set 1: Thermodynamic cycles'],
            ['name' => 'Kinetic Theory & Ideal Gases', 'pages' => [
                ['Kinetic Theory of Gases', '<h3>Kinetic Theory</h3><p>The kinetic theory models a gas as a large number of particles in random motion. Key assumptions: particles are point-like, collisions are elastic, no inter-particle forces except during collisions.</p><p>Key results: PV = NkT, average kinetic energy per molecule = 3/2 kT, RMS speed v_rms = √(3kT/m). The Maxwell-Boltzmann distribution describes the spread of molecular speeds.</p>'],
                ['Equipartition Theorem', '<h3>Equipartition</h3><p>The equipartition theorem states that each quadratic degree of freedom contributes ½kT to the average energy. A monatomic gas (3 translational DOF): U = 3/2 NkT. A diatomic gas (3 translational + 2 rotational): U = 5/2 NkT at moderate temperatures.</p><p>At high temperatures, vibrational modes become active, adding a further kT per mode. This explains the temperature dependence of heat capacities.</p>'],
            ], 'quiz' => [
                'name' => 'Quiz: Kinetic Theory',
                'questions' => [
                    ['Average KE per molecule of an ideal gas:', ['kT', '3/2 kT', '2kT', '5/2 kT'], 1],
                    ['RMS speed depends on temperature as:', ['T', '√T', 'T²', '1/T'], 1],
                    ['For a monatomic ideal gas, Cv =', ['3/2 Nk', '5/2 Nk', '7/2 Nk', 'Nk'], 0],
                ],
            ], 'forum' => 'Discussion: Limitations of the ideal gas model',
               'assign' => 'Problem Set 2: Gas calculations'],
            ['name' => 'Statistical Mechanics', 'pages' => [
                ['Microstates, Macrostates & Boltzmann Entropy', '<h3>Statistical Mechanics</h3><p>A macrostate is defined by macroscopic variables (P, V, T). A microstate specifies the exact state of every particle. The number of microstates Ω for a macrostate determines its entropy: S = k ln Ω (Boltzmann\'s entropy formula).</p><p>The most probable macrostate has the most microstates. In the thermodynamic limit, fluctuations become negligible and the system is overwhelmingly likely to be in the most probable macrostate.</p>'],
                ['The Partition Function', '<h3>Partition Function</h3><p>The canonical partition function Z = Σ exp(-E_i/kT) encodes all thermodynamic information. From Z we can derive: Free energy F = -kT ln Z, internal energy U = -∂(ln Z)/∂β, entropy S = k ln Z + U/T.</p><p>For a system of N identical particles: Z_N = Z₁ᴺ/N! (including the Gibbs factor for indistinguishability). This connects microscopic physics to macroscopic thermodynamics.</p>'],
            ], 'quiz' => [
                'name' => 'Quiz: Statistical Mechanics',
                'questions' => [
                    ['Boltzmann\'s entropy formula is:', ['S = kT', 'S = k ln Ω', 'S = PV/T', 'S = nR'], 1],
                    ['The partition function Z is a sum over:', ['Energies', 'Boltzmann factors', 'Temperatures', 'Pressures'], 1],
                    ['Free energy F is derived from Z as:', ['F = kT/Z', 'F = -kT ln Z', 'F = Z/kT', 'F = k ln Z'], 1],
                ],
            ], 'forum' => 'Discussion: Entropy in information theory',
               'assign' => 'Problem Set 3: Partition function calculations'],
            ['name' => 'Quantum Statistics & Phase Transitions', 'pages' => [
                ['Fermi-Dirac and Bose-Einstein Statistics', '<h3>Quantum Statistics</h3><p>Fermions (half-integer spin) obey the Pauli exclusion principle and follow Fermi-Dirac statistics: f(E) = 1/(exp((E-μ)/kT) + 1). Examples: electrons, protons, neutrons.</p><p>Bosons (integer spin) can occupy the same state and follow Bose-Einstein statistics: f(E) = 1/(exp((E-μ)/kT) - 1). At low temperatures, bosons can undergo Bose-Einstein condensation into the ground state.</p>'],
                ['Phase Transitions', '<h3>Phase Transitions</h3><p>First-order transitions (e.g., melting, boiling) involve latent heat and a discontinuity in entropy. Second-order transitions (e.g., ferromagnetic to paramagnetic) have continuous entropy but discontinuous heat capacity.</p><p>The Ising model is a simple lattice model for ferromagnetism. Near the critical point, physical quantities show power-law behaviour characterised by critical exponents. Universality: systems with different microscopic details can share the same critical exponents.</p>'],
            ], 'quiz' => [
                'name' => 'Quiz: Quantum Statistics',
                'questions' => [
                    ['Electrons follow:', ['Maxwell-Boltzmann statistics', 'Bose-Einstein statistics', 'Fermi-Dirac statistics', 'Classical statistics'], 2],
                    ['Bose-Einstein condensation occurs at:', ['High temperatures', 'Very low temperatures', 'Room temperature', 'Any temperature'], 1],
                    ['A first-order phase transition involves:', ['No energy change', 'Latent heat', 'Zero entropy change', 'Infinite temperature'], 1],
                ],
            ], 'forum' => 'Discussion: Superfluidity and superconductivity',
               'assign' => 'Essay: Bose-Einstein condensation — history and applications'],
        ],
    ],
    [
        'shortname' => 'PHY301',
        'fullname' => 'Nuclear & Particle Physics',
        'category' => $catids['year3'],
        'summary' => 'A third-year module covering nuclear structure, radioactive decay, nuclear reactions, the Standard Model of particle physics, and fundamental interactions.',
        'sections' => [
            ['name' => 'Nuclear Structure', 'pages' => [
                ['The Nucleus: Protons, Neutrons & Binding Energy', '<h3>Nuclear Structure</h3><p>The nucleus contains Z protons and N neutrons (A = Z + N nucleons). Nuclear radius R ≈ R₀A^(1/3) with R₀ ≈ 1.2 fm.</p><p>The binding energy B(Z,N) is the energy required to disassemble the nucleus. The semi-empirical mass formula (Weizsäcker): B = a_v·A - a_s·A^(2/3) - a_c·Z(Z-1)/A^(1/3) - a_a·(N-Z)²/A + δ includes volume, surface, Coulomb, asymmetry, and pairing terms.</p>'],
                ['Nuclear Models', '<h3>Models of the Nucleus</h3><p>The shell model treats nucleons as independent particles in a potential well, explaining magic numbers (2, 8, 20, 28, 50, 82, 126) and nuclear spin.</p><p>The liquid drop model treats the nucleus as a drop of incompressible nuclear fluid. It successfully predicts binding energies and fission. The collective model combines both approaches for deformed nuclei and rotational/vibrational excitations.</p>'],
            ], 'quiz' => [
                'name' => 'Quiz: Nuclear Structure',
                'questions' => [
                    ['Nuclear radius scales with mass number as:', ['A', 'A^(1/2)', 'A^(1/3)', 'A²'], 2],
                    ['Magic numbers in nuclear physics are: 2, 8, 20, 28, 50, 82, and:', ['100', '126', '150', '200'], 1],
                    ['The semi-empirical mass formula includes how many terms?', ['Three', 'Four', 'Five', 'Six'], 2],
                ],
            ], 'forum' => 'Discussion: Stability of atomic nuclei',
               'assign' => 'Problem Set 1: Binding energy calculations'],
            ['name' => 'Radioactive Decay', 'pages' => [
                ['Alpha, Beta & Gamma Decay', '<h3>Radioactive Decay</h3><p><strong>Alpha decay:</strong> Emission of ⁴He nucleus; Z→Z-2, A→A-4. Explained by quantum tunnelling through the Coulomb barrier.</p><p><strong>Beta decay:</strong> β⁻ (n→p+e⁻+ν̄_e) or β⁺ (p→n+e⁺+ν_e). Mediated by the weak nuclear force. Continuous electron energy spectrum → evidence for neutrinos.</p><p><strong>Gamma decay:</strong> Emission of high-energy photons from excited nuclear states. No change in Z or A.</p>'],
                ['Decay Laws and Half-Life', '<h3>Decay Kinetics</h3><p>Radioactive decay law: N(t) = N₀ exp(-λt), where λ is the decay constant. Half-life t₁/₂ = ln(2)/λ. Activity A = λN, measured in Becquerels (1 Bq = 1 decay/s).</p><p>Decay chains: parent → daughter → granddaughter. Secular equilibrium occurs when the parent half-life is much longer than the daughter\'s. Carbon-14 dating uses t₁/₂ = 5730 years to date organic material.</p>'],
            ], 'quiz' => [
                'name' => 'Quiz: Radioactive Decay',
                'questions' => [
                    ['In alpha decay, the mass number changes by:', ['-1', '-2', '-4', '0'], 2],
                    ['Beta decay provides evidence for:', ['Photons', 'Gravitons', 'Neutrinos', 'Gluons'], 2],
                    ['Half-life is related to decay constant by:', ['t₁/₂ = λ', 't₁/₂ = 1/λ', 't₁/₂ = ln2/λ', 't₁/₂ = λ²'], 2],
                ],
            ], 'forum' => 'Discussion: Applications of radioisotopes in medicine',
               'assign' => 'Problem Set 2: Decay calculations'],
            ['name' => 'Nuclear Reactions & Applications', 'pages' => [
                ['Fission and Fusion', '<h3>Nuclear Reactions</h3><p><strong>Fission:</strong> Heavy nuclei split into lighter fragments, releasing energy. Chain reactions occur when neutrons from one fission event trigger others. Critical mass is the minimum amount of fissile material needed for a sustained chain reaction.</p><p><strong>Fusion:</strong> Light nuclei combine to form heavier ones, releasing energy (binding energy per nucleon increases). The pp chain and CNO cycle power main-sequence stars. Fusion requires extremely high temperatures to overcome Coulomb repulsion.</p>'],
                ['Nuclear Energy and Radiation Safety', '<h3>Applications</h3><p>Nuclear fission reactors use controlled chain reactions. Key components: fuel rods, moderator (slows neutrons), control rods (absorb neutrons), coolant, and containment structure.</p><p>Radiation dose: absorbed dose (Gray), equivalent dose (Sievert). Exposure limits, shielding (alpha: paper, beta: aluminium, gamma: lead), and the ALARA principle (As Low As Reasonably Achievable) guide radiation protection.</p>'],
            ], 'quiz' => [
                'name' => 'Quiz: Nuclear Reactions',
                'questions' => [
                    ['Fission of heavy nuclei releases energy because:', ['Mass increases', 'Binding energy per nucleon increases', 'Temperature decreases', 'Charge is created'], 1],
                    ['The Sun is powered primarily by:', ['Fission', 'Fusion', 'Chemical reactions', 'Gravitational energy alone'], 1],
                    ['The unit of equivalent radiation dose is:', ['Gray', 'Sievert', 'Becquerel', 'Curie'], 1],
                ],
            ], 'forum' => 'Discussion: The future of nuclear fusion energy',
               'assign' => 'Essay: Nuclear power — risks and benefits'],
            ['name' => 'The Standard Model', 'pages' => [
                ['Quarks, Leptons & Force Carriers', '<h3>The Standard Model</h3><p>Matter consists of quarks and leptons, each in three generations. Quarks: (u,d), (c,s), (t,b). Leptons: (e,ν_e), (μ,ν_μ), (τ,ν_τ).</p><p>Four fundamental forces and their carriers: electromagnetic (photon), strong (gluons), weak (W±, Z⁰), gravitational (graviton — not yet observed). The Higgs boson (discovered 2012) gives particles mass through the Higgs mechanism.</p>'],
                ['Conservation Laws and Particle Interactions', '<h3>Symmetries and Conservation</h3><p>Conserved quantities: energy, momentum, charge, baryon number, lepton number, colour charge. Each conservation law corresponds to a symmetry (Noether\'s theorem).</p><p>Feynman diagrams represent particle interactions graphically. Quark confinement: quarks are never observed in isolation. Hadrons: baryons (3 quarks) and mesons (quark-antiquark pairs). The strong force is mediated by gluons which carry colour charge.</p>'],
            ], 'quiz' => [
                'name' => 'Quiz: Standard Model',
                'questions' => [
                    ['How many generations of quarks are there?', ['One', 'Two', 'Three', 'Four'], 2],
                    ['The Higgs boson was discovered in:', ['1998', '2005', '2012', '2020'], 2],
                    ['The strong force is mediated by:', ['Photons', 'W bosons', 'Gluons', 'Gravitons'], 2],
                ],
            ], 'forum' => 'Discussion: Beyond the Standard Model',
               'assign' => 'Essay: The discovery of the Higgs boson'],
        ],
    ],
    [
        'shortname' => 'PHY302',
        'fullname' => 'Astrophysics & Cosmology',
        'category' => $catids['year3'],
        'summary' => 'A third-year module covering stellar structure and evolution, observational techniques, general relativity, the expanding universe, and modern cosmological models.',
        'sections' => [
            ['name' => 'Stellar Structure & Evolution', 'pages' => [
                ['The Hertzsprung-Russell Diagram', '<h3>Stellar Classification</h3><p>The HR diagram plots stellar luminosity against surface temperature (or spectral class). Most stars lie on the main sequence, where hydrogen fusion balances gravitational collapse.</p><p>Spectral classes: O, B, A, F, G, K, M (Oh Be A Fine Girl/Guy, Kiss Me). The Sun is a G2V star. Red giants, white dwarfs, and supergiants occupy distinct regions of the HR diagram.</p>'],
                ['Stellar Evolution and End States', '<h3>Life Cycle of Stars</h3><p>Low-mass stars (< 8 M☉): main sequence → red giant → planetary nebula → white dwarf. Supported by electron degeneracy pressure. The Chandrasekhar limit (1.4 M☉) is the maximum mass for a white dwarf.</p><p>High-mass stars: main sequence → supergiant → supernova → neutron star or black hole. Neutron stars are supported by neutron degeneracy pressure. Black holes form when mass exceeds the Tolman-Oppenheimer-Volkoff limit (~2-3 M☉).</p>'],
            ], 'quiz' => [
                'name' => 'Quiz: Stellar Physics',
                'questions' => [
                    ['The HR diagram plots luminosity against:', ['Mass', 'Temperature', 'Age', 'Distance'], 1],
                    ['The Chandrasekhar limit is approximately:', ['0.5 M☉', '1.4 M☉', '3.0 M☉', '10 M☉'], 1],
                    ['What is the Sun\'s spectral class?', ['A', 'F', 'G', 'K'], 2],
                ],
            ], 'forum' => 'Discussion: The life and death of stars',
               'assign' => 'Problem Set 1: Stellar luminosity and temperature'],
            ['name' => 'General Relativity & Black Holes', 'pages' => [
                ['Einstein\'s General Theory of Relativity', '<h3>General Relativity</h3><p>Einstein\'s general relativity (1915) describes gravity as the curvature of spacetime caused by mass-energy. The Einstein field equations: G_μν = 8πG/c⁴ · T_μν relate spacetime geometry to the energy-momentum tensor.</p><p>Key predictions: gravitational time dilation, light bending near massive objects (confirmed 1919 eclipse), gravitational redshift, and gravitational waves (detected by LIGO in 2015).</p>'],
                ['Black Holes', '<h3>Black Holes</h3><p>A black hole forms when matter collapses within its Schwarzschild radius: r_s = 2GM/c². The event horizon is the boundary beyond which nothing can escape.</p><p>Types: stellar-mass (few to ~100 M☉), intermediate-mass, and supermassive (10⁶-10¹⁰ M☉, found at galactic centres). Hawking radiation predicts black holes slowly evaporate due to quantum effects near the event horizon.</p>'],
            ], 'quiz' => [
                'name' => 'Quiz: General Relativity',
                'questions' => [
                    ['General relativity was published in:', ['1905', '1915', '1925', '1935'], 1],
                    ['Gravitational waves were first detected by:', ['Hubble', 'CERN', 'LIGO', 'Planck satellite'], 2],
                    ['The Schwarzschild radius defines:', ['Star size', 'Event horizon', 'Orbital radius', 'Galaxy size'], 1],
                ],
            ], 'forum' => 'Discussion: The first image of a black hole (M87)',
               'assign' => 'Essay: Gravitational waves — a new window on the universe'],
            ['name' => 'Cosmology: The Expanding Universe', 'pages' => [
                ['Hubble\'s Law and the Big Bang', '<h3>The Expanding Universe</h3><p>Edwin Hubble (1929) discovered that galaxies are receding with velocity proportional to distance: v = H₀d (Hubble\'s Law). Current value H₀ ≈ 70 km/s/Mpc.</p><p>Extrapolating backwards implies a hot, dense origin — the Big Bang, approximately 13.8 billion years ago. Evidence: Hubble\'s law, cosmic microwave background (CMB), primordial nucleosynthesis (abundance of H, He, Li).</p>'],
                ['The Cosmic Microwave Background', '<h3>CMB</h3><p>The CMB radiation (discovered 1964 by Penzias and Wilson) is the thermal afterglow of the Big Bang, now cooled to 2.725 K. It is nearly uniform in all directions, with tiny anisotropies (ΔT/T ~ 10⁻⁵) that seeded structure formation.</p><p>The power spectrum of CMB anisotropies reveals the composition of the universe: ~5% ordinary matter, ~27% dark matter, ~68% dark energy. The geometry is flat (Ω_total ≈ 1).</p>'],
            ], 'quiz' => [
                'name' => 'Quiz: Cosmology',
                'questions' => [
                    ['Hubble\'s constant is approximately:', ['7 km/s/Mpc', '70 km/s/Mpc', '700 km/s/Mpc', '7000 km/s/Mpc'], 1],
                    ['The CMB temperature is approximately:', ['0.27 K', '2.725 K', '27.25 K', '272.5 K'], 1],
                    ['The age of the universe is approximately:', ['4.6 billion years', '10 billion years', '13.8 billion years', '20 billion years'], 2],
                ],
            ], 'forum' => 'Discussion: Evidence for the Big Bang',
               'assign' => 'Problem Set: Hubble\'s Law calculations'],
            ['name' => 'Dark Matter, Dark Energy & the Future', 'pages' => [
                ['Dark Matter', '<h3>Dark Matter</h3><p>Galaxy rotation curves show that visible matter alone cannot account for observed orbital velocities — an invisible component (dark matter) provides additional gravitational attraction.</p><p>Evidence: galaxy rotation curves, gravitational lensing, CMB anisotropies, galaxy cluster dynamics. Candidates include WIMPs (Weakly Interacting Massive Particles) and axions. Direct detection experiments (e.g., LUX, XENON) have yet to find dark matter particles.</p>'],
                ['Dark Energy and the Fate of the Universe', '<h3>Dark Energy</h3><p>In 1998, observations of Type Ia supernovae revealed that the expansion of the universe is accelerating. This requires a component with negative pressure — dark energy, comprising ~68% of the universe\'s energy.</p><p>The cosmological constant Λ (Einstein\'s "biggest blunder") is the simplest model for dark energy. Possible fates of the universe: continued acceleration (Big Freeze), deceleration and collapse (Big Crunch), or increasing acceleration tearing spacetime apart (Big Rip).</p>'],
            ], 'quiz' => [
                'name' => 'Quiz: Dark Universe',
                'questions' => [
                    ['Evidence for dark matter includes:', ['Galaxy colours', 'Galaxy rotation curves', 'Star brightness', 'Planet orbits'], 1],
                    ['Dark energy was discovered through observations of:', ['Quasars', 'Pulsars', 'Type Ia supernovae', 'Neutron stars'], 2],
                    ['Dark energy comprises approximately what percentage of the universe?', ['5%', '27%', '50%', '68%'], 3],
                ],
            ], 'forum' => 'Discussion: What is dark energy?',
               'assign' => 'Essay: The fate of the universe'],
        ],
    ],
];

// Function to create a course module
function create_course_module($courseid, $modulename, $instanceid, $sectionnum, $visible = 1) {
    global $DB;

    $moduleid = $DB->get_field('modules', 'id', ['name' => $modulename]);
    if (!$moduleid) {
        echo "  ERROR: Module $modulename not found!\n";
        return false;
    }

    $cm = new stdClass();
    $cm->course = $courseid;
    $cm->module = $moduleid;
    $cm->instance = $instanceid;
    $cm->section = 0; // Will update
    $cm->visible = $visible;
    $cm->visibleoncoursepage = 1;
    $cm->added = time();
    $cm->completion = 2; // Require manual or automatic
    $cmid = $DB->insert_record('course_modules', $cm);

    // Add to section
    $section = $DB->get_record('course_sections', ['course' => $courseid, 'section' => $sectionnum]);
    if ($section) {
        $sequence = trim($section->sequence);
        $section->sequence = $sequence ? $sequence . ',' . $cmid : (string)$cmid;
        $DB->update_record('course_sections', $section);
        $DB->set_field('course_modules', 'section', $section->id, ['id' => $cmid]);
    }

    context_module::instance($cmid);
    return $cmid;
}

// Function to create a quiz with questions
function create_quiz_with_questions($courseid, $sectionnum, $quizname, $questions) {
    global $DB, $CFG;

    // Create quiz instance
    $quiz = new stdClass();
    $quiz->course = $courseid;
    $quiz->name = $quizname;
    $quiz->intro = "<p>Answer the following questions to test your understanding.</p>";
    $quiz->introformat = 1;
    $quiz->timeopen = 0;
    $quiz->timeclose = 0;
    $quiz->timelimit = 0;
    $quiz->preferredbehaviour = 'deferredfeedback';
    $quiz->attempts = 0;
    $quiz->grademethod = 1; // Highest grade
    $quiz->grade = 100;
    $quiz->sumgrades = count($questions) * 1.0;
    $quiz->decimalpoints = 2;
    $quiz->questiondecimalpoints = -1;
    $quiz->shuffleanswers = 1;
    $quiz->timecreated = time();
    $quiz->timemodified = time();
    $quiz->reviewattempt = 69904;
    $quiz->reviewcorrectness = 69904;
    $quiz->reviewmaxmarks = 69904;
    $quiz->reviewmarks = 69904;
    $quiz->reviewspecificfeedback = 69904;
    $quiz->reviewgeneralfeedback = 69904;
    $quiz->reviewrightanswer = 69904;
    $quiz->reviewoverallfeedback = 69904;
    $quizid = $DB->insert_record('quiz', $quiz);

    // Grade item
    $gradeitem = new stdClass();
    $gradeitem->courseid = $courseid;
    $gradeitem->itemtype = 'mod';
    $gradeitem->itemmodule = 'quiz';
    $gradeitem->iteminstance = $quizid;
    $gradeitem->itemname = $quizname;
    $gradeitem->grademax = 100;
    $gradeitem->grademin = 0;
    $gradeitem->timecreated = time();
    $gradeitem->timemodified = time();
    $gradeitem->gradetype = 1;
    $DB->insert_record('grade_items', $gradeitem);

    $cmid = create_course_module($courseid, 'quiz', $quizid, $sectionnum);

    // Get or create question category for this course context
    $coursecontext = context_course::instance($courseid);
    $qcat = $DB->get_record('question_categories', [
        'contextid' => $coursecontext->id,
        'name' => 'Default for ' . $DB->get_field('course', 'shortname', ['id' => $courseid]),
    ]);
    if (!$qcat) {
        $qcat = new stdClass();
        $qcat->name = 'Default for ' . $DB->get_field('course', 'shortname', ['id' => $courseid]);
        $qcat->contextid = $coursecontext->id;
        $qcat->info = 'Default question category';
        $qcat->infoformat = 0;
        $qcat->stamp = make_unique_id_code();
        $qcat->parent = 0;
        $qcat->sortorder = 999;
        $qcat->idnumber = null;
        $qcatid = $DB->insert_record('question_categories', $qcat);
        $qcat->id = $qcatid;
    }

    // Create questions
    $slot = 1;
    foreach ($questions as $q) {
        $qtext = $q[0];
        $answers = $q[1];
        $correctidx = $q[2];

        // Create question in question_bank_entries -> question_versions -> question
        // Moodle 4+ uses question bank entries
        $qbe = new stdClass();
        $qbe->questioncategoryid = $qcat->id;
        $qbe->idnumber = null;
        $qbe->ownerid = 2; // admin
        $qbeid = $DB->insert_record('question_bank_entries', $qbe);

        // Question record
        $question = new stdClass();
        $question->name = substr($qtext, 0, 200);
        $question->questiontext = '<p>' . $qtext . '</p>';
        $question->questiontextformat = 1;
        $question->generalfeedback = '';
        $question->generalfeedbackformat = 1;
        $question->defaultmark = 1.0000000;
        $question->penalty = 0.3333333;
        $question->qtype = 'multichoice';
        $question->length = 1;
        $question->stamp = make_unique_id_code();
        $question->timecreated = time();
        $question->timemodified = time();
        $question->createdby = 2;
        $question->modifiedby = 2;
        $question->status = 'ready';
        $questionid = $DB->insert_record('question', $question);

        // Question version
        $qv = new stdClass();
        $qv->questionbankentryid = $qbeid;
        $qv->version = 1;
        $qv->questionid = $questionid;
        $qv->status = 'ready';
        $DB->insert_record('question_versions', $qv);

        // Multichoice options
        $mc = new stdClass();
        $mc->questionid = $questionid;
        $mc->layout = 0;
        $mc->single = 1;
        $mc->shuffleanswers = 1;
        $mc->correctfeedback = 'Correct!';
        $mc->correctfeedbackformat = 1;
        $mc->partiallycorrectfeedback = 'Partially correct.';
        $mc->partiallycorrectfeedbackformat = 1;
        $mc->incorrectfeedback = 'Incorrect.';
        $mc->incorrectfeedbackformat = 1;
        $mc->answernumbering = 'abc';
        $mc->shownumcorrect = 1;
        $mc->showstandardinstruction = 0;
        $DB->insert_record('qtype_multichoice_options', $mc);

        // Answer options
        foreach ($answers as $aidx => $atext) {
            $ans = new stdClass();
            $ans->question = $questionid;
            $ans->answer = $atext;
            $ans->answerformat = 1;
            $ans->fraction = ($aidx === $correctidx) ? 1.0000000 : 0.0000000;
            $ans->feedback = ($aidx === $correctidx) ? 'Correct!' : 'Incorrect.';
            $ans->feedbackformat = 1;
            $DB->insert_record('question_answers', $ans);
        }

        // Add question to quiz (quiz_slots)
        $quizslot = new stdClass();
        $quizslot->quizid = $quizid;
        $quizslot->slot = $slot;
        $quizslot->questionid = $questionid;
        $quizslot->page = 1;
        $quizslot->requireprevious = 0;
        $quizslot->maxmark = 1.0000000;

        // Check if quiz_slots has questionid or needs question reference
        $columns = $DB->get_columns('quiz_slots');
        if (isset($columns['questionid'])) {
            $quizslot->questionid = $questionid;
        }
        $DB->insert_record('quiz_slots', $quizslot);

        $slot++;
    }

    return [$quizid, $cmid];
}

// Process each course
$courseids = [];
$course_cmids = []; // courseid => [cmid1, cmid2, ...]

foreach ($courses as $cidx => $cdef) {
    echo "--- Course " . ($cidx + 1) . ": {$cdef['fullname']} ---\n";

    // Check if course already exists
    $existing = $DB->get_record('course', ['shortname' => $cdef['shortname']]);
    if ($existing) {
        echo "  Already exists (ID:{$existing->id}), skipping\n";
        $courseids[$cdef['shortname']] = $existing->id;
        continue;
    }

    // Create course
    $course = new stdClass();
    $course->fullname = $cdef['fullname'];
    $course->shortname = $cdef['shortname'];
    $course->category = $cdef['category'];
    $course->summary = $cdef['summary'];
    $course->summaryformat = 1;
    $course->format = 'topics';
    $course->numsections = count($cdef['sections']);
    $course->startdate = strtotime('2025-09-01');
    $course->enddate = strtotime('2026-06-30');
    $course->visible = 1;
    $course->enablecompletion = 1;
    $course->showgrades = 1;
    $course->timecreated = time();
    $course->timemodified = time();

    $newcourse = create_course($course);
    $courseid = $newcourse->id;
    $courseids[$cdef['shortname']] = $courseid;
    $course_cmids[$courseid] = [];
    echo "  Created course (ID:$courseid)\n";

    // Create sections and activities
    foreach ($cdef['sections'] as $sidx => $sec) {
        $sectionnum = $sidx + 1;

        // Update section name
        $section = $DB->get_record('course_sections', ['course' => $courseid, 'section' => $sectionnum]);
        if (!$section) {
            $section = new stdClass();
            $section->course = $courseid;
            $section->section = $sectionnum;
            $section->name = $sec['name'];
            $section->summary = '';
            $section->summaryformat = 1;
            $section->sequence = '';
            $section->visible = 1;
            $section->timemodified = time();
            $section->id = $DB->insert_record('course_sections', $section);
        } else {
            $DB->set_field('course_sections', 'name', $sec['name'], ['id' => $section->id]);
        }
        echo "  Section $sectionnum: {$sec['name']}\n";

        // Create pages
        foreach ($sec['pages'] as $page) {
            $pg = new stdClass();
            $pg->course = $courseid;
            $pg->name = $page[0];
            $pg->intro = '<p>Read the following material carefully.</p>';
            $pg->introformat = 1;
            $pg->content = $page[1];
            $pg->contentformat = 1;
            $pg->display = 5;
            $pg->timemodified = time();
            $pgid = $DB->insert_record('page', $pg);
            $cmid = create_course_module($courseid, 'page', $pgid, $sectionnum);
            $course_cmids[$courseid][] = $cmid;
            echo "    Page: {$page[0]} (cm:$cmid)\n";
        }

        // Create quiz
        list($quizid, $cmid) = create_quiz_with_questions($courseid, $sectionnum, $sec['quiz']['name'], $sec['quiz']['questions']);
        $course_cmids[$courseid][] = $cmid;
        echo "    Quiz: {$sec['quiz']['name']} (cm:$cmid)\n";

        // Create forum
        $forum = new stdClass();
        $forum->course = $courseid;
        $forum->type = 'general';
        $forum->name = $sec['forum'];
        $forum->intro = '<p>Share your thoughts and discuss with your peers.</p>';
        $forum->introformat = 1;
        $forum->timemodified = time();
        $forumid = $DB->insert_record('forum', $forum);
        $cmid = create_course_module($courseid, 'forum', $forumid, $sectionnum);
        $course_cmids[$courseid][] = $cmid;
        echo "    Forum: {$sec['forum']} (cm:$cmid)\n";

        // Create assignment
        $assign = new stdClass();
        $assign->course = $courseid;
        $assign->name = $sec['assign'];
        $assign->intro = '<p>Complete and submit your work for grading.</p>';
        $assign->introformat = 1;
        $assign->duedate = strtotime('2026-06-01');
        $assign->allowsubmissionsfromdate = strtotime('2025-09-01');
        $assign->grade = 100;
        $assign->timemodified = time();
        $assign->submissiondrafts = 0;
        $assign->requiresubmissionstatement = 0;
        $assign->sendnotifications = 0;
        $assign->sendlatenotifications = 0;
        $assign->sendstudentnotifications = 1;
        $assign->teamsubmission = 0;
        $assign->requireallteammemberssubmit = 0;
        $assign->blindmarking = 0;
        $assign->markingworkflow = 0;
        $assign->markingallocation = 0;
        $assignid = $DB->insert_record('assign', $assign);

        // Grade item for assignment
        $gradeitem = new stdClass();
        $gradeitem->courseid = $courseid;
        $gradeitem->itemtype = 'mod';
        $gradeitem->itemmodule = 'assign';
        $gradeitem->iteminstance = $assignid;
        $gradeitem->itemname = $sec['assign'];
        $gradeitem->grademax = 100;
        $gradeitem->grademin = 0;
        $gradeitem->timecreated = time();
        $gradeitem->timemodified = time();
        $gradeitem->gradetype = 1;
        $DB->insert_record('grade_items', $gradeitem);

        $cmid = create_course_module($courseid, 'assign', $assignid, $sectionnum);
        $course_cmids[$courseid][] = $cmid;
        echo "    Assignment: {$sec['assign']} (cm:$cmid)\n";
    }

    // Rebuild course cache
    rebuild_course_cache($courseid, true);
    echo "  Cache rebuilt\n\n";
}

// Save course IDs
$ids['courseids'] = $courseids;
$ids['course_cmids'] = $course_cmids;
file_put_contents('/tmp/moodle_setup_ids.json', json_encode($ids, JSON_PRETTY_PRINT));

echo "\n===== Course IDs =====\n";
foreach ($courseids as $short => $id) {
    echo "  $short => $id\n";
}
echo "\nPart 2 complete!\n";
