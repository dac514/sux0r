-- Rename link tables

ALTER TABLE link_bayes_bookmarks RENAME TO link__bayes_documents__bookmarks;
ALTER TABLE link_bayes_messages RENAME TO link__bayes_documents__messages;
ALTER TABLE link_bayes_rss RENAME TO link__bayes_documents__rss_items;
ALTER TABLE link_bookmarks_tags RENAME TO link__bookmarks__tags;
ALTER TABLE link_bookmarks_users RENAME TO link__bookmarks__users;
ALTER TABLE link_messages_tags RENAME TO link__messages__tags;
ALTER TABLE link_rss_users RENAME TO link__rss_feeds__users;

-- Changing indexes

ALTER TABLE link__bayes_documents__bookmarks DROP CONSTRAINT link_bayes_bookmarks_grouping;
ALTER TABLE link__bayes_documents__bookmarks ADD CONSTRAINT link__bayes_documents__bookmarks_grouping PRIMARY KEY (bookmarks_id,bayes_documents_id);

ALTER TABLE link__bayes_documents__messages DROP CONSTRAINT link_bayes_messages_grouping;
ALTER TABLE link__bayes_documents__messages ADD CONSTRAINT link__bayes_documents__messages_grouping PRIMARY KEY (messages_id, bayes_documents_id);

ALTER TABLE link__bayes_documents__rss_items DROP CONSTRAINT link_bayes_rss_grouping;
ALTER TABLE link__bayes_documents__rss_items ADD CONSTRAINT link__bayes_documents__rss_items_grouping PRIMARY KEY (rss_items_id, bayes_documents_id);

ALTER TABLE link__bookmarks__tags DROP CONSTRAINT link_bookmarks_tags_grouping;
ALTER TABLE link__bookmarks__tags ADD CONSTRAINT link__bookmarks__tags_grouping PRIMARY KEY (bookmarks_id, tags_id);

ALTER TABLE link__bookmarks__users DROP CONSTRAINT link_bookmarks_users_grouping;
ALTER TABLE link__bookmarks__users ADD CONSTRAINT link__bookmarks__users_grouping PRIMARY KEY (bookmarks_id, users_id);

ALTER TABLE link__messages__tags DROP CONSTRAINT link_messages_tags_grouping;
ALTER TABLE link__messages__tags ADD CONSTRAINT link__messages__tags_grouping PRIMARY KEY (messages_id, tags_id);

ALTER TABLE link__rss_feeds__users DROP CONSTRAINT link_rss_users_grouping;
ALTER TABLE link__rss_feeds__users ADD CONSTRAINT link__rss_feeds__users_grouping PRIMARY KEY (rss_feeds_id, users_id);

ALTER TABLE bayes_auth DROP CONSTRAINT bayes_auth_grouping;
ALTER TABLE bayes_auth ADD CONSTRAINT bayes_auth_grouping UNIQUE ( bayes_vectors_id , users_id );

DROP INDEX bayes_categories_bayes_vectors_id_idx;
ALTER TABLE bayes_categories DROP CONSTRAINT bayes_categories_grouping;
ALTER TABLE bayes_categories ADD CONSTRAINT bayes_categories_grouping UNIQUE (bayes_vectors_id,category);

DROP INDEX bayes_tokens_bayes_categories_id_idx;
ALTER TABLE bayes_tokens DROP CONSTRAINT bayes_tokens_grouping;
ALTER TABLE bayes_tokens ADD CONSTRAINT bayes_tokens_grouping UNIQUE (bayes_categories_id,token);

DROP INDEX messages_thread_idx;
CREATE INDEX messages_thread_id_idx on messages(thread_id);
CREATE INDEX messages_thread_pos_idx on messages(thread_pos);
DROP INDEX messages_type_idx;
CREATE INDEX messages_forum_idx on messages(forum);
CREATE INDEX messages_blog_idx on messages(blog);
CREATE INDEX messages_wiki_idx on messages(wiki);

ALTER TABLE openid_trusted DROP CONSTRAINT openid_trusted_grouping;
ALTER TABLE openid_trusted ADD CONSTRAINT openid_trusted_grouping UNIQUE (users_id,auth_url);

CREATE INDEX socialnetwork_friend_users_id_idx on socialnetwork(friend_users_id);

DROP INDEX users_log_users_id_idx;
CREATE INDEX users_log_users_id_idx on users_log(users_id);
CREATE INDEX users_log_private_idx on users_log(private);

