

CREATE TABLE `log` (
  `admin` varchar(20) NOT NULL DEFAULT '',
  `user` varchar(255) NOT NULL DEFAULT '',
  `cmd` varchar(255) NOT NULL DEFAULT '',
  `msg` varchar(255) NOT NULL DEFAULT '',
  `ip` varchar(20) NOT NULL DEFAULT '',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=ARCHIVE DEFAULT CHARSET=latin1 ROW_FORMAT=FIXED;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `users`
--

CREATE TABLE `users` (
  `server` char(25) NOT NULL DEFAULT 'main',
  `user` varchar(255) NOT NULL DEFAULT '',
  `pass` varchar(20) NOT NULL DEFAULT '',
  `type` enum('directadmin','ftp','mail','database') NOT NULL,
  `dtb` varchar(100) NOT NULL DEFAULT '',
  `domain` varchar(255) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=FIXED;

--
-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user`,`type`),
  ADD KEY `user` (`user`),
  ADD KEY `domain` (`domain`),
  ADD KEY `Type` (`type`),
  ADD KEY `dtb` (`dtb`);
