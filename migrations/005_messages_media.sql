-- Optional manual migration — messages.php applies via messaging_schema.inc.php

ALTER TABLE messages
  ADD COLUMN message_type ENUM('text','image') NOT NULL DEFAULT 'text';

ALTER TABLE messages
  ADD COLUMN image_url VARCHAR(500) NULL DEFAULT NULL;
