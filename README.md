# Trinity Learning Management System

A web-based Learning Management System built with Laravel and Bootstrap.
Supports multiple roles — Super Admin, Admin, Teacher, and Student — each with their own portal and access level, plus secure public grade viewing for parents.

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

Built for Trinity, this system manages the full academic cycle — from setting up school years and semesters, assigning teachers and sections, tracking student grades, to monitoring all user activity through audit logs. Access is controlled per role, so each user only sees what's relevant to them, while parents can view grades via secure, time-limited email links.

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

### Student

#### Dashboard & Content

| Class View | Lectures |
|---|---|
| ![Class View](screenshots/student/class.png) | ![Lectures](screenshots/student/lectures.png) |

| Lessons | Quizzes |
|---|---|
| ![Lessons](screenshots/student/lessons.png) | ![Quizzes](screenshots/student/quizzes.png) |

| View Quiz | |
|---|---|
| ![View Quiz](screenshots/student/view-quiz.png) | |

#### Academic Records

| Grade Details | |
|---|---|
| ![Grade Details](screenshots/student/grade-details.png) | |

---

### Parent (Public View)

Parents can access their child's grades securely without an account via a magic link sent to their email.

| Parent Email Link | Parent Grade View |
|---|---|
| ![Parent Email Link](screenshots/parent/parent-email.png) | ![Parent Grade View](screenshots/parent/parent-view.png) |

| Parent Grade Card | |
|---|---|
| ![Parent Grade Card](screenshots/parent/parent-gradecard.png) | |

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

### Student

**Academics**
- My Classes — Quick access to enrolled classes directly from the sidebar
- Content Hub — Access class lectures, lessons, and take quizzes
- Grades & Gradecard — View individual assessment grades and final report cards
- Notifications — Alerts for pending quizzes on the sidebar

### Parent Access
- Secure email dispatch system containing a unique, time-limited magic link.
- Public, read-only view of their child's grades and report card.

---

## Tech Stack

- **Backend** — Laravel (PHP)
- **Frontend** — Bootstrap, AdminLTE, Font Awesome
- **Database** — MySQL
- **Auth** — Laravel Auth with role-based access control (Admin, Teacher, Student) + Signed Routes (Parent Access)

---

## Setup

```bash
git clone [https://github.com/Zmetrical/Laravel-LMS.git](https://github.com/Zmetrical/Laravel-LMS.git)
cd Laravel-LMS

composer install
npm install && npm run dev

cp .env.example .env
php artisan key:generate
