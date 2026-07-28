<?php
/**
 * template-parts/shop-filters.php
 *
 * NOTE: per the comment at the top of inc/taxonomies.php, this theme
 * deliberately filters wines by WooCommerce Product Category only (Country/
 * Region/Colour/Grape taxonomies were removed to avoid duplicate, confusing
 * sidebar boxes). So this sidebar shows: Category, Price, Availability.
 *
 * If you later want separate Country/Region/Colour/Grape filter boxes again,
 * re-add those taxonomies in inc/taxonomies.php and copy the "Category" block
 * below once per taxonomy.
 *
 * @package maison-vintique-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mve_active_cats = isset( $_GET['product_cat'] ) ? (array) $_GET['product_cat'] : array();
$mve_price_min   = isset( $_GET['price_min'] ) ? floatval( $_GET['price_min'] ) : 12;
$mve_price_max   = isset( $_GET['price_max'] ) ? floatval( $_GET['price_max'] ) : 120;
$mve_stock       = isset( $_GET['stock'] ) ? sanitize_text_field( wp_unslash( $_GET['stock'] ) ) : '';

$mve_categories = get_terms(
	array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
	)
);
?>
<form method="get" id="mve-filters">

	<div class="fbox">
		<h4>Search</h4>
		<div class="search" style="width:100%">
			<input type="text" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="Keyword…" style="width:100%">
			<input type="hidden" name="post_type" value="product">
		</div>
	</div>
	
	<?php
$mve_countries = get_terms(
	array(
		'taxonomy'   => 'wine_country',
		'hide_empty' => true,
	)
);

$mve_active_countries = isset( $_GET['wine_country'] )
	? (array) $_GET['wine_country']
	: array();

$mve_active_countries = array_map( 'sanitize_text_field', $mve_active_countries );
?>

<?php if ( ! is_wp_error( $mve_countries ) && $mve_countries ) : ?>
	<div class="fbox">
		<h4>Country</h4>

		<?php foreach ( $mve_countries as $mve_country ) : ?>
			<label>
				<input
					type="checkbox"
					name="wine_country[]"
					value="<?php echo esc_attr( $mve_country->slug ); ?>"
					<?php checked( in_array( $mve_country->slug, $mve_active_countries, true ) ); ?>
					onchange="this.form.submit()"
				>

				<?php echo esc_html( $mve_country->name ); ?>

				<span class="count">
					<?php echo esc_html( $mve_country->count ); ?>
				</span>
			</label>
		<?php endforeach; ?>
	</div>
<?php endif; ?>
	
	<?php
$mve_regions = get_terms(
	array(
		'taxonomy'   => 'wine_region',
		'hide_empty' => true,
	)
);

$mve_active_regions = isset( $_GET['wine_region'] )
	? (array) $_GET['wine_region']
	: array();

$mve_active_regions = array_map( 'sanitize_text_field', $mve_active_regions );
?>

<?php if ( ! is_wp_error( $mve_regions ) && $mve_regions ) : ?>
	<div class="fbox">
		<h4>Region</h4>

		<?php foreach ( $mve_regions as $mve_region ) : ?>
			<label>
				<input
					type="checkbox"
					name="wine_region[]"
					value="<?php echo esc_attr( $mve_region->slug ); ?>"
					<?php checked( in_array( $mve_region->slug, $mve_active_regions, true ) ); ?>
					onchange="this.form.submit()"
				>

				<?php echo esc_html( $mve_region->name ); ?>

				<span class="count">
					<?php echo esc_html( $mve_region->count ); ?>
				</span>
			</label>
		<?php endforeach; ?>
	</div>
<?php endif; ?>

	<?php if ( ! is_wp_error( $mve_categories ) && $mve_categories ) : ?>
		<div class="fbox">
			<h4>Category</h4>
			<?php foreach ( $mve_categories as $mve_cat ) : ?>
				<label>
					<input type="checkbox" name="product_cat[]" value="<?php echo esc_attr( $mve_cat->slug ); ?>"
						<?php checked( in_array( $mve_cat->slug, $mve_active_cats, true ) ); ?>
						onchange="this.form.submit()">
					<?php echo esc_html( $mve_cat->name ); ?>
					<span class="count"><?php echo esc_html( $mve_cat->count ); ?></span>
				</label>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	
	<?php $mve_colours = get_terms(
	array(
		'taxonomy'   => 'wine_colour',
		'hide_empty' => true,
	)
);

$mve_active_colours = isset( $_GET['wine_colour'] )
	? (array) $_GET['wine_colour']
	: array();

$mve_active_colours = array_map( 'sanitize_text_field', $mve_active_colours );
?>

<?php if ( ! is_wp_error( $mve_colours ) && $mve_colours ) : ?>
	<div class="fbox">
		<h4>Colour</h4>

		<?php foreach ( $mve_colours as $mve_colour ) : ?>
			<label>
				<input
					type="checkbox"
					name="wine_colour[]"
					value="<?php echo esc_attr( $mve_colour->slug ); ?>"
					<?php checked( in_array( $mve_colour->slug, $mve_active_colours, true ) ); ?>
					onchange="this.form.submit()"
				>

				<?php echo esc_html( $mve_colour->name ); ?>

				<span class="count">
					<?php echo esc_html( $mve_colour->count ); ?>
				</span>
			</label>
		<?php endforeach; ?>
	</div>
<?php endif; ?>
	
	
	
	<?php
$mve_grapes = get_terms(
	array(
		'taxonomy'   => 'wine_grape',
		'hide_empty' => true,
	)
);

$mve_active_grapes = isset( $_GET['wine_grape'] )
	? (array) $_GET['wine_grape']
	: array();

$mve_active_grapes = array_map( 'sanitize_text_field', $mve_active_grapes );
?>

<?php if ( ! is_wp_error( $mve_grapes ) && $mve_grapes ) : ?>
	<div class="fbox">
		<h4>Grape</h4>

		<?php foreach ( $mve_grapes as $mve_grape ) : ?>
			<label>
				<input
					type="checkbox"
					name="wine_grape[]"
					value="<?php echo esc_attr( $mve_grape->slug ); ?>"
					<?php checked( in_array( $mve_grape->slug, $mve_active_grapes, true ) ); ?>
					onchange="this.form.submit()"
				>

				<?php echo esc_html( $mve_grape->name ); ?>

				<span class="count">
					<?php echo esc_html( $mve_grape->count ); ?>
				</span>
			</label>
		<?php endforeach; ?>
	</div>
<?php endif; ?>
	
	
	

	<div class="fbox">
		<h4>Price (per bottle)</h4>
		<div class="frange">
			<span>£<?php echo esc_html( $mve_price_min ); ?></span>
			<input type="range" name="price_max" min="12" max="120" value="<?php echo esc_attr( $mve_price_max ); ?>" onchange="this.form.submit()">
			<span>£<?php echo esc_html( $mve_price_max ); ?></span>
		</div>
	</div>
	
	<?php
$mve_recognitions = get_terms(
	array(
		'taxonomy'   => 'wine_recognition',
		'hide_empty' => true,
	)
);

$mve_active_recognitions = isset( $_GET['wine_recognition'] )
	? (array) $_GET['wine_recognition']
	: array();

$mve_active_recognitions = array_map( 'sanitize_text_field', $mve_active_recognitions );
?>

<?php if ( ! is_wp_error( $mve_recognitions ) && $mve_recognitions ) : ?>
	<div class="fbox">
		<h4>Recognition</h4>

		<?php foreach ( $mve_recognitions as $mve_recognition ) : ?>
			<label>
				<input
					type="checkbox"
					name="wine_recognition[]"
					value="<?php echo esc_attr( $mve_recognition->slug ); ?>"
					<?php checked( in_array( $mve_recognition->slug, $mve_active_recognitions, true ) ); ?>
					onchange="this.form.submit()"
				>

				<?php echo esc_html( $mve_recognition->name ); ?>

				<span class="count">
					<?php echo esc_html( $mve_recognition->count ); ?>
				</span>
			</label>
		<?php endforeach; ?>
	</div>
<?php endif; ?>

	<div class="fbox">
		<h4>Availability</h4>
		<label>
			<input type="radio" name="stock" value="" <?php checked( '' === $mve_stock ); ?> onchange="this.form.submit()">
			All
		</label>
		<label>
			<input type="radio" name="stock" value="instock" <?php checked( 'instock' === $mve_stock ); ?> onchange="this.form.submit()">
			In stock
		</label>
		<label>
			<input type="radio" name="stock" value="allocation" <?php checked( 'allocation' === $mve_stock ); ?> onchange="this.form.submit()">
			By allocation
		</label>
	</div>

</form>
