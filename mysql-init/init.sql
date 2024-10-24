-- init.sql

-- Create departments table
CREATE TABLE IF NOT EXISTS departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    added_by INT,
    created_ip VARCHAR(45),
    last_edited_ip VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create positions table
CREATE TABLE IF NOT EXISTS positions (
    position_id INT AUTO_INCREMENT PRIMARY KEY,
    position_name VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    phone_number VARCHAR(20),
    department_id INT,
    position_id INT,
    role ENUM('super', 'admin', 'user') NOT NULL,
    is_active ENUM('pending', 'active', 'inactive') DEFAULT 'pending',
    is_online BOOLEAN DEFAULT FALSE,
    last_login TIMESTAMP NULL DEFAULT NULL,
    failed_login_attempts INT DEFAULT 0,
    password_last_changed TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_ip_address VARCHAR(45),
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    security_question VARCHAR(255),
    security_answer_hash VARCHAR(255),
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL,
    FOREIGN KEY (position_id) REFERENCES positions(position_id) ON DELETE SET NULL
);

-- Create agents table
CREATE TABLE IF NOT EXISTS agents (
    agent_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    agent_group VARCHAR(50) DEFAULT 'default',
    operating_system VARCHAR(100),
    cluster_node VARCHAR(50),
    version VARCHAR(20),
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


-- Add indexes for better query performance

-- Indexes for users table
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_department ON users(department_id);
CREATE INDEX idx_users_position ON users(position_id);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_status ON users(is_active);

-- Indexes for agents table
CREATE INDEX idx_agents_name ON agents(name);
CREATE INDEX idx_agents_ip ON agents(ip_address);
CREATE INDEX idx_agents_status ON agents(status);


-- Insert default data

-- Insert default positions
INSERT INTO positions (position_name) VALUES ('Super Admin'), ('Admin'), ('User');

-- Insert default departments 
INSERT INTO departments (department_name, added_by, created_ip, last_edited_ip) VALUES ('AICS', 1, '127.0.0.1', '127.0.0.1');

-- Insert default super user
INSERT INTO users (username, email, password_hash, department_id, role, is_active, is_online, password_last_changed, position_id)
VALUES ('superadmin', 'super@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'super', 1, 0, NOW(), 1)
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- Insert default admin user
INSERT INTO users (username, email, password_hash, department_id, role, is_active, is_online, password_last_changed, position_id)
VALUES ('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'admin', 1, 0, NOW(), 2)
ON DUPLICATE KEY UPDATE updated_at = NOW();

INSERT INTO agents (name, ip_address, operating_system, cluster_node, version, registration_date, status) VALUES
('wifi-student.msu.edu.my', '10.20.20.3', 'BSD 14.0', 'node01', 'v4.3.10', '2023-01-18 18:44:32', 'active'),
('eklas.msu.edu.my', '10.0.11.73', 'Ubuntu 20.04.6 LTS', 'node01', 'v4.7.5', '2023-01-18 20:16:02', 'active'),
('www.msucollege.edu.my', '10.0.11.203', 'Ubuntu 22.04.4 LTS', 'node01', 'v4.3.10', '2023-04-15 14:33:46', 'active'),
('db3.msumedicalcentre.com', '10.0.16.47', 'Microsoft Windows Server 2016 Standard 10.0.14393.7259', 'node01', 'v4.4.1', '2023-06-23 16:17:37', 'active'),
('app1.msumedicalcentre.com', '10.0.16.52', 'Microsoft Windows Server 2016 Standard 10.0.14393.7159', 'node01', 'v4.4.1', '2023-06-23 18:22:37', 'active'),
('app2.msumedicalcentre.com', '10.0.16.53', 'Microsoft Windows Server 2016 Standard 10.0.14393.7159', 'node01', 'v4.4.1', '2023-06-23 18:25:05', 'active'),
('klas2.jgu.ac.id', '10.0.10.187', 'Ubuntu 18.04.6 LTS', 'node01', 'v4.7.2', '2023-07-26 13:38:24', 'active'),
('kpi.msu.edu.my', '10.0.11.147', 'Ubuntu 18.04.6 LTS', 'node01', 'v4.7.2', '2023-07-26 13:45:58', 'active'),
('kpi.msumedicalcentre.com', '10.0.11.159', 'Ubuntu 18.04.6 LTS', 'node01', 'v4.7.2', '2023-07-26 13:50:40', 'active'),
('kpi.jgu.ac.id', '10.0.11.99', 'Ubuntu 18.04.6 LTS', 'node01', 'v4.7.2', '2023-07-26 13:54:22', 'active'),
('speedtest.msu.edu.my', '10.0.10.150', 'Ubuntu 20.04.6 LTS', 'node01', 'v4.4.2', '2023-07-26 14:06:13', 'active'),
('ldap.msu.edu.my', '10.0.10.208', 'Ubuntu 22.04.4 LTS', 'node01', 'v4.4.2', '2023-07-26 14:33:31', 'active'),
('alumni.msu.edu.my', '10.0.11.120', 'Ubuntu 18.04.6 LTS', 'node01', 'v4.4.2', '2023-08-06 12:06:05', 'active'),
('job-app.msu.edu.my', '10.0.11.121', 'Ubuntu 18.04.6 LTS', 'node01', 'v4.4.2', '2023-08-06 12:11:00', 'active'),
('ojs.msu.edu.my', '10.0.11.141', 'Ubuntu 18.04.6 LTS', 'node01', 'v4.7.2', '2023-08-06 12:17:52', 'active'),
('srm.msu.edu.my', '10.0.11.87', 'Ubuntu 18.04.6 LTS', 'node01', 'v4.7.2', '2023-08-06 13:55:05', 'active'),
('survey.msu.edu.my', '10.0.11.207', 'Ubuntu 18.04.6 LTS', 'node01', 'v4.4.2', '2023-08-06 14:04:53', 'active'),
('isrm.msu.edu.my', '10.0.10.202', 'Ubuntu 18.04.6 LTS', 'node01', 'v4.4.2', '2023-08-06 14:10:22', 'active'),
('www.jgu.ac.id', '10.0.11.135', 'Debian GNU/Linux 9', 'node01', 'v4.4.2', '2023-08-06 14:13:37', 'active'),
('ai.msu.edu.my', '10.0.11.149', 'Ubuntu 22.04.4 LTS', 'node01', 'v4.4.2', '2023-08-06 14:16:39', 'active'),
('klas2.msu.edu.my', '10.0.11.231', 'Ubuntu 18.04.6 LTS', 'node01', 'v4.8.0', '2023-11-13 13:05:17', 'active'),
('portal.msu.edu.my', '10.0.11.72', 'Ubuntu 18.04.6 LTS', 'node01', 'v4.8.2', '2023-11-20 17:00:22', 'active'),
('srm.jgu.ac.id', '10.0.11.146', 'Ubuntu 18.04.6 LTS', 'node01', 'v4.4.2', '2023-11-30 16:58:27', 'active'),
('hrm.jgu.ac.id', '10.0.11.136', 'Debian GNU/Linux 9', 'node01', 'v4.4.2', '2023-12-19 14:03:17', 'active'),
('fms.msu.edu.my', '10.0.10.218', 'Ubuntu 18.04.6 LTS', 'node01', 'v4.7.2', '2024-02-18 16:45:37', 'active'),
('klas2t.msu.edu.my', '10.0.10.219', 'Ubuntu 18.04.6 LTS', 'node01', 'v4.7.2', '2024-02-18 16:47:22', 'active'),
('cgp-klas2.msu.edu.my', '10.0.10.220', 'Ubuntu 18.04.6 LTS', 'node01', 'v4.7.2', '2024-02-18 16:50:10', 'active'),
('pos.msu.edu.my', '10.0.10.221', 'Ubuntu 18.04.6 LTS', 'node01', 'v4.7.2', '2024-02-18 16:51:47', 'active'),
('his.msu.edu.my', '10.0.10.222', 'Ubuntu 18.04.6 LTS', 'node01', 'v4.7.2', '2024-02-18 16:53:17', 'active'),
('demo-klas2.msucollege.edu.my', '10.0.10.223', 'Ubuntu 18.04.6 LTS', 'node01', 'v4.7.2', '2024-02-18 16:54:14', 'active'),
('klas2.msucollege.edu.my', '10.0.10.166', 'Ubuntu 18.04.6 LTS', 'node01', 'v4.7.2', '2024-02-18 17:23:30', 'active'),
('klas2.klsentral.msucollege.edu.my', '10.0.10.169', 'Ubuntu 18.04.6 LTS', 'node01', 'v4.7.2', '2024-02-18 17:25:26', 'active'),
('klas2msu.klsentral.msucollege.edu.my', '10.0.10.170', 'Ubuntu 18.04.6 LTS', 'node01', 'v4.7.2', '2024-02-18 17:43:03', 'active'),
('klas2.seremban.msucollege.edu.my', '10.0.10.173', 'Ubuntu 18.04.6 LTS', 'node01', 'v4.7.2', '2024-02-18 17:48:22', 'active'),
('klas2msu.seremban.msucollege.edu.my', '10.0.10.174', 'Ubuntu 18.04.6 LTS', 'node01', 'v4.7.2', '2024-02-18 17:53:22', 'active'),
('klas2.sp.msucollege.edu.my', '10.0.10.177', 'Ubuntu 18.04.6 LTS', 'node01', 'v4.7.2', '2024-02-18 17:57:29', 'active'),
('klas2msu.sp.msucollege.edu.my', '10.0.10.178', 'Ubuntu 18.04.6 LTS', 'node01', 'v4.7.2', '2024-02-18 18:01:28', 'active'),
('klas2.kb.msucollege.edu.my', '10.0.10.181', 'Ubuntu 18.04.6 LTS', 'node01', 'v4.7.2', '2024-02-18 18:07:52', 'active'),
('klas2msu.kb.msucollege.edu.my', '10.0.10.182', 'Ubuntu 18.04.6 LTS', 'node01', 'v4.7.2', '2024-02-18 18:12:10', 'active'),
('klas2.sa.msucollege.edu.my', '10.0.10.212', 'Ubuntu 18.04.6 LTS', 'node01', 'v4.7.2', '2024-02-18 18:15:52', 'active'),
('klas2.kajang.msucollege.edu.my', '10.0.10.213', 'Ubuntu 18.04.6 LTS', 'node01', 'v4.7.2', '2024-02-18 18:21:24', 'active'),
('demo-msuc.msu.edu.my', '10.0.11.119', 'CentOS Linux 7.9', 'node01', 'v4.7.2', '2024-02-27 12:16:17', 'active'),
('www.msu.edu.my', '46.250.229.119', 'Debian GNU/Linux 12', 'node01', 'v4.7.2', '2024-02-27 12:16:38', 'active'),
('theresident-gtw.msu.edu.my', '10.0.0.11', 'BSD 14.0', 'node01', 'v4.7.3', '2024-05-08 11:32:51', 'active'),
('mcs.msu.edu.my', '10.0.11.155', 'Ubuntu 22.04.4 LTS', 'node01', 'v4.7.2', '2024-06-01 15:49:53', 'active');

-- Grant necessary permissions (adjust as needed for your setup)
GRANT SELECT, INSERT, UPDATE, DELETE ON aics_tms.* TO 'aics_user'@'%';
FLUSH PRIVILEGES;