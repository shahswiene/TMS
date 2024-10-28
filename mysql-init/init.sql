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

-- Create user security history table
CREATE TABLE IF NOT EXISTS user_security_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_type ENUM('two_factor_update', 'security_qa_update', 'status_change') NOT NULL,
    action_description TEXT NOT NULL,
    created_by_ip VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
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

-- Create incident types table
CREATE TABLE IF NOT EXISTS incident_types (
    incident_type_id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    default_priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    default_sla_hours INT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create SLA configuration table
CREATE TABLE IF NOT EXISTS sla_configs (
    sla_id INT AUTO_INCREMENT PRIMARY KEY,
    priority ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    response_time_hours INT NOT NULL,
    resolution_time_hours INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create tickets table
CREATE TABLE IF NOT EXISTS tickets (
    ticket_id VARCHAR(20) PRIMARY KEY,  -- Custom format like "SOC-2024-0001"
    title VARCHAR(255) NOT NULL,
    incident_type_id INT NOT NULL,
    priority ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    description TEXT NOT NULL,
    wazuh_log JSON,
    assigned_agent_id INT,
    created_by INT NOT NULL,
    status ENUM('new', 'assigned', 'in_progress', 'pending', 'resolved', 'closed') DEFAULT 'new',
    sla_deadline TIMESTAMP,
    sla_breach_status ENUM('within', 'warning', 'breached') DEFAULT 'within',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    closed_at TIMESTAMP NULL,
    FOREIGN KEY (incident_type_id) REFERENCES incident_types(incident_type_id),
    FOREIGN KEY (assigned_agent_id) REFERENCES agents(agent_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- Create ticket comments table
CREATE TABLE IF NOT EXISTS ticket_comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id VARCHAR(20) NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Create ticket attachments table
CREATE TABLE IF NOT EXISTS ticket_attachments (
    attachment_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id VARCHAR(20) NOT NULL,
    user_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(512) NOT NULL,
    file_size INT NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Create ticket history table
CREATE TABLE IF NOT EXISTS ticket_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id VARCHAR(20) NOT NULL,
    user_id INT NOT NULL,
    action_type ENUM('created', 'updated', 'comment_added', 'attachment_added', 
                    'status_changed', 'priority_changed', 'agent_assigned', 'sla_updated') NOT NULL,
    old_value TEXT,
    new_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Create ticket watchers table
CREATE TABLE IF NOT EXISTS ticket_watchers (
    watcher_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id VARCHAR(20) NOT NULL,
    user_id INT NOT NULL,
    notification_preference ENUM('all', 'major_updates', 'resolution_only') DEFAULT 'all',
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_ticket_watcher (ticket_id, user_id),
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Create notification settings table
CREATE TABLE IF NOT EXISTS notification_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email_notifications BOOLEAN DEFAULT TRUE,
    in_app_notifications BOOLEAN DEFAULT TRUE,
    notify_on_assignment BOOLEAN DEFAULT TRUE,
    notify_on_update BOOLEAN DEFAULT TRUE,
    notify_on_comment BOOLEAN DEFAULT TRUE,
    notify_on_resolution BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_settings (user_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
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

-- Indexes for tickets and related tables
CREATE INDEX idx_tickets_status ON tickets(status);
CREATE INDEX idx_tickets_priority ON tickets(priority);
CREATE INDEX idx_tickets_assigned_agent ON tickets(assigned_agent_id);
CREATE INDEX idx_tickets_created_by ON tickets(created_by);
CREATE INDEX idx_tickets_incident_type ON tickets(incident_type_id);
CREATE INDEX idx_ticket_history_ticket ON ticket_history(ticket_id);
CREATE INDEX idx_ticket_comments_ticket ON ticket_comments(ticket_id);
CREATE INDEX idx_ticket_attachments_ticket ON ticket_attachments(ticket_id);
CREATE INDEX idx_ticket_watchers_ticket ON ticket_watchers(ticket_id);
CREATE INDEX idx_ticket_watchers_user ON ticket_watchers(user_id);
CREATE INDEX idx_user_security_history ON user_security_history(user_id);

-- Create triggers
DELIMITER //

-- Trigger to enforce security requirements for user activation
CREATE TRIGGER enforce_security_requirements
BEFORE UPDATE ON users
FOR EACH ROW
BEGIN
    -- Check if user is trying to become active without any security measures
    IF NEW.is_active = 'active' AND
       (
           -- Neither security Q&A nor 2FA is set up
           (
               (NEW.security_question IS NULL OR NEW.security_question = '' OR 
                NEW.security_answer_hash IS NULL OR NEW.security_answer_hash = '')
               AND
               (NEW.two_factor_enabled = FALSE OR NEW.two_factor_enabled = 0)
           )
       ) THEN
        SET NEW.is_active = 'pending';
    END IF;
END;
//

-- Trigger to log security changes
CREATE TRIGGER log_security_requirement_check
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    IF OLD.is_active != NEW.is_active OR 
       OLD.two_factor_enabled != NEW.two_factor_enabled OR
       OLD.security_question != NEW.security_question OR
       OLD.security_answer_hash != NEW.security_answer_hash THEN
        
        INSERT INTO user_security_history (
            user_id,
            action_type,
            action_description,
            created_by_ip
        ) 
        SELECT 
            NEW.user_id,
            'status_change',
            CASE
                WHEN NEW.is_active = 'pending' AND 
                     (
                         (NEW.security_question IS NULL OR NEW.security_question = '' OR 
                          NEW.security_answer_hash IS NULL OR NEW.security_answer_hash = '')
                         AND
                         (NEW.two_factor_enabled = FALSE OR NEW.two_factor_enabled = 0)
                     )
                THEN 'Activation denied - No security measures configured'
                WHEN NEW.is_active = 'active' AND NEW.two_factor_enabled = TRUE 
                THEN 'User activated with 2FA'
                WHEN NEW.is_active = 'active' AND 
                     (NEW.security_question IS NOT NULL AND NEW.security_question != '' AND 
                      NEW.security_answer_hash IS NOT NULL AND NEW.security_answer_hash != '')
                THEN 'User activated with Security Q&A'
                ELSE 'Security settings updated'
            END,
            NEW.last_ip_address;
    END IF;
END;
//

-- Trigger to add ticket creator as watcher
CREATE TRIGGER add_ticket_creator_as_watcher
AFTER INSERT ON tickets
FOR EACH ROW
BEGIN
    INSERT INTO ticket_watchers (ticket_id, user_id, notification_preference)
    VALUES (NEW.ticket_id, NEW.created_by, 'all');
END;
//

-- Trigger to add assigned agent as watcher
CREATE TRIGGER add_assigned_agent_as_watcher
AFTER UPDATE ON tickets
FOR EACH ROW
BEGIN
    IF NEW.assigned_agent_id IS NOT NULL AND 
       (OLD.assigned_agent_id IS NULL OR NEW.assigned_agent_id != OLD.assigned_agent_id) THEN
        INSERT INTO ticket_watchers (ticket_id, user_id, notification_preference)
        VALUES (NEW.ticket_id, NEW.assigned_agent_id, 'all')
        ON DUPLICATE KEY UPDATE notification_preference = 'all';
    END IF;
END;
//

-- Trigger to add comment author as watcher
CREATE TRIGGER add_commenter_as_watcher
AFTER INSERT ON ticket_comments
FOR EACH ROW
BEGIN
    INSERT INTO ticket_watchers (ticket_id, user_id, notification_preference)
    VALUES (NEW.ticket_id, NEW.user_id, 'all')
    ON DUPLICATE KEY UPDATE notification_preference = notification_preference;
END;
//

-- Trigger to track ticket history
CREATE TRIGGER track_ticket_history
AFTER UPDATE ON tickets
FOR EACH ROW
BEGIN
    -- Track status changes
    IF OLD.status != NEW.status THEN
        INSERT INTO ticket_history (ticket_id, user_id, action_type, old_value, new_value)
        VALUES (NEW.ticket_id, NEW.created_by, 'status_changed', OLD.status, NEW.status);
    END IF;
    
    -- Track priority changes
    IF OLD.priority != NEW.priority THEN
        INSERT INTO ticket_history (ticket_id, user_id, action_type, old_value, new_value)
        VALUES (NEW.ticket_id, NEW.created_by, 'priority_changed', OLD.priority, NEW.priority);
    END IF;
    
    -- Track agent assignment changes
    IF IFNULL(OLD.assigned_agent_id, 0) != IFNULL(NEW.assigned_agent_id, 0) THEN
        INSERT INTO ticket_history (ticket_id, user_id, action_type, old_value, new_value)
        VALUES (NEW.ticket_id, NEW.created_by, 'agent_assigned', 
                IFNULL(OLD.assigned_agent_id, 'unassigned'), 
                IFNULL(NEW.assigned_agent_id, 'unassigned'));
    END IF;
    
    -- Track SLA changes
    IF IFNULL(OLD.sla_deadline, '') != IFNULL(NEW.sla_deadline, '') THEN
        INSERT INTO ticket_history (ticket_id, user_id, action_type, old_value, new_value)
        VALUES (NEW.ticket_id, NEW.created_by, 'sla_updated', 
                IFNULL(OLD.sla_deadline, 'no_sla'), 
                IFNULL(NEW.sla_deadline, 'no_sla'));
    END IF;
END;
//

DELIMITER ;

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