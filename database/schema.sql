-- ASC Library Management System Database Schema
-- MySQL 8.0

CREATE DATABASE IF NOT EXISTS asc_library;
USE asc_library;

-- Users table for authentication
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('admin', 'librarian', 'student') NOT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    last_login DATETIME,
    reset_token VARCHAR(255),
    reset_expiry DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Books table
CREATE TABLE books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    main_creator VARCHAR(255),
    date_of_publication DATE,
    publisher VARCHAR(255),
    call_number VARCHAR(50),
    accession_number VARCHAR(50) UNIQUE,
    language VARCHAR(50),
    location VARCHAR(100),
    parallel_title VARCHAR(255),
    variant_title VARCHAR(255),
    corporate_body VARCHAR(255),
    place_of_publication VARCHAR(100),
    contributors TEXT,
    edition_statement VARCHAR(100),
    extent_of_text VARCHAR(100),
    illustrative_content TEXT,
    dimension VARCHAR(100),
    accompanying_materials TEXT,
    series_title VARCHAR(255),
    supplementary_content TEXT,
    isbn VARCHAR(20),
    content_type VARCHAR(50),
    media_type VARCHAR(50),
    carrier_type VARCHAR(50),
    url TEXT,
    prefix ENUM('CER','CIR','FIC','FIL','FOLIO','GN-FIC','GN-NF','ISL','LR-FIC','LR-NF','MEP','NF','PAM','PB-FIC','PB-NF','PHD','REF','TR-FIC','TR-NF'),
    electronic_access TEXT,
    access_topical TEXT,
    access_personal TEXT,
    access_corporate TEXT,
    access_geographical TEXT,
    type_of_material VARCHAR(100),
    volume_copy VARCHAR(50),
    on_shelf BOOLEAN DEFAULT TRUE,
    content TEXT,
    abstract_summary TEXT,
    reviews TEXT,
    acquisition_mode VARCHAR(100),
    donor VARCHAR(255),
    entered_by VARCHAR(100),
    updated_by VARCHAR(100),
    date_entered DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Academic Coursework table
CREATE TABLE academic_coursework (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    creator VARCHAR(255),
    institution VARCHAR(255),
    program_course VARCHAR(255),
    date_year YEAR,
    extent_of_material TEXT,
    illustrative_details TEXT,
    dimension VARCHAR(100),
    supplementary_content TEXT,
    call_number VARCHAR(50),
    accession VARCHAR(50) UNIQUE,
    language VARCHAR(50),
    location VARCHAR(100),
    type_of_research_study VARCHAR(100),
    electronic_access TEXT,
    type_of_material VARCHAR(100),
    subjects_keywords TEXT,
    abstract TEXT,
    copy INT DEFAULT 1,
    on_shelf BOOLEAN DEFAULT TRUE,
    out BOOLEAN DEFAULT FALSE,
    entered_by VARCHAR(100),
    updated_by VARCHAR(100),
    date_entered DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Electronic Resources table
CREATE TABLE electronic_resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    creator VARCHAR(255),
    publisher VARCHAR(255),
    date DATE,
    identifier VARCHAR(100),
    description TEXT,
    source TEXT,
    format_language VARCHAR(50),
    contributor VARCHAR(255),
    electronic_access TEXT,
    type_of_material VARCHAR(100),
    relation TEXT,
    rights TEXT,
    coverage TEXT,
    subjects TEXT,
    entered_by VARCHAR(100),
    updated_by VARCHAR(100),
    date_entered DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Patrons table
CREATE TABLE patrons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    id_number VARCHAR(50) UNIQUE NOT NULL,
    gender ENUM('Male','Female','Other'),
    group VARCHAR(100),
    course_department VARCHAR(100),
    year_level VARCHAR(50),
    address TEXT,
    contact_number VARCHAR(20),
    email VARCHAR(100),
    fine DECIMAL(10,2) DEFAULT 0.00,
    payment DECIMAL(10,2) DEFAULT 0.00,
    official_receipt_number VARCHAR(50),
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    date_entered DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Circulation table
CREATE TABLE circulation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patron_id INT NOT NULL,
    resource_type ENUM('book', 'academic_coursework', 'electronic_resource') NOT NULL,
    resource_id INT NOT NULL,
    borrow_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE,
    actual_return DATE,
    fine DECIMAL(10,2) DEFAULT 0.00,
    payment DECIMAL(10,2) DEFAULT 0.00,
    receipt_number VARCHAR(50),
    status ENUM('borrowed', 'returned', 'overdue', 'lost') DEFAULT 'borrowed',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patron_id) REFERENCES patrons(id) ON DELETE CASCADE
);

-- Reservations table
CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patron_id INT NOT NULL,
    resource_type ENUM('book', 'academic_coursework', 'electronic_resource') NOT NULL,
    resource_id INT NOT NULL,
    reservation_date DATETIME NOT NULL,
    pickup_date DATE,
    expiry_date DATE,
    status ENUM('pending', 'ready', 'picked_up', 'expired', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patron_id) REFERENCES patrons(id) ON DELETE CASCADE
);

-- Inventory table
CREATE TABLE inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resource_type ENUM('book', 'academic_coursework', 'electronic_resource') NOT NULL,
    resource_id INT NOT NULL,
    quantity INT DEFAULT 1,
    available_quantity INT DEFAULT 1,
    acquisition_date DATE,
    source VARCHAR(255),
    cost DECIMAL(10,2),
    status ENUM('Available', 'Damaged', 'Lost', 'Maintenance') DEFAULT 'Available',
    condition_notes TEXT,
    entered_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Audit logs table
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- System settings table
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_by VARCHAR(100),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin user
INSERT INTO users (username, password, name, email, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@asclibrary.edu', 'admin');

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('library_name', 'ASC Library Management System', 'Name of the library system'),
('max_borrow_days', '14', 'Maximum days for borrowing resources'),
('fine_per_day', '5.00', 'Fine amount per day for overdue items'),
('max_reservations', '5', 'Maximum number of reservations per patron'),
('library_address', '123 University Street, Academic City', 'Library physical address'),
('library_phone', '+1-234-567-8900', 'Library contact phone number'),
('library_email', 'library@asclibrary.edu', 'Library contact email address');

-- Create indexes for better performance
CREATE INDEX idx_books_title ON books(title);
CREATE INDEX idx_books_creator ON books(main_creator);
CREATE INDEX idx_books_accession ON books(accession_number);
CREATE INDEX idx_patrons_id_number ON patrons(id_number);
CREATE INDEX idx_circulation_patron ON circulation(patron_id);
CREATE INDEX idx_circulation_resource ON circulation(resource_type, resource_id);
CREATE INDEX idx_circulation_status ON circulation(status);
CREATE INDEX idx_audit_logs_user ON audit_logs(user_id);
CREATE INDEX idx_audit_logs_created ON audit_logs(created_at);