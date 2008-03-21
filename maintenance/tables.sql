CREATE TABLE projects (
  id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(100) NOT NULL DEFAULT '',
  PRIMARY KEY (id)
);

CREATE TABLE eventtypes (
  id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(100) NOT NULL DEFAULT '',
  capacity INTEGER UNSIGNED NOT NULL,
  minpeople INTEGER UNSIGNED NOT NULL DEFAULT 0,
  project INTEGER UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  CONSTRAINT FK_eventtypes_projects FOREIGN KEY FK_eventtypes_projects (project) REFERENCES projects (id),
  INDEX IX_eventtypes_title(title)
);

CREATE TABLE events (
  id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(100) NOT NULL,
  date DATETIME NOT NULL,
  eventtype INTEGER UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  CONSTRAINT FK_events_eventtypes FOREIGN KEY FK_events_eventtypes (eventtype) REFERENCES eventtypes (id)
);

CREATE TABLE users (
  id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(100) NOT NULL,
  password VARCHAR(128) BINARY NOT NULL,
  caption VARCHAR(100) NOT NULL,
  firstname VARCHAR(50) NOT NULL DEFAULT  '',
  lastname VARCHAR(50) NOT NULL DEFAULT '',
  priority SMALLINT NOT NULL DEFAULT 0,
  email VARCHAR(100) NOT NULL DEFAULT '',
  icq VARCHAR(12) NOT NULL DEFAULT '',
  emailvalidated TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  CONSTRAINT UN_users_username UNIQUE INDEX UN_users_username(username)
);

CREATE TABLE usersprojects (
  user INTEGER UNSIGNED NOT NULL,
  project INTEGER UNSIGNED NOT NULL,
  access INTEGER UNSIGNED NOT NULL,
  priority SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (user, project),
  CONSTRAINT FK_userprojects_users FOREIGN KEY FK_userprojects_users (user) REFERENCES users (id),
  CONSTRAINT FK_userprojects_projects FOREIGN KEY FK_userprojects_projects (project) REFERENCES projects (id)
);

CREATE TABLE subscriptions (
  user INTEGER UNSIGNED NOT NULL,
  event INTEGER UNSIGNED NOT NULL,
  subscribed DATETIME NOT NULL,
  priority SMALLINT NOT NULL DEFAULT 0,
  guests INTEGER UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (user, event),
  CONSTRAINT FK_subscriptions_users FOREIGN KEY FK_subscriptions_users (user) REFERENCES users (id),
  CONSTRAINT FK_subscriptions_events FOREIGN KEY FK_subscriptions_events (event) REFERENCES events (id),
  INDEX IX_subscriptions_event_priority_subscribed(event, priority, subscribed)
);

CREATE TABLE emailcodes (
  id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  code VARCHAR(25) NOT NULL,
  email VARCHAR(100) NOT NULL,
  fromuser INTEGER UNSIGNED NOT NULL,
  createdate DATETIME NOT NULL,
  forproject INTEGER UNSIGNED NULL,
  accepted TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  CONSTRAINT FK_emailcodes_fromuser FOREIGN KEY FK_emailcodes_fromuser (fromuser) REFERENCES users(id),
  CONSTRAINT FK_emailcodes_forproject FOREIGN KEY FK_emailcodes_forproject (forproject) REFERENCES projects(id),
  CONSTRAINT UN_emailcodes_code UNIQUE INDEX UN_emailcodes_code(code)
);

CREATE TABLE logs (
  id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  timestamp DATETIME NOT NULL,
  entry VARCHAR(512) NOT NULL,
  PRIMARY KEY (id),
  INDEX IX_log_timestamp (timestamp)
);
