# Trinity Learning Management System

A web-based Learning Management System built with Laravel and Bootstrap.
Supports multiple roles — Super Admin, Admin, Teacher, and Student — each with their own portal and access level.

---

## Table of Contents

- [About](#about)
- [Screenshots](#screenshots)
- [Modules](#modules)
- [Tech Stack](#tech-stack)
- [Setup](#setup)
- [Roles](#roles)

---

## About

Built for Trinity, this system manages the full academic cycle — from setting up school years and semesters, assigning teachers and sections, tracking student grades, to monitoring all user activity through audit logs. Access is controlled per role, so each user only sees what's relevant to them.

---

## Screenshots

> Screenshots are organized by role inside the `/screenshots` folder.

---

### Admin

#### Semester Operation — Classes

| Assign Class | Section Reassignment |
|---|---|
| ![Assign Class](screenshots/admin/assign-class.png) | ![Section Reassignment](screenshots/admin/section-reassignment.png) |

#### Semester Operation — Grades

| Grade Section | Grade Card |
|---|---|
| ![Grade Section](screenshots/admin/grade-section.png) | ![Grade Card](screenshots/admin/grade-card.png) |

#### User Management

| Create Student | List Students |
|---|---|
| ![Create Student](screenshots/admin/create-student.png) | ![List Students](screenshots/admin/list-student.png) |

| List Teachers | Student Profile |
|---|---|
| ![List Teachers](screenshots/admin/list-teacher.png) | ![Student Profile](screenshots/admin/profile-student.png) |

#### Class Management

| List Class | List Section |
|---|---|
| ![List Class](screenshots/admin/list-class.png) | ![List Section](screenshots/admin/list-section.png) |

#### Audit & Monitoring

| Historical Records | Teacher Activity |
|---|---|
| ![Historical Records](screenshots/admin/historical-records.png) | ![Teacher Activity](screenshots/admin/audit-teacher.png) |

| Login History | |
|---|---|
| ![Login History](screenshots/admin/audit-login.png) | |

---

### Teacher

#### Class Management & Content

| My Classes | Lessons |
|---|---|
| ![My Classes](screenshots/teacher/class-list.png) | ![Lessons](screenshots/teacher/lessons.png) |

| Create Lecture | Create Quiz |
|---|---|
| ![Create Lecture](screenshots/teacher/create-lecture.png) | ![Create Quiz](screenshots/teacher/create-quiz.png) |

#### Gradebook & Assessments

| Gradebook View | Gradebook Import (Excel) |
|---|---|
| ![Gradebook View](screenshots/teacher/gradebook-view.png) | ![Gradebook Import](screenshots/teacher/gradebook-import.png) |

| Quiz Grades | |
|---|---|
| ![Quiz Grades](screenshots/teacher/quiz-grades.png) | |

---

> 📌 More screenshots coming — Student role screenshots will be added here.

---

## Modules

### Admin

**Semester Operation**
- Classes — Assign Teacher, Assign for Section, Assign for Irregular, Update Section
- Grades — Grade Search, Section Grade, Irregular Grades, Grade Card

**System Operation**
- User Management — Insert Student, Insert Teacher, List Students, List Teachers
- Class Management — List Class, List Strand, List Section

**Audit & Monitoring**
- Audit Logs — Admin Activity, Teacher Activity, Student Activity, Login History

### Super Admin

Everything the Admin has, plus:
- Admin Management — Insert Admin, List Admins
- School Year / Semester management

### Teacher

**Class Management & Instruction**
- My Classes — View assigned classes, access lessons, and create lectures/quizzes
- My Students — View student grade cards

**Gradebook & Assessments**
- View and manage student grades
- Import grades directly from Excel
- Monitor and grade quizzes

**Activity Logs**
- My Logs — Track personal system activity
- Student Logs — Monitor activity of enrolled students

---

## Tech Stack

- **Backend** — Laravel (PHP)
- **Frontend** — Bootstrap, AdminLTE, Font Awesome
- **Database** — MySQL
- **Auth** — Laravel Auth with role-based access control

---

## Setup

```bash
git clone [https://github.com/Zmetrical/Laravel-LMS.git](https://github.com/Zmetrical/Laravel-LMS.git)
cd Laravel-LMS

composer install
npm install && npm run dev

cp .env.example .env
php artisan key:generate
