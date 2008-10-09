SET client_encoding = 'UTF8';


-- --------------------------------------------------------


CREATE TABLE bayes_auth (
  id serial NOT NULL,
  users_id integer NOT NULL,
  bayes_vectors_id integer NOT NULL,
  "owner" boolean DEFAULT false,
  trainer boolean DEFAULT false,
  CONSTRAINT bayes_auth_pkey PRIMARY KEY (id),
  CONSTRAINT bayes_auth_grouping UNIQUE (users_id,bayes_vectors_id)
) ;


-- --------------------------------------------------------


CREATE TABLE bayes_cache (
  md5 character(32) NOT NULL,
  bayes_vectors_id integer NOT NULL,
  expiration integer NOT NULL,
  scores text NOT NULL,
  CONSTRAINT bayes_cache_pkey PRIMARY KEY (md5)
);
CREATE INDEX bayes_cache_expiration_idx on bayes_cache(expiration);
CREATE INDEX bayes_cache_bayes_vectors_id_idx on bayes_cache(bayes_vectors_id);


-- --------------------------------------------------------


CREATE TABLE bayes_categories (
  id serial NOT NULL,
  category varchar(64) NOT NULL,
  bayes_vectors_id integer NOT NULL,
  probability float8 NOT NULL default '0',
  token_count bigint NOT NULL default '0',
  CONSTRAINT bayes_categories_pkey PRIMARY KEY (id),
  CONSTRAINT bayes_categories_grouping UNIQUE (category,bayes_vectors_id)
)  ;
CREATE INDEX bayes_categories_bayes_vectors_id_idx on bayes_categories(bayes_vectors_id);


-- --------------------------------------------------------


CREATE TABLE bayes_documents (
  id serial NOT NULL,
  bayes_categories_id integer NOT NULL,
  body_plaintext text NOT NULL,
  CONSTRAINT bayes_documents_pkey PRIMARY KEY (id)
)  ;
CREATE INDEX bayes_documents_bayes_categories_id_idx on bayes_documents(bayes_categories_id);


-- --------------------------------------------------------


CREATE TABLE bayes_tokens (
  id serial NOT NULL,
  token varchar(64) NOT NULL,
  bayes_categories_id integer NOT NULL,
  count bigint NOT NULL default '0',
  CONSTRAINT bayes_tokens_pkey PRIMARY KEY (id),
  CONSTRAINT bayes_tokens_grouping UNIQUE (token,bayes_categories_id)
)  ;
CREATE INDEX bayes_tokens_bayes_categories_id_idx on bayes_tokens(bayes_categories_id);


-- --------------------------------------------------------


CREATE TABLE bayes_vectors (
  id serial NOT NULL,
  vector varchar(64) NOT NULL,
  CONSTRAINT bayes_vectors_pkey PRIMARY KEY (id)
)  ;


-- --------------------------------------------------------

CREATE TABLE bookmarks (
  id serial NOT NULL,
  users_id integer NOT NULL,
  url varchar(255) NOT NULL,
  title varchar(255) NOT NULL,
  body_html text,
  body_plaintext text,
  draft bool NOT NULL,
  published_on timestamp NOT NULL,
  CONSTRAINT bookmarks_pkey PRIMARY KEY (id),
  CONSTRAINT bookmarks_grouping UNIQUE (url)
)  ;
CREATE INDEX bookmarks_users_id_idx on bookmarks(users_id);
CREATE INDEX bookmarks_published_idx on bookmarks(draft,published_on);


-- --------------------------------------------------------


CREATE TABLE link_bayes_bookmarks (
  bookmarks_id integer NOT NULL,
  bayes_documents_id integer NOT NULL,
  CONSTRAINT link_bayes_bookmarks_grouping UNIQUE (bookmarks_id,bayes_documents_id)
) ;


-- --------------------------------------------------------


CREATE TABLE link_bayes_messages (
  messages_id integer NOT NULL,
  bayes_documents_id integer NOT NULL,
  CONSTRAINT link_bayes_messages_grouping UNIQUE (messages_id,bayes_documents_id)
) ;


-- --------------------------------------------------------


CREATE TABLE link_bayes_rss (
  rss_items_id integer NOT NULL,
  bayes_documents_id integer NOT NULL,
  CONSTRAINT link_bayes_rss_grouping UNIQUE (rss_items_id,bayes_documents_id)
) ;


-- --------------------------------------------------------


CREATE TABLE link_bookmarks_tags (
  bookmarks_id integer NOT NULL,
  tags_id integer NOT NULL,
  CONSTRAINT link_bookmarks_tags_grouping UNIQUE (bookmarks_id,tags_id)
) ;


-- --------------------------------------------------------


CREATE TABLE link_bookmarks_users (
  bookmarks_id integer NOT NULL,
  users_id integer NOT NULL,
  CONSTRAINT link_bookmarks_users_grouping UNIQUE (bookmarks_id,users_id)
) ;


-- --------------------------------------------------------


CREATE TABLE link_messages_tags (
  messages_id integer NOT NULL,
  tags_id integer NOT NULL,
  CONSTRAINT link_messages_tags_grouping UNIQUE (messages_id,tags_id)
) ;


-- --------------------------------------------------------


CREATE TABLE link_rss_users (
  rss_feeds_id integer NOT NULL,
  users_id integer NOT NULL,
  CONSTRAINT link_rss_users_grouping UNIQUE (rss_feeds_id,users_id)
) ;


-- --------------------------------------------------------


CREATE TABLE messages (
  id serial NOT NULL,
  users_id integer NOT NULL,
  title varchar(255) NOT NULL,
  image varchar(255) default NULL,
  body_html text NOT NULL,
  body_plaintext text NOT NULL,
  thread_id integer NOT NULL,
  parent_id integer NOT NULL,
  level integer NOT NULL,
  thread_pos integer NOT NULL,
  draft boolean NOT NULL,
  published_on timestamp NOT NULL,
  forum boolean NOT NULL,
  blog boolean NOT NULL,
  wiki boolean NOT NULL,
  slideshow boolean NOT NULL,
  CONSTRAINT messages_pkey PRIMARY KEY (id)
) ;
CREATE INDEX messages_users_id_idx on messages(users_id);
CREATE INDEX messages_thread_idx on messages(thread_id,thread_pos);
CREATE INDEX messages_published_idx on messages(published_on,draft);
CREATE INDEX messages_type_idx on messages(forum,blog,wiki,slideshow);
CREATE INDEX messages_parent_id_idx on messages(parent_id);


-- --------------------------------------------------------


CREATE TABLE messages_history (
  id serial NOT NULL,
  messages_id integer NOT NULL,
  users_id integer NOT NULL,
  title varchar(255) NOT NULL,
  image varchar(255) default NULL,
  body_html text NOT NULL,
  body_plaintext text NOT NULL,
  edited_on timestamp NOT NULL,
  CONSTRAINT messages_history_pkey PRIMARY KEY (id)
) ;
CREATE INDEX messages_history_messages_id_idx on messages_history(messages_id);
CREATE INDEX messages_history_users_id_idx on messages_history(users_id);


-- --------------------------------------------------------


CREATE TABLE openid_secrets (
  id serial NOT NULL,
  expiration integer NOT NULL,
  shared_secret varchar(255) NOT NULL,
  CONSTRAINT openid_secrets_pkey PRIMARY KEY (id)
) ;


-- --------------------------------------------------------


CREATE TABLE openid_trusted (
  id serial NOT NULL,
  auth_url varchar(255) NOT NULL,
  users_id integer NOT NULL,
  CONSTRAINT openid_trusted_pkey PRIMARY KEY (id),
  CONSTRAINT openid_trusted_grouping UNIQUE (auth_url,users_id)
) ;


-- --------------------------------------------------------


CREATE TABLE photoalbums (
  id serial NOT NULL,
  users_id integer NOT NULL,
  title varchar(255) NOT NULL,
  body_html text NOT NULL,
  body_plaintext text NOT NULL,
  thumbnail integer default NULL,
  draft boolean NOT NULL,
  published_on timestamp NOT NULL,
  CONSTRAINT photoalbums_pkey PRIMARY KEY (id)
) ;
CREATE INDEX photoalbums_users_id_idx on photoalbums(users_id);
CREATE INDEX photoalbums_published_idx on photoalbums(draft,published_on);


-- --------------------------------------------------------


CREATE TABLE photos (
  id serial NOT NULL,
  users_id integer NOT NULL,
  photoalbums_id integer NOT NULL,
  image varchar(255) NOT NULL,
  description text,
  md5 character(32) NOT NULL,
  CONSTRAINT photos_pkey PRIMARY KEY (id),
  CONSTRAINT photos_grouping UNIQUE (md5,users_id,photoalbums_id)
) ;
CREATE INDEX photos_users_id_idx on photos(users_id);
CREATE INDEX photos_photoalbums_id_idx on photos(photoalbums_id);
CREATE INDEX photos_image_idx on photos(image);


-- --------------------------------------------------------


CREATE TABLE rss_feeds (
  id serial NOT NULL,
  users_id integer NOT NULL,
  url varchar(255) NOT NULL,
  title varchar(255) NOT NULL,
  body_html text,
  body_plaintext text,
  draft boolean NOT NULL,
  CONSTRAINT rss_feeds_pkey PRIMARY KEY (id),
  CONSTRAINT rss_feeds_grouping UNIQUE (url)
) ;
CREATE INDEX rss_feeds_users_id_idx on rss_feeds(users_id);
CREATE INDEX rss_feeds_approved_idx on rss_feeds(draft);


-- --------------------------------------------------------


CREATE TABLE rss_items (
  id serial NOT NULL,
  rss_feeds_id integer NOT NULL,
  url varchar(255) NOT NULL,
  title varchar(255) NOT NULL,
  body_html text,
  body_plaintext text,
  published_on timestamp NOT NULL,
  CONSTRAINT rss_items_pkey PRIMARY KEY (id),
  CONSTRAINT rss_items_grouping UNIQUE (url)
) ;
CREATE INDEX rss_items_rss_feeds_id_idx on rss_items(rss_feeds_id);
CREATE INDEX rss_items_published_on_idx on rss_items(published_on);


-- --------------------------------------------------------


CREATE TABLE socialnetwork (
  id serial NOT NULL,
  users_id integer NOT NULL,
  friend_users_id integer NOT NULL,
  relationship varchar(255) default NULL,
  CONSTRAINT socialnetwork_pkey PRIMARY KEY (id),
  CONSTRAINT socialnetwork_grouping UNIQUE (users_id,friend_users_id)
) ;


-- --------------------------------------------------------


CREATE TABLE tags (
  id serial NOT NULL,
  users_id integer NOT NULL,
  tag varchar(64) NOT NULL,
  CONSTRAINT tags_pkey PRIMARY KEY (id),
  CONSTRAINT tags_grouping UNIQUE (tag)
) ;


-- --------------------------------------------------------

CREATE TABLE users (
  id serial NOT NULL,
  nickname varchar(64) NOT NULL,
  email varchar(255) NOT NULL,
  password varchar(255) NOT NULL,
  root boolean,
  banned boolean,
  CONSTRAINT users_pkey PRIMARY KEY (id),
  CONSTRAINT users_grouping UNIQUE (nickname),
  CONSTRAINT users_grouping_2 UNIQUE (email)
) ;


-- --------------------------------------------------------


CREATE TABLE users_access (
  id serial NOT NULL,
  users_id integer NOT NULL,
  module varchar(32) NOT NULL,
  accesslevel smallint NOT NULL,
  CONSTRAINT users_access_pkey PRIMARY KEY (id),
  CONSTRAINT users_access_grouping UNIQUE (users_id,module)
) ;


-- --------------------------------------------------------


CREATE TABLE users_info (
  id serial NOT NULL,
  users_id integer NOT NULL,
  given_name varchar(255) default NULL,
  family_name varchar(255) default NULL,
  street_address varchar(255) default NULL,
  locality varchar(255) default NULL,
  region varchar(255) default NULL,
  postcode varchar(255) default NULL,
  country char(2) default NULL,
  tel varchar(255) default NULL,
  url varchar(255) default NULL,
  dob date default NULL,
  gender char(1) default NULL,
  language char(2) default NULL,
  timezone varchar(255) default NULL,
  image varchar(255) default NULL,
  CONSTRAINT users_info_pkey PRIMARY KEY (id),
  CONSTRAINT users_info_grouping UNIQUE (users_id)
) ;


-- --------------------------------------------------------


CREATE TABLE users_log (
  id serial NOT NULL,
  users_id integer NOT NULL,
  body_html text NOT NULL,
  body_plaintext text NOT NULL,
  ts timestamp NOT NULL,
  private boolean NOT NULL,
  CONSTRAINT users_log_pkey PRIMARY KEY (id)
) ;  
CREATE INDEX users_log_users_id_idx on users_log(users_id,private);  
CREATE INDEX users_log_ts_idx on users_log(ts);


-- --------------------------------------------------------


CREATE TABLE users_openid (
  id serial NOT NULL,
  openid_url varchar(255) NOT NULL,
  users_id integer NOT NULL,
  CONSTRAINT users_openid_pkey PRIMARY KEY (id),
  CONSTRAINT users_openid_grouping UNIQUE (openid_url)
) ;
CREATE INDEX users_openid_users_id_idx on users_openid(users_id);


