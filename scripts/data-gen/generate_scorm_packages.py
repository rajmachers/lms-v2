#!/usr/bin/env python3
"""
Generate 4 SCORM 1.2 packages with strict timed playback:
- No fast-forward, no skip, no TOC navigation
- Countdown timer per slide (Next button appears only after timer)
- Resume from last position via cmi.core.lesson_location
- Completion only after viewing ALL slides
"""

import zipfile
import os
import json

OUTPUT_DIR = os.path.dirname(os.path.abspath(__file__))

# ============================================================
# COURSE CONTENT DEFINITIONS
# ============================================================

COURSES = {
    "academic_integrity": {
        "title": "Academic Integrity & Plagiarism Prevention",
        "shortname": "COMP-AI01",
        "timer_seconds": 10,
        "slides": [
            {
                "title": "What is Academic Integrity?",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">🎓</div>
                    <h2>What is Academic Integrity?</h2>
                </div>
                <div class="content-card">
                    <p>Academic integrity is the commitment to <strong>honesty, trust, fairness, respect, responsibility, and courage</strong> in all academic work.</p>
                    <div class="highlight-box">
                        <h3>The Six Pillars</h3>
                        <div class="pillar-grid">
                            <div class="pillar"><span class="pillar-icon">🤝</span><strong>Honesty</strong><br>Truthful in all academic endeavours</div>
                            <div class="pillar"><span class="pillar-icon">🔒</span><strong>Trust</strong><br>Building confidence in academic community</div>
                            <div class="pillar"><span class="pillar-icon">⚖️</span><strong>Fairness</strong><br>Equal standards for all students</div>
                            <div class="pillar"><span class="pillar-icon">🙏</span><strong>Respect</strong><br>Valuing others' ideas and contributions</div>
                            <div class="pillar"><span class="pillar-icon">📋</span><strong>Responsibility</strong><br>Owning your academic work</div>
                            <div class="pillar"><span class="pillar-icon">💪</span><strong>Courage</strong><br>Acting with integrity even when difficult</div>
                        </div>
                    </div>
                    <p class="key-point">Academic integrity is the foundation of higher education. Without it, qualifications become meaningless and trust in academic institutions erodes.</p>
                </div>"""
            },
            {
                "title": "Types of Plagiarism",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">⚠️</div>
                    <h2>Types of Plagiarism</h2>
                </div>
                <div class="content-card">
                    <p>Plagiarism takes many forms — some obvious, some subtle. All are violations of academic integrity.</p>
                    <div class="type-list">
                        <div class="type-item danger"><span class="badge">Direct</span> Copying text word-for-word without quotation marks or attribution</div>
                        <div class="type-item danger"><span class="badge">Mosaic</span> Mixing copied phrases with your own words without citing</div>
                        <div class="type-item warning"><span class="badge">Paraphrase</span> Rewriting someone's ideas in your own words without citing the source</div>
                        <div class="type-item warning"><span class="badge">Self</span> Resubmitting your own previous work for a different assignment without permission</div>
                        <div class="type-item danger"><span class="badge">Contract</span> Having someone else write your work (essay mills, AI-generated submissions)</div>
                        <div class="type-item info"><span class="badge">Accidental</span> Forgetting to cite sources, poor note-taking, or misunderstanding referencing rules</div>
                    </div>
                    <p class="key-point">Regardless of intent, all forms of plagiarism carry consequences. "I didn't know" is not a defence.</p>
                </div>"""
            },
            {
                "title": "Paraphrasing vs Copying",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">✍️</div>
                    <h2>Paraphrasing vs Copying</h2>
                </div>
                <div class="content-card">
                    <h3>What Good Paraphrasing Looks Like</h3>
                    <div class="comparison-box">
                        <div class="compare-col bad">
                            <h4>❌ Poor Paraphrase (Plagiarism)</h4>
                            <blockquote>"Climate change is <u>one of the most pressing</u> challenges <u>facing humanity today</u>, requiring <u>urgent collective action</u>."</blockquote>
                            <p class="note">Just swapping a few words — still plagiarism!</p>
                        </div>
                        <div class="compare-col good">
                            <h4>✅ Good Paraphrase</h4>
                            <blockquote>"Addressing the global climate crisis demands immediate cooperation across nations and sectors (Smith, 2024)."</blockquote>
                            <p class="note">New sentence structure, own words, properly cited</p>
                        </div>
                    </div>
                    <div class="highlight-box">
                        <h3>The Paraphrasing Test</h3>
                        <ol>
                            <li>Read and understand the original passage</li>
                            <li><strong>Put the source away</strong> — do not look at it</li>
                            <li>Write the idea from memory in your own words</li>
                            <li>Compare with the original — is it genuinely different?</li>
                            <li>Add the proper citation</li>
                        </ol>
                    </div>
                </div>"""
            },
            {
                "title": "Referencing Basics",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">📚</div>
                    <h2>Referencing Basics</h2>
                </div>
                <div class="content-card">
                    <p>Every idea, fact, or argument taken from another source must be referenced — both <strong>in-text</strong> and in a <strong>reference list</strong>.</p>
                    <div class="ref-examples">
                        <div class="ref-style">
                            <h4>Harvard (Author-Date)</h4>
                            <p class="example">In-text: (Smith, 2024, p. 45)<br>
                            Reference: Smith, J. (2024) <em>Academic Writing</em>. London: Routledge.</p>
                        </div>
                        <div class="ref-style">
                            <h4>APA 7th Edition</h4>
                            <p class="example">In-text: (Smith, 2024)<br>
                            Reference: Smith, J. (2024). <em>Academic writing</em>. Routledge.</p>
                        </div>
                        <div class="ref-style">
                            <h4>Vancouver (Numbered)</h4>
                            <p class="example">In-text: [1] or (1)<br>
                            Reference: 1. Smith J. Academic Writing. London: Routledge; 2024.</p>
                        </div>
                    </div>
                    <p class="key-point">Always check which referencing style your department requires. Use reference management tools like Zotero, Mendeley, or EndNote to stay organised.</p>
                </div>"""
            },
            {
                "title": "Using Turnitin Effectively",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">🔍</div>
                    <h2>Using Turnitin Effectively</h2>
                </div>
                <div class="content-card">
                    <p>Turnitin is a <strong>text-matching tool</strong>, not a plagiarism detector. It highlights text that matches other sources — academic judgement determines if it's problematic.</p>
                    <div class="highlight-box">
                        <h3>Understanding Your Similarity Score</h3>
                        <div class="score-guide">
                            <div class="score green"><span>0–15%</span> Typically acceptable — common phrases, properly quoted text</div>
                            <div class="score yellow"><span>15–25%</span> Review carefully — check if matches are properly referenced</div>
                            <div class="score orange"><span>25–40%</span> Likely issues — significant text overlap needs attention</div>
                            <div class="score red"><span>40%+</span> Serious concern — substantial unoriginal content detected</div>
                        </div>
                    </div>
                    <div class="tip-box">
                        <h3>💡 Top Tips</h3>
                        <ul>
                            <li>Submit drafts early so you can review your similarity report</li>
                            <li>A low score doesn't guarantee good work — and a high score isn't always plagiarism</li>
                            <li>Properly quoted and referenced text is fine, even if highlighted</li>
                            <li>Turnitin now detects AI-generated content</li>
                        </ul>
                    </div>
                </div>"""
            },
            {
                "title": "Self-Plagiarism",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">🔄</div>
                    <h2>Self-Plagiarism</h2>
                </div>
                <div class="content-card">
                    <p>Submitting your own previously assessed work — in whole or part — for a different assignment is <strong>self-plagiarism</strong>, even though you wrote it.</p>
                    <div class="highlight-box">
                        <h3>Why It's a Problem</h3>
                        <ul>
                            <li>Each assignment assesses <strong>new learning</strong> — reusing work circumvents this</li>
                            <li>You may receive <strong>double credit</strong> for the same work</li>
                            <li>It's <strong>explicitly prohibited</strong> in most university regulations</li>
                        </ul>
                    </div>
                    <div class="scenario-box">
                        <h3>Common Scenarios</h3>
                        <div class="scenario">
                            <span class="scenario-label bad">❌ Not OK</span>
                            <p>Submitting the same essay to two different modules</p>
                        </div>
                        <div class="scenario">
                            <span class="scenario-label bad">❌ Not OK</span>
                            <p>Copying large sections from your Year 1 assignment into your Year 3 dissertation</p>
                        </div>
                        <div class="scenario">
                            <span class="scenario-label good">✅ OK</span>
                            <p>Building on previous ideas with <strong>new analysis, new sources, and proper self-citation</strong></p>
                        </div>
                    </div>
                    <p class="key-point">When in doubt, ask your tutor before submitting work that overlaps with previous submissions.</p>
                </div>"""
            },
            {
                "title": "Collusion vs Collaboration",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">👥</div>
                    <h2>Collusion vs Collaboration</h2>
                </div>
                <div class="content-card">
                    <div class="comparison-box">
                        <div class="compare-col good">
                            <h4>✅ Collaboration (Encouraged)</h4>
                            <ul>
                                <li>Discussing ideas and concepts in study groups</li>
                                <li>Sharing reading lists and resources</li>
                                <li>Peer review and feedback on drafts</li>
                                <li>Working together on <strong>designated group assignments</strong></li>
                                <li>Explaining concepts to each other</li>
                            </ul>
                        </div>
                        <div class="compare-col bad">
                            <h4>❌ Collusion (Misconduct)</h4>
                            <ul>
                                <li>Sharing your written answers for individual work</li>
                                <li>Allowing others to copy your code or text</li>
                                <li>Dividing up an individual assignment between friends</li>
                                <li>Using a shared answer template</li>
                                <li>Writing sections for someone else's work</li>
                            </ul>
                        </div>
                    </div>
                    <p class="key-point">The line is clear: for <strong>individual</strong> assignments, the final submitted work must be entirely your own. Discuss ideas, but write independently.</p>
                </div>"""
            },
            {
                "title": "Consequences of Academic Misconduct",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">⚖️</div>
                    <h2>Consequences of Academic Misconduct</h2>
                </div>
                <div class="content-card">
                    <p>Academic misconduct is taken extremely seriously. Penalties escalate with severity and repeat offences.</p>
                    <div class="penalty-scale">
                        <div class="penalty-level level1">
                            <h4>Level 1 — Minor First Offence</h4>
                            <p>Written warning, required to resubmit work (capped mark), mandatory academic integrity workshop</p>
                        </div>
                        <div class="penalty-level level2">
                            <h4>Level 2 — Significant or Repeat Offence</h4>
                            <p>Zero mark for the assignment, formal record on academic file, meeting with Academic Conduct Officer</p>
                        </div>
                        <div class="penalty-level level3">
                            <h4>Level 3 — Serious or Multiple Offences</h4>
                            <p>Zero mark for the entire module, academic probation, referred to Disciplinary Panel</p>
                        </div>
                        <div class="penalty-level level4">
                            <h4>Level 4 — Very Serious / Contract Cheating</h4>
                            <p>Expulsion from the university, permanent record, degree revocation (even retrospectively)</p>
                        </div>
                    </div>
                    <p class="key-point">A misconduct record can affect future employment references. It is never worth the risk.</p>
                </div>"""
            },
            {
                "title": "Case Studies",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">📋</div>
                    <h2>Case Studies</h2>
                </div>
                <div class="content-card">
                    <div class="case-study">
                        <h3>Case 1: "I Forgot to Reference"</h3>
                        <p>A student submitted a well-written essay but forgot to include four in-text citations. Turnitin flagged 28% similarity. The student claimed it was an honest mistake.</p>
                        <p class="outcome"><strong>Outcome:</strong> Level 1 penalty — resubmission with capped mark at 50%. Academic skills support recommended.</p>
                    </div>
                    <div class="case-study">
                        <h3>Case 2: "My Friend Helped Me"</h3>
                        <p>Two students submitted near-identical individual lab reports. Both claimed they "just studied together." Digital forensics showed identical file metadata.</p>
                        <p class="outcome"><strong>Outcome:</strong> Level 2 penalty for both students — zero marks for the assignment, formal record.</p>
                    </div>
                    <div class="case-study">
                        <h3>Case 3: "I Used an Essay Mill"</h3>
                        <p>A student purchased a dissertation from an online essay mill. Stylometric analysis revealed inconsistencies with previous writing samples.</p>
                        <p class="outcome"><strong>Outcome:</strong> Level 4 — expelled from the university. Degree not awarded.</p>
                    </div>
                </div>"""
            },
            {
                "title": "Your Integrity Pledge",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">✅</div>
                    <h2>Your Integrity Pledge</h2>
                </div>
                <div class="content-card">
                    <p>You have completed the Academic Integrity training. By progressing through this module, you understand and commit to the following:</p>
                    <div class="pledge-box">
                        <div class="pledge-item">✓ I will submit only my own original work for individual assessments</div>
                        <div class="pledge-item">✓ I will properly reference all sources using the required citation style</div>
                        <div class="pledge-item">✓ I will not share my completed work with other students for individual assignments</div>
                        <div class="pledge-item">✓ I will not use essay mills, contract cheating services, or submit AI-generated text as my own</div>
                        <div class="pledge-item">✓ I will seek help from my tutors if I am unsure about academic integrity</div>
                        <div class="pledge-item">✓ I understand the consequences of academic misconduct</div>
                    </div>
                    <div class="completion-message">
                        <h3>🎉 Module Complete</h3>
                        <p>Thank you for completing this mandatory training. Your commitment to academic integrity helps maintain the value and reputation of your degree.</p>
                        <p><strong>Remember:</strong> If you are ever unsure whether something constitutes misconduct, ask your tutor before submitting.</p>
                    </div>
                </div>"""
            }
        ]
    },
    "lab_safety": {
        "title": "Laboratory Safety Essentials",
        "shortname": "COMP-LS01",
        "timer_seconds": 10,
        "slides": [
            {
                "title": "Lab Rules Overview",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">🔬</div>
                    <h2>Laboratory Rules Overview</h2>
                </div>
                <div class="content-card">
                    <p>Laboratories contain hazards that can cause serious injury if safety rules are not followed. <strong>All personnel must complete this training before entering any laboratory.</strong></p>
                    <div class="highlight-box">
                        <h3>Fundamental Lab Rules</h3>
                        <div class="rule-grid">
                            <div class="rule"><span class="rule-num">1</span> Never work alone in the laboratory</div>
                            <div class="rule"><span class="rule-num">2</span> Wear appropriate PPE at all times</div>
                            <div class="rule"><span class="rule-num">3</span> No food, drink, or cosmetics in the lab</div>
                            <div class="rule"><span class="rule-num">4</span> Know the location of all safety equipment</div>
                            <div class="rule"><span class="rule-num">5</span> Read all protocols before starting work</div>
                            <div class="rule"><span class="rule-num">6</span> Report all incidents, spills, and near-misses</div>
                            <div class="rule"><span class="rule-num">7</span> Clean your workspace before leaving</div>
                            <div class="rule"><span class="rule-num">8</span> Wash hands thoroughly when leaving the lab</div>
                        </div>
                    </div>
                    <p class="key-point">These rules exist to protect you and your colleagues. Violations may result in immediate removal from the laboratory and academic penalties.</p>
                </div>"""
            },
            {
                "title": "Personal Protective Equipment",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">🥽</div>
                    <h2>Personal Protective Equipment (PPE)</h2>
                </div>
                <div class="content-card">
                    <p>PPE is your last line of defence against laboratory hazards. The required PPE depends on the risk assessment for each activity.</p>
                    <div class="ppe-grid">
                        <div class="ppe-item">
                            <div class="ppe-icon">🥼</div>
                            <h4>Lab Coat</h4>
                            <p>Cotton, buttoned up, long sleeves. Protects skin and clothing from splashes. <strong>Always required.</strong></p>
                        </div>
                        <div class="ppe-item">
                            <div class="ppe-icon">🥽</div>
                            <h4>Safety Goggles</h4>
                            <p>Splash-proof goggles (not safety glasses) when handling chemicals. Must meet EN166 standard.</p>
                        </div>
                        <div class="ppe-item">
                            <div class="ppe-icon">🧤</div>
                            <h4>Gloves</h4>
                            <p>Nitrile (general chemical), latex (biological), or specialist gloves. Check compatibility with chemicals used.</p>
                        </div>
                        <div class="ppe-item">
                            <div class="ppe-icon">👟</div>
                            <h4>Closed-Toe Shoes</h4>
                            <p>No sandals, flip-flops, or open-toe shoes. Leather preferred for chemical protection.</p>
                        </div>
                    </div>
                    <p class="key-point">Inspect your PPE before each use. Replace damaged items immediately. Remove gloves before touching shared equipment (door handles, phones).</p>
                </div>"""
            },
            {
                "title": "Chemical Hazard Symbols (GHS)",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">☠️</div>
                    <h2>Chemical Hazard Symbols (GHS)</h2>
                </div>
                <div class="content-card">
                    <p>The Globally Harmonized System (GHS) uses standardised red-bordered diamond pictograms on all chemical labels and Safety Data Sheets.</p>
                    <div class="ghs-grid">
                        <div class="ghs-item"><div class="ghs-symbol">🔥</div><h4>Flammable</h4><p>Catches fire easily. Keep away from ignition sources.</p></div>
                        <div class="ghs-item"><div class="ghs-symbol">⚗️</div><h4>Corrosive</h4><p>Destroys skin tissue and metals on contact.</p></div>
                        <div class="ghs-item"><div class="ghs-symbol">☠️</div><h4>Acute Toxicity</h4><p>Fatal or toxic if inhaled, ingested, or absorbed through skin.</p></div>
                        <div class="ghs-item"><div class="ghs-symbol">⚠️</div><h4>Irritant / Harmful</h4><p>May cause irritation, allergic response, or drowsiness.</p></div>
                        <div class="ghs-item"><div class="ghs-symbol">🫁</div><h4>Health Hazard</h4><p>May cause cancer, organ damage, or respiratory sensitisation.</p></div>
                        <div class="ghs-item"><div class="ghs-symbol">🌊</div><h4>Environmental</h4><p>Toxic to aquatic life. Prevent release to environment.</p></div>
                        <div class="ghs-item"><div class="ghs-symbol">💥</div><h4>Explosive</h4><p>May explode if heated, shocked, or exposed to friction.</p></div>
                        <div class="ghs-item"><div class="ghs-symbol">🅾️</div><h4>Oxidiser</h4><p>May cause or intensify fire. Keep away from combustibles.</p></div>
                        <div class="ghs-item"><div class="ghs-symbol">🫙</div><h4>Gas Under Pressure</h4><p>May explode if heated. Contains gas under pressure.</p></div>
                    </div>
                    <p class="key-point">Always read the label and Safety Data Sheet (SDS) before handling any chemical. SDS available in the lab safety folder and online.</p>
                </div>"""
            },
            {
                "title": "COSHH Assessment",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">📋</div>
                    <h2>COSHH Assessment</h2>
                </div>
                <div class="content-card">
                    <p><strong>COSHH</strong> = Control of Substances Hazardous to Health. UK law requires a written risk assessment before working with any hazardous substance.</p>
                    <div class="highlight-box">
                        <h3>COSHH Assessment Steps</h3>
                        <div class="steps-list">
                            <div class="step"><span class="step-num">1</span><strong>Identify the hazard</strong> — What substance? Check SDS for hazard classification</div>
                            <div class="step"><span class="step-num">2</span><strong>Assess the risk</strong> — Who is exposed? How? For how long? What concentration?</div>
                            <div class="step"><span class="step-num">3</span><strong>Control the risk</strong> — Elimination → Substitution → Engineering controls → PPE (hierarchy of control)</div>
                            <div class="step"><span class="step-num">4</span><strong>Record your assessment</strong> — Written COSHH form, signed by supervisor</div>
                            <div class="step"><span class="step-num">5</span><strong>Review regularly</strong> — When procedure changes, after an incident, or annually</div>
                        </div>
                    </div>
                    <div class="warning-box">
                        <p>⚠️ <strong>You must not begin any experiment until a COSHH assessment has been completed and approved by your supervisor.</strong></p>
                    </div>
                </div>"""
            },
            {
                "title": "Fire Safety & Extinguishers",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">🧯</div>
                    <h2>Fire Safety & Extinguishers</h2>
                </div>
                <div class="content-card">
                    <p>Know the fire procedures <strong>before</strong> you need them. In case of fire: <strong>RACE</strong> — Rescue, Alarm, Contain, Evacuate.</p>
                    <div class="extinguisher-table">
                        <h3>Fire Extinguisher Types</h3>
                        <div class="ext-row header"><span>Type</span><span>Colour</span><span>Use For</span><span>Never Use On</span></div>
                        <div class="ext-row"><span><strong>Water</strong></span><span class="colour red">Red</span><span>Paper, wood, textiles (Class A)</span><span>Electrical, chemical, oil fires</span></div>
                        <div class="ext-row"><span><strong>CO₂</strong></span><span class="colour black">Black</span><span>Electrical equipment, flammable liquids</span><span>Enclosed spaces (asphyxiation risk)</span></div>
                        <div class="ext-row"><span><strong>Dry Powder</strong></span><span class="colour blue">Blue</span><span>Multi-purpose: A, B, C, electrical</span><span>Enclosed spaces, sensitive equipment</span></div>
                        <div class="ext-row"><span><strong>Foam</strong></span><span class="colour cream">Cream</span><span>Flammable liquids (Class B)</span><span>Electrical fires, cooking oil</span></div>
                    </div>
                    <p class="key-point">Know where the nearest fire extinguisher, fire blanket, fire alarm call point, and emergency exit are in your lab. Only attempt to fight small fires — evacuate if in doubt.</p>
                </div>"""
            },
            {
                "title": "Spill Procedures",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">🧪</div>
                    <h2>Spill Procedures</h2>
                </div>
                <div class="content-card">
                    <p>Chemical spills can occur even with careful handling. Your response depends on the substance and scale.</p>
                    <div class="highlight-box">
                        <h3>Small Spill (< 100 mL, low hazard)</h3>
                        <ol>
                            <li>Alert nearby colleagues</li>
                            <li>Wear appropriate PPE (gloves, goggles, lab coat)</li>
                            <li>Contain the spill with absorbent material from the spill kit</li>
                            <li>Neutralise if appropriate (acid → sodium bicarbonate, base → citric acid)</li>
                            <li>Collect waste into labelled container for disposal</li>
                            <li>Clean the area and report the incident</li>
                        </ol>
                    </div>
                    <div class="warning-box">
                        <h3>Large Spill or Highly Hazardous Substance</h3>
                        <ol>
                            <li><strong>Evacuate</strong> the immediate area</li>
                            <li><strong>Alert</strong> others — close doors but do not lock</li>
                            <li><strong>Call</strong> the emergency number (displayed on lab wall)</li>
                            <li><strong>Do NOT</strong> attempt to clean up yourself</li>
                            <li>Wait for trained hazmat personnel</li>
                        </ol>
                    </div>
                    <p class="key-point">If a chemical contacts your skin or eyes, use the emergency shower or eyewash station immediately for at least 15 minutes.</p>
                </div>"""
            },
            {
                "title": "Electrical Safety",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">⚡</div>
                    <h2>Electrical Safety</h2>
                </div>
                <div class="content-card">
                    <p>Electrical hazards in laboratories include shock, burns, and fire from faulty equipment, water ingress, or overloaded circuits.</p>
                    <div class="highlight-box">
                        <h3>Key Electrical Safety Rules</h3>
                        <ul>
                            <li>All equipment must be <strong>PAT tested</strong> (Portable Appliance Testing) — check the label</li>
                            <li>Never use equipment with damaged cables, plugs, or exposed wires</li>
                            <li>Keep electrical equipment <strong>away from water</strong> and wet surfaces</li>
                            <li>Do not daisy-chain extension leads or overload sockets</li>
                            <li>Switch off and unplug equipment before servicing or moving</li>
                            <li>Report any electrical faults immediately — <strong>do not attempt repairs yourself</strong></li>
                        </ul>
                    </div>
                    <div class="tip-box">
                        <h3>💡 If Someone Receives an Electric Shock</h3>
                        <ol>
                            <li>Do NOT touch the person while they are in contact with the source</li>
                            <li>Switch off the power source at the mains if safe to do so</li>
                            <li>Call emergency services immediately</li>
                            <li>If the person is unresponsive, begin CPR if trained</li>
                        </ol>
                    </div>
                </div>"""
            },
            {
                "title": "Sharps & Biological Waste",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">🗑️</div>
                    <h2>Sharps & Biological Waste</h2>
                </div>
                <div class="content-card">
                    <p>Incorrect disposal of sharps and biological materials poses serious health risks from needlestick injuries and infection.</p>
                    <div class="waste-guide">
                        <div class="waste-type">
                            <h4 class="yellow-waste">🟡 Sharps Container (Yellow)</h4>
                            <p>Needles, syringes with needles, scalpel blades, broken glass contaminated with biological material. <strong>Never recap needles. Never overfill beyond the line.</strong></p>
                        </div>
                        <div class="waste-type">
                            <h4 class="orange-waste">🟠 Clinical Waste (Orange Bag)</h4>
                            <p>Gloves, swabs, contaminated PPE, culture plates, biological samples. Must be autoclaved before collection.</p>
                        </div>
                        <div class="waste-type">
                            <h4 class="black-waste">⚫ General Lab Waste (Black Bag)</h4>
                            <p>Paper, non-contaminated packaging, clean disposables. Standard waste stream.</p>
                        </div>
                    </div>
                    <div class="warning-box">
                        <p>⚠️ <strong>Needlestick Injury:</strong> Allow wound to bleed freely, wash with soap and water, report immediately, attend Occupational Health within 1 hour for risk assessment and possible post-exposure prophylaxis.</p>
                    </div>
                </div>"""
            },
            {
                "title": "Emergency Protocols",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">🚨</div>
                    <h2>Emergency Protocols</h2>
                </div>
                <div class="content-card">
                    <div class="emergency-grid">
                        <div class="emergency-item">
                            <h4>🔥 Fire</h4>
                            <p>Activate alarm → Evacuate via nearest exit → Assemble at designated point → Do not re-enter → Call 999 if needed</p>
                        </div>
                        <div class="emergency-item">
                            <h4>🧪 Chemical Exposure</h4>
                            <p>Remove contaminated clothing → Flush skin/eyes with water (15 min) → Call First Aider → Bring SDS to medical responders</p>
                        </div>
                        <div class="emergency-item">
                            <h4>🤕 Injury</h4>
                            <p>Call First Aider → Apply first aid if trained → Call 999 for serious injuries → Complete accident report form</p>
                        </div>
                        <div class="emergency-item">
                            <h4>💨 Gas Leak</h4>
                            <p>Do not operate switches → Open windows if safe → Evacuate → Call emergency number → Report to Estates</p>
                        </div>
                    </div>
                    <div class="highlight-box">
                        <h3>Know Your Lab's Emergency Equipment Locations</h3>
                        <p>✓ Fire extinguisher &nbsp; ✓ Fire blanket &nbsp; ✓ Emergency shower &nbsp; ✓ Eyewash station &nbsp; ✓ First aid kit &nbsp; ✓ Spill kit &nbsp; ✓ Fire alarm call point &nbsp; ✓ Emergency exit routes</p>
                    </div>
                </div>"""
            },
            {
                "title": "Fume Cupboard Use",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">💨</div>
                    <h2>Fume Cupboard Use</h2>
                </div>
                <div class="content-card">
                    <p>Fume cupboards protect you from inhaling hazardous vapours, gases, and particulates. They must be used whenever working with volatile, toxic, or odorous substances.</p>
                    <div class="highlight-box">
                        <h3>Correct Fume Cupboard Operation</h3>
                        <ol>
                            <li><strong>Check airflow</strong> before use — look for the airflow indicator or tissue test</li>
                            <li>Keep the <strong>sash as low as possible</strong> during work (ideally below the marked working height)</li>
                            <li>Place apparatus <strong>at least 15 cm inside</strong> the cupboard, not at the front edge</li>
                            <li><strong>Do not block</strong> the rear or side baffles (air vents)</li>
                            <li><strong>Do not store chemicals</strong> inside the fume cupboard permanently — it reduces airflow</li>
                            <li>Close the sash fully when not in use or when leaving the lab</li>
                        </ol>
                    </div>
                    <div class="warning-box">
                        <p>⚠️ If the fume cupboard alarm sounds (low airflow), stop work immediately, close the sash, and report to the laboratory manager. Do not continue using a malfunctioning fume cupboard.</p>
                    </div>
                </div>"""
            },
            {
                "title": "Risk Assessment",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">📊</div>
                    <h2>Risk Assessment</h2>
                </div>
                <div class="content-card">
                    <p>A risk assessment is a <strong>legal requirement</strong> (Health and Safety at Work Act 1974, Management of Health and Safety at Work Regulations 1999) for all laboratory activities.</p>
                    <div class="highlight-box">
                        <h3>Five Steps to Risk Assessment</h3>
                        <div class="steps-list">
                            <div class="step"><span class="step-num">1</span><strong>Identify hazards</strong> — What could cause harm? (chemicals, equipment, procedures)</div>
                            <div class="step"><span class="step-num">2</span><strong>Identify who might be harmed</strong> — Students, staff, cleaners, visitors?</div>
                            <div class="step"><span class="step-num">3</span><strong>Evaluate risks and controls</strong> — Likelihood × Severity = Risk level. Apply hierarchy of controls.</div>
                            <div class="step"><span class="step-num">4</span><strong>Record findings</strong> — Written document, signed by assessor and supervisor</div>
                            <div class="step"><span class="step-num">5</span><strong>Review regularly</strong> — After incidents, changes to procedure, or annually</div>
                        </div>
                    </div>
                    <div class="risk-matrix">
                        <h3>Risk Matrix</h3>
                        <table>
                            <tr><th></th><th>Low Impact</th><th>Medium Impact</th><th>High Impact</th></tr>
                            <tr><th>High Likelihood</th><td class="medium-risk">Medium</td><td class="high-risk">High</td><td class="high-risk">Very High</td></tr>
                            <tr><th>Medium Likelihood</th><td class="low-risk">Low</td><td class="medium-risk">Medium</td><td class="high-risk">High</td></tr>
                            <tr><th>Low Likelihood</th><td class="low-risk">Very Low</td><td class="low-risk">Low</td><td class="medium-risk">Medium</td></tr>
                        </table>
                    </div>
                </div>"""
            },
            {
                "title": "Module Complete — Safety Commitment",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">✅</div>
                    <h2>Laboratory Safety — Module Complete</h2>
                </div>
                <div class="content-card">
                    <p>You have completed the Laboratory Safety Essentials training. You now understand:</p>
                    <div class="summary-grid">
                        <div class="summary-item">✓ Fundamental laboratory rules and PPE requirements</div>
                        <div class="summary-item">✓ GHS hazard symbols and COSHH assessments</div>
                        <div class="summary-item">✓ Fire safety, spill procedures, and emergency protocols</div>
                        <div class="summary-item">✓ Electrical safety, sharps disposal, and fume cupboard use</div>
                        <div class="summary-item">✓ Risk assessment process and documentation</div>
                    </div>
                    <div class="pledge-box">
                        <h3>Safety Commitment</h3>
                        <div class="pledge-item">✓ I will always wear appropriate PPE in the laboratory</div>
                        <div class="pledge-item">✓ I will complete risk assessments before starting any experiment</div>
                        <div class="pledge-item">✓ I will report all incidents, spills, and near-misses</div>
                        <div class="pledge-item">✓ I understand my responsibility for my own safety and that of others</div>
                    </div>
                    <div class="completion-message">
                        <h3>🎉 Training Complete</h3>
                        <p>You are now authorised to enter and work in university laboratories, subject to specific lab inductions for each facility.</p>
                    </div>
                </div>"""
            }
        ]
    },
    "info_security": {
        "title": "Information Security & GDPR Compliance",
        "shortname": "COMP-IS01",
        "timer_seconds": 10,
        "slides": [
            {
                "title": "What is GDPR?",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">🔒</div>
                    <h2>What is GDPR?</h2>
                </div>
                <div class="content-card">
                    <p>The <strong>General Data Protection Regulation</strong> (UK GDPR / Data Protection Act 2018) is the law governing how organisations collect, store, and use personal data.</p>
                    <div class="highlight-box">
                        <h3>The 7 Key Principles</h3>
                        <div class="principle-list">
                            <div class="principle"><span class="num">1</span><strong>Lawfulness, fairness & transparency</strong> — Process data legally, fairly, and openly</div>
                            <div class="principle"><span class="num">2</span><strong>Purpose limitation</strong> — Collect for specified, explicit purposes only</div>
                            <div class="principle"><span class="num">3</span><strong>Data minimisation</strong> — Only collect what is necessary</div>
                            <div class="principle"><span class="num">4</span><strong>Accuracy</strong> — Keep data accurate and up to date</div>
                            <div class="principle"><span class="num">5</span><strong>Storage limitation</strong> — Do not keep data longer than needed</div>
                            <div class="principle"><span class="num">6</span><strong>Integrity & confidentiality</strong> — Protect against unauthorised access</div>
                            <div class="principle"><span class="num">7</span><strong>Accountability</strong> — Demonstrate compliance</div>
                        </div>
                    </div>
                    <p class="key-point">GDPR applies to everyone who handles personal data — not just IT staff. As a student or staff member, you have responsibilities too.</p>
                </div>"""
            },
            {
                "title": "Personal vs Sensitive Data",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">📂</div>
                    <h2>Personal vs Sensitive Data</h2>
                </div>
                <div class="content-card">
                    <div class="comparison-box">
                        <div class="compare-col">
                            <h4>Personal Data</h4>
                            <p>Any information that can identify a living individual:</p>
                            <ul>
                                <li>Name, address, email, phone number</li>
                                <li>Student ID number</li>
                                <li>IP address, device identifiers</li>
                                <li>Photos, CCTV footage</li>
                                <li>Location data</li>
                            </ul>
                        </div>
                        <div class="compare-col">
                            <h4>Special Category (Sensitive) Data</h4>
                            <p>Requires additional protection and an explicit lawful basis:</p>
                            <ul>
                                <li>Health and medical records</li>
                                <li>Racial or ethnic origin</li>
                                <li>Religious or philosophical beliefs</li>
                                <li>Sexual orientation</li>
                                <li>Biometric data (fingerprints, facial recognition)</li>
                                <li>Trade union membership</li>
                                <li>Genetic data</li>
                            </ul>
                        </div>
                    </div>
                    <p class="key-point">If you handle research data containing personal information, it must be anonymised or pseudonymised wherever possible. Seek guidance from the Data Protection Officer.</p>
                </div>"""
            },
            {
                "title": "Lawful Basis for Processing",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">⚖️</div>
                    <h2>Lawful Basis for Processing</h2>
                </div>
                <div class="content-card">
                    <p>Every processing activity must have a <strong>lawful basis</strong> under Article 6 of GDPR. You cannot process personal data "just because."</p>
                    <div class="basis-list">
                        <div class="basis-item"><span class="basis-label">Consent</span> The individual has given clear, affirmative consent for specific purposes. Can be withdrawn at any time.</div>
                        <div class="basis-item"><span class="basis-label">Contract</span> Processing is necessary to fulfil a contract (e.g., student enrollment agreement).</div>
                        <div class="basis-item"><span class="basis-label">Legal Obligation</span> Processing is required by law (e.g., tax records, safeguarding reporting).</div>
                        <div class="basis-item"><span class="basis-label">Vital Interests</span> Processing is necessary to protect someone's life (emergency situations).</div>
                        <div class="basis-item"><span class="basis-label">Public Task</span> Processing is necessary for a task in the public interest (common in universities for teaching/research).</div>
                        <div class="basis-item"><span class="basis-label">Legitimate Interests</span> Processing is necessary for legitimate purposes, balanced against individual rights.</div>
                    </div>
                    <p class="key-point">For research involving personal data, consent is most commonly used. Ensure your ethics application specifies the lawful basis.</p>
                </div>"""
            },
            {
                "title": "Data Subject Rights",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">👤</div>
                    <h2>Data Subject Rights</h2>
                </div>
                <div class="content-card">
                    <p>Under GDPR, individuals have the following rights regarding their personal data:</p>
                    <div class="rights-grid">
                        <div class="right-item"><h4>Right to be Informed</h4><p>Know what data is collected, why, and how it will be used (privacy notices)</p></div>
                        <div class="right-item"><h4>Right of Access</h4><p>Request a copy of all personal data held (Subject Access Request — SAR, 30-day response)</p></div>
                        <div class="right-item"><h4>Right to Rectification</h4><p>Have inaccurate data corrected without undue delay</p></div>
                        <div class="right-item"><h4>Right to Erasure</h4><p>"Right to be forgotten" — request deletion when data is no longer necessary</p></div>
                        <div class="right-item"><h4>Right to Restrict Processing</h4><p>Limit how data is used while disputes are resolved</p></div>
                        <div class="right-item"><h4>Right to Data Portability</h4><p>Receive data in a structured, machine-readable format</p></div>
                        <div class="right-item"><h4>Right to Object</h4><p>Object to processing based on legitimate interests or direct marketing</p></div>
                        <div class="right-item"><h4>Rights re: Automated Decisions</h4><p>Not be subject to solely automated decisions that significantly affect you</p></div>
                    </div>
                </div>"""
            },
            {
                "title": "Password Security",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">🔑</div>
                    <h2>Password Security</h2>
                </div>
                <div class="content-card">
                    <p>Weak passwords are the most common cause of data breaches. Your university account gives access to student records, research data, and internal systems.</p>
                    <div class="comparison-box">
                        <div class="compare-col bad">
                            <h4>❌ Weak Passwords</h4>
                            <ul>
                                <li>password123</li>
                                <li>University2026</li>
                                <li>Your name + birthday</li>
                                <li>Qwerty!1</li>
                                <li>Same password everywhere</li>
                            </ul>
                        </div>
                        <div class="compare-col good">
                            <h4>✅ Strong Passwords</h4>
                            <ul>
                                <li>3+ random words: <em>correct-horse-battery-staple</em></li>
                                <li>12+ characters minimum</li>
                                <li>Unique for every account</li>
                                <li>Managed with a password manager</li>
                                <li>Multi-Factor Authentication (MFA) enabled</li>
                            </ul>
                        </div>
                    </div>
                    <div class="warning-box">
                        <p>⚠️ <strong>Never share your password</strong> with anyone — not colleagues, not IT support, not your supervisor. Legitimate IT staff will never ask for your password.</p>
                    </div>
                </div>"""
            },
            {
                "title": "Phishing Awareness",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">🎣</div>
                    <h2>Phishing Awareness</h2>
                </div>
                <div class="content-card">
                    <p>Phishing attacks trick you into revealing credentials or clicking malicious links. Universities are heavily targeted — 43% of UK universities reported weekly attacks.</p>
                    <div class="highlight-box">
                        <h3>Red Flags to Watch For</h3>
                        <div class="red-flag-list">
                            <div class="red-flag">📧 <strong>Urgency</strong> — "Your account will be suspended in 24 hours!"</div>
                            <div class="red-flag">📧 <strong>Generic greeting</strong> — "Dear Student" instead of your name</div>
                            <div class="red-flag">📧 <strong>Suspicious links</strong> — Hover to check the actual URL before clicking</div>
                            <div class="red-flag">📧 <strong>Spelling/grammar errors</strong> — Professional organisations proofread</div>
                            <div class="red-flag">📧 <strong>Unexpected attachments</strong> — Especially .exe, .zip, .docm files</div>
                            <div class="red-flag">📧 <strong>Request for credentials</strong> — No legitimate service asks for your password via email</div>
                        </div>
                    </div>
                    <div class="tip-box">
                        <h3>💡 What to Do</h3>
                        <p><strong>Do not click, do not reply.</strong> Forward suspicious emails to the IT security team and delete them. If you have clicked a link or entered credentials, change your password immediately and report the incident.</p>
                    </div>
                </div>"""
            },
            {
                "title": "Reporting Data Breaches",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">🚨</div>
                    <h2>Reporting Data Breaches</h2>
                </div>
                <div class="content-card">
                    <p>A <strong>data breach</strong> is any security incident where personal data is accidentally or unlawfully accessed, disclosed, altered, or destroyed.</p>
                    <div class="breach-examples">
                        <h3>Common Breach Scenarios</h3>
                        <div class="example">📧 Sending an email with personal data to the wrong recipient</div>
                        <div class="example">💻 Losing an unencrypted USB drive or laptop containing research data</div>
                        <div class="example">🔓 Unauthorised access to student records due to weak password</div>
                        <div class="example">📨 Accidentally using CC instead of BCC for a mass email with personal addresses</div>
                        <div class="example">🗑️ Disposing of paper records in general waste instead of confidential waste</div>
                    </div>
                    <div class="warning-box">
                        <h3>Reporting Timeline</h3>
                        <p>⚠️ Report <strong>immediately</strong> to the Data Protection Officer. Under GDPR, breaches must be reported to the ICO (Information Commissioner's Office) <strong>within 72 hours</strong> if they pose a risk to individuals. Late reporting can result in significant fines.</p>
                    </div>
                    <p class="key-point">Do not try to cover up a breach — the penalty for late/non-reporting is far worse than the breach itself.</p>
                </div>"""
            },
            {
                "title": "Data Minimisation & Retention",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">📉</div>
                    <h2>Data Minimisation & Retention</h2>
                </div>
                <div class="content-card">
                    <div class="highlight-box">
                        <h3>Data Minimisation</h3>
                        <p>Only collect the <strong>minimum personal data necessary</strong> for your specific purpose.</p>
                        <div class="scenario">
                            <span class="scenario-label bad">❌ Over-collection</span>
                            <p>A research survey asks for full name, date of birth, home address, and phone number when only age range and postcode area are needed.</p>
                        </div>
                        <div class="scenario">
                            <span class="scenario-label good">✅ Minimised</span>
                            <p>The survey collects age range and first three characters of postcode. No directly identifiable data.</p>
                        </div>
                    </div>
                    <div class="highlight-box">
                        <h3>Data Retention</h3>
                        <p>Personal data must be deleted when no longer needed. Follow your department's retention schedule.</p>
                        <ul>
                            <li>Research data: as specified in your ethics approval (typically 10 years)</li>
                            <li>Student records: 6 years after graduation</li>
                            <li>Financial records: 7 years (HMRC requirement)</li>
                            <li>Consent records: retained for as long as data is processed</li>
                        </ul>
                    </div>
                </div>"""
            },
            {
                "title": "Secure File Sharing",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">📤</div>
                    <h2>Secure File Sharing</h2>
                </div>
                <div class="content-card">
                    <p>How you share data matters as much as how you store it. Insecure sharing is one of the most common causes of data breaches.</p>
                    <div class="comparison-box">
                        <div class="compare-col bad">
                            <h4>❌ Do Not Use</h4>
                            <ul>
                                <li>Personal email (Gmail, Yahoo) for university data</li>
                                <li>Unencrypted USB drives</li>
                                <li>WhatsApp for sharing personal data</li>
                                <li>Public cloud storage (personal Dropbox/Google Drive)</li>
                                <li>Printing sensitive data on shared printers</li>
                            </ul>
                        </div>
                        <div class="compare-col good">
                            <h4>✅ Approved Methods</h4>
                            <ul>
                                <li>University email (encrypted in transit)</li>
                                <li>University OneDrive / SharePoint</li>
                                <li>Password-protected files for sensitive data</li>
                                <li>Encrypted USB drives (hardware encryption)</li>
                                <li>University-approved file transfer services</li>
                            </ul>
                        </div>
                    </div>
                    <p class="key-point">When sharing files externally, always password-protect the file and send the password via a separate communication channel.</p>
                </div>"""
            },
            {
                "title": "Your Responsibilities",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">✅</div>
                    <h2>Your Responsibilities</h2>
                </div>
                <div class="content-card">
                    <p>You have completed the Information Security & GDPR Compliance training. Here are your ongoing responsibilities:</p>
                    <div class="pledge-box">
                        <div class="pledge-item">✓ Use strong, unique passwords and enable MFA on all university accounts</div>
                        <div class="pledge-item">✓ Lock your computer when leaving your desk (Ctrl+L / Cmd+L)</div>
                        <div class="pledge-item">✓ Only collect personal data with a lawful basis and for a specific purpose</div>
                        <div class="pledge-item">✓ Report any data breaches or security incidents immediately</div>
                        <div class="pledge-item">✓ Use only university-approved tools for sharing personal data</div>
                        <div class="pledge-item">✓ Be vigilant about phishing — think before you click</div>
                        <div class="pledge-item">✓ Complete data protection impact assessments for high-risk processing</div>
                    </div>
                    <div class="completion-message">
                        <h3>🎉 Module Complete</h3>
                        <p>Thank you for completing this mandatory training. Data protection is everyone's responsibility. If you have questions, contact the Data Protection Officer.</p>
                    </div>
                </div>"""
            }
        ]
    },
    "edi": {
        "title": "Equality, Diversity & Inclusion",
        "shortname": "COMP-EDI01",
        "timer_seconds": 10,
        "slides": [
            {
                "title": "Protected Characteristics",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">🌍</div>
                    <h2>Protected Characteristics</h2>
                </div>
                <div class="content-card">
                    <p>The <strong>Equality Act 2010</strong> protects individuals from discrimination based on nine protected characteristics. The university is legally required to eliminate discrimination, advance equality, and foster good relations.</p>
                    <div class="characteristic-grid">
                        <div class="char-item"><span class="char-icon">👤</span><strong>Age</strong></div>
                        <div class="char-item"><span class="char-icon">♿</span><strong>Disability</strong></div>
                        <div class="char-item"><span class="char-icon">⚧️</span><strong>Gender Reassignment</strong></div>
                        <div class="char-item"><span class="char-icon">💍</span><strong>Marriage & Civil Partnership</strong></div>
                        <div class="char-item"><span class="char-icon">🤰</span><strong>Pregnancy & Maternity</strong></div>
                        <div class="char-item"><span class="char-icon">🏳️</span><strong>Race</strong></div>
                        <div class="char-item"><span class="char-icon">🙏</span><strong>Religion or Belief</strong></div>
                        <div class="char-item"><span class="char-icon">👫</span><strong>Sex</strong></div>
                        <div class="char-item"><span class="char-icon">🏳️‍🌈</span><strong>Sexual Orientation</strong></div>
                    </div>
                    <p class="key-point">Discrimination, harassment, and victimisation based on any of these characteristics is unlawful and will result in disciplinary action.</p>
                </div>"""
            },
            {
                "title": "Unconscious Bias",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">🧠</div>
                    <h2>Unconscious Bias</h2>
                </div>
                <div class="content-card">
                    <p>Unconscious biases are automatic mental shortcuts that influence our judgments about people without our awareness. Everyone has them — they are a normal brain function, but they can lead to unfair treatment.</p>
                    <div class="bias-types">
                        <div class="bias-item"><strong>Affinity Bias</strong><br>Preferring people who are like us (same background, interests, appearance)</div>
                        <div class="bias-item"><strong>Confirmation Bias</strong><br>Seeking information that confirms our existing beliefs about a group</div>
                        <div class="bias-item"><strong>Halo/Horn Effect</strong><br>One positive/negative trait colours our entire impression of a person</div>
                        <div class="bias-item"><strong>Attribution Bias</strong><br>Attributing success to skill for "our group" but to luck for "others"</div>
                    </div>
                    <div class="tip-box">
                        <h3>💡 Mitigating Your Biases</h3>
                        <ul>
                            <li>Acknowledge that everyone has unconscious biases</li>
                            <li>Slow down decision-making — don't rely on gut feelings for important choices</li>
                            <li>Seek out diverse perspectives and challenge stereotypes</li>
                            <li>Use structured criteria for academic and professional assessments</li>
                        </ul>
                    </div>
                </div>"""
            },
            {
                "title": "Inclusive Language",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">💬</div>
                    <h2>Inclusive Language</h2>
                </div>
                <div class="content-card">
                    <p>The words we use shape our environment. Inclusive language respects diversity and avoids marginalising any group.</p>
                    <div class="language-examples">
                        <div class="lang-row"><span class="old">❌ Chairman</span><span class="arrow">→</span><span class="new">✅ Chairperson / Chair</span></div>
                        <div class="lang-row"><span class="old">❌ Manpower</span><span class="arrow">→</span><span class="new">✅ Workforce / Staff / Personnel</span></div>
                        <div class="lang-row"><span class="old">❌ Disabled person</span><span class="arrow">→</span><span class="new">✅ Person with a disability</span></div>
                        <div class="lang-row"><span class="old">❌ Suffers from...</span><span class="arrow">→</span><span class="new">✅ Lives with... / Has a diagnosis of...</span></div>
                        <div class="lang-row"><span class="old">❌ Normal students</span><span class="arrow">→</span><span class="new">✅ Non-disabled students</span></div>
                        <div class="lang-row"><span class="old">❌ Guys / Ladies</span><span class="arrow">→</span><span class="new">✅ Everyone / Colleagues / Team</span></div>
                    </div>
                    <div class="highlight-box">
                        <h3>Pronouns</h3>
                        <p>If someone shares their pronouns (she/her, he/him, they/them), use them. If unsure, use their name or ask respectfully: <em>"What pronouns do you use?"</em></p>
                    </div>
                </div>"""
            },
            {
                "title": "Microaggressions",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">🔍</div>
                    <h2>Microaggressions</h2>
                </div>
                <div class="content-card">
                    <p>Microaggressions are subtle, often unintentional comments or behaviours that communicate hostile or derogatory messages to members of marginalised groups. Their cumulative impact is significant.</p>
                    <div class="micro-examples">
                        <div class="micro-item">
                            <p class="micro-quote">"Where are you <em>really</em> from?"</p>
                            <p class="micro-impact">Implies the person is not truly British / doesn't belong</p>
                        </div>
                        <div class="micro-item">
                            <p class="micro-quote">"You speak English so well!"</p>
                            <p class="micro-impact">Assumes someone's first language based on appearance</p>
                        </div>
                        <div class="micro-item">
                            <p class="micro-quote">"I don't see colour — I treat everyone the same."</p>
                            <p class="micro-impact">Dismisses the lived experience of racial identity and systemic inequality</p>
                        </div>
                        <div class="micro-item">
                            <p class="micro-quote">"You don't look disabled."</p>
                            <p class="micro-impact">Invalidates invisible disabilities and chronic conditions</p>
                        </div>
                    </div>
                    <div class="tip-box">
                        <h3>💡 If You're Called Out</h3>
                        <p>Listen without defensiveness → Acknowledge the impact → Apologise sincerely → Learn and adjust your behaviour. <strong>Intent does not erase impact.</strong></p>
                    </div>
                </div>"""
            },
            {
                "title": "Reasonable Adjustments",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">♿</div>
                    <h2>Reasonable Adjustments</h2>
                </div>
                <div class="content-card">
                    <p>The Equality Act requires universities to make <strong>reasonable adjustments</strong> to remove barriers that put disabled students at a substantial disadvantage compared to non-disabled students.</p>
                    <div class="adjustment-examples">
                        <h3>Examples of Reasonable Adjustments</h3>
                        <div class="adj-grid">
                            <div class="adj-item"><strong>Extra time</strong><br>25% additional time in exams for students with dyslexia, processing difficulties</div>
                            <div class="adj-item"><strong>Lecture recordings</strong><br>Providing recordings for students who cannot attend or need to re-listen</div>
                            <div class="adj-item"><strong>Alternative formats</strong><br>Large print, braille, screen-reader compatible documents</div>
                            <div class="adj-item"><strong>Flexible deadlines</strong><br>Extensions for students experiencing mental health crises or chronic condition flare-ups</div>
                            <div class="adj-item"><strong>Rest breaks</strong><br>Supervised breaks during exams for students with chronic pain or fatigue</div>
                            <div class="adj-item"><strong>Assistive technology</strong><br>Screen readers, speech-to-text, note-taking support</div>
                        </div>
                    </div>
                    <p class="key-point">Adjustments are not special treatment — they level the playing field. If you need adjustments, contact Disability Services early in the academic year.</p>
                </div>"""
            },
            {
                "title": "Reporting Discrimination",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">📢</div>
                    <h2>Reporting Discrimination</h2>
                </div>
                <div class="content-card">
                    <p>If you experience or witness discrimination, harassment, or hate-related incidents, you have the right and responsibility to report it.</p>
                    <div class="reporting-options">
                        <h3>How to Report</h3>
                        <div class="report-option"><span class="badge">Online</span> University Report + Support portal — can be anonymous</div>
                        <div class="report-option"><span class="badge">In Person</span> Student Support Services, Personal Tutor, or any staff member</div>
                        <div class="report-option"><span class="badge">Emergency</span> University Security (24/7) or Police (999 for immediate danger, 101 for non-emergency)</div>
                        <div class="report-option"><span class="badge">External</span> Students' Union Advice Centre (confidential, independent)</div>
                    </div>
                    <div class="highlight-box">
                        <h3>What Happens Next</h3>
                        <ol>
                            <li>Your report is acknowledged within 48 hours</li>
                            <li>A trained investigator contacts you to discuss your options</li>
                            <li>You choose the resolution path: informal, formal, or mediation</li>
                            <li>You are supported throughout the process and have the right to accompaniment</li>
                            <li>You will not face retaliation for reporting in good faith</li>
                        </ol>
                    </div>
                </div>"""
            },
            {
                "title": "Cultural Competence",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">🌏</div>
                    <h2>Cultural Competence</h2>
                </div>
                <div class="content-card">
                    <p>Our university community includes students and staff from over 100 countries. Cultural competence is the ability to interact effectively with people from different cultural backgrounds.</p>
                    <div class="highlight-box">
                        <h3>Building Cultural Competence</h3>
                        <div class="competence-steps">
                            <div class="step"><span class="step-num">1</span><strong>Awareness</strong> — Recognise your own cultural assumptions and biases</div>
                            <div class="step"><span class="step-num">2</span><strong>Knowledge</strong> — Learn about different cultural practices, values, and communication styles</div>
                            <div class="step"><span class="step-num">3</span><strong>Skills</strong> — Practise adapting your communication and behaviour in cross-cultural settings</div>
                            <div class="step"><span class="step-num">4</span><strong>Attitude</strong> — Approach differences with curiosity rather than judgment</div>
                        </div>
                    </div>
                    <div class="tip-box">
                        <h3>💡 Practical Tips</h3>
                        <ul>
                            <li>Don't assume everyone celebrates the same holidays</li>
                            <li>Be aware that eye contact, personal space, and directness vary by culture</li>
                            <li>Respect dietary requirements and religious observances</li>
                            <li>Ask names you're unsure about — don't shorten without permission</li>
                        </ul>
                    </div>
                </div>"""
            },
            {
                "title": "Allyship",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">🤝</div>
                    <h2>Allyship</h2>
                </div>
                <div class="content-card">
                    <p>An <strong>ally</strong> is someone who actively supports and advocates for members of marginalised groups, especially when those groups are not present. Allyship is an ongoing practice, not a label.</p>
                    <div class="ally-actions">
                        <h3>What Allyship Looks Like in Practice</h3>
                        <div class="ally-item"><strong>Speak up</strong> — Challenge discriminatory language or "jokes" even when the targeted group isn't present</div>
                        <div class="ally-item"><strong>Amplify</strong> — Credit and promote the ideas and contributions of underrepresented colleagues</div>
                        <div class="ally-item"><strong>Educate yourself</strong> — Don't rely on marginalised people to educate you about their experiences</div>
                        <div class="ally-item"><strong>Listen</strong> — Centre the voices of those with lived experience rather than your own perspective</div>
                        <div class="ally-item"><strong>Accept mistakes</strong> — You will get things wrong. Apologise, learn, and keep going</div>
                        <div class="ally-item"><strong>Use your privilege</strong> — Advocate for systemic change in spaces where marginalised voices may not be heard</div>
                    </div>
                    <p class="key-point">Allyship is demonstrated through action, not declaration. It requires ongoing commitment and a willingness to be uncomfortable.</p>
                </div>"""
            },
            {
                "title": "University EDI Policy",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">📜</div>
                    <h2>University EDI Policy</h2>
                </div>
                <div class="content-card">
                    <p>Our university is committed to creating an inclusive environment where all students and staff can thrive regardless of background or identity.</p>
                    <div class="policy-highlights">
                        <h3>Key Policy Commitments</h3>
                        <div class="policy-item"><strong>Zero tolerance</strong> for discrimination, harassment, bullying, and hate speech</div>
                        <div class="policy-item"><strong>Athena SWAN</strong> charter member — committed to gender equality in academia</div>
                        <div class="policy-item"><strong>Race Equality Charter</strong> — actively working to address racial inequalities</div>
                        <div class="policy-item"><strong>Disability Confident</strong> employer — committed to accessible recruitment and support</div>
                        <div class="policy-item"><strong>Stonewall Diversity Champion</strong> — supporting LGBTQ+ staff and students</div>
                        <div class="policy-item"><strong>EDI training</strong> is mandatory for all staff and students (this module)</div>
                    </div>
                    <div class="highlight-box">
                        <h3>EDI Support & Resources</h3>
                        <p>✓ EDI Office &nbsp; ✓ Disability Services &nbsp; ✓ Student Wellbeing &nbsp; ✓ Students' Union Liberation Officers &nbsp; ✓ Staff Networks (BAME, Disability, LGBTQ+, Women's, Parents & Carers)</p>
                    </div>
                </div>"""
            },
            {
                "title": "Your EDI Commitment",
                "content": """
                <div class="slide-hero">
                    <div class="icon-circle">✅</div>
                    <h2>Your EDI Commitment</h2>
                </div>
                <div class="content-card">
                    <p>You have completed the Equality, Diversity & Inclusion training. By completing this module, you commit to:</p>
                    <div class="pledge-box">
                        <div class="pledge-item">✓ Treating all individuals with dignity and respect, regardless of their background</div>
                        <div class="pledge-item">✓ Challenging discriminatory language and behaviour when I encounter it</div>
                        <div class="pledge-item">✓ Using inclusive language in all communications</div>
                        <div class="pledge-item">✓ Acknowledging and working to address my own unconscious biases</div>
                        <div class="pledge-item">✓ Reporting discrimination, harassment, or hate incidents through the proper channels</div>
                        <div class="pledge-item">✓ Contributing to an inclusive, welcoming university community</div>
                    </div>
                    <div class="completion-message">
                        <h3>🎉 Module Complete</h3>
                        <p>Thank you for completing this mandatory training. Building an inclusive university is a collective responsibility. Every action matters.</p>
                        <p><strong>Remember:</strong> EDI is not a one-off training — it is an ongoing commitment to learning, listening, and action.</p>
                    </div>
                </div>"""
            }
        ]
    }
}


# ============================================================
# PLAYER HTML TEMPLATE (strict timed playback)
# ============================================================

def get_css():
    return """
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif; background: #0f172a; color: #e2e8f0; height: 100vh; display: flex; flex-direction: column; overflow: hidden; }
    
    /* === TOP PROGRESS BAR (YouTube-style seekbar) === */
    .top-bar { position: relative; background: #0a0f1a; flex-shrink: 0; }
    .progress-track { width: 100%; height: 5px; background: #1e293b; cursor: default; position: relative; transition: height 0.2s; }
    .progress-track:hover { height: 8px; }
    .progress-buffered { position: absolute; top: 0; left: 0; height: 100%; background: #334155; border-radius: 0; transition: width 0.3s; }
    .progress-played { position: absolute; top: 0; left: 0; height: 100%; background: linear-gradient(90deg, #3b82f6, #8b5cf6); transition: width 0.5s ease; z-index: 2; }
    .progress-thumb { position: absolute; top: 50%; right: -6px; width: 12px; height: 12px; border-radius: 50%; background: #8b5cf6; transform: translateY(-50%); opacity: 0; transition: opacity 0.2s; z-index: 3; }
    .progress-track:hover .progress-thumb { opacity: 1; }
    .top-info { display: flex; justify-content: space-between; align-items: center; padding: 6px 20px 8px; }
    .top-title { font-size: 14px; color: #93c5fd; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex: 1; margin-right: 16px; }
    .top-counter { font-size: 13px; color: #64748b; font-variant-numeric: tabular-nums; white-space: nowrap; }
    
    /* === SCROLLABLE CONTENT AREA === */
    .slide-container { flex: 1; overflow-y: auto; overflow-x: hidden; padding: 28px 24px 28px; display: flex; justify-content: center; scrollbar-width: thin; scrollbar-color: #334155 transparent; }
    .slide-container::-webkit-scrollbar { width: 6px; }
    .slide-container::-webkit-scrollbar-track { background: transparent; }
    .slide-container::-webkit-scrollbar-thumb { background: #334155; border-radius: 3px; }
    .slide-container::-webkit-scrollbar-thumb:hover { background: #475569; }
    .slide-content { max-width: 800px; width: 100%; }
    
    /* === BOTTOM CONTROLS BAR (video player style) === */
    .controls-bar { background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%); border-top: 1px solid #1e293b; flex-shrink: 0; padding: 0; }
    
    /* Timer progress inside controls bar */
    .timer-track { width: 100%; height: 3px; background: #1e293b; position: relative; }
    .timer-fill { height: 100%; background: linear-gradient(90deg, #f59e0b, #22c55e); transition: width 1s linear; border-radius: 0; }
    
    .controls-row { display: flex; align-items: center; justify-content: space-between; padding: 10px 20px 12px; }
    
    /* Left: prev, play/pause, next cluster */
    .controls-left { display: flex; align-items: center; gap: 6px; }
    .ctrl-btn { width: 40px; height: 40px; border: none; border-radius: 50%; background: transparent; color: #94a3b8; font-size: 18px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s; }
    .ctrl-btn:hover:not(.disabled) { background: #334155; color: #f1f5f9; }
    .ctrl-btn.disabled { color: #334155; cursor: not-allowed; }
    .ctrl-btn.play-pause { width: 48px; height: 48px; font-size: 22px; background: #3b82f6; color: white; margin: 0 4px; }
    .ctrl-btn.play-pause:hover { background: #2563eb; transform: scale(1.05); }
    .ctrl-btn.play-pause.paused { background: #f59e0b; }
    .ctrl-btn.play-pause.paused:hover { background: #d97706; }
    
    /* Center: timer & status */
    .controls-center { display: flex; align-items: center; gap: 12px; }
    .timer-badge { display: flex; align-items: center; gap: 6px; padding: 4px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; font-variant-numeric: tabular-nums; }
    .timer-badge.locked { background: #451a03; color: #fbbf24; border: 1px solid #92400e; }
    .timer-badge.ready { background: #052e16; color: #4ade80; border: 1px solid #166534; }
    .timer-badge .timer-icon { font-size: 14px; }
    .slide-status { font-size: 12px; color: #475569; }
    
    /* Right: next action button */
    .controls-right { display: flex; align-items: center; gap: 12px; }
    .btn-action { padding: 8px 24px; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.25s; display: flex; align-items: center; gap: 8px; }
    .btn-action.locked { background: #1e293b; color: #475569; cursor: not-allowed; border: 1px solid #334155; }
    .btn-action.unlocked { background: linear-gradient(135deg, #3b82f6, #8b5cf6); color: white; border: 1px solid transparent; box-shadow: 0 2px 10px rgba(59,130,246,0.3); }
    .btn-action.unlocked:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(59,130,246,0.5); }
    .btn-action.completed { background: #16a34a; color: white; border: 1px solid transparent; }
    
    /* Content styling */
    .slide-hero { text-align: center; margin-bottom: 28px; }
    .icon-circle { width: 64px; height: 64px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6, #8b5cf6); display: inline-flex; align-items: center; justify-content: center; font-size: 32px; margin-bottom: 16px; }
    .slide-hero h2 { font-size: 28px; color: #f1f5f9; }
    
    .content-card { background: #1e293b; border-radius: 12px; padding: 28px; border: 1px solid #334155; }
    .content-card p { line-height: 1.7; margin-bottom: 16px; color: #cbd5e1; font-size: 15px; }
    .content-card h3 { color: #93c5fd; font-size: 18px; margin-bottom: 12px; }
    .content-card h4 { color: #c4b5fd; font-size: 16px; margin-bottom: 8px; }
    .content-card ul, .content-card ol { margin: 12px 0 16px 24px; color: #cbd5e1; line-height: 1.8; }
    .content-card li { margin-bottom: 4px; }
    .content-card strong { color: #f1f5f9; }
    .content-card em { color: #fbbf24; }
    
    .key-point { background: #1e3a5f; border-left: 4px solid #3b82f6; padding: 12px 16px; border-radius: 0 8px 8px 0; color: #93c5fd !important; font-weight: 500; }
    
    .highlight-box { background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 20px; margin: 20px 0; }
    .warning-box { background: #451a03; border: 1px solid #ea580c; border-radius: 8px; padding: 16px; margin: 20px 0; color: #fed7aa; }
    .tip-box { background: #052e16; border: 1px solid #16a34a; border-radius: 8px; padding: 16px; margin: 20px 0; color: #bbf7d0; }
    
    .comparison-box { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin: 16px 0; }
    .compare-col { background: #0f172a; border-radius: 8px; padding: 16px; border: 1px solid #334155; }
    .compare-col.good { border-color: #16a34a; }
    .compare-col.bad { border-color: #dc2626; }
    
    .pillar-grid, .rule-grid, .ppe-grid, .ghs-grid, .characteristic-grid, .adj-grid, .emergency-grid, .rights-grid, .summary-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; margin: 16px 0; }
    .pillar, .rule, .ppe-item, .ghs-item, .char-item, .adj-item, .emergency-item, .right-item, .summary-item { background: #1e293b; border: 1px solid #334155; border-radius: 8px; padding: 12px; text-align: center; font-size: 14px; color: #cbd5e1; }
    .pillar-icon, .ppe-icon, .ghs-symbol, .char-icon { font-size: 28px; display: block; margin-bottom: 8px; }
    .rule-num, .step-num, .num { display: inline-flex; width: 28px; height: 28px; border-radius: 50%; background: #3b82f6; color: white; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; margin-right: 8px; flex-shrink: 0; }
    
    .type-list, .steps-list, .basis-list, .competence-steps { display: flex; flex-direction: column; gap: 10px; margin: 16px 0; }
    .type-item, .step, .basis-item, .red-flag, .ally-item, .policy-item, .report-option, .micro-item, .pledge-item, .example, .breach-examples .example, .scenario { background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 12px 16px; font-size: 14px; color: #cbd5e1; line-height: 1.6; }
    .type-item.danger { border-left: 4px solid #dc2626; }
    .type-item.warning { border-left: 4px solid #f59e0b; }
    .type-item.info { border-left: 4px solid #3b82f6; }
    .badge, .basis-label, .scenario-label { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 12px; font-weight: 700; margin-right: 8px; }
    .badge { background: #3b82f6; color: white; }
    .basis-label { background: #7c3aed; color: white; }
    .scenario-label.good { background: #16a34a; color: white; }
    .scenario-label.bad { background: #dc2626; color: white; }
    
    .case-study { background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 16px; margin: 12px 0; }
    .case-study h3 { color: #fbbf24; margin-bottom: 8px; }
    .outcome { background: #1e3a5f; padding: 8px 12px; border-radius: 6px; margin-top: 8px; }
    
    .pledge-box { background: #052e16; border: 1px solid #16a34a; border-radius: 8px; padding: 20px; margin: 20px 0; }
    .pledge-item { padding: 8px 12px; border-bottom: 1px solid #1e3a2e; color: #bbf7d0; font-size: 15px; line-height: 1.6; }
    
    .completion-message { text-align: center; margin-top: 24px; padding: 24px; background: linear-gradient(135deg, #1e3a5f, #2e1065); border-radius: 12px; border: 1px solid #3b82f6; }
    .completion-message h3 { color: #fbbf24; font-size: 24px; margin-bottom: 12px; }
    .completion-message p { color: #e2e8f0; }
    
    .ref-examples, .ref-style { margin: 12px 0; }
    .ref-style { background: #0f172a; border-radius: 8px; padding: 12px; margin-bottom: 8px; border: 1px solid #334155; }
    .example { font-size: 14px; }
    
    .score-guide { display: flex; flex-direction: column; gap: 8px; margin: 12px 0; }
    .score { display: flex; align-items: center; gap: 12px; padding: 8px 12px; border-radius: 6px; font-size: 14px; }
    .score span { font-weight: 700; min-width: 60px; }
    .score.green { background: #052e16; color: #bbf7d0; }
    .score.yellow { background: #422006; color: #fde68a; }
    .score.orange { background: #431407; color: #fed7aa; }
    .score.red { background: #450a0a; color: #fecaca; }
    
    .penalty-scale { display: flex; flex-direction: column; gap: 8px; margin: 16px 0; }
    .penalty-level { padding: 12px 16px; border-radius: 8px; }
    .penalty-level h4 { margin-bottom: 4px; }
    .level1 { background: #422006; border-left: 4px solid #f59e0b; color: #fde68a; }
    .level2 { background: #431407; border-left: 4px solid #ea580c; color: #fed7aa; }
    .level3 { background: #450a0a; border-left: 4px solid #dc2626; color: #fecaca; }
    .level4 { background: #3b0764; border-left: 4px solid #a855f7; color: #e9d5ff; }
    
    .extinguisher-table { margin: 16px 0; }
    .ext-row { display: grid; grid-template-columns: 1fr 0.7fr 1.5fr 1.5fr; gap: 8px; padding: 8px 12px; border-radius: 6px; margin-bottom: 4px; font-size: 14px; }
    .ext-row.header { background: #334155; font-weight: 700; color: #f1f5f9; }
    .ext-row:not(.header) { background: #0f172a; border: 1px solid #334155; }
    .colour { font-weight: 700; }
    .colour.red { color: #ef4444; }
    .colour.black { color: #94a3b8; }
    .colour.blue { color: #60a5fa; }
    .colour.cream { color: #fde68a; }
    
    .waste-type { margin: 12px 0; padding: 12px; border-radius: 8px; background: #0f172a; border: 1px solid #334155; }
    .yellow-waste { color: #fbbf24 !important; }
    .orange-waste { color: #fb923c !important; }
    .black-waste { color: #94a3b8 !important; }
    
    .micro-quote { font-style: italic; font-size: 16px; color: #f1f5f9; margin-bottom: 4px; }
    .micro-impact { color: #f87171; font-size: 13px; }
    
    .language-examples { margin: 16px 0; }
    .lang-row { display: flex; align-items: center; gap: 12px; padding: 8px 12px; border-radius: 6px; margin-bottom: 4px; background: #0f172a; font-size: 14px; }
    .old { color: #f87171; min-width: 200px; }
    .arrow { color: #64748b; }
    .new { color: #4ade80; }
    
    table { width: 100%; border-collapse: collapse; margin: 12px 0; }
    th, td { padding: 8px 12px; text-align: center; border: 1px solid #334155; font-size: 13px; }
    th { background: #334155; color: #f1f5f9; }
    .low-risk { background: #052e16; color: #4ade80; }
    .medium-risk { background: #422006; color: #fbbf24; }
    .high-risk { background: #450a0a; color: #f87171; }
    
    /* Responsive */
    @media (max-width: 768px) {
        .comparison-box, .pillar-grid, .rule-grid, .ppe-grid, .ghs-grid { grid-template-columns: 1fr; }
        .ext-row { grid-template-columns: 1fr; }
        .lang-row { flex-direction: column; gap: 4px; }
    }
"""


def get_player_js(total_slides, timer_seconds):
    return f"""
    var TOTAL_SLIDES = {total_slides};
    var TIMER_SECONDS = {timer_seconds};
    var currentSlide = 0;
    var timerRemaining = TIMER_SECONDS;
    var timerInterval = null;
    var slideUnlocked = false;
    var timerPaused = false;
    var highestUnlocked = -1;  // track which slides have been viewed
    var api = null;
    
    function findAPI(win) {{
        var tries = 0;
        while (win && !win.API && tries < 10) {{
            tries++;
            if (win.parent && win.parent !== win) win = win.parent;
            else if (win.opener) win = win.opener;
            else break;
        }}
        return win ? win.API : null;
    }}
    
    function initAPI() {{
        api = findAPI(window);
        if (!api && window.parent) api = findAPI(window.parent);
        if (!api && window.top) api = findAPI(window.top);
        if (api) {{
            api.LMSInitialize("");
            var loc = api.LMSGetValue("cmi.core.lesson_location");
            if (loc && parseInt(loc) > 0 && parseInt(loc) < TOTAL_SLIDES) {{
                currentSlide = parseInt(loc);
                highestUnlocked = currentSlide - 1;
            }}
            api.LMSSetValue("cmi.core.lesson_status", "incomplete");
            api.LMSCommit("");
        }}
    }}
    
    function saveProgress() {{
        if (api) {{
            api.LMSSetValue("cmi.core.lesson_location", String(currentSlide));
            var suspendData = JSON.stringify({{ slide: currentSlide, highest: highestUnlocked, time: new Date().toISOString() }});
            api.LMSSetValue("cmi.suspend_data", suspendData);
            if (currentSlide >= TOTAL_SLIDES - 1 && slideUnlocked) {{
                api.LMSSetValue("cmi.core.lesson_status", "completed");
                api.LMSSetValue("cmi.core.score.raw", "100");
                api.LMSSetValue("cmi.core.score.min", "0");
                api.LMSSetValue("cmi.core.score.max", "100");
            }}
            api.LMSCommit("");
        }}
    }}
    
    function showSlide(index) {{
        var slides = document.querySelectorAll('.slide');
        for (var i = 0; i < slides.length; i++) {{
            slides[i].style.display = 'none';
        }}
        slides[index].style.display = 'block';
        currentSlide = index;
        
        // Update top progress bar
        var pct = ((index + 1) / TOTAL_SLIDES) * 100;
        document.getElementById('progress-played').style.width = pct + '%';
        document.getElementById('top-counter').textContent = (index + 1) + ' / ' + TOTAL_SLIDES;
        
        // Update prev button state
        var prevBtn = document.getElementById('btn-prev');
        if (index <= 0) {{
            prevBtn.className = 'ctrl-btn disabled';
            prevBtn.title = 'No previous slide';
        }} else {{
            prevBtn.className = 'ctrl-btn';
            prevBtn.title = 'Previous slide';
        }}
        
        // Determine if this slide was already unlocked
        if (index <= highestUnlocked) {{
            // Already viewed — unlock immediately
            slideUnlocked = true;
            timerRemaining = 0;
            timerPaused = false;
            if (timerInterval) clearInterval(timerInterval);
            updateControlsUnlocked();
        }} else {{
            // New slide — start timer
            slideUnlocked = false;
            timerRemaining = TIMER_SECONDS;
            timerPaused = false;
            updateTimerUI();
            updateActionBtn();
            updatePlayPauseBtn();
            
            if (timerInterval) clearInterval(timerInterval);
            timerInterval = setInterval(timerTick, 1000);
        }}
        
        // Scroll content to top
        document.querySelector('.slide-container').scrollTop = 0;
        
        saveProgress();
    }}
    
    function timerTick() {{
        if (timerPaused) return;
        timerRemaining--;
        updateTimerUI();
        if (timerRemaining <= 0) {{
            clearInterval(timerInterval);
            slideUnlocked = true;
            if (currentSlide > highestUnlocked) highestUnlocked = currentSlide;
            updateControlsUnlocked();
        }}
    }}
    
    function updateControlsUnlocked() {{
        // Timer track full
        document.getElementById('timer-fill').style.width = '100%';
        
        // Timer badge
        var badge = document.getElementById('timer-badge');
        badge.className = 'timer-badge ready';
        badge.innerHTML = '<span class="timer-icon">✓</span> Ready';
        
        // Play/pause becomes a checkmark
        var pp = document.getElementById('btn-playpause');
        pp.innerHTML = '✓';
        pp.className = 'ctrl-btn play-pause';
        pp.title = 'Slide complete';
        
        // Action button
        updateActionBtn();
    }}
    
    function updateTimerUI() {{
        var pct = ((TIMER_SECONDS - timerRemaining) / TIMER_SECONDS) * 100;
        document.getElementById('timer-fill').style.width = pct + '%';
        
        var badge = document.getElementById('timer-badge');
        badge.className = 'timer-badge locked';
        var secs = timerRemaining;
        badge.innerHTML = '<span class="timer-icon">⏱</span> ' + secs + 's';
    }}
    
    function updateActionBtn() {{
        var btn = document.getElementById('btn-action');
        var isLast = currentSlide >= TOTAL_SLIDES - 1;
        
        if (slideUnlocked) {{
            btn.className = 'btn-action unlocked';
            btn.innerHTML = isLast ? '✓ Complete Module' : 'Next →';
        }} else {{
            btn.className = 'btn-action locked';
            btn.innerHTML = isLast ? '⏳ Complete Module' : '⏳ Next →';
        }}
    }}
    
    function updatePlayPauseBtn() {{
        var pp = document.getElementById('btn-playpause');
        if (timerPaused) {{
            pp.innerHTML = '▶';
            pp.className = 'ctrl-btn play-pause paused';
            pp.title = 'Resume timer';
        }} else {{
            pp.innerHTML = '⏸';
            pp.className = 'ctrl-btn play-pause';
            pp.title = 'Pause timer';
        }}
    }}
    
    function togglePause() {{
        if (slideUnlocked) return;
        timerPaused = !timerPaused;
        updatePlayPauseBtn();
    }}
    
    function prevSlide() {{
        if (currentSlide > 0) {{
            showSlide(currentSlide - 1);
        }}
    }}
    
    function nextSlide() {{
        if (!slideUnlocked) return;
        if (currentSlide >= TOTAL_SLIDES - 1) {{
            // Final slide — mark complete
            saveProgress();
            if (api) {{
                api.LMSSetValue("cmi.core.lesson_status", "completed");
                api.LMSSetValue("cmi.core.score.raw", "100");
                api.LMSCommit("");
                api.LMSFinish("");
            }}
            var btn = document.getElementById('btn-action');
            btn.innerHTML = '✅ Module Completed';
            btn.className = 'btn-action completed';
            slideUnlocked = false;
            return;
        }}
        showSlide(currentSlide + 1);
    }}
    
    window.onload = function() {{
        initAPI();
        showSlide(currentSlide);
    }};
    
    window.onbeforeunload = function() {{
        saveProgress();
        if (api) api.LMSFinish("");
    }};
"""


def build_html(course_data):
    slides_html = ""
    for i, slide in enumerate(course_data["slides"]):
        display = "block" if i == 0 else "none"
        slides_html += f'<div class="slide" id="slide-{i}" style="display:{display}">\n{slide["content"]}\n</div>\n'
    
    total = len(course_data["slides"])
    timer = course_data["timer_seconds"]
    
    return f"""<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{course_data["title"]}</title>
<style>
{get_css()}
</style>
</head>
<body>

<!-- TOP: Progress seekbar -->
<div class="top-bar">
    <div class="progress-track">
        <div class="progress-buffered" style="width:100%"></div>
        <div class="progress-played" id="progress-played" style="width:{100/total}%">
            <div class="progress-thumb"></div>
        </div>
    </div>
    <div class="top-info">
        <div class="top-title">{course_data["title"]}</div>
        <div class="top-counter" id="top-counter">1 / {total}</div>
    </div>
</div>

<!-- MIDDLE: Scrollable slide content -->
<div class="slide-container">
    <div class="slide-content">
        {slides_html}
    </div>
</div>

<!-- BOTTOM: Video-player-style controls -->
<div class="controls-bar">
    <div class="timer-track">
        <div class="timer-fill" id="timer-fill" style="width:0%"></div>
    </div>
    <div class="controls-row">
        <div class="controls-left">
            <button class="ctrl-btn disabled" id="btn-prev" onclick="prevSlide()" title="Previous slide">⏮</button>
            <button class="ctrl-btn play-pause" id="btn-playpause" onclick="togglePause()" title="Pause timer">⏸</button>
            <button class="ctrl-btn" id="btn-skip" onclick="nextSlide()" title="Next slide">⏭</button>
        </div>
        <div class="controls-center">
            <div class="timer-badge locked" id="timer-badge">
                <span class="timer-icon">⏱</span> {timer}s
            </div>
        </div>
        <div class="controls-right">
            <button class="btn-action locked" id="btn-action" onclick="nextSlide()">⏳ Next →</button>
        </div>
    </div>
</div>

<script>
{get_player_js(total, timer)}
</script>
</body>
</html>"""


def build_imsmanifest(identifier, title, launch_file="index.html"):
    return f"""<?xml version="1.0" encoding="UTF-8"?>
<manifest identifier="{identifier}" version="1.0"
    xmlns="http://www.imsproject.org/xsd/imscp_rootv1p1p2"
    xmlns:adlcp="http://www.adlnet.org/xsd/adlcp_rootv1p2"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://www.imsproject.org/xsd/imscp_rootv1p1p2 imscp_rootv1p1p2.xsd
                        http://www.imsglobal.org/xsd/imsmd_rootv1p2p1 imsmd_rootv1p2p1.xsd
                        http://www.adlnet.org/xsd/adlcp_rootv1p2 adlcp_rootv1p2.xsd">
    <metadata>
        <schema>ADL SCORM</schema>
        <schemaversion>1.2</schemaversion>
    </metadata>
    <organizations default="ORG-{identifier}">
        <organization identifier="ORG-{identifier}">
            <title>{title}</title>
            <item identifier="ITEM-{identifier}" identifierref="RES-{identifier}">
                <title>{title}</title>
                <adlcp:prerequisites type="aicc_script"></adlcp:prerequisites>
                <adlcp:maxtimeallowed/>
                <adlcp:timelimitaction/>
                <adlcp:datafromlms/>
                <adlcp:masteryscore/>
            </item>
        </organization>
    </organizations>
    <resources>
        <resource identifier="RES-{identifier}" type="webcontent" adlcp:scormtype="sco" href="{launch_file}">
            <file href="{launch_file}"/>
        </resource>
    </resources>
</manifest>"""


def create_scorm_zip(key, course_data):
    filename = f"scorm_{key}.zip"
    filepath = os.path.join(OUTPUT_DIR, filename)
    
    html = build_html(course_data)
    manifest = build_imsmanifest(key.upper(), course_data["title"])
    
    with zipfile.ZipFile(filepath, 'w', zipfile.ZIP_DEFLATED) as zf:
        zf.writestr("index.html", html)
        zf.writestr("imsmanifest.xml", manifest)
    
    size_kb = os.path.getsize(filepath) / 1024
    print(f"  ✅ {filename} ({size_kb:.0f} KB) — {len(course_data['slides'])} slides, {course_data['timer_seconds']}s timer")
    return filepath


# ============================================================
# MAIN
# ============================================================
if __name__ == "__main__":
    print("=" * 60)
    print("  SCORM 1.2 PACKAGE GENERATOR")
    print("  Strict timed playback — no skip, no fast-forward")
    print("=" * 60)
    print()
    
    for key, data in COURSES.items():
        create_scorm_zip(key, data)
    
    print()
    print(f"  Generated {len(COURSES)} SCORM packages in: {OUTPUT_DIR}")
    print("  Done.")
