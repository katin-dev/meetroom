CREATE TABLE room (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	name VARCHAR(100) NULL DEFAULT NULL,
	PRIMARY KEY (id)
);
CREATE TABLE reserve (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	room_id INT UNSIGNED NOT NULL,
	dt_from DATETIME NOT NULL,
	dt_to DATETIME NOT NULL,
	comment VARCHAR(200) NULL DEFAULT NULL
);
