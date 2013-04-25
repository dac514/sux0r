-- Rename link tables

RENAME TABLE link_bayes_bookmarks TO link__bayes_documents__bookmarks;
RENAME TABLE link_bayes_messages TO link__bayes_documents__messages;
RENAME TABLE link_bayes_rss TO link__bayes_documents__rss_items;
RENAME TABLE link_bookmarks_tags TO link__bookmarks__tags;
RENAME TABLE link_bookmarks_users TO link__bookmarks__users;
RENAME TABLE link_messages_tags TO link__messages__tags;
RENAME TABLE link_rss_users TO link__rss_feeds__users;

-- Change MyISAM to Innodb

ALTER TABLE bayes_cache ENGINE = innodb;
ALTER TABLE bookmarks ENGINE = innodb;
ALTER TABLE openid_secrets ENGINE = innodb;
ALTER TABLE openid_trusted ENGINE = innodb;
ALTER TABLE socialnetwork ENGINE = innodb;
ALTER TABLE tags ENGINE = innodb;
ALTER TABLE users_log ENGINE = innodb;

-- Changing indexes

ALTER TABLE link__bayes_documents__bookmarks DROP INDEX idx;
ALTER TABLE link__bayes_documents__bookmarks ADD PRIMARY KEY ( bookmarks_id , bayes_documents_id );
ALTER TABLE link__bayes_documents__bookmarks ADD INDEX ( bayes_documents_id );

ALTER TABLE link__bayes_documents__messages DROP INDEX idx;
ALTER TABLE link__bayes_documents__messages ADD PRIMARY KEY ( messages_id , bayes_documents_id );
ALTER TABLE link__bayes_documents__messages ADD INDEX ( bayes_documents_id );

ALTER TABLE link__bayes_documents__rss_items DROP INDEX idx;
ALTER TABLE link__bayes_documents__rss_items ADD PRIMARY KEY ( rss_items_id , bayes_documents_id );
ALTER TABLE link__bayes_documents__rss_items ADD INDEX idx ( bayes_documents_id );

ALTER TABLE link__bookmarks__tags DROP INDEX idx;
ALTER TABLE link__bookmarks__tags ADD PRIMARY KEY ( bookmarks_id , tags_id );
ALTER TABLE link__bookmarks__tags ADD INDEX idx ( tags_id );

ALTER TABLE link__bookmarks__users DROP INDEX idx;
ALTER TABLE link__bookmarks__users ADD PRIMARY KEY ( bookmarks_id , users_id );
ALTER TABLE link__bookmarks__users ADD INDEX ( users_id );

ALTER TABLE link__messages__tags DROP INDEX idx;
ALTER TABLE link__messages__tags ADD PRIMARY KEY ( messages_id , tags_id );
ALTER TABLE link__messages__tags ADD INDEX ( tags_id );

ALTER TABLE link__rss_feeds__users DROP INDEX idx;
ALTER TABLE link__rss_feeds__users ADD PRIMARY KEY ( rss_feeds_id , users_id );
ALTER TABLE link__rss_feeds__users ADD INDEX ( users_id );

ALTER TABLE bayes_auth DROP INDEX users_bayes_vectors , ADD UNIQUE `grouping` ( bayes_vectors_id , users_id );

ALTER TABLE bayes_categories DROP INDEX `grouping` , ADD UNIQUE `grouping` ( bayes_vectors_id , category );
ALTER TABLE bayes_categories DROP INDEX bayes_vectors_id;

ALTER TABLE bayes_tokens DROP INDEX `grouping` , ADD UNIQUE `grouping` ( bayes_categories_id , token );
ALTER TABLE bayes_tokens DROP INDEX bayes_categories_id;

ALTER TABLE messages DROP INDEX thread;
ALTER TABLE messages ADD INDEX thread ( thread_id, parent_id );
ALTER TABLE messages ADD INDEX ( thread_pos );
ALTER TABLE messages DROP INDEX type;
ALTER TABLE messages ADD INDEX ( blog );

ALTER TABLE openid_trusted DROP INDEX authorized , ADD UNIQUE authorized ( users_id , auth_url );

ALTER TABLE socialnetwork ADD INDEX ( friend_users_id );

ALTER TABLE users_log DROP INDEX users_id;
ALTER TABLE users_log ADD INDEX ( users_id );
ALTER TABLE users_log ADD INDEX ( private );

