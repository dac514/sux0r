ALTER TABLE link_bayes_bookmarks RENAME TO link__bayes_documents__bookmarks;
ALTER TABLE link_bayes_messages RENAME TO link__bayes_documents__messages;
ALTER TABLE link_bayes_rss RENAME TO link__bayes_documents__rss_items;
ALTER TABLE link_bookmarks_tags RENAME TO link__bookmarks__tags;
ALTER TABLE link_bookmarks_users RENAME TO link__bookmarks__users;
ALTER TABLE link_messages_tags RENAME TO link__messages__tags;
ALTER TABLE link_rss_users RENAME TO link__rss_feeds__users;


ALTER TABLE link__bayes_documents__bookmarks DROP CONSTRAINT link_bayes_bookmarks_grouping;
ALTER TABLE link__bayes_documents__bookmarks ADD CONSTRAINT link__bayes_documents__bookmarks_grouping UNIQUE (bookmarks_id,bayes_documents_id);

ALTER TABLE link__bayes_documents__messages DROP CONSTRAINT link_bayes_messages_grouping;
ALTER TABLE link__bayes_documents__messages ADD CONSTRAINT link__bayes_documents__messages_grouping UNIQUE (messages_id, bayes_documents_id);

ALTER TABLE link__bayes_documents__rss_items DROP CONSTRAINT link_bayes_rss_grouping;
ALTER TABLE link__bayes_documents__rss_items ADD CONSTRAINT link__bayes_documents__rss_items_grouping UNIQUE (rss_items_id, bayes_documents_id);

ALTER TABLE link__bookmarks__tags DROP CONSTRAINT link_bookmarks_tags_grouping;
ALTER TABLE link__bookmarks__tags ADD CONSTRAINT link__bookmarks__tags_grouping UNIQUE (bookmarks_id, tags_id);


ALTER TABLE link__bookmarks__users DROP CONSTRAINT link_bookmarks_users_grouping;
ALTER TABLE link__bookmarks__users ADD CONSTRAINT link__bookmarks__users_grouping UNIQUE (bookmarks_id, users_id);

ALTER TABLE link__messages__tags DROP CONSTRAINT link_messages_tags_grouping;
ALTER TABLE link__messages__tags ADD CONSTRAINT link__messages__tags_grouping UNIQUE (messages_id, tags_id);

ALTER TABLE link__rss_feeds__users DROP CONSTRAINT link_rss_users_grouping;
ALTER TABLE link__rss_feeds__users ADD CONSTRAINT link__rss_feeds__users_grouping UNIQUE (rss_feeds_id, users_id);
