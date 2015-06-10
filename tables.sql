

CREATE TABLE IF NOT EXISTS `log` (
  `ID` int(10) unsigned NOT NULL,
  `admin` varchar(20) NOT NULL DEFAULT '',
  `user` varchar(255) NOT NULL DEFAULT '',
  `cmd` varchar(255) NOT NULL DEFAULT '',
  `msg` varchar(255) NOT NULL DEFAULT '',
  `ip` varchar(20) NOT NULL DEFAULT '',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=FIXED;


CREATE TABLE IF NOT EXISTS `users` (
  `server` char(25) NOT NULL DEFAULT 'main',
  `user` varchar(255) NOT NULL DEFAULT '',
  `pass` varchar(20) NOT NULL DEFAULT '',
  `type` enum('directadmin','ftp','mail','database') NOT NULL,
  `dtb` varchar(100) NOT NULL DEFAULT '',
  `domain` varchar(255) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=FIXED;

ALTER TABLE `log`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `user` (`user`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`user`,`type`),
  ADD KEY `user` (`user`),
  ADD KEY `domain` (`domain`),
  ADD KEY `Type` (`type`),
  ADD KEY `dtb` (`dtb`);

ALTER TABLE `log`
  MODIFY `ID` int(10) unsigned NOT NULL AUTO_INCREMENT;
