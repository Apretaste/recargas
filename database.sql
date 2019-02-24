CREATE TABLE `_recargas` (
  `id` int(11) NOT NULL,
  `person_id` int(11) NOT NULL,
  `product_code` varchar(20) NOT NULL,
  `inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `paid` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `_recargas`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `_recargas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;