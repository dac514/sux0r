
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `sux0r`
--

-- --------------------------------------------------------

--
-- Table structure for table `bayes_auth`
--

CREATE TABLE IF NOT EXISTS `bayes_auth` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `users_id` int(11) NOT NULL,
  `bayes_vectors_id` int(11) NOT NULL,
  `owner` tinyint(1) DEFAULT NULL,
  `trainer` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `grouping` (`bayes_vectors_id`,`users_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `bayes_cache`
--

CREATE TABLE IF NOT EXISTS `bayes_cache` (
  `md5` char(32) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `bayes_vectors_id` int(11) NOT NULL,
  `expiration` int(11) NOT NULL,
  `scores` text NOT NULL,
  PRIMARY KEY (`md5`),
  KEY `expiration` (`expiration`),
  KEY `bayes_vectors_id` (`bayes_vectors_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `bayes_categories`
--

CREATE TABLE IF NOT EXISTS `bayes_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(64) NOT NULL,
  `bayes_vectors_id` int(11) NOT NULL,
  `probability` double NOT NULL DEFAULT '0',
  `token_count` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `grouping` (`bayes_vectors_id`,`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `bayes_documents`
--

CREATE TABLE IF NOT EXISTS `bayes_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bayes_categories_id` int(11) NOT NULL,
  `body_plaintext` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `bayes_categories_id` (`bayes_categories_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `bayes_tokens`
--

CREATE TABLE IF NOT EXISTS `bayes_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `token` varchar(64) NOT NULL,
  `bayes_categories_id` int(11) NOT NULL,
  `count` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `grouping` (`bayes_categories_id`,`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `bayes_vectors`
--

CREATE TABLE IF NOT EXISTS `bayes_vectors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vector` varchar(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `bookmarks`
--

CREATE TABLE IF NOT EXISTS `bookmarks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `users_id` int(11) NOT NULL,
  `url` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `body_html` text,
  `body_plaintext` text,
  `draft` tinyint(1) NOT NULL,
  `published_on` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `url` (`url`),
  KEY `users_id` (`users_id`),
  KEY `published` (`draft`,`published_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `link__bayes_documents__bookmarks`
--

CREATE TABLE IF NOT EXISTS `link__bayes_documents__bookmarks` (
  `bookmarks_id` int(11) NOT NULL,
  `bayes_documents_id` int(11) NOT NULL,
  PRIMARY KEY (`bookmarks_id`,`bayes_documents_id`),
  KEY `bayes_documents_id` (`bayes_documents_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `link__bayes_documents__messages`
--

CREATE TABLE IF NOT EXISTS `link__bayes_documents__messages` (
  `messages_id` int(11) NOT NULL,
  `bayes_documents_id` int(11) NOT NULL,
  PRIMARY KEY (`messages_id`,`bayes_documents_id`),
  KEY `bayes_documents_id` (`bayes_documents_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `link__bayes_documents__rss_items`
--

CREATE TABLE IF NOT EXISTS `link__bayes_documents__rss_items` (
  `rss_items_id` int(11) NOT NULL,
  `bayes_documents_id` int(11) NOT NULL,
  PRIMARY KEY (`rss_items_id`,`bayes_documents_id`),
  KEY `idx` (`bayes_documents_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `link__bookmarks__tags`
--

CREATE TABLE IF NOT EXISTS `link__bookmarks__tags` (
  `bookmarks_id` int(11) NOT NULL,
  `tags_id` int(11) NOT NULL,
  PRIMARY KEY (`bookmarks_id`,`tags_id`),
  KEY `idx` (`tags_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `link__bookmarks__users`
--

CREATE TABLE IF NOT EXISTS `link__bookmarks__users` (
  `bookmarks_id` int(11) NOT NULL,
  `users_id` int(11) NOT NULL,
  PRIMARY KEY (`bookmarks_id`,`users_id`),
  KEY `users_id` (`users_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `link__messages__tags`
--

CREATE TABLE IF NOT EXISTS `link__messages__tags` (
  `messages_id` int(11) NOT NULL,
  `tags_id` int(11) NOT NULL,
  PRIMARY KEY (`messages_id`,`tags_id`),
  KEY `tags_id` (`tags_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `link__rss_feeds__users`
--

CREATE TABLE IF NOT EXISTS `link__rss_feeds__users` (
  `rss_feeds_id` int(11) NOT NULL,
  `users_id` int(11) NOT NULL,
  PRIMARY KEY (`rss_feeds_id`,`users_id`),
  KEY `users_id` (`users_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE IF NOT EXISTS `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `users_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `body_html` mediumtext NOT NULL,
  `body_plaintext` mediumtext NOT NULL,
  `thread_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `level` int(11) NOT NULL,
  `thread_pos` int(11) NOT NULL,
  `draft` tinyint(1) NOT NULL,
  `published_on` datetime NOT NULL,
  `forum` tinyint(1) NOT NULL,
  `blog` tinyint(1) NOT NULL,
  `wiki` tinyint(1) NOT NULL,
  `slideshow` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `users_id` (`users_id`),
  KEY `published` (`published_on`,`draft`),
  KEY `thread` (`thread_id`, `parent_id`),
  KEY `thread_pos` (`thread_pos`),
  KEY `blog` (`blog`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `messages_history`
--

CREATE TABLE IF NOT EXISTS `messages_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `messages_id` int(11) NOT NULL,
  `users_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `body_html` mediumtext NOT NULL,
  `body_plaintext` mediumtext NOT NULL,
  `edited_on` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `messages_id` (`messages_id`),
  KEY `users_id` (`users_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `openid_secrets`
--

CREATE TABLE IF NOT EXISTS `openid_secrets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `expiration` int(11) NOT NULL,
  `shared_secret` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `openid_trusted`
--

CREATE TABLE IF NOT EXISTS `openid_trusted` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `auth_url` varchar(255) NOT NULL,
  `users_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `authorized` (`users_id`,`auth_url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `photoalbums`
--

CREATE TABLE IF NOT EXISTS `photoalbums` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `users_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `body_html` text NOT NULL,
  `body_plaintext` text NOT NULL,
  `thumbnail` int(11) DEFAULT NULL,
  `draft` tinyint(1) NOT NULL,
  `published_on` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `users_id` (`users_id`),
  KEY `published` (`draft`,`published_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `photos`
--

CREATE TABLE IF NOT EXISTS `photos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `users_id` int(11) NOT NULL,
  `photoalbums_id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `description` text,
  `md5` char(32) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dupechecker` (`md5`,`users_id`,`photoalbums_id`),
  KEY `users_id` (`users_id`),
  KEY `photoalbums_id` (`photoalbums_id`),
  KEY `image` (`image`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `rss_feeds`
--

CREATE TABLE IF NOT EXISTS `rss_feeds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `users_id` int(11) NOT NULL,
  `url` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `body_html` text,
  `body_plaintext` text,
  `draft` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `url` (`url`),
  KEY `users_id` (`users_id`),
  KEY `approved` (`draft`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `rss_items`
--

CREATE TABLE IF NOT EXISTS `rss_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rss_feeds_id` int(11) NOT NULL,
  `url` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `body_html` text,
  `body_plaintext` text,
  `published_on` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `url` (`url`),
  KEY `rss_feeds_id` (`rss_feeds_id`),
  KEY `published` (`published_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `socialnetwork`
--

CREATE TABLE IF NOT EXISTS `socialnetwork` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `users_id` int(11) NOT NULL,
  `friend_users_id` int(11) NOT NULL,
  `relationship` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `friendship` (`users_id`,`friend_users_id`),
  KEY `friend_users_id` (`friend_users_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE IF NOT EXISTS `tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `users_id` int(11) NOT NULL,
  `tag` varchar(64) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tag` (`tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nickname` varchar(64) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `root` tinyint(1) DEFAULT NULL,
  `banned` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nickname` (`nickname`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `users_access`
--

CREATE TABLE IF NOT EXISTS `users_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `users_id` int(11) NOT NULL,
  `module` varchar(32) NOT NULL,
  `accesslevel` int(3) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_id` (`users_id`,`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `users_info`
--

CREATE TABLE IF NOT EXISTS `users_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `users_id` int(11) NOT NULL,
  `given_name` varchar(255) DEFAULT NULL,
  `family_name` varchar(255) DEFAULT NULL,
  `street_address` varchar(255) DEFAULT NULL,
  `locality` varchar(255) DEFAULT NULL,
  `region` varchar(255) DEFAULT NULL,
  `postcode` varchar(255) DEFAULT NULL,
  `country` char(2) DEFAULT NULL,
  `tel` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` char(1) DEFAULT NULL,
  `language` char(2) DEFAULT NULL,
  `timezone` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_id` (`users_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `users_log`
--

CREATE TABLE IF NOT EXISTS `users_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `users_id` int(11) NOT NULL,
  `body_html` text NOT NULL,
  `body_plaintext` text NOT NULL,
  `ts` datetime NOT NULL,
  `private` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ts` (`ts`),
  KEY `users_id` (`users_id`),
  KEY `private` (`private`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `users_openid`
--

CREATE TABLE IF NOT EXISTS `users_openid` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `openid_url` varchar(255) NOT NULL,
  `users_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `openid_url` (`openid_url`),
  KEY `users_id` (`users_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

