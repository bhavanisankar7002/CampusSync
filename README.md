# CampusSync

Connect • Collaborate • Create

CampusSync is a college networking and collaboration platform that connects students and faculty in one place — share project ideas, form teams, register for hackathons, submit projects for evaluation, and build a student profile that reflects real work and skills.

🔗 Live Demo: campussync.42web.io

📌 About

CampusSync gives students a single platform to turn ideas into real projects. Students can post ideas and recruit collaborators, form teams for hackathons and events, submit full projects for faculty evaluation, and build a profile that showcases their skills and achievements. Faculty members can review submissions, score them against a structured rubric, and give feedback — bringing mentorship directly into the platform.

✨ Features

For Students


Register and log in with a role-based account (student/faculty)
Build a profile with skills, bio, socials (GitHub, LinkedIn, portfolio), and achievements
Post project ideas and apply to join others' ideas
Create or join teams for hackathons and campus events
Register teams for hackathons
Submit full projects with abstracts, objectives, diagrams, and demo links
Share updates through a post/feed system with image and video uploads


For Faculty


Review submitted ideas and approve, request revisions, or reject them
Evaluate projects using a multi-criteria rubric (innovation, technical execution, documentation, presentation, implementation, scalability, collaboration, problem-solving)
Leave feedback and ratings on student ideas
Track submission status and assign marks


🛠 Tech Stack

LayerTechnologyBackendPHPDatabaseMySQL (InnoDB)FrontendHTML, CSS, JavaScriptHostingInfinityFree

🗄 Database Schema

The platform is backed by a relational schema with the following core tables:

TablePurposeusersStudent and faculty accounts, profiles, and skillsideasPosted project ideas, open for collaborationidea_applicationsApplications from students to join an ideateams / team_membersInternal collaboration teamsevent_teams / event_team_enrollmentsTeam formation for hackathons/eventshackathons / hackathon_teamsHackathon listings and team registrationsfeedbackFaculty comments and ratings on ideasevaluationsStructured faculty scoring rubric for ideasproject_submissionsFull project submissions with faculty review workflow

Full schema with relationships is in setup.sql.
🚀 Getting Started

Prerequisites


XAMPP (or any local PHP + MySQL environment)
PHP 7.4+
MySQL 5.6+
