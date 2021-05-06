CREATE TABLE /*_*/user_register_track (
  `ur_id` int unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `ur_actor` bigint unsigned NOT NULL,
  `ur_actor_referral` bigint unsigned NOT NULL,
  `ur_from` int(5) default 0,
  `ur_date` datetime default NULL
) /*$wgDBTableOptions*/;