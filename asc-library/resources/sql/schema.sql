-- ASC Library Management System Schema

DROP TABLE IF EXISTS help_contact;
DROP TABLE IF EXISTS developers;
DROP TABLE IF EXISTS system_info;
DROP TABLE IF EXISTS user_settings;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS export_backups;
DROP TABLE IF EXISTS user_admin;
DROP TABLE IF EXISTS roles_permissions;
DROP TABLE IF EXISTS staff;
DROP TABLE IF EXISTS patron_masterlist;
DROP TABLE IF EXISTS accession_list;
DROP TABLE IF EXISTS acquisition;
DROP TABLE IF EXISTS inventory;
DROP TABLE IF EXISTS fines_payment;
DROP TABLE IF EXISTS resource_reservation;
DROP TABLE IF EXISTS borrow_return;
DROP TABLE IF EXISTS patrons;
DROP TABLE IF EXISTS electronic_resources;
DROP TABLE IF EXISTS academic_coursework;
DROP TABLE IF EXISTS books;

CREATE TABLE books (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255),
  main_creator VARCHAR(255),
  date_of_publication DATE,
  publisher VARCHAR(255),
  call_number VARCHAR(100),
  accession_number VARCHAR(100),
  language VARCHAR(50),
  location VARCHAR(100),
  parallel_title VARCHAR(255),
  variant_title VARCHAR(255),
  corporate_body VARCHAR(255),
  place_of_publication VARCHAR(255),
  contributors TEXT,
  edition_statement VARCHAR(100),
  extent_of_text VARCHAR(100),
  illustrative_content TEXT,
  dimension VARCHAR(100),
  accompanying_materials TEXT,
  series_title VARCHAR(255),
  supplementary_content TEXT,
  isbn VARCHAR(50),
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
  on_shelf BOOLEAN,
  content TEXT,
  abstract_summary TEXT,
  reviews TEXT,
  acquisition_mode VARCHAR(100),
  donor VARCHAR(255),
  entered_by VARCHAR(100),
  updated_by VARCHAR(100),
  date_entered DATETIME,
  date_updated DATETIME
);

CREATE TABLE academic_coursework (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255),
  creator VARCHAR(255),
  institution VARCHAR(255),
  program_course VARCHAR(255),
  date_year YEAR,
  extent_of_material TEXT,
  illustrative_details TEXT,
  dimension VARCHAR(100),
  supplementary_content TEXT,
  call_number VARCHAR(100),
  accession VARCHAR(100),
  language VARCHAR(50),
  location VARCHAR(100),
  type_of_research VARCHAR(100),
  electronic_access TEXT,
  type_of_material VARCHAR(100),
  subjects_keywords TEXT,
  abstract TEXT,
  copy INT,
  on_shelf BOOLEAN,
  out BOOLEAN,
  entered_by VARCHAR(100),
  updated_by VARCHAR(100),
  date_entered DATETIME,
  date_updated DATETIME
);

CREATE TABLE electronic_resources (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255),
  creator VARCHAR(255),
  publisher VARCHAR(255),
  date DATE,
  identifier VARCHAR(100),
  description TEXT,
  source TEXT,
  format_language VARCHAR(100),
  contributor TEXT,
  electronic_access TEXT,
  type_of_material VARCHAR(100),
  relation TEXT,
  rights TEXT,
  coverage TEXT,
  subjects TEXT,
  entered_by VARCHAR(100),
  updated_by VARCHAR(100),
  date_entered DATETIME,
  date_updated DATETIME
);

CREATE TABLE patrons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255),
  id_number VARCHAR(100),
  gender ENUM('Male','Female','Other'),
  `group` VARCHAR(100),
  course_department VARCHAR(255),
  year_level VARCHAR(50),
  address TEXT,
  contact_number VARCHAR(50),
  fine DECIMAL(10,2),
  payment DECIMAL(10,2),
  official_receipt_number VARCHAR(100),
  email VARCHAR(255),
  date_entered DATETIME
);

CREATE TABLE borrow_return (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patron_id INT,
  resource_id INT,
  borrow_date DATE,
  return_date DATE,
  status ENUM('Borrowed','Returned'),
  receipt TEXT,
  FOREIGN KEY (patron_id) REFERENCES patrons(id)
);

CREATE TABLE resource_reservation (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patron_id INT,
  resource_id INT,
  reservation_date DATE,
  status ENUM('Pending','Approved','Cancelled'),
  receipt TEXT
);

CREATE TABLE fines_payment (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patron_id INT,
  amount DECIMAL(10,2),
  payment_date DATE,
  receipt_number VARCHAR(100)
);

CREATE TABLE inventory (
  id INT AUTO_INCREMENT PRIMARY KEY,
  resource_type VARCHAR(100),
  transaction_type ENUM('Add','Remove','Update'),
  quantity INT,
  transaction_date DATE,
  saved_as ENUM('CSV','PDF','EXCEL')
);

CREATE TABLE acquisition (
  id INT AUTO_INCREMENT PRIMARY KEY,
  resource_type VARCHAR(100),
  title VARCHAR(255),
  quantity INT,
  acquisition_date DATE,
  source VARCHAR(255),
  export_format ENUM('CSV','PDF','EXCEL')
);

CREATE TABLE accession_list (
  id INT AUTO_INCREMENT PRIMARY KEY,
  resource_id INT,
  accession_number VARCHAR(100),
  export_format ENUM('CSV','PDF','EXCEL')
);

CREATE TABLE patron_masterlist (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patron_id INT,
  export_format ENUM('CSV','PDF','EXCEL')
);

CREATE TABLE staff (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255),
  role VARCHAR(100),
  email VARCHAR(255),
  contact_number VARCHAR(50)
);

CREATE TABLE roles_permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  role_name VARCHAR(100),
  permissions TEXT
);

CREATE TABLE user_admin (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) UNIQUE,
  password_hash TEXT,
  role VARCHAR(100),
  status ENUM('Active','Inactive')
);

CREATE TABLE export_backups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  backup_type ENUM('Full','Partial'),
  date_created DATETIME,
  file_path TEXT
);

CREATE TABLE audit_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  action TEXT,
  timestamp DATETIME
);

CREATE TABLE user_settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  setting_key VARCHAR(100),
  setting_value TEXT
);

CREATE TABLE system_info (
  id INT AUTO_INCREMENT PRIMARY KEY,
  system_name VARCHAR(255),
  version VARCHAR(50),
  description TEXT
);

CREATE TABLE developers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255),
  email VARCHAR(255),
  contact_number VARCHAR(50)
);

CREATE TABLE help_contact (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  message TEXT,
  contact_email VARCHAR(255),
  contact_phone VARCHAR(50),
  date_sent DATETIME
);

-- Seed admin user (password: admin123)
-- Generate a PHP password hash and paste below:
-- php -r "echo password_hash('admin123', PASSWORD_DEFAULT), PHP_EOL;"
-- Example:
-- INSERT INTO user_admin (username, password_hash, role, status) VALUES
-- ('admin', '$2y$10$REPLACE_WITH_HASH', 'Admin', 'Active');