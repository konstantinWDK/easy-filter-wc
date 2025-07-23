<?php
/**
 * Plugin Name: Easy Filter WC
 * Description: A simple and powerful WooCommerce product filter plugin with category, tag, price, and attribute filtering.
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: easy-filter-wc
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * WC requires at least: 3.0
 * WC tested up to: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

function easy_filter_wc_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>' . __('Easy Filter WC requires WooCommerce to be installed and activated.', 'easy-filter-wc') . '</p></div>';
        });
        return false;
    }
    return true;
}

add_action('plugins_loaded', function() {
    if (!easy_filter_wc_check_woocommerce()) {
        return;
    }
    
    new EasyFilterWC();
});

define('EASY_FILTER_WC_VERSION', '1.0.0');
define('EASY_FILTER_WC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EASY_FILTER_WC_PLUGIN_PATH', plugin_dir_path(__FILE__));

class EasyFilterWC {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_easy_filter_products', array($this, 'ajax_filter_products'));
        add_action('wp_ajax_nopriv_easy_filter_products', array($this, 'ajax_filter_products'));
        add_action('wp_ajax_easy_filter_test', array($this, 'ajax_test'));
        add_action('wp_ajax_nopriv_easy_filter_test', array($this, 'ajax_test'));
        add_shortcode('easy_filter', array($this, 'shortcode'));
        add_shortcode('easy_products', array($this, 'products_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }
    
    public function init() {
        load_plugin_textdomain('easy-filter-wc', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function activate() {
        $default_options = array(
            'show_categories' => 1,
            'show_tags' => 1,
            'show_price' => 1,
            'show_attributes' => 1,
            'filter_style' => 'checkboxes',
            'price_slider' => 1,
            'ajax_filtering' => 1,
            'selected_attributes' => array() // Empty array means show all attributes
        );
        add_option('easy_filter_wc_options', $default_options);
    }
    
    public function admin_menu() {
        add_options_page(
            'Configuración Easy Filter WC',
            'Easy Filter WC',
            'manage_options',
            'easy-filter-wc',
            array($this, 'admin_page')
        );
    }
    
    public function admin_init() {
        register_setting('easy_filter_wc_group', 'easy_filter_wc_options');
        
        add_settings_section(
            'easy_filter_wc_main',
            'Opciones del Filtro',
            null,
            'easy-filter-wc'
        );
        
        add_settings_field(
            'show_categories',
            'Mostrar Categorías',
            array($this, 'checkbox_field'),
            'easy-filter-wc',
            'easy_filter_wc_main',
            array('field' => 'show_categories')
        );
        
        add_settings_field(
            'show_tags',
            'Mostrar Etiquetas',
            array($this, 'checkbox_field'),
            'easy-filter-wc',
            'easy_filter_wc_main',
            array('field' => 'show_tags')
        );
        
        add_settings_field(
            'show_price',
            'Mostrar Filtro de Precio',
            array($this, 'checkbox_field'),
            'easy-filter-wc',
            'easy_filter_wc_main',
            array('field' => 'show_price')
        );
        
        add_settings_field(
            'show_attributes',
            'Mostrar Atributos',
            array($this, 'checkbox_field'),
            'easy-filter-wc',
            'easy_filter_wc_main',
            array('field' => 'show_attributes')
        );
        
        add_settings_field(
            'price_slider',
            'Usar Slider de Precio',
            array($this, 'checkbox_field'),
            'easy-filter-wc',
            'easy_filter_wc_main',
            array('field' => 'price_slider')
        );
        
        add_settings_field(
            'ajax_filtering',
            'Habilitar Filtrado AJAX',
            array($this, 'checkbox_field'),
            'easy-filter-wc',
            'easy_filter_wc_main',
            array('field' => 'ajax_filtering')
        );
        
        add_settings_field(
            'selected_attributes',
            'Seleccionar Atributos a Mostrar',
            array($this, 'attributes_field'),
            'easy-filter-wc',
            'easy_filter_wc_main'
        );
    }
    
    public function checkbox_field($args) {
        $options = get_option('easy_filter_wc_options');
        $value = isset($options[$args['field']]) ? $options[$args['field']] : 0;
        echo '<input type="checkbox" name="easy_filter_wc_options[' . $args['field'] . ']" value="1" ' . checked(1, $value, false) . ' />';
    }
    
    public function attributes_field() {
        $options = get_option('easy_filter_wc_options');
        $selected_attributes = isset($options['selected_attributes']) ? $options['selected_attributes'] : array();
        
        $attributes = wc_get_attribute_taxonomies();
        
        if (empty($attributes)) {
            echo '<p>No se encontraron atributos de producto. Crea algunos atributos primero en WooCommerce > Productos > Atributos.</p>';
            return;
        }
        
        echo '<div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">';
        echo '<p><em>Selecciona qué atributos incluir en el filtro. Deja todos sin marcar para mostrar todos los atributos.</em></p>';
        
        foreach ($attributes as $attribute) {
            $taxonomy = 'pa_' . $attribute->attribute_name;
            $is_checked = in_array($taxonomy, $selected_attributes);
            
            echo '<label style="display: block; margin-bottom: 8px;">';
            echo '<input type="checkbox" name="easy_filter_wc_options[selected_attributes][]" value="' . esc_attr($taxonomy) . '" ' . checked(true, $is_checked, false) . ' style="margin-right: 8px;">';
            echo '<strong>' . esc_html($attribute->attribute_label) . '</strong>';
            echo ' <span style="color: #666;">(' . esc_html($attribute->attribute_name) . ')</span>';
            
            // Show attribute terms count
            $terms = get_terms(array(
                'taxonomy' => $taxonomy,
                'hide_empty' => false
            ));
            if (!is_wp_error($terms)) {
                echo ' <span style="color: #999; font-size: 0.9em;">- ' . count($terms) . ' términos</span>';
            }
            
            echo '</label>';
        }
        
        echo '</div>';
        echo '<p class="description">Solo los atributos seleccionados aparecerán en el widget del filtro. Si no se selecciona ningún atributo, se mostrarán todos los atributos.</p>';
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Easy Filter WC Settings', 'easy-filter-wc'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('easy_filter_wc_group');
                do_settings_sections('easy-filter-wc');
                submit_button();
                ?>
            </form>
            <div class="postbox" style="margin-top: 20px; padding: 15px;">
                <h3><?php _e('Shortcode Usage', 'easy-filter-wc'); ?></h3>
                
                <h4><?php _e('Filter Shortcode', 'easy-filter-wc'); ?></h4>
                <p><?php _e('Use this shortcode to display the filter (usually in sidebar):', 'easy-filter-wc'); ?></p>
                <code style="background: #f1f1f1; padding: 5px; border-radius: 3px;">[easy_filter]</code>
                
                <h4 style="margin-top: 20px;"><?php _e('Products Listing Shortcode', 'easy-filter-wc'); ?></h4>
                <p><?php _e('Use this shortcode to display a custom products listing:', 'easy-filter-wc'); ?></p>
                <code style="background: #f1f1f1; padding: 5px; border-radius: 3px;">[easy_products]</code>
                
                <h4 style="margin-top: 15px;"><?php _e('Products Shortcode Options:', 'easy-filter-wc'); ?></h4>
                <ul style="margin: 10px 0 0 20px;">
                    <li><strong>columns</strong>: Number of columns (1-5, default: 3)</li>
                    <li><strong>per_page</strong>: Products per page (default: 12)</li>
                    <li><strong>category</strong>: Show products from specific category</li>
                    <li><strong>orderby</strong>: Sort products (menu_order, date, price, popularity)</li>
                    <li><strong>show_pagination</strong>: Show pagination (yes/no, default: yes)</li>
                    <li><strong>show_sorting</strong>: Show sorting dropdown (yes/no, default: yes)</li>
                    <li><strong>show_result_count</strong>: Show result count (yes/no, default: yes)</li>
                </ul>
                
                <h4 style="margin-top: 15px;"><?php _e('Example:', 'easy-filter-wc'); ?></h4>
                <code style="background: #f1f1f1; padding: 5px; border-radius: 3px;">[easy_products columns="4" per_page="16"]</code>
                
                <p style="margin-top: 15px;"><strong><?php _e('Perfect for category pages:', 'easy-filter-wc'); ?></strong> <?php _e('Use both shortcodes together - [easy_filter] in sidebar and [easy_products] in content area for a complete filtering experience.', 'easy-filter-wc'); ?></p>
            </div>
        </div>
        <?php
    }
    
    public function enqueue_scripts() {
        // Load on WooCommerce pages AND pages that might have Divi Woo Products module
        $should_load = is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy();
        
        // Also check if it's a page that might contain the shortcode or Divi modules
        global $post;
        if (!$should_load && $post) {
            // Check if shortcode is present in content
            $should_load = has_shortcode($post->post_content, 'easy_filter');
            
            // Check for Divi builder content (stored in _et_pb_page_layout_style)
            if (!$should_load) {
                $page_layout = get_post_meta($post->ID, '_et_pb_use_builder', true);
                if ($page_layout === 'on') {
                    $should_load = true; // Load on all Divi builder pages
                }
            }
        }
        
        // Always load on pages with widgets that might contain our shortcode
        if (!$should_load && (is_active_sidebar('sidebar-1') || is_active_sidebar('primary-sidebar'))) {
            $should_load = true;
        }
        
        if ($should_load) {
            wp_enqueue_script('jquery');
            wp_enqueue_script('jquery-ui-slider');
            wp_enqueue_style('jquery-ui-style', 'https://code.jquery.com/ui/1.12.1/themes/ui-lightness/jquery-ui.css');
            wp_enqueue_script('easy-filter-wc', EASY_FILTER_WC_PLUGIN_URL . 'assets/js/filter.js', array('jquery', 'jquery-ui-slider'), EASY_FILTER_WC_VERSION, true);
            wp_enqueue_style('easy-filter-wc', EASY_FILTER_WC_PLUGIN_URL . 'assets/css/filter.css', array(), EASY_FILTER_WC_VERSION);
            
            wp_localize_script('easy-filter-wc', 'easy_filter_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('easy_filter_nonce')
            ));
        }
    }
    
    public function shortcode($atts) {
        $options = get_option('easy_filter_wc_options');
        
        if (!$options) {
            return '<p>' . __('Please configure the filter settings first.', 'easy-filter-wc') . '</p>';
        }
        
        ob_start();
        $this->render_filter($options);
        return ob_get_clean();
    }
    
    private function render_filter($options) {
        echo '<div id="easy-filter-wc" class="easy-filter-widget">';
        echo '<h3 class="filter-title">Filtrar Productos</h3>';
        echo '<form id="easy-filter-form">';
        
        $current_context = $this->get_current_context();
        
        if (!empty($options['show_categories'])) {
            $this->render_categories($current_context);
        }
        
        if (!empty($options['show_tags'])) {
            $this->render_tags($current_context);
        }
        
        if (!empty($options['show_price'])) {
            $this->render_price_filter($options, $current_context);
        }
        
        if (!empty($options['show_attributes'])) {
            $this->render_attributes($current_context);
        }
        
        // Add current context as hidden field
        if ($current_context['category_id']) {
            echo '<input type="hidden" name="current_category" value="' . $current_context['category_id'] . '">';
        }
        
        echo '<div class="filter-buttons">';
        echo '<button type="submit" class="filter-submit">Aplicar Filtros</button>';
        echo '<button type="button" class="filter-reset">Limpiar Todo</button>';
        echo '</div>';
        
        echo '</form>';
        echo '<div id="filter-loading" style="display:none;">' . __('Loading...', 'easy-filter-wc') . '</div>';
        echo '</div>';
    }
    
    private function get_current_context() {
        $context = array(
            'category_id' => 0,
            'tag_id' => 0,
            'is_shop' => is_shop(),
            'is_product_category' => is_product_category(),
            'is_product_tag' => is_product_tag(),
            'current_products' => array()
        );
        
        if (is_product_category()) {
            $context['category_id'] = get_queried_object_id();
        } elseif (is_product_tag()) {
            $context['tag_id'] = get_queried_object_id();
        }
        
        // Get current products based on context
        $context['current_products'] = $this->get_current_page_products($context);
        
        return $context;
    }
    
    private function get_current_page_products($context) {
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1, // Get all products to build accurate filters
            'fields' => 'ids', // Only need IDs for efficiency
            'tax_query' => array()
        );
        
        // Add category context
        if ($context['category_id'] > 0) {
            $args['tax_query'][] = array(
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $context['category_id'],
                'operator' => 'IN'
            );
        }
        
        // Add tag context
        if ($context['tag_id'] > 0) {
            $args['tax_query'][] = array(
                'taxonomy' => 'product_tag',
                'field' => 'term_id',
                'terms' => $context['tag_id'],
                'operator' => 'IN'
            );
        }
        
        $products = get_posts($args);
        return $products;
    }
    
    private function get_term_product_count($term_id, $taxonomy, $context_products) {
        if (empty($context_products)) {
            return 0;
        }
        
        // Use direct database query for better performance
        global $wpdb;
        
        $product_ids = implode(',', array_map('intval', $context_products));
        
        $sql = $wpdb->prepare("
            SELECT COUNT(DISTINCT p.ID)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            WHERE p.ID IN ({$product_ids})
            AND tt.taxonomy = %s
            AND tt.term_id = %d
            AND p.post_status = 'publish'
        ", $taxonomy, $term_id);
        
        return (int) $wpdb->get_var($sql);
    }
    
    private function render_categories($context = null) {
        if (!$context || empty($context['current_products'])) {
            return;
        }
        
        // Get categories that are actually used by current products
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
            'object_ids' => $context['current_products']
        ));
        
        // If we're on a category page, also show subcategories
        if ($context['is_product_category'] && $context['category_id']) {
            $subcategories = get_terms(array(
                'taxonomy' => 'product_cat',
                'hide_empty' => true,
                'parent' => $context['category_id'],
                'object_ids' => $context['current_products']
            ));
            
            // Combine categories, giving priority to subcategories
            if (!empty($subcategories)) {
                $categories = $subcategories;
            }
        }
        
        // Remove current category from the list since we're already in it
        if ($context['category_id'] > 0) {
            $categories = array_filter($categories, function($cat) use ($context) {
                return $cat->term_id !== $context['category_id'];
            });
        }
        
        if (!empty($categories) && !is_wp_error($categories)) {
            $has_categories_with_products = false;
            $categories_html = '';
            
            foreach ($categories as $category) {
                // Calculate accurate product count for this category in current context
                $product_count = $this->get_term_product_count($category->term_id, 'product_cat', $context['current_products']);
                
                if ($product_count > 0) { // Only show categories that have products
                    $has_categories_with_products = true;
                    $categories_html .= '<label>';
                    $categories_html .= '<input type="checkbox" name="categories[]" value="' . $category->term_id . '" data-category-name="' . esc_attr($category->name) . '">';
                    $categories_html .= ' ' . $category->name . ' (' . $product_count . ')';
                    $categories_html .= '</label>';
                }
            }
            
            // Only show the Categories section if there are categories with products
            if ($has_categories_with_products) {
                echo '<div class="filter-group">';
                echo '<h4>Categorías</h4>';
                echo $categories_html;
                echo '</div>';
            }
        }
    }
    
    private function render_tags($context = null) {
        if (!$context || empty($context['current_products'])) {
            return;
        }
        
        // Get tags that are actually used by current products
        $tags = get_terms(array(
            'taxonomy' => 'product_tag',
            'hide_empty' => true,
            'object_ids' => $context['current_products']
        ));
        
        if (!empty($tags) && !is_wp_error($tags)) {
            $has_tags_with_products = false;
            $tags_html = '';
            
            foreach ($tags as $tag) {
                // Calculate accurate product count for this tag in current context
                $product_count = $this->get_term_product_count($tag->term_id, 'product_tag', $context['current_products']);
                
                if ($product_count > 0) { // Only show tags that have products
                    $has_tags_with_products = true;
                    $tags_html .= '<label>';
                    $tags_html .= '<input type="checkbox" name="tags[]" value="' . $tag->term_id . '">';
                    $tags_html .= ' ' . $tag->name . ' (' . $product_count . ')';
                    $tags_html .= '</label>';
                }
            }
            
            // Only show the Tags section if there are tags with products
            if ($has_tags_with_products) {
                echo '<div class="filter-group">';
                echo '<h4>Etiquetas</h4>';
                echo $tags_html;
                echo '</div>';
            }
        }
    }
    
    private function render_price_filter($options, $context = null) {
        $prices = $this->get_price_range($context);
        
        // Only show price filter if we have valid price range and products with prices
        if ($prices['min'] >= $prices['max'] || $prices['max'] <= 0) {
            return; // No valid price range, don't show price filter
        }
        
        echo '<div class="filter-group price-filter">';
        echo '<h4>Rango de Precio</h4>';
        
        if (!empty($options['price_slider'])) {
            echo '<div id="price-slider"></div>';
            echo '<div class="price-inputs">';
            echo '<input type="number" id="min-price" name="min_price" placeholder="' . __('Min', 'easy-filter-wc') . '" min="' . $prices['min'] . '" max="' . $prices['max'] . '">';
            echo '<input type="number" id="max-price" name="max_price" placeholder="' . __('Max', 'easy-filter-wc') . '" min="' . $prices['min'] . '" max="' . $prices['max'] . '">';
            echo '</div>';
            echo '<script>
                jQuery(document).ready(function($) {
                    $("#price-slider").slider({
                        range: true,
                        min: ' . $prices['min'] . ',
                        max: ' . $prices['max'] . ',
                        values: [' . $prices['min'] . ', ' . $prices['max'] . '],
                        slide: function(event, ui) {
                            $("#min-price").val(ui.values[0]);
                            $("#max-price").val(ui.values[1]);
                        }
                    });
                });
            </script>';
        } else {
            echo '<input type="number" name="min_price" placeholder="' . __('Min Price', 'easy-filter-wc') . '" min="' . $prices['min'] . '" max="' . $prices['max'] . '">';
            echo '<input type="number" name="max_price" placeholder="' . __('Max Price', 'easy-filter-wc') . '" min="' . $prices['min'] . '" max="' . $prices['max'] . '">';
        }
        
        echo '</div>';
    }
    
    private function render_attributes($context = null) {
        if (!$context || empty($context['current_products'])) {
            return;
        }
        
        $options = get_option('easy_filter_wc_options');
        $selected_attributes = isset($options['selected_attributes']) ? $options['selected_attributes'] : array();
        
        $attributes = wc_get_attribute_taxonomies();
        $has_attributes = false;
        
        if (!empty($attributes)) {
            $attributes_html = '';
            
            foreach ($attributes as $attribute) {
                $taxonomy = 'pa_' . $attribute->attribute_name;
                
                // Skip this attribute if it's not in the selected list (unless no attributes are selected, then show all)
                if (!empty($selected_attributes) && !in_array($taxonomy, $selected_attributes)) {
                    continue;
                }
                
                // Get attribute terms that are actually used by current products
                $terms = get_terms(array(
                    'taxonomy' => $taxonomy,
                    'hide_empty' => true,
                    'object_ids' => $context['current_products']
                ));
                
                if (!empty($terms) && !is_wp_error($terms)) {
                    $attribute_has_terms = false;
                    $attribute_html = '<div class="attribute-group">';
                    $attribute_html .= '<h5>' . $attribute->attribute_label . '</h5>';
                    
                    foreach ($terms as $term) {
                        // Calculate accurate product count for this attribute term in current context
                        $product_count = $this->get_term_product_count($term->term_id, $taxonomy, $context['current_products']);
                        
                        if ($product_count > 0) { // Only show attribute values that have products
                            $attribute_has_terms = true;
                            $attribute_html .= '<label>';
                            $attribute_html .= '<input type="checkbox" name="attributes[' . $taxonomy . '][]" value="' . $term->term_id . '">';
                            $attribute_html .= ' ' . $term->name . ' (' . $product_count . ')';
                            $attribute_html .= '</label>';
                        }
                    }
                    
                    $attribute_html .= '</div>';
                    
                    // Only add this attribute group if it has at least one term with products
                    if ($attribute_has_terms) {
                        $has_attributes = true;
                        $attributes_html .= $attribute_html;
                    }
                }
            }
            
            // Only show the entire Attributes section if there's at least one attribute with terms that have products
            if ($has_attributes) {
                echo '<div class="filter-group">';
                echo '<h4>Atributos</h4>';
                echo $attributes_html;
                echo '</div>';
            }
        }
    }
    
    private function get_price_range($context = null) {
        global $wpdb;
        
        if (!$context || empty($context['current_products'])) {
            // No context or products, return invalid range to hide price filter
            return array('min' => 0, 'max' => 0);
        }
        
        $product_ids = implode(',', array_map('intval', $context['current_products']));
        
        // Use direct SQL with proper escaping for IN clause
        $sql = "
            SELECT MIN(CAST(meta_value AS DECIMAL(10,2))) as min_price, 
                   MAX(CAST(meta_value AS DECIMAL(10,2))) as max_price 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_price' 
            AND meta_value != '' 
            AND meta_value > 0 
            AND post_id IN ({$product_ids})
        ";
        
        $min_max = $wpdb->get_row($sql);
        
        // If no valid prices found, return invalid range to hide filter
        if (!$min_max || !$min_max->min_price || !$min_max->max_price) {
            return array('min' => 0, 'max' => 0);
        }
        
        $min_price = floor(floatval($min_max->min_price));
        $max_price = ceil(floatval($min_max->max_price));
        
        // Ensure we have a valid range (min must be less than max)
        if ($min_price >= $max_price) {
            return array('min' => 0, 'max' => 0);
        }
        
        return array(
            'min' => $min_price,
            'max' => $max_price
        );
    }
    
    public function ajax_filter_products() {
        error_log('Easy Filter WC: AJAX function called');
        error_log('Easy Filter WC: POST data received: ' . print_r($_POST, true));
        
        check_ajax_referer('easy_filter_nonce', 'nonce');
        
        $categories = isset($_POST['categories']) ? array_map('intval', $_POST['categories']) : array();
        $tags = isset($_POST['tags']) ? array_map('intval', $_POST['tags']) : array();
        $min_price = isset($_POST['min_price']) && $_POST['min_price'] !== '' ? floatval($_POST['min_price']) : 0;
        $max_price = isset($_POST['max_price']) && $_POST['max_price'] !== '' ? floatval($_POST['max_price']) : 0;
        $attributes = isset($_POST['attributes']) ? $_POST['attributes'] : array();
        $current_category = isset($_POST['current_category']) ? intval($_POST['current_category']) : 0;
        $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;
        
        error_log('Easy Filter WC: Processing multiple filters - Categories: ' . implode(',', $categories) . ' | Tags: ' . implode(',', $tags) . ' | Attributes: ' . print_r($attributes, true));
        
        // Build comprehensive query args with AND logic
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 12,
            'paged' => $paged,
            'meta_query' => array('relation' => 'AND'),
            'tax_query' => array('relation' => 'AND')
        );
        
        // Price filter (AND logic - products must be within this range)
        if ($min_price > 0 || $max_price > 0) {
            $price_meta_query = array(
                'key' => '_price',
                'type' => 'DECIMAL(10,2)',
                'compare' => 'BETWEEN'
            );
            
            if ($min_price > 0 && $max_price > 0) {
                $price_meta_query['value'] = array($min_price, $max_price);
            } elseif ($min_price > 0) {
                $price_meta_query['value'] = $min_price;
                $price_meta_query['compare'] = '>=';
            } elseif ($max_price > 0) {
                $price_meta_query['value'] = $max_price;
                $price_meta_query['compare'] = '<=';
            }
            
            $args['meta_query'][] = $price_meta_query;
            error_log('Easy Filter WC: Added price filter - Min: ' . $min_price . ', Max: ' . $max_price);
        }
        
        // Category filter (AND logic - if multiple categories selected, product must be in ALL of them)
        if (!empty($categories)) {
            $args['tax_query'][] = array(
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $categories,
                'operator' => 'AND' // Changed from IN to AND for multiple selection
            );
            error_log('Easy Filter WC: Added category filter with AND logic for categories: ' . implode(',', $categories));
        } elseif ($current_category > 0) {
            // If no specific categories selected but we're in a category context, maintain that
            $args['tax_query'][] = array(
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $current_category,
                'operator' => 'IN'
            );
            error_log('Easy Filter WC: Using current category context: ' . $current_category);
        }
        
        // Tag filter (AND logic - if multiple tags selected, product must have ALL of them)
        if (!empty($tags)) {
            $args['tax_query'][] = array(
                'taxonomy' => 'product_tag',
                'field' => 'term_id',
                'terms' => $tags,
                'operator' => 'AND' // Changed from IN to AND for multiple selection
            );
            error_log('Easy Filter WC: Added tag filter with AND logic for tags: ' . implode(',', $tags));
        }
        
        // Attribute filters (AND logic - product must have ALL selected attribute values)
        if (!empty($attributes)) {
            foreach ($attributes as $taxonomy => $terms) {
                if (!empty($terms)) {
                    $args['tax_query'][] = array(
                        'taxonomy' => $taxonomy,
                        'field' => 'term_id',
                        'terms' => array_map('intval', $terms),
                        'operator' => 'AND' // Changed from IN to AND for multiple selection
                    );
                    error_log('Easy Filter WC: Added attribute filter ' . $taxonomy . ' with AND logic for terms: ' . implode(',', $terms));
                }
            }
        }
        
        // Remove empty tax_query if no taxonomy filters
        if (count($args['tax_query']) <= 1) {
            unset($args['tax_query']);
        }
        
        // Remove empty meta_query if no meta filters
        if (count($args['meta_query']) <= 1) {
            unset($args['meta_query']);
        }
        
        error_log('Easy Filter WC: Final query args: ' . print_r($args, true));
        
        $query = new WP_Query($args);
        
        error_log('Easy Filter WC: Query executed. Found posts: ' . $query->found_posts . ' | Have posts: ' . ($query->have_posts() ? 'YES' : 'NO'));
        
        ob_start();
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                wc_get_template_part('content', 'product');
            }
            wp_reset_postdata();
        } else {
            echo '<p class="woocommerce-info">' . __('No products found matching your selection.', 'easy-filter-wc') . '</p>';
        }
        
        $products_html = ob_get_clean();
        
        // Generate result count HTML
        $result_count_html = '';
        if ($query->found_posts > 0) {
            $per_page = $args['posts_per_page'];
            $paged = max(1, $args['paged'] ?? 1);
            $total = $query->found_posts;
            $first = ($per_page * ($paged - 1)) + 1;
            $last = min($total, $per_page * $paged);
            
            if ($total <= $per_page || -1 === $per_page) {
                $result_count_html = sprintf(_n('Showing the single result', 'Showing all %d results', $total, 'easy-filter-wc'), $total);
            } else {
                $result_count_html = sprintf(_nx('Showing the single result', 'Showing %1$s–%2$s of %3$s results', $total, '%1$s = first, %2$s = last, %3$s = total', 'easy-filter-wc'), $first, $last, $total);
            }
        } else {
            $result_count_html = __('No products found', 'easy-filter-wc');
        }
        
        // Generate pagination if needed
        $pagination_html = '';
        if ($query->max_num_pages > 1) {
            $pagination_args = array(
                'base' => '%_%',
                'format' => '?paged=%#%',
                'current' => $paged,
                'total' => $query->max_num_pages,
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'type' => 'plain'
            );
            $pagination_html = '<nav class="woocommerce-pagination">' . paginate_links($pagination_args) . '</nav>';
        }
        
        // Add detailed debugging
        global $wpdb;
        $last_query = $wpdb->last_query;
        
        wp_send_json_success(array(
            'products' => $products_html,
            'pagination' => $pagination_html,
            'found_posts' => $query->found_posts,
            'result_count_html' => $result_count_html,
            'debug' => array(
                'wp_query_args' => $args,
                'sql_query' => $last_query,
                'received_data' => array(
                    'categories' => $categories,
                    'tags' => $tags,
                    'min_price' => $min_price,
                    'max_price' => $max_price,
                    'attributes' => $attributes,
                    'current_category' => $current_category,
                    'paged' => $paged
                ),
                'query_results' => array(
                    'found_posts' => $query->found_posts,
                    'has_posts' => $query->have_posts(),
                    'post_count' => $query->post_count,
                    'max_num_pages' => $query->max_num_pages
                ),
                'filter_logic' => array(
                    'using_and_logic' => true,
                    'categories_count' => count($categories),
                    'tags_count' => count($tags),
                    'attributes_count' => count($attributes),
                    'has_price_filter' => ($min_price > 0 || $max_price > 0)
                )
            )
        ));
    }
    
    public function ajax_test() {
        wp_send_json_success(array(
            'message' => 'AJAX Test Successful!',
            'timestamp' => current_time('mysql'),
            'is_woocommerce_active' => class_exists('WooCommerce'),
            'total_products' => wp_count_posts('product')->publish
        ));
    }
    
    public function products_shortcode($atts) {
        $atts = shortcode_atts(array(
            'columns' => 4,
            'per_page' => 12,
            'category' => '',
            'tag' => '',
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'show_pagination' => 'yes',
            'show_sorting' => 'yes',
            'show_result_count' => 'yes'
        ), $atts, 'easy_products');
        
        ob_start();
        $this->render_products_listing($atts);
        return ob_get_clean();
    }
    
    private function render_products_listing($atts) {
        global $woocommerce_loop;
        
        // Set up pagination
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
        
        // Get current category if we're on a category page
        $current_category = '';
        if (is_product_category()) {
            $current_category = get_queried_object()->slug;
        } elseif (!empty($atts['category'])) {
            $current_category = $atts['category'];
        }
        
        // Set up query args
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['per_page']),
            'paged' => $paged,
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_stock_status',
                    'value' => 'outofstock',
                    'compare' => '!='
                )
            )
        );
        
        // Add category filter
        if (!empty($current_category)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => $current_category
                )
            );
        }
        
        // Add tag filter
        if (!empty($atts['tag'])) {
            if (!isset($args['tax_query'])) {
                $args['tax_query'] = array('relation' => 'AND');
            }
            $args['tax_query'][] = array(
                'taxonomy' => 'product_tag',
                'field' => 'slug',
                'terms' => $atts['tag']
            );
        }
        
        // Handle sorting from URL parameter
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : $atts['orderby'];
        switch ($orderby) {
            case 'price':
                $args['orderby'] = 'meta_value_num';
                $args['meta_key'] = '_price';
                $args['order'] = 'ASC';
                break;
            case 'price-desc':
                $args['orderby'] = 'meta_value_num';
                $args['meta_key'] = '_price';
                $args['order'] = 'DESC';
                break;
            case 'popularity':
                $args['orderby'] = 'meta_value_num';
                $args['meta_key'] = 'total_sales';
                $args['order'] = 'DESC';
                break;
            case 'rating':
                $args['orderby'] = 'meta_value_num';
                $args['meta_key'] = '_wc_average_rating';
                $args['order'] = 'DESC';
                break;
            case 'date':
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
                break;
        }
        
        $products = new WP_Query($args);
        
        // Set loop properties
        $woocommerce_loop['columns'] = intval($atts['columns']);
        $woocommerce_loop['is_shortcode'] = true;
        
        echo '<div id="easy-products-container" class="easy-products-wrapper">';
        
        if ($atts['show_result_count'] === 'yes') {
            $this->render_result_count($products, $atts);
        }
        
        if ($atts['show_sorting'] === 'yes') {
            $this->render_sorting_dropdown();
        }
        
        if ($products->have_posts()) {
            echo '<ul class="products columns-' . esc_attr($atts['columns']) . '">';
            
            while ($products->have_posts()) {
                $products->the_post();
                wc_get_template_part('content', 'product');
            }
            
            echo '</ul>';
            
            if ($atts['show_pagination'] === 'yes') {
                $this->render_pagination($products);
            }
            
        } else {
            echo '<p class="woocommerce-info">' . __('No products found.', 'easy-filter-wc') . '</p>';
        }
        
        echo '</div>';
        
        wp_reset_postdata();
    }
    
    private function render_result_count($products, $atts) {
        $per_page = intval($atts['per_page']);
        $paged = max(1, get_query_var('paged'));
        $total = $products->found_posts;
        $first = ($per_page * ($paged - 1)) + 1;
        $last = min($total, $per_page * $paged);
        
        echo '<p class="woocommerce-result-count">';
        if ($total <= $per_page || -1 === $per_page) {
            printf(_n('Showing the single result', 'Showing all %d results', $total, 'easy-filter-wc'), $total);
        } else {
            printf(_nx('Showing the single result', 'Showing %1$s–%2$s of %3$s results', $total, '%1$s = first, %2$s = last, %3$s = total', 'easy-filter-wc'), $first, $last, $total);
        }
        echo '</p>';
    }
    
    private function render_sorting_dropdown() {
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : '';
        $current_url = remove_query_arg('orderby');
        
        echo '<form class="woocommerce-ordering" method="get">';
        echo '<select name="orderby" class="orderby" aria-label="' . esc_attr__('Shop order', 'easy-filter-wc') . '">';
        
        $options = array(
            'menu_order' => __('Default sorting', 'easy-filter-wc'),
            'popularity' => __('Sort by popularity', 'easy-filter-wc'),
            'rating' => __('Sort by average rating', 'easy-filter-wc'),
            'date' => __('Sort by latest', 'easy-filter-wc'),
            'price' => __('Sort by price: low to high', 'easy-filter-wc'),
            'price-desc' => __('Sort by price: high to low', 'easy-filter-wc'),
        );
        
        foreach ($options as $key => $label) {
            echo '<option value="' . esc_attr($key) . '"' . selected($orderby, $key, false) . '>' . esc_html($label) . '</option>';
        }
        
        echo '</select>';
        
        // Preserve other query parameters
        foreach ($_GET as $key => $value) {
            if ($key !== 'orderby' && $key !== 'submit') {
                echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
            }
        }
        
        echo '</form>';
        
        // Auto-submit on change
        echo '<script>
            jQuery(document).ready(function($) {
                $(".woocommerce-ordering select").on("change", function() {
                    $(this).closest("form").submit();
                });
            });
        </script>';
    }
    
    private function render_pagination($products) {
        $total_pages = $products->max_num_pages;
        $current_page = max(1, get_query_var('paged'));
        
        if ($total_pages <= 1) {
            return;
        }
        
        $pagination_args = array(
            'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
            'format' => '?paged=%#%',
            'current' => $current_page,
            'total' => $total_pages,
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
            'type' => 'plain',
            'end_size' => 3,
            'mid_size' => 3
        );
        
        echo '<nav class="woocommerce-pagination">';
        echo paginate_links($pagination_args);
        echo '</nav>';
    }
}