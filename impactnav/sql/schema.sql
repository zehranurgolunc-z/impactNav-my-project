-- =============================================================
-- impactNav Database Schema
-- MySQL 8+ / utf8mb4 / InnoDB
-- =============================================================

CREATE DATABASE IF NOT EXISTS impactnav CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE impactnav;

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

CREATE TABLE users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email         VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('volunteer','org') NOT NULL,
    full_name     VARCHAR(200) NOT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE volunteer_profiles (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED NOT NULL UNIQUE,
    city          VARCHAR(100),
    district      VARCHAR(100),
    remote_ok     TINYINT(1) NOT NULL DEFAULT 0,
    skills        TEXT,
    interests     TEXT,
    bio           TEXT,
    embedding     MEDIUMTEXT,
    embedding_at  DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_city (city)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE categories (
    id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug  VARCHAR(60) NOT NULL UNIQUE,
    label VARCHAR(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO categories (slug, label) VALUES
('egitim',     'Nitelikli Eğitim'),
('esitsizlik', 'Eşitsizliklerin Azaltılması'),
('sehir',      'Sürdürülebilir Şehirler ve Topluluklar'),
('cevre',      'Çevre ve İklim'),
('saglik',     'Sağlık ve İyilik Hali'),
('genclik',    'Gençlik ve Spor'),
('kadin',      'Kadın ve Haklar'),
('hayvan',     'Hayvan Hakları'),
('multecilik', 'Mültecilik ve Göç'),
('kultur',     'Kültür ve Sanat'),
('dijital',    'Dijital Haklar'),
('engelli',    'Engellilik ve Erişilebilirlik');

CREATE TABLE volunteer_categories (
    volunteer_id INT UNSIGNED NOT NULL,
    category_id  INT UNSIGNED NOT NULL,
    PRIMARY KEY (volunteer_id, category_id),
    FOREIGN KEY (volunteer_id) REFERENCES volunteer_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id)  REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE organizations (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NOT NULL UNIQUE,
    org_name     VARCHAR(255) NOT NULL,
    org_type     VARCHAR(100),
    city         VARCHAR(100),
    website      VARCHAR(300),
    description  TEXT,
    verified     TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE listings (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    org_id          INT UNSIGNED NOT NULL,
    title           VARCHAR(255) NOT NULL,
    description     TEXT NOT NULL,
    required_skills TEXT,
    city            VARCHAR(100),
    remote_ok       TINYINT(1) NOT NULL DEFAULT 0,
    weekly_hours    TINYINT UNSIGNED,
    status          ENUM('active','paused','closed') NOT NULL DEFAULT 'active',
    embedding       MEDIUMTEXT,
    embedding_at    DATETIME,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_org (org_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE listing_categories (
    listing_id  INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (listing_id, category_id),
    FOREIGN KEY (listing_id)  REFERENCES listings(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE match_scores (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    volunteer_id     INT UNSIGNED NOT NULL,
    listing_id       INT UNSIGNED NOT NULL,
    score            DECIMAL(5,4) NOT NULL,
    rationale        TEXT,
    method           ENUM('embedding','gpt','hybrid') NOT NULL DEFAULT 'hybrid',
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_vol_listing (volunteer_id, listing_id),
    FOREIGN KEY (volunteer_id) REFERENCES volunteer_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (listing_id)   REFERENCES listings(id) ON DELETE CASCADE,
    INDEX idx_volunteer_score (volunteer_id, score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE applications (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    volunteer_id INT UNSIGNED NOT NULL,
    listing_id   INT UNSIGNED NOT NULL,
    status       ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
    message      TEXT,
    applied_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_application (volunteer_id, listing_id),
    FOREIGN KEY (volunteer_id) REFERENCES volunteer_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (listing_id)   REFERENCES listings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE chat_sessions (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED,
    session_key  VARCHAR(64) NOT NULL UNIQUE,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE chat_messages (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id  INT UNSIGNED NOT NULL,
    role        ENUM('system','user','assistant') NOT NULL,
    content     TEXT NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES chat_sessions(id) ON DELETE CASCADE,
    INDEX idx_session (session_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET foreign_key_checks = 1;
