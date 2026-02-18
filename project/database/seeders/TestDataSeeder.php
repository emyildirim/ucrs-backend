<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Course;
use App\Models\Assignment;
use App\Models\Enrollment;
use App\Models\Submission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    /**
     * Seed the database with test data for Postman testing.
     * Creates predictable, idempotent test data.
     */
    public function run(): void
    {
        // Disable foreign key checks
        \DB::statement('PRAGMA foreign_keys = OFF');

        // Clear existing data
        Submission::truncate();
        Assignment::truncate();
        Enrollment::truncate();
        Course::truncate();
        User::truncate();
        Role::truncate();

        // Reset auto-increment counters
        \DB::statement('DELETE FROM sqlite_sequence WHERE name="users"');
        \DB::statement('DELETE FROM sqlite_sequence WHERE name="courses"');
        \DB::statement('DELETE FROM sqlite_sequence WHERE name="assignments"');
        \DB::statement('DELETE FROM sqlite_sequence WHERE name="enrollments"');
        \DB::statement('DELETE FROM sqlite_sequence WHERE name="submissions"');
        \DB::statement('DELETE FROM sqlite_sequence WHERE name="roles"');

        // Re-enable foreign key checks
        \DB::statement('PRAGMA foreign_keys = ON');

        // Create Roles (ID: 1=Admin, 2=Instructor, 3=Student)
        Role::insert([
            ['role_id' => 1, 'name' => 'Admin', 'created_at' => now(), 'updated_at' => now()],
            ['role_id' => 2, 'name' => 'Instructor', 'created_at' => now(), 'updated_at' => now()],
            ['role_id' => 3, 'name' => 'Student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Create Users
        $password = Hash::make('password123');
        
        // ID 1: Admin
        User::create([
            'user_id' => 1,
            'role_id' => 1,
            'full_name' => 'Admin User',
            'email' => 'admin@ucrs.edu',
            'password_hash' => $password,
            'status' => 'active',
        ]);

        // ID 2: Instructor
        User::create([
            'user_id' => 2,
            'role_id' => 2,
            'full_name' => 'John Instructor',
            'email' => 'instructor@ucrs.edu',
            'password_hash' => $password,
            'status' => 'active',
        ]);

        // ID 3: Student
        User::create([
            'user_id' => 3,
            'role_id' => 3,
            'full_name' => 'Alice Student',
            'email' => 'student@ucrs.edu',
            'password_hash' => $password,
            'status' => 'active',
        ]);

        // ID 4: Another Student
        User::create([
            'user_id' => 4,
            'role_id' => 3,
            'full_name' => 'Bob Student',
            'email' => 'student2@ucrs.edu',
            'password_hash' => $password,
            'status' => 'active',
        ]);

        // Create Courses
        // ID 1: CS101 (taught by instructor ID 2)
        Course::create([
            'course_id' => 1,
            'code' => 'CS101',
            'title' => 'Introduction to Computer Science',
            'instructor_id' => 2,
            'is_active' => true,
        ]);

        // ID 2: MATH201 (taught by instructor ID 2)
        Course::create([
            'course_id' => 2,
            'code' => 'MATH201',
            'title' => 'Advanced Mathematics',
            'instructor_id' => 2,
            'is_active' => true,
        ]);

        // ID 3: PHY101 (taught by instructor ID 2)
        Course::create([
            'course_id' => 3,
            'code' => 'PHY101',
            'title' => 'Physics Fundamentals',
            'instructor_id' => 2,
            'is_active' => true,
        ]);

        // Create Enrollments
        // ID 1: Student 3 enrolled in Course 1
        Enrollment::create([
            'enrollment_id' => 1,
            'course_id' => 1,
            'student_id' => 3,
            'final_grade' => null,
        ]);

        // ID 2: Student 3 enrolled in Course 2
        Enrollment::create([
            'enrollment_id' => 2,
            'course_id' => 2,
            'student_id' => 3,
            'final_grade' => 85.5,
        ]);

        // ID 3: Student 4 enrolled in Course 1
        Enrollment::create([
            'enrollment_id' => 3,
            'course_id' => 1,
            'student_id' => 4,
            'final_grade' => null,
        ]);

        // Create Assignments
        // ID 1: Assignment for Course 1
        Assignment::create([
            'assignment_id' => 1,
            'course_id' => 1,
            'title' => 'Homework 1',
            'description' => 'Complete programming exercises 1-10',
            'due_at' => now()->addDays(7),
            'max_points' => 100,
        ]);

        // ID 2: Assignment for Course 1
        Assignment::create([
            'assignment_id' => 2,
            'course_id' => 1,
            'title' => 'Homework 2',
            'description' => 'Complete programming exercises 11-20',
            'due_at' => now()->addDays(14),
            'max_points' => 100,
        ]);

        // ID 3: Assignment for Course 2
        Assignment::create([
            'assignment_id' => 3,
            'course_id' => 2,
            'title' => 'Math Problem Set 1',
            'description' => 'Solve problems from chapter 3',
            'due_at' => now()->addDays(7),
            'max_points' => 50,
        ]);

        // Create Submissions
        // ID 1: Student 3 submits Assignment 1
        Submission::create([
            'submission_id' => 1,
            'assignment_id' => 1,
            'student_id' => 3,
            'content_url' => 'https://example.com/submissions/hw1_alice.pdf',
            'score' => 95,
            'graded_by' => 2,
        ]);

        // ID 2: Student 3 submits Assignment 3
        Submission::create([
            'submission_id' => 2,
            'assignment_id' => 3,
            'student_id' => 3,
            'content_url' => 'https://example.com/submissions/math1_alice.pdf',
            'score' => null,
            'graded_by' => null,
        ]);

        // ID 3: Student 4 submits Assignment 1
        Submission::create([
            'submission_id' => 3,
            'assignment_id' => 1,
            'student_id' => 4,
            'content_url' => 'https://example.com/submissions/hw1_bob.pdf',
            'score' => null,
            'graded_by' => null,
        ]);

        $this->command->info('✅ Test data seeded successfully!');
        $this->command->info('');
        $this->command->info('Test Accounts:');
        $this->command->info('  Admin:      admin@ucrs.edu / password123');
        $this->command->info('  Instructor: instructor@ucrs.edu / password123');
        $this->command->info('  Student:    student@ucrs.edu / password123');
        $this->command->info('  Student2:   student2@ucrs.edu / password123');
        $this->command->info('');
        $this->command->info('Test Data:');
        $this->command->info('  Roles: 3 (Admin, Instructor, Student)');
        $this->command->info('  Users: 4');
        $this->command->info('  Courses: 3');
        $this->command->info('  Enrollments: 3');
        $this->command->info('  Assignments: 3');
        $this->command->info('  Submissions: 3');
    }
}
