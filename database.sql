CREATE DATABASE IF NOT EXISTS deafconnect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE deafconnect;

CREATE TABLE IF NOT EXISTS admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(120) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS contacts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  initials VARCHAR(8) NOT NULL,
  description VARCHAR(255) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  contact_id INT UNSIGNED NOT NULL,
  conversation_key VARCHAR(80) NULL,
  sender_type ENUM('guest','admin') NOT NULL,
  sender_name VARCHAR(120) NOT NULL,
  body TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_messages_contact FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
  INDEX idx_messages_conversation (contact_id, conversation_key, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS bookings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(80) NOT NULL,
  last_name VARCHAR(80) NOT NULL,
  email VARCHAR(190) NOT NULL,
  service_type VARCHAR(40) NOT NULL,
  preferred_date DATE NOT NULL,
  preferred_time VARCHAR(20) NOT NULL,
  notes TEXT NULL,
  status ENUM('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_bookings_status (status),
  INDEX idx_bookings_date (preferred_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_reports (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  baseline_score TINYINT UNSIGNED NOT NULL,
  critical_count INT UNSIGNED NOT NULL DEFAULT 0,
  serious_count INT UNSIGNED NOT NULL DEFAULT 0,
  moderate_count INT UNSIGNED NOT NULL DEFAULT 0,
  report_json JSON NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Demo administrator. Change this password immediately after installation.
INSERT INTO admins (username, password_hash, full_name)
SELECT 'admin', '$2y$12$lt2g0VbDD17in6mxXUMoXeXM4d/jaj8ivnYU62fGqYteznctOg3kC', 'DeafConnect Administrator'
WHERE NOT EXISTS (SELECT 1 FROM admins WHERE username='admin');

INSERT INTO contacts (name, initials, description, sort_order)
SELECT * FROM (
  SELECT 'DeafConnect Support' name, 'DC' initials, 'General support — text only' description, 1 sort_order
  UNION ALL SELECT 'Dr Adeyemi','DA','Audiology appointments and support',2
  UNION ALL SELECT 'Community Group','CG','Community events and announcements',3
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM contacts LIMIT 1);

INSERT INTO messages (contact_id,conversation_key,sender_type,sender_name,body,is_read)
SELECT c.id,NULL,'admin','DeafConnect Support','Hello! Welcome to DeafConnect. How can we help you today?',1 FROM contacts c WHERE c.name='DeafConnect Support' AND NOT EXISTS (SELECT 1 FROM messages m WHERE m.contact_id=c.id AND m.conversation_key IS NULL AND m.body='Hello! Welcome to DeafConnect. How can we help you today.') LIMIT 1;
INSERT INTO messages (contact_id,conversation_key,sender_type,sender_name,body,is_read)
SELECT c.id,NULL,'admin','DeafConnect Support','All our support is fully text-based — no phone calls required.',1 FROM contacts c WHERE c.name='DeafConnect Support' AND NOT EXISTS (SELECT 1 FROM messages m WHERE m.contact_id=c.id AND m.conversation_key IS NULL AND m.body='All our support is fully text-based — no phone calls required.') LIMIT 1;
