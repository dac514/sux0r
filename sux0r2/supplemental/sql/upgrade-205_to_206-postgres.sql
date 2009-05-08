ALTER TABLE link_bayes_bookmarks RENAME TO link__bayes_documents__bookmarks;
ALTER TABLE link_bayes_messages RENAME TO link__bayes_documents__messages;
ALTER TABLE link_bayes_rss RENAME TO link__bayes_documents__rss_items;
ALTER TABLE link_bookmarks_tags RENAME TO link__bookmarks__tags;
ALTER TABLE link_bookmarks_users RENAME TO link__bookmarks__users;
ALTER TABLE link_messages_tags RENAME TO link__messages__tags;
ALTER TABLE link_rss_users RENAME TO link__rss_feeds__users; 
