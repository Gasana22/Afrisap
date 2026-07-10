-- Safarisap.com database schema
-- Engine: MySQL/MariaDB, InnoDB, utf8mb4
-- Organized by domain: Lookups -> Safari domain -> Experiential domain -> Activities -> Media -> Bookings/CMS -> Admin

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. LOOKUPS
-- ============================================================

CREATE TABLE countries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Safari Tours / Trip Tours / School Trips sub-menu categories
CREATE TABLE tour_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    menu_group ENUM('safari', 'trip', 'school') NOT NULL DEFAULT 'safari',
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tour_operators (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(150) NOT NULL,
    logo_path VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(150) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 2. SAFARI DOMAIN
-- ============================================================

-- One landing page per Safari Tours sub-menu item (Gorilla Trekking, Chimp Trekking, ...)
-- "Form for its data & applies for all" -- same template reused per category.
CREATE TABLE category_pages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    brief_overview TEXT NULL,
    detailed_overview TEXT NULL,
    highlights TEXT NULL,
    when_to_visit TEXT NULL,
    unique_about TEXT NULL,
    more_activities TEXT NULL,
    country_id INT UNSIGNED NULL,
    gorilla_permit_info TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_category_pages_category (category_id),
    CONSTRAINT fk_category_pages_category FOREIGN KEY (category_id) REFERENCES tour_categories(id) ON DELETE CASCADE,
    CONSTRAINT fk_category_pages_country FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Safari Destinations: national parks / game reserves
CREATE TABLE destinations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    country_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    overview TEXT NULL,
    why_consider TEXT NULL,
    additional_info TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_destinations_country FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Which national parks a category page (e.g. Gorilla Trekking) is relevant to
CREATE TABLE category_page_parks (
    category_page_id INT UNSIGNED NOT NULL,
    destination_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (category_page_id, destination_id),
    CONSTRAINT fk_cpp_category_page FOREIGN KEY (category_page_id) REFERENCES category_pages(id) ON DELETE CASCADE,
    CONSTRAINT fk_cpp_destination FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE destination_animals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    destination_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    CONSTRAINT fk_destination_animals_destination FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE destination_birds (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    destination_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    CONSTRAINT fk_destination_birds_destination FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Popular Activities shown as tile+box+gallery on a destination page
CREATE TABLE destination_activities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    destination_id INT UNSIGNED NOT NULL,
    activity_id INT UNSIGNED NULL, -- optional link to the master activities catalog (added below)
    title VARCHAR(150) NOT NULL,
    description TEXT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_destination_activities_destination FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bookable itineraries (Safari Tours, and reused for Trip Tours / School Trips)
CREATE TABLE tours (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    budget_type ENUM('Luxury', 'Mid-Range', 'Budget') NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    days INT UNSIGNED NOT NULL,
    min_pax INT UNSIGNED NOT NULL DEFAULT 1,
    max_pax INT UNSIGNED NOT NULL,
    short_overview TEXT NULL,
    full_overview TEXT NULL,
    top_highlights TEXT NULL,
    hotel_info TEXT NULL,
    vehicle_info TEXT NULL,
    flight_info TEXT NULL,
    includes TEXT NULL,
    excludes TEXT NULL,
    operator_id INT UNSIGNED NULL,
    status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_tours_category FOREIGN KEY (category_id) REFERENCES tour_categories(id) ON DELETE RESTRICT,
    CONSTRAINT fk_tours_operator FOREIGN KEY (operator_id) REFERENCES tour_operators(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- A tour must cover 2+ destinations (enforced in application layer)
CREATE TABLE tour_destinations (
    tour_id INT UNSIGNED NOT NULL,
    destination_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (tour_id, destination_id),
    CONSTRAINT fk_tour_destinations_tour FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE,
    CONSTRAINT fk_tour_destinations_destination FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Activities included within a specific tour itinerary (optionally linked to the master activities catalog)
CREATE TABLE tour_activities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tour_id INT UNSIGNED NOT NULL,
    activity_id INT UNSIGNED NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_tour_activities_tour FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tour_faqs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tour_id INT UNSIGNED NOT NULL,
    question VARCHAR(255) NOT NULL,
    answer TEXT NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_tour_faqs_tour FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 3. ACTIVITIES (top-level "Activities" nav menu / master catalog)
-- ============================================================

CREATE TABLE activities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(150) NULL,
    duration_hours DECIMAL(5,2) NULL,
    short_description TEXT NULL,
    full_description TEXT NULL,
    operator_id INT UNSIGNED NULL,
    destination_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_activities_operator FOREIGN KEY (operator_id) REFERENCES tour_operators(id) ON DELETE SET NULL,
    CONSTRAINT fk_activities_destination FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE destination_activities
    ADD CONSTRAINT fk_destination_activities_activity FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE SET NULL;

ALTER TABLE tour_activities
    ADD CONSTRAINT fk_tour_activities_activity FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE SET NULL;

-- ============================================================
-- 4. EXPERIENTIAL TOURS DOMAIN
-- ============================================================

CREATE TABLE experience_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE, -- Cultural, Farm, Manufactural/Factory, Sports, Ghetto
    slug VARCHAR(120) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE service_providers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(150) NOT NULL,
    logo_path VARCHAR(255) NULL,
    region VARCHAR(100) NULL,
    experience_type_id INT UNSIGNED NOT NULL,
    contact_person VARCHAR(150) NULL,
    email VARCHAR(150) NULL,
    phone VARCHAR(50) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_service_providers_type FOREIGN KEY (experience_type_id) REFERENCES experience_types(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Experiential "destinations": tribes (Cultural), farm types (Farm), sports, industries (Manufacturing), ghetto areas
CREATE TABLE experience_destinations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    experience_type_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    location VARCHAR(150) NULL,
    short_overview TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_experience_destinations_type FOREIGN KEY (experience_type_id) REFERENCES experience_types(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Activities / cultural uniqueness boxes on an experience destination page (tile + description, no day-by-day)
CREATE TABLE experience_destination_activities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    experience_destination_id INT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_eda_destination FOREIGN KEY (experience_destination_id) REFERENCES experience_destinations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE experience_tours (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    experience_type_id INT UNSIGNED NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    days INT UNSIGNED NOT NULL,
    min_pax INT UNSIGNED NOT NULL DEFAULT 1,
    max_pax INT UNSIGNED NOT NULL,
    short_overview TEXT NULL,
    full_overview TEXT NULL,
    top_highlights TEXT NULL,
    status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_experience_tours_type FOREIGN KEY (experience_type_id) REFERENCES experience_types(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- An experience tour must cover 2+ experience destinations (enforced in application layer)
CREATE TABLE experience_tour_destinations (
    experience_tour_id INT UNSIGNED NOT NULL,
    experience_destination_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (experience_tour_id, experience_destination_id),
    CONSTRAINT fk_etd_tour FOREIGN KEY (experience_tour_id) REFERENCES experience_tours(id) ON DELETE CASCADE,
    CONSTRAINT fk_etd_destination FOREIGN KEY (experience_destination_id) REFERENCES experience_destinations(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Auto-populated from the chosen destinations' activities, then editable/removable per tour --
-- stored as independent copies (source_activity_id kept only for traceability).
CREATE TABLE experience_tour_activities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    experience_tour_id INT UNSIGNED NOT NULL,
    source_activity_id INT UNSIGNED NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_eta_tour FOREIGN KEY (experience_tour_id) REFERENCES experience_tours(id) ON DELETE CASCADE,
    CONSTRAINT fk_eta_source FOREIGN KEY (source_activity_id) REFERENCES experience_destination_activities(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 5. MEDIA (polymorphic gallery used across every entity above)
-- ============================================================

CREATE TABLE media (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(50) NOT NULL, -- 'destination','category_page','tour','activity','experience_destination','experience_tour','tour_operator','service_provider'
    entity_id INT UNSIGNED NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    caption VARCHAR(255) NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_media_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 6. BOOKINGS, QUOTES & ABOUT US CONTENT
-- ============================================================

CREATE TABLE bookings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bookable_type ENUM('tour', 'experience_tour') NOT NULL,
    bookable_id INT UNSIGNED NOT NULL,
    customer_name VARCHAR(150) NOT NULL,
    customer_email VARCHAR(150) NOT NULL,
    customer_phone VARCHAR(50) NULL,
    travel_date DATE NULL,
    num_people INT UNSIGNED NOT NULL DEFAULT 1,
    message TEXT NULL,
    status ENUM('pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_bookings_bookable (bookable_type, bookable_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE quote_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quote_type ENUM('safari', 'experiential') NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(50) NULL,
    details TEXT NULL,
    status ENUM('new', 'contacted', 'closed') NOT NULL DEFAULT 'new',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE agents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    company_name VARCHAR(150) NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(50) NULL,
    region VARCHAR(100) NULL,
    message TEXT NULL,
    status ENUM('new', 'approved', 'rejected') NOT NULL DEFAULT 'new',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE careers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT NULL,
    location VARCHAR(100) NULL,
    status ENUM('open', 'closed') NOT NULL DEFAULT 'open',
    posted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE career_applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    career_id INT UNSIGNED NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(50) NULL,
    cv_path VARCHAR(255) NULL,
    cover_letter TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_career_applications_career FOREIGN KEY (career_id) REFERENCES careers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE blog_posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL UNIQUE,
    excerpt TEXT NULL,
    body LONGTEXT NULL,
    cover_image_path VARCHAR(255) NULL,
    author VARCHAR(100) NULL,
    status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Simple CMS pages: About, Uganda Travel Tips, Contact Us, etc.
CREATE TABLE pages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(120) NOT NULL UNIQUE,
    title VARCHAR(200) NOT NULL,
    body LONGTEXT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 7. ADMIN
-- ============================================================

CREATE TABLE admin_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'editor') NOT NULL DEFAULT 'editor',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
