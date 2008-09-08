-- phpMyAdmin SQL Dump
-- version 2.10.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Aug 06, 2008 at 05:47 PM
-- Server version: 5.0.41
-- PHP Version: 5.2.5

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `sux0r`
--

-- --------------------------------------------------------

--
-- Table structure for table `bayes_auth`
--

CREATE TABLE `bayes_auth` (
  `id` int(11) NOT NULL auto_increment,
  `users_id` int(11) NOT NULL,
  `bayes_vectors_id` int(11) NOT NULL,
  `owner` tinyint(1) default NULL,
  `trainer` tinyint(1) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `users_bayes_vectors` (`users_id`,`bayes_vectors_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `bayes_auth`
--

-- --------------------------------------------------------

--
-- Table structure for table `bayes_cache`
--

CREATE TABLE `bayes_cache` (
  `md5` char(32) character set latin1 collate latin1_general_ci NOT NULL,
  `bayes_vectors_id` int(11) NOT NULL,
  `expiration` int(11) NOT NULL,
  `scores` text NOT NULL,
  PRIMARY KEY  (`md5`),
  KEY `expiration` (`expiration`),
  KEY `bayes_vectors_id` (`bayes_vectors_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `bayes_cache`
--

-- --------------------------------------------------------

--
-- Table structure for table `bayes_categories`
--

CREATE TABLE `bayes_categories` (
  `id` int(11) NOT NULL auto_increment,
  `category` varchar(64) NOT NULL,
  `bayes_vectors_id` int(11) NOT NULL,
  `probability` double NOT NULL default '0',
  `token_count` bigint(20) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `grouping` (`category`,`bayes_vectors_id`),
  KEY `bayes_vectors_id` (`bayes_vectors_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `bayes_categories`
--

-- --------------------------------------------------------

--
-- Table structure for table `bayes_documents`
--

CREATE TABLE `bayes_documents` (
  `id` int(11) NOT NULL auto_increment,
  `bayes_categories_id` int(11) NOT NULL,
  `body_plaintext` mediumtext NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `bayes_categories_id` (`bayes_categories_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `bayes_documents`
--

-- --------------------------------------------------------

--
-- Table structure for table `bayes_tokens`
--

CREATE TABLE `bayes_tokens` (
  `id` int(11) NOT NULL auto_increment,
  `token` varchar(64) NOT NULL,
  `bayes_categories_id` int(11) NOT NULL,
  `count` bigint(20) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `grouping` (`token`,`bayes_categories_id`),
  KEY `bayes_categories_id` (`bayes_categories_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `bayes_tokens`
--

-- --------------------------------------------------------

--
-- Table structure for table `bayes_vectors`
--

CREATE TABLE `bayes_vectors` (
  `id` int(11) NOT NULL auto_increment,
  `vector` varchar(64) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `bayes_vectors`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookmarks`
--

CREATE TABLE `bookmarks` (
  `id` int(11) NOT NULL auto_increment,
  `users_id` int(11) NOT NULL,
  `url` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `body_html` text,
  `body_plaintext` text,
  `draft` tinyint(1) NOT NULL,
  `published_on` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `url` (`url`),
  KEY `users_id` (`users_id`),
  KEY `published` (`draft`,`published_on`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `bookmarks`
--


-- --------------------------------------------------------

--
-- Table structure for table `link_bayes_messages`
--

CREATE TABLE `link_bayes_messages` (
  `messages_id` int(11) NOT NULL,
  `bayes_documents_id` int(11) NOT NULL,
  UNIQUE KEY `idx` (`messages_id`,`bayes_documents_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `link_bayes_messages`
--

-- --------------------------------------------------------

--
-- Table structure for table `link_bayes_rss`
--

CREATE TABLE `link_bayes_rss` (
  `rss_items_id` int(11) NOT NULL,
  `bayes_documents_id` int(11) NOT NULL,
  UNIQUE KEY `idx` (`rss_items_id`,`bayes_documents_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `link_bayes_rss`
--


-- --------------------------------------------------------

--
-- Table structure for table `link_bookmarks_tags`
--

CREATE TABLE `link_bookmarks_tags` (
  `bookmarks_id` int(11) NOT NULL,
  `tags_id` int(11) NOT NULL,
  UNIQUE KEY `idx` (`bookmarks_id`,`tags_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `link_bookmarks_tags`
--


-- --------------------------------------------------------

--
-- Table structure for table `link_messages_tags`
--

CREATE TABLE `link_messages_tags` (
  `messages_id` int(11) NOT NULL,
  `tags_id` int(11) NOT NULL,
  UNIQUE KEY `idx` (`messages_id`,`tags_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `link_messages_tags`
--

-- --------------------------------------------------------

--
-- Table structure for table `link_rss_users`
--

CREATE TABLE `link_rss_users` (
  `rss_feeds_id` int(11) NOT NULL,
  `users_id` int(11) NOT NULL,
  UNIQUE KEY `idx` (`rss_feeds_id`,`users_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `link_rss_users`
--


-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL auto_increment,
  `users_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `image` varchar(255) default NULL,
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
  PRIMARY KEY  (`id`),
  KEY `users_id` (`users_id`),
  KEY `thread` (`thread_id`,`thread_pos`),
  KEY `published` (`published_on`,`draft`),
  KEY `type` (`forum`,`blog`,`wiki`,`slideshow`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `messages`
--

-- --------------------------------------------------------

--
-- Table structure for table `messages_history`
--

CREATE TABLE `messages_history` (
  `id` int(11) NOT NULL auto_increment,
  `messages_id` int(11) NOT NULL,
  `users_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `image` varchar(255) default NULL,
  `body_html` mediumtext NOT NULL,
  `body_plaintext` mediumtext NOT NULL,
  `edited_on` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `messages_id` (`messages_id`),
  KEY `users_id` (`users_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `messages_history`
--

-- --------------------------------------------------------

--
-- Table structure for table `openid_secrets`
--

CREATE TABLE `openid_secrets` (
  `id` int(11) NOT NULL auto_increment,
  `expiration` int(11) NOT NULL,
  `shared_secret` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `openid_secrets`
--


-- --------------------------------------------------------

--
-- Table structure for table `openid_trusted`
--

CREATE TABLE `openid_trusted` (
  `id` int(11) NOT NULL auto_increment,
  `auth_url` varchar(255) NOT NULL,
  `users_id` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `authorized` (`auth_url`,`users_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `openid_trusted`
--


-- --------------------------------------------------------

--
-- Table structure for table `photoalbums`
--

CREATE TABLE `photoalbums` (
  `id` int(11) NOT NULL auto_increment,
  `users_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `body_html` text NOT NULL,
  `body_plaintext` text NOT NULL,
  `thumbnail` int(11) default NULL,
  `draft` tinyint(1) NOT NULL,
  `published_on` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `users_id` (`users_id`),
  KEY `published` (`draft`,`published_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `photoalbums`
--


-- --------------------------------------------------------

--
-- Table structure for table `photos`
--

CREATE TABLE `photos` (
  `id` int(11) NOT NULL auto_increment,
  `users_id` int(11) NOT NULL,
  `photoalbums_id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `md5` char(32) character set latin1 collate latin1_general_ci NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `dupechecker` (`md5`,`users_id`,`photoalbums_id`),
  KEY `users_id` (`users_id`),
  KEY `photoalbums_id` (`photoalbums_id`),
  KEY `image` (`image`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `photos`
--

-- --------------------------------------------------------

--
-- Table structure for table `rss_feeds`
--

CREATE TABLE `rss_feeds` (
  `id` int(11) NOT NULL auto_increment,
  `users_id` int(11) NOT NULL,
  `url` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `body_html` text,
  `body_plaintext` text,
  `draft` tinyint(1) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `url` (`url`),
  KEY `users_id` (`users_id`),
  KEY `approved` (`draft`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `rss_feeds`
--

-- --------------------------------------------------------

--
-- Table structure for table `rss_items`
--

CREATE TABLE `rss_items` (
  `id` int(11) NOT NULL auto_increment,
  `rss_feeds_id` int(11) NOT NULL,
  `url` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `body_html` text,
  `body_plaintext` text,
  `published_on` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `url` (`url`),
  KEY `rss_feeds_id` (`rss_feeds_id`),
  KEY `published` (`published_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `rss_items`
--

-- --------------------------------------------------------

--
-- Table structure for table `socialnetwork`
--

CREATE TABLE `socialnetwork` (
  `id` int(11) NOT NULL auto_increment,
  `users_id` int(11) NOT NULL,
  `friend_users_id` int(11) NOT NULL,
  `relationship` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `friendship` (`users_id`,`friend_users_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `socialnetwork`
--


-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `id` int(11) NOT NULL auto_increment,
  `users_id` int(11) NOT NULL,
  `tag` varchar(64) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `tag` (`tag`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `tags`
--


-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL auto_increment,
  `nickname` varchar(64) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `accesslevel` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `nickname` (`nickname`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `users`
--

INSERT INTO `users` VALUES (1, 'test', 'test@test.com', '24d7d9859810e5834bbfdcc9dd931fca', 0);

-- --------------------------------------------------------

--
-- Table structure for table `users_info`
--

CREATE TABLE `users_info` (
  `id` int(11) NOT NULL auto_increment,
  `users_id` int(11) NOT NULL,
  `given_name` varchar(255) default NULL,
  `family_name` varchar(255) default NULL,
  `street_address` varchar(255) default NULL,
  `locality` varchar(255) default NULL,
  `region` varchar(255) default NULL,
  `postcode` varchar(255) default NULL,
  `country` char(2) default NULL,
  `tel` varchar(255) default NULL,
  `url` varchar(255) default NULL,
  `dob` date default NULL,
  `gender` char(1) default NULL,
  `language` char(2) default NULL,
  `timezone` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `users_id` (`users_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `users_info`
--

INSERT INTO `users_info` VALUES (1, 1, '', '', '', '', '', '', 'ca', '', '', '0000-00-00', 'm', 'en', 'America/Montreal');

-- --------------------------------------------------------

--
-- Table structure for table `users_openid`
--

CREATE TABLE `users_openid` (
  `id` int(11) NOT NULL auto_increment,
  `openid_url` varchar(255) NOT NULL,
  `users_id` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `openid_url` (`openid_url`),
  KEY `users_id` (`users_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `users_openid`
--

