-- Yanza Wellness Database Schema
-- Designed for MySQL / MariaDB on cPanel

-- 1. The Users Table
-- Stores user credentials. We use VARCHAR(36) for the ID to store UUIDs.
CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(36) PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    mfa_secret VARCHAR(100) DEFAULT NULL,
    role VARCHAR(20) DEFAULT 'user', -- 'user' or 'admin'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. The Journals Table
-- Stores sensitive diary entries.
-- The ON DELETE CASCADE rule means if a user deletes their account, their journals are instantly purged.
CREATE TABLE IF NOT EXISTS journals (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    mood INT NOT NULL CHECK (mood BETWEEN 1 AND 5),
    entry_text TEXT NOT NULL, -- This will store AES-256-GCM encrypted strings
    is_private TINYINT(1) DEFAULT 1, -- 1 = Private, 0 = Shared Anonymously
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. The Counselors Table
-- Stores verified profile listings for directory display.
CREATE TABLE IF NOT EXISTS counselors (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    credentials VARCHAR(255) NOT NULL,
    specialties VARCHAR(255) NOT NULL,
    availability VARCHAR(50) DEFAULT 'Available', -- 'Available', 'Busy', 'Offline'
    avatar VARCHAR(255) DEFAULT NULL,
    rating DECIMAL(3,2) DEFAULT 5.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. The Resources Table (Educational Articles)
CREATE TABLE IF NOT EXISTS resources (
    id VARCHAR(36) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    summary TEXT NOT NULL,
    content TEXT NOT NULL,
    category VARCHAR(100) NOT NULL,
    read_time VARCHAR(20) DEFAULT '5 min read',
    banner_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. The Community Feed Table
-- Stores anonymous shared posts.
-- Linked to users via foreign key. ON DELETE CASCADE ensures posts are erased if the user deletes their account.
CREATE TABLE IF NOT EXISTS community_feed (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) DEFAULT NULL,
    username_alias VARCHAR(100) DEFAULT 'Anonymous',
    title VARCHAR(255) DEFAULT NULL,
    content TEXT NOT NULL,
    trigger_warning VARCHAR(100) DEFAULT NULL,
    is_flagged TINYINT(1) DEFAULT 0, -- 0 = Active, 1 = Flagged for review
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Data for Directory
INSERT INTO counselors (id, name, credentials, specialties, availability, avatar, rating) VALUES
('c1e1a123-1234-5678-90ab-cdef12345678', 'Shanya Khesooba', 'Therapist, Clinical Psychologist (M.Sc)', 'Anxiety, Depression, Cultural Adjustment', 'Available', NULL, 4.8),
('c2e2b234-2345-6789-01bc-defa23456789', 'Grace Aurna', 'Counselor, Social worker (BSW)', 'Grief, Peer Relationships, Youth Support', 'Busy', NULL, 4.9),
('c3e3c345-3456-7890-12cd-efab34567890', 'Drake Pius Isooba', 'Peer Specialist, Mental Health Advocate (B.A. Soc)', 'Youth Mentorship, Stress Recovery, Community Circles', 'Available', NULL, 4.9);

-- Seed Data for Resources
INSERT INTO resources (id, title, summary, content, category, read_time, banner_image) VALUES
('r1a1a123-1234-5678-90ab-cdef12345678', 'Navigating Anxiety in Uganda', 'A short guide on managing anxiety in urban and rural environments with local support resources.', '<p>Anxiety is a common human experience, but managing it requires local understanding. In Uganda, support networks are growing. This guide covers deep breathing, connecting with community peer circles, and finding local professional help.</p>', 'Mental Health A-Z', '6 min read', NULL),
('r2b2b234-2345-6789-01bc-defa23456789', 'Traditional Support and Modern Therapy', 'Understanding the balance between traditional community structures (like "Yanza") and clinical therapies.', '<p>The concept of "Yanza" represents caring and embracing. This article explores how modern psychotherapy can be integrated with community support circles in East Africa to improve mental well-being.</p>', 'Research', '8 min read', NULL);
