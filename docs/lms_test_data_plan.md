# Moodle LMS Test Data Preparation Plan

**Target Instance:** http://159.65.149.161/lms  
**Category:** Distance Education Unit (`categoryid=1`)  
**Current Date Reference:** 27 February 2026  

---

## 1. Course Schedule Summary (from Screenshots)

| # | Course | Course ID | Start Date | End Date | Weekly Sections | Status as of 27 Feb 2026 |
|---|--------|-----------|------------|----------|-----------------|--------------------------|
| 1 | **Certificate on Family Medicine** | `courseid=3` | 10 Feb 2026 | 10 Mar 2026 | Wk1: 10-16 Feb, Wk2: 17-23 Feb, **Wk3: 24 Feb-2 Mar (current)**, Wk4: 3-9 Mar | **In Progress** — Week 3 of 4 |
| 2 | **Fellowship in Family Medicine** | `courseid=2` | 28 Jan 2026 | 25 Feb 2026 | Wk1: 28 Jan-3 Feb, Wk2: 4-10 Feb, Wk3: 11-17 Feb, Wk4: 18-24 Feb | **Completed** — ended 25 Feb |

### Known Activities (from screenshots)
- **Certificate on FM:** Basic concepts in family medicine, ENT/Ophthalmology, Knowledge Check – Clinical Presentation & Initial Assessment (assignment)
- **Fellowship in FM:** Introduction, Know About Tech Machers, Making a strawberry smoothie

### Assignment Sample: Knowledge Check – Clinical Presentation & Initial Assessment
- **Course:** Certificate on Family Medicine (Week 2: 17-23 Feb)
- **Opened:** 17 January 2026, 12:00 AM
- **Due:** 20 February 2026, 12:00 AM
- **Type:** File upload (single PDF/Word)
- **Requirement:** Structured written response, 300-400 words per topic, all sections answered

---

## 2. Student Data — 60 Students with Indian Names

### Naming Convention
- Username: `firstname.lastname` (lowercase)
- Password: `Student@2026` (uniform for testing; change in production)
- Email: `firstname.lastname@demo.com`

### Student Master List (60 Students)

| # | First Name | Last Name | Username | Email | Gender |
|---|-----------|-----------|----------|-------|--------|
| 1 | Aarav | Sharma | aarav.sharma | aarav.sharma@demo.com | M |
| 2 | Vivaan | Patel | vivaan.patel | vivaan.patel@demo.com | M |
| 3 | Aditya | Reddy | aditya.reddy | aditya.reddy@demo.com | M |
| 4 | Vihaan | Nair | vihaan.nair | vihaan.nair@demo.com | M |
| 5 | Arjun | Iyer | arjun.iyer | arjun.iyer@demo.com | M |
| 6 | Reyansh | Menon | reyansh.menon | reyansh.menon@demo.com | M |
| 7 | Sai | Krishnan | sai.krishnan | sai.krishnan@demo.com | M |
| 8 | Arnav | Gupta | arnav.gupta | arnav.gupta@demo.com | M |
| 9 | Dhruv | Joshi | dhruv.joshi | dhruv.joshi@demo.com | M |
| 10 | Kabir | Rao | kabir.rao | kabir.rao@demo.com | M |
| 11 | Ananya | Das | ananya.das | ananya.das@demo.com | F |
| 12 | Diya | Pillai | diya.pillai | diya.pillai@demo.com | F |
| 13 | Isha | Bhat | isha.bhat | isha.bhat@demo.com | F |
| 14 | Saanvi | Kulkarni | saanvi.kulkarni | saanvi.kulkarni@demo.com | F |
| 15 | Aanya | Thomas | aanya.thomas | aanya.thomas@demo.com | F |
| 16 | Prisha | Verma | prisha.verma | prisha.verma@demo.com | F |
| 17 | Myra | Shetty | myra.shetty | myra.shetty@demo.com | F |
| 18 | Sara | Joseph | sara.joseph | sara.joseph@demo.com | F |
| 19 | Navya | Hegde | navya.hegde | navya.hegde@demo.com | F |
| 20 | Riya | Deshmukh | riya.deshmukh | riya.deshmukh@demo.com | F |
| 21 | Rohan | Chatterjee | rohan.chatterjee | rohan.chatterjee@demo.com | M |
| 22 | Ishaan | Mukherjee | ishaan.mukherjee | ishaan.mukherjee@demo.com | M |
| 23 | Pranav | Sinha | pranav.sinha | pranav.sinha@demo.com | M |
| 24 | Karthik | Nambiar | karthik.nambiar | karthik.nambiar@demo.com | M |
| 25 | Advait | Bhatt | advait.bhatt | advait.bhatt@demo.com | M |
| 26 | Lakshmi | Subramaniam | lakshmi.subramaniam | lakshmi.subramaniam@demo.com | F |
| 27 | Meera | Venkatesh | meera.venkatesh | meera.venkatesh@demo.com | F |
| 28 | Tanvi | Kamath | tanvi.kamath | tanvi.kamath@demo.com | F |
| 29 | Pooja | Agarwal | pooja.agarwal | pooja.agarwal@demo.com | F |
| 30 | Sneha | Raghavan | sneha.raghavan | sneha.raghavan@demo.com | F |
| 31 | Nikhil | Tiwari | nikhil.tiwari | nikhil.tiwari@demo.com | M |
| 32 | Rahul | Pandey | rahul.pandey | rahul.pandey@demo.com | M |
| 33 | Vikram | Saxena | vikram.saxena | vikram.saxena@demo.com | M |
| 34 | Amit | Kapoor | amit.kapoor | amit.kapoor@demo.com | M |
| 35 | Deepak | Chauhan | deepak.chauhan | deepak.chauhan@demo.com | M |
| 36 | Kavya | Mishra | kavya.mishra | kavya.mishra@demo.com | F |
| 37 | Shruti | Mehta | shruti.mehta | shruti.mehta@demo.com | F |
| 38 | Neha | Banerjee | neha.banerjee | neha.banerjee@demo.com | F |
| 39 | Divya | Choudhary | divya.choudhary | divya.choudhary@demo.com | F |
| 40 | Swati | Rajan | swati.rajan | swati.rajan@demo.com | F |
| 41 | Manish | Goyal | manish.goyal | manish.goyal@demo.com | M |
| 42 | Rajesh | Kumar | rajesh.kumar | rajesh.kumar@demo.com | M |
| 43 | Suresh | Prasad | suresh.prasad | suresh.prasad@demo.com | M |
| 44 | Harish | Mohan | harish.mohan | harish.mohan@demo.com | M |
| 45 | Ganesh | Sundar | ganesh.sundar | ganesh.sundar@demo.com | M |
| 46 | Anitha | Balakrishnan | anitha.balakrishnan | anitha.balakrishnan@demo.com | F |
| 47 | Radha | Gopalan | radha.gopalan | radha.gopalan@demo.com | F |
| 48 | Vidya | Narayanan | vidya.narayanan | vidya.narayanan@demo.com | F |
| 49 | Sowmya | Ramasamy | sowmya.ramasamy | sowmya.ramasamy@demo.com | F |
| 50 | Padma | Subramani | padma.subramani | padma.subramani@demo.com | F |
| 51 | Arun | Mahajan | arun.mahajan | arun.mahajan@demo.com | M |
| 52 | Varun | Sethi | varun.sethi | varun.sethi@demo.com | M |
| 53 | Tarun | Malhotra | tarun.malhotra | tarun.malhotra@demo.com | M |
| 54 | Kiran | Bhargava | kiran.bhargava | kiran.bhargava@demo.com | M |
| 55 | Mohan | Sundaresan | mohan.sundaresan | mohan.sundaresan@demo.com | M |
| 56 | Shalini | Venkat | shalini.venkat | shalini.venkat@demo.com | F |
| 57 | Deepa | Ramesh | deepa.ramesh | deepa.ramesh@demo.com | F |
| 58 | Priya | Krishnamurthy | priya.krishnamurthy | priya.krishnamurthy@demo.com | F |
| 59 | Gayathri | Ranganathan | gayathri.ranganathan | gayathri.ranganathan@demo.com | F |
| 60 | Bhavana | Seshadri | bhavana.seshadri | bhavana.seshadri@demo.com | F |

---

## 3. Cohort / Batch Design — 5 Batches

### Batch Allocation Strategy

| Batch | Name | Students | Course Enrolled | Enrollment Date | Status | Purpose |
|-------|------|----------|-----------------|-----------------|--------|---------|
| **Batch A** | `FM-CERT-2026-A` | Students 1-10 | Certificate on Family Medicine | 10 Feb 2026 | **Active — In Progress (Week 3)** | Show active learning, ~60-70% progress |
| **Batch B** | `FM-CERT-2026-B` | Students 11-20 | Certificate on Family Medicine | 12 Feb 2026 | **Active — In Progress (Week 3)** | Show active learning, ~50-65% progress |
| **Batch C** | `FM-CERT-2026-C` | Students 21-30 | Certificate on Family Medicine | 14 Feb 2026 | **Active — In Progress (Week 2-3)** | Show active learning, ~40-55% progress |
| **Batch D** | `FM-FELLOW-2026-D` | Students 31-40 | Fellowship in Family Medicine | 28 Jan 2026 | **Nearing Complete — ~85-95%** | Show near-completion analytics |
| **Batch E** | `FM-FELLOW-2026-E` | Students 41-50 | Fellowship in Family Medicine | 30 Jan 2026 | **Nearing Complete — ~80-90%** | Show near-completion analytics |

### Unassigned Students (for manual/later enrollment)
- **Students 51-60** (10 students) — Not enrolled in any cohort
- Can login, browse course catalog, self-enroll or purchase courses
- Used to demonstrate enrollment workflows and new-student onboarding

---

## 4. Activities & Content to Create Per Course

### 4.1 Certificate on Family Medicine (courseid=3) — 4 Weeks

| Week | Section | Activities to Create |
|------|---------|---------------------|
| **Wk 1** (10-16 Feb) | Basic Concepts in Family Medicine; ENT/Ophthalmology | • **Quiz 1:** MCQ – Basic Concepts (10 questions, auto-graded)<br>• **Forum 1:** "Introduce Yourself & Clinical Interest"<br>• **Resource:** Lecture PDF – Intro to Family Medicine<br>• **Page:** Week 1 Learning Objectives |
| **Wk 2** (17-23 Feb) | Knowledge Check – Clinical Presentation & Initial Assessment | • **Assignment 1:** Clinical Presentation & Initial Assessment (file upload, 300-400 words) *(already exists)*<br>• **Quiz 2:** Clinical Scenarios MCQ (15 questions)<br>• **Forum 2:** "Case Discussion: Common Presentations in Family Practice"<br>• **Resource:** Clinical Assessment Guidelines PDF |
| **Wk 3** (24 Feb-2 Mar) | *(Current week)* | • **Assignment 2:** Patient History & Examination Write-up (file upload)<br>• **Quiz 3:** Pharmacology Basics for Primary Care (10 questions)<br>• **Forum 3:** "Discuss: Managing Chronic Conditions in Primary Care"<br>• **Resource:** Patient History Template |
| **Wk 4** (3-9 Mar) | *(Final week)* | • **Assignment 3:** Integrated Case Study – Final Assessment (file upload)<br>• **Quiz 4:** Comprehensive Course Review (20 questions)<br>• **Forum 4:** "Reflections & Key Takeaways"<br>• **Feedback:** Course Evaluation (Choice activity) |

### 4.2 Fellowship in Family Medicine (courseid=2) — 4 Weeks

| Week | Section | Activities to Create |
|------|---------|---------------------|
| **Wk 1** (28 Jan-3 Feb) | Introduction; Know About Tech Machers | • **Quiz 1:** Orientation Quiz (10 questions)<br>• **Forum 1:** "Fellowship Expectations & Goals"<br>• **Resource:** Fellowship Handbook PDF<br>• **Assignment 1:** Personal Learning Plan (file upload) |
| **Wk 2** (4-10 Feb) | Making a strawberry smoothie *(+ additional)* | • **Quiz 2:** Advanced Clinical Assessment (15 questions)<br>• **Forum 2:** "Evidence-Based Medicine Discussion"<br>• **Assignment 2:** Literature Review Summary (file upload)<br>• **Resource:** Research Methodology Guide PDF |
| **Wk 3** (11-17 Feb) | *(Add content)* | • **Quiz 3:** Specialty Topics in Family Medicine (12 questions)<br>• **Forum 3:** "Complex Case Presentations"<br>• **Assignment 3:** Clinical Audit Report (file upload)<br>• **Resource:** Clinical Audit Template |
| **Wk 4** (18-24 Feb) | *(Final week)* | • **Quiz 4:** End-of-Fellowship Assessment (25 questions)<br>• **Forum 4:** "Fellowship Journey Reflections"<br>• **Assignment 4:** Portfolio Submission (file upload)<br>• **Feedback:** Fellowship Evaluation Survey |

---

## 5. Student Progress Data Matrix

### 5.1 Batches A/B/C — Certificate on Family Medicine (Active, In Progress)

**Target Progress Ranges:**

| Batch | Week 1 Activities | Week 2 Activities | Week 3 Activities | Overall % |
|-------|-------------------|-------------------|-------------------|-----------|
| **Batch A** (Students 1-10) | 100% complete | 90-100% complete | 30-50% complete | 60-70% |
| **Batch B** (Students 11-20) | 100% complete | 70-90% complete | 10-30% complete | 50-65% |
| **Batch C** (Students 21-30) | 90-100% complete | 50-70% complete | 0-20% complete | 40-55% |

**Per-Student Variation Pattern** (within each batch, stagger to look realistic):
- **High performers (3 students/batch):** Ahead of schedule, all available work complete
- **On-track (4 students/batch):** Following expected timeline
- **Slow learners (3 students/batch):** Slightly behind, some items incomplete

### 5.2 Batches D/E — Fellowship in Family Medicine (Nearing Complete)

| Batch | Week 1 | Week 2 | Week 3 | Week 4 | Overall % |
|-------|--------|--------|--------|--------|-----------|
| **Batch D** (Students 31-40) | 100% | 100% | 100% | 70-95% | 85-95% |
| **Batch E** (Students 41-50) | 100% | 100% | 90-100% | 50-80% | 80-90% |

### 5.3 Detailed Activity Completion Matrix

For each student, generate data for:

| Activity Type | Data to Generate | Realistic Ranges |
|---------------|-----------------|-------------------|
| **Quizzes** | Attempt timestamp, score, time taken, number of attempts | Score: 45-98%, Time: 8-35 min |
| **Assignments** | Submission timestamp, file (dummy PDF), grade, feedback | Grade: 55-95/100, feedback text |
| **Forums** | Post count, reply count, timestamps across discussion period | Posts: 1-5 per forum, Replies: 0-3 |
| **Resources** | View/access timestamps | Viewed: yes/no per student |
| **Course completion** | Activity completion tracking, % progress | Per activity checkbox |

---

## 6. Forum Discussion Sample Data

### Forum Post Templates (to be varied per student)

**Forum: "Introduce Yourself & Clinical Interest"** (Week 1, Certificate course)
```
Sample posts to create per student:
- "Hello, I'm [Name] from [City]. I'm particularly interested in [subspecialty]. 
   Looking forward to learning with everyone."
- Replies: "Welcome [Name]! I share your interest in [topic]."
```

**Forum: "Case Discussion: Common Presentations"** (Week 2, Certificate course)
```
Sample discussion points:
- "In my clinical experience, the most common presentation I've seen is..."
- "I agree with [Name], additionally I want to highlight..."  
- "What diagnostic approach would you recommend for [condition]?"
```

**Create 3-8 posts per forum per batch** with staggered timestamps matching the course week dates.

---

## 7. Quiz Question Bank Samples

### Quiz 1: Basic Concepts (Certificate course, Week 1)

```
Q1. Which of the following is a core principle of family medicine?
    a) Specialization in one organ system
    b) Continuity of care across the lifespan ✓
    c) Focus only on acute conditions
    d) Treating only pediatric patients

Q2. The biopsychosocial model includes which domains?
    a) Biological and social only
    b) Psychological and economic
    c) Biological, psychological, and social ✓
    d) Physical and spiritual

... (10 questions per quiz, vary difficulty)
```

### Assignment Submission Templates

Generate dummy PDF files (1-2 pages) with varied content per student for:
- Clinical Presentation write-ups
- Patient History templates  
- Literature reviews
- Case study responses

---

## 8. Additional Recommended Test Data

### 8.1 Roles & Permissions
| Role | Count | Names | Purpose |
|------|-------|-------|---------|
| Admin | 1 | admin | System administrator |
| Manager | 1 | Dr. Ramachandran (manager) | Category manager |
| Course Creator | 1 | Dr. Lakshmi Iyer (creator) | Can create courses |
| Teacher (Certificate) | 2 | Dr. Priya Sundaram, Dr. Karthik Natarajan | Teach & grade Certificate course |
| Teacher (Fellowship) | 2 | Dr. Anand Bhargava, Dr. Meenakshi Raman | Teach & grade Fellowship course |
| Non-editing Teacher | 2 | Dr. Srinivas Rao, Dr. Kavitha Pillai | View grades, no editing |

### 8.2 Grading Data
- **Gradebook entries** for all completed activities
- Mix of pass/fail/distinction ranges
- Some students with late submissions (penalty applied)
- A few students with extension grants

### 8.3 Calendar Events
- Course milestones & deadlines
- Upcoming assignment due dates
- Week-start notifications

### 8.4 Messaging & Notifications
- Teacher-to-student messages for late submissions
- System notifications for grade releases
- Forum subscription notifications

### 8.5 Badges & Certificates
- "Week 1 Champion" badge (auto-issued on completing all Week 1 activities)
- "Active Participant" badge (5+ forum posts)
- Course completion certificate template

### 8.6 Groups within Courses
- Study groups within each batch (3-4 students per group)
- Group assignments for collaborative activities

### 8.7 Attendance Module Data
- Mark attendance for sessions (present/absent/late)
- 85-95% attendance rate for most students

### 8.8 Log / Analytics Data
- Login timestamps (daily logins over course duration)
- Page view counts per resource
- Time spent per activity
- Course engagement heatmap data

---

## 9. Data Loading Methods — Implementation Approach

### Phase 1: Users & Cohorts (CSV Upload via Moodle Admin)

**Method:** Moodle Admin → Site Administration → Users → Upload Users

**CSV File: `students.csv`**
```csv
username,password,firstname,lastname,email,cohort1,city,country
aarav.sharma,Student@2026,Aarav,Sharma,aarav.sharma@demo.com,FM-CERT-2026-A,Bangalore,IN
vivaan.patel,Student@2026,Vivaan,Patel,vivaan.patel@demo.com,FM-CERT-2026-A,Bangalore,IN
...
```

**Pros:** Native Moodle feature, no plugins needed, creates users + assigns cohorts in one step  
**Steps:**
1. Create the 5 cohorts first via `Site Admin → Users → Cohorts`
2. Upload the CSV with `cohort1` column mapping
3. Unassigned students (51-60) get uploaded without cohort assignment

### Phase 2: Course Enrollment (Cohort Sync)

**Method:** Course → Participants → Enrol Methods → Cohort Sync

**Steps per course:**
1. Go to course → Participants → Enrol methods
2. Add "Cohort sync" enrollment method
3. Select the relevant cohort(s)
4. Set enrollment start dates to match batch schedule

| Course | Cohorts to Sync |
|--------|----------------|
| Certificate on Family Medicine | FM-CERT-2026-A, FM-CERT-2026-B, FM-CERT-2026-C |
| Fellowship in Family Medicine | FM-FELLOW-2026-D, FM-FELLOW-2026-E |

### Phase 3: Course Content (Moodle Backup/Restore or Moosh CLI)

**Option A: Moosh CLI (Recommended for automation)**
```bash
# Install moosh on the Moodle server
cd /var/www/html/lms
# Create quiz activities
moosh -n activity-add --name "Quiz 1: Basic Concepts" --section 1 quiz 3
moosh -n activity-add --name "Forum: Introduce Yourself" --section 1 forum 3
moosh -n activity-add --name "Assignment: Clinical Presentation" --section 2 assign 3
```

**Option B: Moodle REST API**
```python
import requests

MOODLE_URL = "http://159.65.149.161/lms/webservice/rest/server.php"
TOKEN = "<admin_webservice_token>"

# Create forum
params = {
    'wstoken': TOKEN,
    'wsfunction': 'mod_forum_add_discussion',
    'moodlewsrestformat': 'json',
    'forumid': 1,
    'subject': 'Introduce Yourself',
    'message': 'Please introduce yourself...',
}
response = requests.post(MOODLE_URL, params=params)
```

**Option C: Moodle XML Import (Backup/Restore)**
- Create one fully set-up course manually
- Backup and restore as template
- Modify dates/content as needed

### Phase 4: Student Progress Data (Python Script via Moodle Web Services API)

**This is the most complex phase — requires API scripting**

```python
# Pseudo-code structure for the automation script
"""
1. Authenticate → get token
2. For each student in batch:
   a. Simulate quiz attempts (core_grades_update_grades)
   b. Submit assignments (mod_assign_save_submission)
   c. Post to forums (mod_forum_add_discussion_post)
   d. Mark activities as complete (core_completion_update_activity_completion_status_manually)
   e. Generate grade entries
3. Vary timestamps to look realistic (spread across course weeks)
"""
```

### Phase 5: Grades & Completion (Moodle Gradebook Import)

**Method:** Course → Grades → Import → CSV file

```csv
Email address,Quiz 1: Basic Concepts,Assignment 1: Clinical Presentation,Forum 1 (rating)
aarav.sharma@demo.com,85,78,90
vivaan.patel@demo.com,72,81,85
...
```

---

## 10. Recommended Script Execution Order

```
Step 1: Create Cohorts (Manual via Admin UI — 5 minutes)
         ↓
Step 2: Upload Students CSV (Admin → Upload Users — 5 minutes)
         ↓
Step 3: Setup Cohort Enrollment in Courses (Manual — 10 minutes)
         ↓
Step 4: Create Course Activities (Moosh CLI or REST API script — 30 minutes)
         ↓
Step 5: Generate & Upload Quiz Questions (Moodle XML/GIFT format — 20 minutes)
         ↓
Step 6: Run Progress Simulation Script (Python + REST API — 1-2 hours)
         ↓
Step 7: Import Grades CSV (Gradebook Import — 10 minutes)
         ↓
Step 8: Create Forum Posts (REST API script — 30 minutes)
         ↓
Step 9: Set Activity Completion Records (REST API — 30 minutes)
         ↓
Step 10: Create Additional Data (Badges, Messages, Logs — 30 minutes)
```

**Total Estimated Time:** 3-4 hours for full data population

---

## 11. Questions / Information Needed Before Proceeding

To build the actual scripts, I need the following:

1. **Moodle Admin Credentials** — Or a web service token with admin privileges for API calls
2. **Web Services Enabled?** — Is `Site Admin → Plugins → Web Services` enabled on this instance?
3. **Moosh Availability** — Is `moosh` CLI installed on the server, or do we have SSH access to install it?
4. **Moodle Version** — Which exact Moodle version (4.x?) for API compatibility
5. **Email domain** — Confirm `@demo.com` or another domain for student emails
6. **Self-enrollment** — Should the 10 unassigned students see both courses or specific ones? Is payment/enrollment fee enabled?
7. **Quiz question content** — Should I generate medically accurate questions or placeholder questions? (placeholder is faster, but medical accuracy is better for demo)
8. **Assignment dummy files** — Should I generate dummy PDF submissions or leave assignments unsubmitted for some students?
9. **Server SSH access** — Available for direct DB operations or CLI tools?
10. **Existing plugins** — Are any additional plugins installed (e.g., Attendance, Certificate, Custom Certificate)?

---

## 12. File Deliverables (to be generated)

Once questions are answered, I will generate:

| File | Purpose | Format |
|------|---------|--------|
| `students_upload.csv` | Bulk user upload with cohort assignments | CSV |
| `quiz_questions_cert.xml` | Quiz question bank for Certificate course | Moodle XML |
| `quiz_questions_fellow.xml` | Quiz question bank for Fellowship course | Moodle XML |
| `grades_cert.csv` | Gradebook import for Certificate course | CSV |
| `grades_fellow.csv` | Gradebook import for Fellowship course | CSV |
| `moodle_test_data_loader.py` | Main automation script for progress & forums | Python |
| `assignment_submissions/` | Dummy PDF files for assignment submissions | PDF |
| `forum_posts.json` | Forum discussion content data | JSON |
| `setup_instructions.md` | Step-by-step execution guide | Markdown |

---

## 13. Summary

| Item | Count |
|------|-------|
| Students | 60 (50 in cohorts + 10 unassigned) |
| Cohorts/Batches | 5 (3 active + 2 nearing complete) |
| Courses | 2 |
| Quizzes | 8 (4 per course) |
| Assignments | 7 (3 in Certificate + 4 in Fellowship) |
| Forums | 8 (4 per course) |
| Resources | 8+ (PDFs, pages) |
| Teachers | 6 |
| Forum Posts | ~200-400 generated posts |
| Quiz Attempts | ~400 attempts across all students |
| Assignment Submissions | ~250+ submissions |
| Grade Entries | ~500+ individual grade records |
