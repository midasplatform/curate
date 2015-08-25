-- Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- MySQL database for the curate module, version 1.0.0

CREATE TABLE IF NOT EXISTS `curate_curatedfolder` (
  `curatedfolder_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `folder_id` bigint(20) NOT NULL,
  `curation_state` varchar(50) NOT NULL DEFAULT 'construction',
  `creation_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`curatedfolder_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `curate_moderator` (
  `moderator_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `creation_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`moderator_id`)
) DEFAULT CHARSET=utf8;
