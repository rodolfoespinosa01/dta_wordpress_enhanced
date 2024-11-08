<?php
/*
Template Name: Admin Dashboard Template
*/

get_header();

if (function_exists('Dashboard::display_dashboard')) {
    echo Dashboard::display_dashboard();
}

get_footer();
