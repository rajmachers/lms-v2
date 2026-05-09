<?php
define('CLI_SCRIPT', true);
require('/var/www/html/moodle/config.php');

// Slider content
set_config('slidercount', '3', 'theme_cafmed');
set_config('slide1heading', 'Welcome to CAF Medical Education', 'theme_cafmed');
set_config('slide1text', 'Empowering healthcare professionals with world-class medical education and training programs.', 'theme_cafmed');
set_config('slide1ctatext', 'Explore Courses', 'theme_cafmed');
set_config('slide1ctaurl', '/lms/course/index.php', 'theme_cafmed');

set_config('slide2heading', 'Advance Your Medical Career', 'theme_cafmed');
set_config('slide2text', 'Join thousands of healthcare professionals who have elevated their practice through our programs.', 'theme_cafmed');
set_config('slide2ctatext', 'Get Started', 'theme_cafmed');
set_config('slide2ctaurl', '/lms/login/signup.php', 'theme_cafmed');

set_config('slide3heading', 'Evidence-Based Learning', 'theme_cafmed');
set_config('slide3text', 'Our curriculum is designed by leading medical educators and aligned with the latest clinical guidelines.', 'theme_cafmed');
set_config('slide3ctatext', 'Learn More', 'theme_cafmed');
set_config('slide3ctaurl', '/lms/course/index.php', 'theme_cafmed');

// Search section
set_config('searchheading', 'Find Your Course', 'theme_cafmed');
set_config('searchtext', 'Search from our wide range of medical education programs', 'theme_cafmed');

// Categories heading
set_config('categoryheading', 'Browse by Specialty', 'theme_cafmed');

// Stats
set_config('statsheading', 'Our Impact in Numbers', 'theme_cafmed');
set_config('statstext', 'CAF Medical Education has been dedicated to advancing healthcare education since 2005.', 'theme_cafmed');
set_config('stat1number', '5000', 'theme_cafmed');
set_config('stat1label', 'Students Enrolled', 'theme_cafmed');
set_config('stat2number', '150', 'theme_cafmed');
set_config('stat2label', 'Expert Instructors', 'theme_cafmed');
set_config('stat3number', '200', 'theme_cafmed');
set_config('stat3label', 'Courses Available', 'theme_cafmed');
set_config('stat4number', '98', 'theme_cafmed');
set_config('stat4label', 'Success Rate %', 'theme_cafmed');

// Featured courses
set_config('enablefeaturedcourses', '1', 'theme_cafmed');
set_config('featuredcoursesheading', 'Featured Programs', 'theme_cafmed');

// Footer content
set_config('footercol1', '<h5>About CAF Medical</h5><p>CAF Medical Education is a premier institution dedicated to advancing healthcare education and professional development for medical practitioners worldwide.</p>', 'theme_cafmed');
set_config('footercol2', '<h5>Quick Links</h5><ul><li><a href="/lms">Home</a></li><li><a href="/lms/course/index.php">All Courses</a></li><li><a href="/lms/login/index.php">Login</a></li><li><a href="/lms/login/signup.php">Register</a></li></ul>', 'theme_cafmed');
set_config('footercol3', '<h5>Contact Us</h5><p><i class="fa fa-envelope"></i> info@cafmed.edu<br><i class="fa fa-phone"></i> +1 (555) 123-4567<br><i class="fa fa-map-marker"></i> 123 Medical Center Drive</p>', 'theme_cafmed');
set_config('copyright', '© 2026 CAF Medical Education. All rights reserved.', 'theme_cafmed');

echo "Frontpage content configured successfully.\n";
