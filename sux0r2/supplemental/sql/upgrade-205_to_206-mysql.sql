RENAME TABLE link_bayes_bookmarks TO link__bayes_documents__bookmarks;
RENAME TABLE link_bayes_messages TO link__bayes_documents__messages;
RENAME TABLE link_bayes_rss TO link__bayes_documents__rss_items;
RENAME TABLE link_bookmarks_tags TO link__bookmarks__tags;
RENAME TABLE link_bookmarks_users TO link__bookmarks__users;
RENAME TABLE link_messages_tags TO link__messages__tags;
RENAME TABLE link_rss_users TO link__rss_feeds__users;

ALTER TABLE bayes_cache ENGINE = innodb;
ALTER TABLE bookmarks ENGINE = innodb;
ALTER TABLE openid_secrets ENGINE = innodb;
ALTER TABLE openid_trusted ENGINE = innodb;
ALTER TABLE socialnetwork ENGINE = innodb;
ALTER TABLE tags ENGINE = innodb;
ALTER TABLE users_log ENGINE = innodb;
