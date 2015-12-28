DROP TABLE mail_queue;

CREATE TABLE mail_queue (
  id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  message MEDIUMTEXT NOT NULL,
  attempt TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  state CHAR(1) NOT NULL DEFAULT 'N', /* 'N': queued (new), 'A': processing (active), 'C': completed */
  sentTime TIMESTAMP NULL DEFAULT NULL,
  timeToSend TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  createdTime TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updatedTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  KEY id (id),
  KEY time_to_send (timeToSend)
);
