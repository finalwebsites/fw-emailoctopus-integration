<?php


use ElementorPro\Plugin;
use Elementor\Controls_Manager;
use Elementor\Settings;
use ElementorPro\Core\Utils;
use ElementorPro\Modules\Forms\Classes\Form_Record;
use ElementorPro\Modules\Forms\Classes\Integration_Base;

class EmailOctopus_Action_After_Submit extends Integration_Base {


    const OPTION_NAME_API_KEY = 'pro_emailoctopus_api_key';

    /**
     * @var string - Mailchimp API key.
     */
    private $api_key;

    private function get_global_api_key() {
        return get_option( 'elementor_' . self::OPTION_NAME_API_KEY );
    }

    /**
     * Get Name
     *
     * Return the action name
     *
     * @access public
     * @return string
     */
    public function get_name() {
        return 'emailoctopus';
    }

    /**
     * Get Label
     *
     * Returns the action label
     *
     * @access public
     * @return string
     */
    public function get_label() {
        return __( 'EmailOctopus', 'text-domain' );
    }

    
    /**
     * Register Settings Section
     *
     * Registers the Action controls
     *
     * @access public
     * @param \Elementor\Widget_Base $widget
     */
    public function register_settings_section( $widget ) {

        $widget->start_controls_section(
            'section_emailoctopus',
            [
                'label' => __( 'EmailOctopus', 'text-domain' ),
                'condition' => [
                    'submit_actions' => $this->get_name(),
                ],
            ]
        );

        $handler = new EmailOctopus_subscriptions();
        $list_items = $handler->get_lists();

        $widget->add_control(
            'emailoctopus_list',
            [
                'label' => __( 'Mailing list', 'text-domain' ),
                'type' => Controls_Manager::SELECT,
                'options' => $list_items,
                'render_type' => 'none',
            ]
        );

        $settings = $this->get_settings_for_display(); 

        print_r($settings);

        $emailoctopus_repeater = new \Elementor\Repeater();

        $customfields      = $handler->get_list_fields($list_items[0]['id']);

        $customfield_array = [];

        foreach ( $customfields as $id => $data ) {
            $customfield_array[ $id ] = $data['name'];
        }

        $defaultfields_array = [
            'email'     => __( 'Email', 'integrate-elementor-mailster' ),
            'name' => __( 'Name', 'integrate-elementor-mailster' ),
            'lastname'  => __( 'Last Name', 'integrate-elementor-mailster' ),
        ];

        // Create options array for repeater field.
        $options_array = array_merge( $defaultfields_array, $customfield_array );

        $emailoctopus_repeater->add_control(
            'list_options',
            [
                'label'   => __( 'Custom Fields', 'integrate-elementor-mailster' ),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'options' => $options_array,
            ]
        );

        $emailoctopus_repeater->add_control(
            'list_form_id',
            [
                'label'       => __( 'Field ID', 'integrate-elementor-mailster' ),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'placeholder' => __( 'field ID', 'integrate-elementor-mailster' ),
            ]
        );

        $widget->add_control(
            'list',
            [
                'label'       => __( 'Custom Fields', 'integrate-elementor-mailster' ),
                'type'        => \Elementor\Controls_Manager::REPEATER,
                'fields'      => $emailoctopus_repeater->get_controls(),
                'default'     => [
                    [
                        'list_options' => 'email',
                        'list_form_id' => 'email',
                    ],
                ],
                'title_field' => '{{{ list_options }}}',
            ]
        );


        $widget->add_control(
            'emailoctopus_tags',
            [
                'label' => esc_html__( 'Tags', 'elementor-pro' ),
                'description' => esc_html__( 'Add comma separated tags', 'elementor-pro' ),
                'type' => Controls_Manager::TEXT,
                'render_type' => 'none',
                'condition' => [
                    'emailoctopus_list!' => '',
                ],
            ]
        );

        $this->register_fields_map_control( $widget );

        $widget->end_controls_section();
    }

    /**
     * On Export
     *
     * Clears form settings on export
     * @access Public
     * @param array $element
     */
    public function on_export( $element ) {
        unset(
            $element['settings']['emailoctopus_api_key_source'],
            $element['settings']['emailoctopus_custom_api_key'],
            $element['emailoctopus_list'],
            $element['emailoctopus_fields_map']
        );
    }

    /**
     * Run
     *
     * Runs the action after submit
     *
     * @access public
     * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record
     * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler
     */
    public function run( $record, $ajax_handler ) {
        $settings = $record->get( 'form_settings' );

        if ( 'default' === $form_settings['emailoctopus_api_key_source'] ) {
            $api_key = $this->get_global_api_key();
        } else {
            $api_key = $form_settings['emailoctopus_custom_api_key'];
        }
        
        // Data from the form in the frontend.
        $subscriber_data = $this->map_fields( $record );

        // Create or update a subscriber.
        $subscriber = $this->create_or_update_subscriber( $subscriber_data, $form_settings );

        /*
        $emailoctopus_data = $this->get_emailoctopus_custom_fields( $record );
        $email = $this->get_mapped_field( $record, 'EmailAddress' );

        $eo_object = new EmailOctopus_subscriptions($api_key);
        $eo_object->add_subscriber($email, $settings['emailoctopus_list'], $emailoctopus_data);
        */
    }

    private function normalize_type( $type ) {
        static $types = [
            'TEXT' => 'text',
            'NUMBER' => 'number',
            'DATE' => 'text'
        ];

        return $types[ $type ];
    }

    /**
     * Create or update a EmailOctopus subscriber.
     *
     * @param array $subscriber - Subscriber data from the form in the frontend.
     * @param array $form_settings - Settings from the editor.
     *
     * @return array - An array that contains the newly created subscriber's data.
     */
    private function create_or_update_subscriber( array $subscriber, array $form_settings ) {

        if ( ! empty( $form_settings['mailchimp_tags'] ) ) {
            $subscriber['tags'] = explode( ',', trim( $form_settings['emailoctopus_tags'] ) );
        }

        $list = $form_settings['emailoctopus_list'];
        //$email_hash = md5( strtolower( $subscriber['email_address'] ) );

        $subscriber['status_if_new'] = 'subscribed';
        $subscriber['status'] = 'subscribed';


        return $this->set_subscriber_data( $list, $subscriber['email_address'], $subscriber );
    }

    private function set_subscriber_data( $list, $email, $data ) {
        $handler = new EmailOctopus_subscriptions($this->api_key );

        $response = $handler->add_subscriber($email, $settings['emailoctopus_list'], $emailoctopus_data, true);

        if ( 200 !== $response['error']['code'] ) {
            $error = ! empty( $response['error']['message'] ) ? $response['error']['message'] : '';
            $code = $response['error']['code'];

            throw new \Exception( "HTTP {$code} - {$error}" );
        }

        return $response;
    }

    /**
     * @param Form_Record $record
     *
     * @return array
     */
    private function map_fields( $record ) {
        $subscriber = [];
        $fields = $record->get( 'fields' );

        // Other form has a field mapping
        foreach ( $record->get_form_settings( 'emailoctopus_fields_map' ) as $map_item ) {
            if ( empty( $fields[ $map_item['local_id'] ]['value'] ) ) {
                continue;
            }

            $value = $fields[ $map_item['local_id'] ]['value'];
            if ( 'email' === $map_item['remote_id'] ) {
                $subscriber['email_address'] = $value;
            } else {
                $subscriber['merge_fields'][ $map_item['remote_id'] ] = $value;
            }
        }

        return $subscriber;
    }
    /*
    private function get_mapped_field( Form_Record $record, $field_id ) {
        $fields = $record->get( 'fields' );
        foreach ( $record->get_form_settings( 'emailoctopus_fields_map' ) as $map_item ) {
            if ( empty( $fields[ $map_item['local_id'] ]['value'] ) ) {
                continue;
            }

            if ( $field_id === $map_item['remote_id'] ) {
                return $fields[ $map_item['local_id'] ]['value'];
            }
        }

        return '';
    }

    public function get_list_and_fields( $list_id ) {

        $lists = [
            '' => esc_html__( 'Select...', 'elementor-pro' ),
        ];
        $handler = new EmailOctopus_subscriptions();
        $all_lists = $handler->get_lists();
        array_push($lists, $all_lists);

        $results = $handler->get_list_fields($list_id);
        $fields = array();

        if ( ! empty( $results ) ) {
            foreach ( $results as $field ) {
                $fields[] = [
                    'remote_label' => $field['label'],
                    'remote_type' => $this->normalize_type( $field['type'] ),
                    'remote_id' => $field['tag'],
                    'remote_required' => false,
                ];
            }
        }

        $return_array = [
            'lists' => $lists,
            'fields' => $fields
        ];

        return $return_array;
    }
    */

    public function handle_panel_request( array $data ) {

        if ( ! empty( $data['api_key'] ) && 'default' === $data['api_key'] ) {
            $api_key = $this->get_global_api_key();
        } elseif ( ! empty( $data['custom_api_key'] ) ) {
            $api_key = $data['custom_api_key'];
        }

        if ( empty( $api_key ) ) {
            throw new \Exception( '`API key` is required.', 400 );
        }

        $handler = new EmailOctopus_subscriptions( $api_key );

        switch ( $data['emailoctopus_action'] ) {
            case 'lists':
                return $handler->get_lists();

            default:
                return $handler->get_list_fields( $data['emailoctopus_list'] );

        }
        
    }

    public function register_admin_fields( Settings $settings ) {
        $settings->add_section( Settings::TAB_INTEGRATIONS, 'emailoctopus', [
            'callback' => function() {
                echo '<hr><h2>' . esc_html__( 'EmailOctopus', 'elementor-pro' ) . '</h2>';
            },
            'fields' => [
                self::OPTION_NAME_API_KEY => [
                    'label' => esc_html__( 'API Key', 'elementor-pro' ),
                    'field_args' => [
                        'type' => 'text',
                        'desc' => sprintf(
                            /* translators: 1: Link opening tag, 2: Link closing tag. */
                            esc_html__( 'To integrate with our forms you need an %1$sAPI Key%2$s.', 'elementor-pro' ),
                            '<a href="https://emailoctopus.com/api-documentation" target="_blank">',
                            '</a>'
                        ),
                    ],
                ],
                'validate_api_data' => [
                    'field_args' => [
                        'type' => 'raw_html',
                        'html' => sprintf( '<button data-action="%s" data-nonce="%s" class="button elementor-button-spinner" id="elementor_pro_emailoctopus_api_key_button">%s</button>', self::OPTION_NAME_API_KEY . '_validate', wp_create_nonce( self::OPTION_NAME_API_KEY ), esc_html__( 'Validate API Key', 'elementor-pro' ) ),
                    ],
                ],
            ],
        ] );
    }

    public function ajax_validate_api_key() {
        check_ajax_referer( self::OPTION_NAME_API_KEY, '_nonce' );
        if ( ! isset( $_POST['api_key'] ) ) {
            wp_send_json_error();
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
        }

        try {
            new EmailOctopus_subscriptions( $_POST['api_key'] ); // phpcs:ignore -- No need to sanitize to support special characters.
        } catch ( \Exception $exception ) {
            wp_send_json_error();
        }
        wp_send_json_success();
    }

    public function __construct() {
        if ( is_admin() ) {
            add_action( 'elementor/admin/after_create_settings/' . Settings::PAGE_ID, [ $this, 'register_admin_fields' ], 15 );
        }
        add_action( 'wp_ajax_' . self::OPTION_NAME_API_KEY . '_validate', [ $this, 'ajax_validate_api_key' ] );
    }


    protected function get_fields_map_control_options() {
        return [
            'condition' => [
                'emailoctopus_list!' => '',
            ],
        ];
    }


    
}
add_action( 'elementor_pro/init', function() {


// Instantiate the action class
    $emailoctopus_action = new EmailOctopus_Action_After_Submit();

// Register the action with form widget
    \ElementorPro\Plugin::instance()->modules_manager->get_modules( 'forms' )->add_form_action( $emailoctopus_action->get_name(), $emailoctopus_action );
});