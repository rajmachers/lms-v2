#!/bin/bash
# deploy_scripts.sh — Copy scripts to lmsv2-web container and run them
# Usage: ./deploy_scripts.sh [step_number]
# If no step given, lists available steps
set -e

WEB_CONTAINER="lmsv2-web"
SCRIPTS_DIR="$(cd "$(dirname "$0")/../setup" && pwd)"
REMOTE_DIR="/scripts/setup"
SSH_CMD="ssh -o StrictHostKeyChecking=no root@159.65.149.161"

echo "=== LMS V2 Script Deployer ==="

# Copy all scripts to container
echo "--- Copying scripts to container ---"
for f in "$SCRIPTS_DIR"/*.php; do
    fname=$(basename "$f")
    docker cp "$f" "$WEB_CONTAINER:$REMOTE_DIR/$fname" 2>/dev/null || \
        $SSH_CMD "docker exec $WEB_CONTAINER mkdir -p $REMOTE_DIR && docker cp $f $WEB_CONTAINER:$REMOTE_DIR/$fname"
    echo "  Copied: $fname"
done

STEP="${1:-list}"

if [ "$STEP" = "list" ]; then
    echo ""
    echo "Available steps:"
    echo "  1  — Categories + Users (60 students, 6 teachers, cohorts)"
    echo "  2  — Courses + Activities (quizzes, forums, assignments)"
    echo "  3  — Enrollments + Competencies"
    echo "  3b — Competency-Course Mapping"
    echo "  3c — Enrollment Fixes"
    echo "  4  — Completions + Grades simulation"
    echo "  5  — Certificates"
    echo "  5b — Course Certificates (completion criteria)"
    echo "  6  — Badges + Activity Visibility"
    echo "  6b — Issue Badges"
    echo "  7  — Theme Content (sliders, stats)"
    echo "  8  — AI Provider (DeepSeek)"
    echo "  9  — Paid Courses (PayPal)"
    echo "  10 — Question Bank"
    echo "  11 — SCORM Courses"
    echo "  all — Run steps 1-11 in order"
    echo ""
    echo "Usage: $0 <step>"
    exit 0
fi

run_step() {
    local script="$1"
    local desc="$2"
    echo ""
    echo "=== Running: $desc ==="
    docker exec "$WEB_CONTAINER" php "$REMOTE_DIR/$script" 2>&1
    echo "=== Completed: $desc ==="
}

case "$STEP" in
    1)   run_step "01_categories_users.php" "Categories + Users" ;;
    2)   run_step "02_courses_activities.php" "Courses + Activities" ;;
    3)   run_step "03_enrollments_competencies.php" "Enrollments + Competencies" ;;
    3b)  run_step "03b_competency_mapping.php" "Competency Mapping" ;;
    3c)  run_step "03c_enrollment_fixes.php" "Enrollment Fixes" ;;
    4)   run_step "04_completions_grades.php" "Completions + Grades" ;;
    5)   run_step "05_certificates.php" "Certificates" ;;
    5b)  run_step "05b_course_certificates.php" "Course Certificates" ;;
    6)   run_step "06_badges_visibility.php" "Badges + Visibility" ;;
    6b)  run_step "06b_issue_badges.php" "Issue Badges" ;;
    7)   run_step "07_theme_content.php" "Theme Content" ;;
    8)   run_step "08_ai_provider.php" "AI Provider" ;;
    9)   run_step "09_paid_courses.php" "Paid Courses" ;;
    10)  run_step "10_question_bank.php" "Question Bank" ;;
    11)  run_step "11_scorm_courses.php" "SCORM Courses" ;;
    all)
        run_step "01_categories_users.php" "Categories + Users"
        run_step "02_courses_activities.php" "Courses + Activities"
        run_step "03_enrollments_competencies.php" "Enrollments + Competencies"
        run_step "03b_competency_mapping.php" "Competency Mapping"
        run_step "03c_enrollment_fixes.php" "Enrollment Fixes"
        run_step "04_completions_grades.php" "Completions + Grades"
        run_step "05_certificates.php" "Certificates"
        run_step "05b_course_certificates.php" "Course Certificates"
        run_step "06_badges_visibility.php" "Badges + Visibility"
        run_step "06b_issue_badges.php" "Issue Badges"
        run_step "07_theme_content.php" "Theme Content"
        run_step "08_ai_provider.php" "AI Provider"
        run_step "09_paid_courses.php" "Paid Courses"
        run_step "10_question_bank.php" "Question Bank"
        run_step "11_scorm_courses.php" "SCORM Courses"
        ;;
    *)  echo "Unknown step: $STEP"; exit 1 ;;
esac
