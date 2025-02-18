<?php

// Check for duplicate course name or course code when creating/updating a course
function checkDuplicateCourse($conn, $course_name, $course_code, $course_id = null) {
    // If updating, exclude the current course ID from the duplicate check
    if ($course_id) {
        $name_query = "SELECT 1 FROM courses WHERE course_name = ? AND course_id != ?";
        $code_query = "SELECT 1 FROM courses WHERE course_code = ? AND course_id != ?";
    } else {
        // When creating a new course, check for any existing course with the same name or code
        $name_query = "SELECT 1 FROM courses WHERE course_name = ?";
        $code_query = "SELECT 1 FROM courses WHERE course_code = ?";
    }

    // Check for duplicate course name
    $name_stmt = $conn->prepare($name_query);
    if ($course_id) {
        $name_stmt->bind_param('si', $course_name, $course_id);
    } else {
        $name_stmt->bind_param('s', $course_name);
    }
    $name_stmt->execute();
    if ($name_stmt->get_result()->num_rows > 0) {
        return "The course name you entered already exists. Please choose a unique name.";
    }

    // Check for duplicate course code
    $code_stmt = $conn->prepare($code_query);
    if ($course_id) {
        $code_stmt->bind_param('si', $course_code, $course_id);
    } else {
        $code_stmt->bind_param('s', $course_code);
    }
    $code_stmt->execute();
    if ($code_stmt->get_result()->num_rows > 0) {
        return "The course code you entered already exists. Please choose a unique code.";
    }

    return null; // No duplicate found
}

// Validate course input fields for create and update course forms
function validateCourseInput($course_name, $course_code, $start_date, $end_date) {
    $current_date = date('Y-m-d'); 
    $six_months_later = (new DateTime($current_date))->modify('+6 months')->format('Y-m-d');
    $min_start_date = '2015-01-01';
    $max_end_date = '2035-12-31';

    // Ensure date format is valid (YYYY-MM-DD)
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !strtotime($start_date)) {
        return "Invalid start date format. Please use MM-DD-YYYY.";
    }
    if (!is_null($end_date) && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date) || !strtotime($end_date))) {
        return "Invalid end date format. Please use MM-DD-YYYY.";
    }

    if (empty($course_name)) {     // Validate course name
        return "Please enter a course name.";
    } elseif (!preg_match('/^[a-zA-Z0-9\-_ ]+$/', $course_name)) {
        return "Course name can only include letters, numbers, spaces, and the symbols (- and _).";
    } elseif (strlen($course_name) > 50) {
        return "Course name must not exceed 50 characters.";
    } elseif (empty($course_code)) {    // Validate course code
        return "Please provide a course code."; 
    } elseif (!preg_match('/^[a-zA-Z0-9\-_]+$/', $course_code)) {
        return "Course code can only include letters, numbers, and the symbols (- and _).";
    } elseif (strlen($course_code) > 10) {
        return "Course code must not exceed 10 characters.";
    } elseif (!preg_match('/[a-zA-Z]/', $course_code) || !preg_match('/\d/', $course_code)) {
        return "Course code must include at least one letter and one digit.";
    } elseif (empty($start_date)) {
        return "Start date is required.";   // Validate start and end dates
    } elseif ($start_date < $min_start_date) {
        return "Start date must not be before 2015.";
    } elseif ($start_date > $six_months_later) {
        return "Start date cannot exceed six months from the current date.";
    } elseif (!is_null($end_date) && $end_date > $max_end_date) {
        return "End date must not be beyond 2035.";
    } elseif (!is_null($end_date) && (strtotime($end_date) < strtotime($current_date))) { 
        return "End date cannot be in the past.";
    } elseif (!is_null($end_date) && strtotime($end_date) < strtotime($start_date)) {
        return "End date must be after the start date.";
    } elseif (!is_null($end_date) && (strtotime($end_date) - strtotime($start_date)) < 365 * 24 * 60 * 60) {
        return "The Difference between the start date and the end date must be at least one year.";
    }

    return null; // No validation errors
}

// Combine validation and duplicate check into a single function
function validateAndCheckDuplicates($conn, $course_name, $course_code, $start_date, $end_date, $course_id = null) {
    return checkDuplicateCourse($conn, $course_name, $course_code, $course_id) ?: 
           validateCourseInput($course_name, $course_code, $start_date, $end_date);
}


