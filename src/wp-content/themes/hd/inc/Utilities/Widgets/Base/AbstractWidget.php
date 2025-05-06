<?php

namespace HD\Utilities\Widgets\Base;

\defined( 'ABSPATH' ) || die;

/**
 * Abstract Widget
 *
 * @package Gaudev
 */
abstract class AbstractWidget extends \WP_Widget {
	protected string $prefix = 'w-';
	protected string $widget_id;
	protected string $widget_classname;
	protected string $widget_name = 'Unknown Widget';
	protected string $widget_description = '';
	protected string $widget_mime_type = '';

	protected array $settings;

	/**
	 * Whether the widget hasn't been registered yet.
	 *
	 * @var bool
	 */
	protected bool $registered = false;

	// --------------------------------------------------

	/**
	 * Constructor.
	 */
	public function __construct() {
		$className              = ( new \ReflectionClass( $this ) )->getShortName();
		$this->widget_classname = str_replace( [ '_widget', '-widget', ], '', \HD_Helper::dashCase( strtolower( $className ) ) );
		$this->widget_id        = $this->prefix . $this->widget_classname;

		parent::__construct( $this->widget_id, $this->widget_name, $this->widget_options(), $this->control_options() );

		add_action( 'save_post', [ $this, 'flush_widget_cache' ] );
		add_action( 'deleted_post', [ $this, 'flush_widget_cache' ] );
		add_action( 'switch_theme', [ $this, 'flush_widget_cache' ] );
	}

	// --------------------------------------------------

	/**
	 * @return array
	 */
	protected function widget_options(): array {
		return [
			'classname'                   => $this->widget_classname,
			'description'                 => $this->widget_description,
			'customize_selective_refresh' => true,
			'show_instance_in_rest'       => true,
			'mime_type'                   => $this->widget_mime_type,
		];
	}

	// --------------------------------------------------

	/**
	 * @return array
	 */
	protected function control_options(): array {
		return [];
	}

	// --------------------------------------------------

	/**
	 * Flush the cache
	 *
	 * @return void
	 */
	public function flush_widget_cache(): void {
		foreach ( [ 'https', 'http' ] as $scheme ) {
			wp_cache_delete( $this->get_widget_id_for_cache( $this->widget_id, $scheme ), 'widget' );
		}
	}

	// --------------------------------------------------

	/**
	 * @param        $widget_id
	 * @param string $scheme
	 *
	 * @return string
	 */
	protected function get_widget_id_for_cache( $widget_id, string $scheme = '' ): string {
		if ( $scheme ) {
			$widget_id_for_cache = $widget_id . '-' . $scheme;
		} else {
			$widget_id_for_cache = $widget_id . '-' . ( is_ssl() ? 'https' : 'http' );
		}

		return $widget_id_for_cache;
	}

	// --------------------------------------------------

	/**
	 * @param array $args
	 * @param string $content
	 * @param int $expiration
	 *
	 * @return string
	 */
	public function cache_widget( array $args, string $content, int $expiration = 3600 ): string {

		// Don't set any cache if widget_id doesn't exist
		if ( empty( $args['widget_id'] ) ) {
			return $content;
		}

		$cache_key = $this->get_widget_id_for_cache( $this->widget_id );
		$cache     = wp_cache_get( $cache_key, 'widget' );
		if ( ! is_array( $cache ) ) {
			$cache = [];
		}

		$cache[ $this->get_widget_id_for_cache( $args['widget_id'] ) ] = $content;
		wp_cache_set( $cache_key, $cache, 'widget', $expiration );

		return $content;
	}

	// --------------------------------------------------

	/**
	 * Get cached widget
	 *
	 * @param array $args Arguments
	 *
	 * @return bool true if the widget is cached otherwise false
	 */
	public function get_cached_widget( array $args ): bool {
		// Don't get cache if widget_id doesn't exists
		if ( empty( $args['widget_id'] ) ) {
			return false;
		}

		$cache = wp_cache_get( $this->get_widget_id_for_cache( $this->widget_id ), 'widget' );
		if ( ! is_array( $cache ) ) {
			$cache = [];
		}

		if ( isset( $cache[ $this->get_widget_id_for_cache( $args['widget_id'] ) ] ) ) {
			echo $cache[ $this->get_widget_id_for_cache( $args['widget_id'] ) ];

			return true;
		}

		return false;
	}

	// --------------------------------------------------

	/**
	 * @param array $instance Array of instance options.
	 *
	 * @return string
	 */
	protected function get_instance_title( array $instance ): string {
		return $instance['title'] ?? $this->settings['title']['std'] ?? '';
	}

	// --------------------------------------------------

	/**
	 * @param int $number
	 */
	public function _register_one( $number = - 1 ): void {
		parent::_register_one( $number );

		if ( $this->registered ) {
			return;
		}

		$this->registered = true;

		if ( is_active_widget( false, false, $this->id_base, true ) ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'styles_and_scripts' ], 29 );
		}
	}

	// --------------------------------------------------

	/**
	 * @return void
	 */
	public function styles_and_scripts(): void {}

	// --------------------------------------------------

	/**
	 * @param $id
	 *
	 * @return object|bool
	 */
	protected function acfFields( $id ): object|bool {
		return \HD_Helper::getFields( $id, true );
	}

	// --------------------------------------------------

	/**
	 * @param $new_instance
	 * @param $old_instance
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ): array {
		$instance = $old_instance;

		if ( empty( $this->settings ) ) {
			return $instance;
		}

		// Loop settings and get values to save
		foreach ( $this->settings as $key => $setting ) {
			$setting_type = $setting['type'] ?? '';

			if ( ! $setting_type ) {
				continue;
			}

			// Format the value based on a settings type.
			switch ( $setting_type ) {
				case 'number':
					$instance[ $key ] = absint( $new_instance[ $key ] );
					if ( isset( $setting['min'] ) && '' !== $setting['min'] ) {
						$instance[ $key ] = max( $instance[ $key ], $setting['min'] );
					}
					if ( isset( $setting['max'] ) && '' !== $setting['max'] ) {
						$instance[ $key ] = min( $instance[ $key ], $setting['max'] );
					}
					break;

				case 'textarea':
					$instance[ $key ] = wp_kses( trim( wp_unslash( $new_instance[ $key ] ) ), wp_kses_allowed_html( 'post' ) );
					break;

				case 'checkbox':
					$instance[ $key ] = empty( $new_instance[ $key ] ) ? 0 : 1;
					break;

				default:
					$instance[ $key ] = isset( $new_instance[ $key ] ) ? sanitize_text_field( $new_instance[ $key ] ) : $setting['std'];
					break;
			}

			// Sanitize the value of a setting.
			$instance[ $key ] = apply_filters( 'hd_widget_settings_sanitize_filter', $instance[ $key ], $new_instance, $key, $setting );
		}

		$this->flush_widget_cache();

		return $instance;
	}

	// --------------------------------------------------

	/**
	 * @param $instance
	 *
	 * @return void
	 */
	public function form( $instance ): void {
		if ( empty( $this->settings ) ) {
			return;
		}

		foreach ( $this->settings as $key => $setting ) {

			$class = $setting['class'] ?? '';
			$value = $instance[ $key ] ?? $setting['std'];

			switch ( $setting['type'] ) {
				case 'number':
					$this->render_number_input( $key, $setting, $value, $class );
					break;

				case 'textarea':
					$this->render_textarea( $key, $setting, $value, $class );
					break;

				case 'checkbox':
					$this->render_checkbox( $key, $setting, $value, $class );
					break;

				case 'text':
					$this->render_text_input( $key, $setting, $value, $class );
					break;

				case 'select':
					$this->render_select( $key, $setting, $value, $class );
					break;

				case 'select_multi':
					$this->render_select( $key, $setting, $value, $class, true );
					break;

				// Default: run an action.
				default:
					do_action( 'hd_widget_field_' . $setting['type'], $key, $value, $setting, $instance );
					break;
			}
		}
	}

	// --------------------------------------------------

	/**
	 * Render Text Input Field
	 *
	 * @param string $key
	 * @param array $setting
	 * @param mixed $value
	 * @param string $class
	 */
	protected function render_text_input( string $key, array $setting, mixed $value, string $class ): void {
		echo '<p>';
		echo '<label for="' . \HD_Helper::escAttr( $this->get_field_id( $key ) ) . '">' . wp_kses_post( $setting['label'] ) . '</label>';
		echo '<input class="widefat ' . \HD_Helper::escAttr( $class ) . '"';
		echo ' id="' . \HD_Helper::escAttr( $this->get_field_id( $key ) ) . '"';
		echo ' name="' . \HD_Helper::escAttr( $this->get_field_name( $key ) ) . '" type="text"';
		echo ' value="' . \HD_Helper::escAttr( $value ) . '">';
		if ( isset( $setting['desc'] ) ) {
			echo '<small class="help-text">' . $setting['desc'] . '</small>';
		}
		echo '</p>';
	}

	// --------------------------------------------------

	/**
	 * Render Number Input Field
	 *
	 * @param string $key
	 * @param array $setting
	 * @param mixed $value
	 * @param string $class
	 */
	protected function render_number_input( string $key, array $setting, mixed $value, string $class ): void {
		echo '<p class="' . \HD_Helper::escAttr( $class ) . '">';
		echo '<label for="' . \HD_Helper::escAttr( $this->get_field_id( $key ) ) . '">' . wp_kses_post( $setting['label'] ) . '</label>';
		echo '<input class="widefat"';
		echo ' id="' . \HD_Helper::escAttr( $this->get_field_id( $key ) ) . '"';
		echo ' name="' . \HD_Helper::escAttr( $this->get_field_name( $key ) ) . '" type="number"';
		echo ' min="' . \HD_Helper::escAttr( $setting['min'] ) . '"';
		echo ' max="' . \HD_Helper::escAttr( $setting['max'] ) . '"';
		echo ' value="' . \HD_Helper::escAttr( $value ) . '"/>';
		if ( isset( $setting['desc'] ) ) {
			echo '<small class="help-text">' . $setting['desc'] . '</small>';
		}
		echo '</p>';
	}

	// --------------------------------------------------

	/**
	 * Render Select Dropdown
	 *
	 * @param string $key
	 * @param array $setting
	 * @param mixed $value
	 * @param string $class
	 * @param bool $multi
	 */
	protected function render_select( string $key, array $setting, mixed $value, string $class, bool $multi = false ): void {
		if ( $multi ) {
			$class .= ' select2 select2-multi';
		}

		echo '<p>';
		echo '<label for="' . \HD_Helper::escAttr( $this->get_field_id( $key ) ) . '">' . wp_kses_post( $setting['label'] ) . '</label>';
		echo '<select class="widefat ' . \HD_Helper::escAttr( $class ) . '"';

		if ( $multi ) {
			echo ' multiple';
		}

		echo ' id="' . \HD_Helper::escAttr( $this->get_field_id( $key ) ) . '"';
		echo ' name="' . \HD_Helper::escAttr( $this->get_field_name( $key ) ) . '">';

		foreach ( $setting['options'] as $option_key => $option_value ) {
			echo '<option value="' . \HD_Helper::escAttr( $option_key ) . '" ' . selected( $option_key, $value ) . '>';
			echo esc_html( $option_value );
			echo '</option>';
		}
		echo '</select>';
		if ( isset( $setting['desc'] ) ) {
			echo '<small class="help-text">' . $setting['desc'] . '</small>';
		}
		echo '</p>';
	}

	// --------------------------------------------------

	/**
	 * Render Textarea Field
	 *
	 * @param string $key
	 * @param array $setting
	 * @param mixed $value
	 * @param string $class
	 */
	protected function render_textarea( string $key, array $setting, mixed $value, string $class ): void {
		$rows = ! empty( $setting['rows'] ) ? (int) $setting['rows'] : 3;
		echo '<p>';
		echo '<label for="' . \HD_Helper::escAttr( $this->get_field_id( $key ) ) . '">' . wp_kses_post( $setting['label'] ) . '</label>';
		echo '<textarea class="widefat ' . \HD_Helper::escAttr( $class ) . '"';
		echo ' id="' . \HD_Helper::escAttr( $this->get_field_id( $key ) ) . '"';
		echo ' name="' . \HD_Helper::escAttr( $this->get_field_name( $key ) ) . '" cols="20"';
		echo ' rows="' . esc_attr( $rows ) . '">' . esc_textarea( $value ) . '</textarea>';
		if ( isset( $setting['desc'] ) ) {
			echo '<small class="help-text">' . $setting['desc'] . '</small>';
		}
		echo '</p>';
	}

	// --------------------------------------------------

	/**
	 * Render Checkbox Input
	 *
	 * @param string $key
	 * @param array $setting
	 * @param mixed $value
	 * @param string $class
	 */
	protected function render_checkbox( string $key, array $setting, mixed $value, string $class ): void {
		echo '<p>';
		echo '<label>';
		echo '<input class="checkbox ' . \HD_Helper::escAttr( $class ) . '"';
		echo ' id="' . \HD_Helper::escAttr( $this->get_field_id( $key ) ) . '"';
		echo ' name="' . \HD_Helper::escAttr( $this->get_field_name( $key ) ) . '"';
		echo ' type="checkbox"';
		echo ' value="1" ' . checked( $value, 1 ) . '>';
		echo '<span class="message">' . wp_kses_post( $setting['label'] ) . '</span>';
		echo '</label>';
		echo '</p>';
	}
}
