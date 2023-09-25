<?php

class Stock {

	public static function add($warehouse_id, $product_id, $location_id, $qty) {
		$warehouse_id = App::escape($warehouse_id);
		$product_id = App::escape($product_id);
		$location_id = App::escape($location_id);
		$qty = App::escape($qty);

		App::sql()->insert(
			"INSERT INTO stock (warehouse_id, product_id, location_id, qty)
			VALUES ('$warehouse_id', '$product_id', '$location_id', '$qty')
			ON DUPLICATE KEY UPDATE qty = qty + '$qty';
		");
	}

}
