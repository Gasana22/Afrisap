ALTER TABLE audit_logs ADD INDEX idx_created_at (created_at);
ALTER TABLE crop_cycles ADD INDEX idx_status (status);
ALTER TABLE field_activities ADD INDEX idx_activity_date (activity_date);
ALTER TABLE worker_tasks ADD INDEX idx_status_updated (status, updated_at);
ALTER TABLE monitoring_records ADD INDEX idx_type_severity_date (type, severity, record_date);
ALTER TABLE crop_inputs ADD INDEX idx_expiry_date (expiry_date);
ALTER TABLE notifications ADD INDEX idx_user_read (user_id, is_read);
