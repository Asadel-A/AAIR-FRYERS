-- add import tracking so imports can be deleted

USE mej_attendance;

-- Links each import log entry to the attendance rows it created
-- Allows full deletion/rollback of a specific import
CREATE TABLE IF NOT EXISTS import_attendance_map (
    import_id    INT NOT NULL,
    attendance_id INT NOT NULL,
    PRIMARY KEY (import_id, attendance_id),
    FOREIGN KEY (import_id)    REFERENCES import_log(id) ON DELETE CASCADE,
    FOREIGN KEY (attendance_id) REFERENCES attendance(id) ON DELETE CASCADE
);

-- Also tracks which members and events were created by which import
CREATE TABLE IF NOT EXISTS import_member_map (
    import_id INT NOT NULL,
    member_id INT NOT NULL,
    PRIMARY KEY (import_id, member_id),
    FOREIGN KEY (import_id) REFERENCES import_log(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id)    ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS import_event_map (
    import_id INT NOT NULL,
    event_id  INT NOT NULL,
    PRIMARY KEY (import_id, event_id),
    FOREIGN KEY (import_id) REFERENCES import_log(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id)  REFERENCES events(id)     ON DELETE CASCADE
);
