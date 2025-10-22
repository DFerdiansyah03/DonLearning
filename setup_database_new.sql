-- =====================================================
-- DATABASE: smartlms
-- =====================================================
DROP DATABASE IF EXISTS smartlms;
CREATE DATABASE smartlms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smartlms;

-- =====================================================
-- TABLE: users
-- =====================================================
CREATE TABLE users (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student','teacher','admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLE: classes
-- =====================================================
CREATE TABLE classes (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    teacher_id BIGINT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    token VARCHAR(64) UNIQUE NOT NULL,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- TABLE: class_members
-- =====================================================
CREATE TABLE class_members (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    class_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    role_in_class ENUM('student','assistant') DEFAULT 'student',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(class_id, user_id),
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- TABLE: materials
-- =====================================================
CREATE TABLE materials (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    class_id BIGINT NOT NULL,
    teacher_id BIGINT NOT NULL,
    title VARCHAR(255),
    description TEXT,
    file_url VARCHAR(1000),
    file_type ENUM('pdf','ppt','video','link') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- TABLE: quizzes
-- =====================================================
CREATE TABLE quizzes (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    class_id BIGINT NOT NULL,
    title VARCHAR(255),
    instructions TEXT,
    total_score INT DEFAULT 100,
    created_by BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- TABLE: questions
-- =====================================================
CREATE TABLE questions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    quiz_id BIGINT NOT NULL,
    type ENUM('mcq','essay') NOT NULL,
    text TEXT NOT NULL,
    points INT DEFAULT 1,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- =====================================================
-- TABLE: choices
-- =====================================================
CREATE TABLE choices (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    question_id BIGINT NOT NULL,
    text VARCHAR(1000),
    is_correct BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

-- =====================================================
-- TABLE: attempts
-- =====================================================
CREATE TABLE attempts (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    quiz_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    submitted_at TIMESTAMP NULL,
    auto_score FLOAT DEFAULT 0,
    manual_score FLOAT DEFAULT 0,
    final_score FLOAT DEFAULT 0,
    status ENUM('in_progress','submitted','graded') DEFAULT 'in_progress',
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- TABLE: answers
-- =====================================================
CREATE TABLE answers (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    attempt_id BIGINT NOT NULL,
    question_id BIGINT NOT NULL,
    answer_text TEXT,
    selected_choice_id BIGINT NULL,
    is_correct BOOLEAN NULL,
    awarded_points FLOAT DEFAULT 0,
    FOREIGN KEY (attempt_id) REFERENCES attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    FOREIGN KEY (selected_choice_id) REFERENCES choices(id) ON DELETE SET NULL
);

-- =====================================================
-- TABLE: forum_posts
-- =====================================================
CREATE TABLE forum_posts (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    class_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    title VARCHAR(255),
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- TABLE: forum_comments
-- =====================================================
CREATE TABLE forum_comments (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT NOT NULL,
    parent_comment_id BIGINT NULL,
    user_id BIGINT NOT NULL,
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES forum_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_comment_id) REFERENCES forum_comments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
