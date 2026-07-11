-- audit_logs: AuditLogController paginates ORDER BY created_at DESC on an insert-only,
-- ever-growing table -- without this the ORDER BY + LIMIT/OFFSET does a filesort over the
-- whole table on every page view.
ALTER TABLE audit_logs ADD INDEX idx_created_at (created_at);

-- crop_cycles.status: filtered by AlertEngine::missingRecords() and the crop list.
ALTER TABLE crop_cycles ADD INDEX idx_status (status);

-- field_activities.activity_date: filtered by reports/daily-activities and AlertEngine.
ALTER TABLE field_activities ADD INDEX idx_activity_date (activity_date);

-- worker_tasks: AlertEngine::unverifiedActivities() filters status + updated_at together.
ALTER TABLE worker_tasks ADD INDEX idx_status_updated (status, updated_at);

-- monitoring_records: AlertEngine::diseaseOutbreaks() filters type + severity + record_date.
ALTER TABLE monitoring_records ADD INDEX idx_type_severity_date (type, severity, record_date);

-- crop_inputs.expiry_date: AlertEngine::expiredInputs() filters on this directly.
ALTER TABLE crop_inputs ADD INDEX idx_expiry_date (expiry_date);

-- notifications: Notification::forUser()/unreadCount() filter user_id + is_read together.
ALTER TABLE notifications ADD INDEX idx_user_read (user_id, is_read);
