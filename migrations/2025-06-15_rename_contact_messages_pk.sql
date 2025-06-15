-- Fix contact_messages primary key naming for consistency
ALTER TABLE contact_messages 
  CHANGE id contact_message_id INT(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE contact_messages 
  DROP PRIMARY KEY, 
  ADD PRIMARY KEY (contact_message_id);

-- If you have any foreign keys referencing contact_messages(id), update them to contact_message_id
-- Example (if any):
-- ALTER TABLE some_table DROP FOREIGN KEY fk_name;
-- ALTER TABLE some_table CHANGE message_id contact_message_id INT(11);
-- ALTER TABLE some_table ADD CONSTRAINT fk_name FOREIGN KEY (contact_message_id) REFERENCES contact_messages(contact_message_id);
