DROP SEQUENCE IF EXISTS user_register_track_ur_id_seq CASCADE;
CREATE SEQUENCE user_register_track_ur_id_seq;

CREATE TABLE user_register_track (
	ur_id INTEGER NOT NULL PRIMARY KEY DEFAULT nextval('user_register_track_ur_id_seq'),
	ur_actor INTEGER NOT NULL,
	ur_actor_referral INTEGER NOT NULL,
	ur_from SMALLINT default 0,
	ur_date TIMESTAMPTZ default NULL
);

ALTER SEQUENCE user_register_track_ur_id_seq OWNED BY user_register_track.ur_id;
