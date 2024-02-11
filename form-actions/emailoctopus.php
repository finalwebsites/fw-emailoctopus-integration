<?php


use ElementorPro\Plugin;
use Elementor\Controls_Manager;
use Elementor\Settings;
use ElementorPro\Core\Utils;
use ElementorPro\Modules\Forms\Classes\Form_Record;
use ElementorPro\Modules\Forms\Classes\Integration_Base;

class EmailOctopus_Action_After_Submit extends Integration_Base {

    /**
     * @var string - Mailchimp API key.
     */
    private $api_key;

    private function get_global_api_key() {
        return get_option( 'fw_emailoctopus_api_key' );
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
        return __( 'EmailOctopus', 'fw_emailoctopus_subscribe' );
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
                'label' => __( 'EmailOctopus', 'fw_emailoctopus_subscribe' ),
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
                'label' => __( 'Mailing list', 'fw_emailoctopus_subscribe' ),
                'type' => Controls_Manager::SELECT,
                'options' => $list_items,
                'render_type' => 'none',
            ]
        );

        $emailoctopus_repeater = new \Elementor\Repeater();

        $options_array = [
            'email_address'     => __( 'Email address', 'fw_emailoctopus_subscribe' ),
            'FirstName' => __( 'First Name', 'fw_emailoctopus_subscribe' ),
            'LastName'  => __( 'Last Name', 'fw_emailoctopus_subscribe' ),
            'Source'  => __( 'Source', 'fw_emailoctopus_subscribe' ),
            'Newsletter'  => __( 'Newsletter', 'fw_emailoctopus_subscribe' ),
        ];


        $emailoctopus_repeater->add_control(
            'list_options',
            [
                'label'   => __( 'Merge Fields', 'fw_emailoctopus_subscribe' ),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'options' => $options_array,
            ]
        );

        $emailoctopus_repeater->add_control(
            'list_form_id',
            [
                'label'       => __( 'Field ID', 'fw_emailoctopus_subscribe' ),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'placeholder' => __( 'field ID', 'integrate-elementor-mailster' ),
            ]
        );

        $widget->add_control(
            'list',
            [
                'label'       => __( 'List Field', 'fw_emailoctopus_subscribe' ),
                'type'        => \Elementor\Controls_Manager::REPEATER,
                'fields'      => $emailoctopus_repeater->get_controls(),
                'default'     => [
                    [
                        'list_options' => 'email_address',
                        'list_form_id' => 'email',
                    ],
                ],
                'title_field' => __( 'Email address', 'fw_emailoctopus_subscribe' ),
                'condition' => [
                    'emailoctopus_list!' => '',
                ],
            ]
        );


        $widget->add_control(
            'emailoctopus_tags',
            [
                'label' => esc_html__( 'Tags', 'fw_emailoctopus_subscribe' ),
                'description' => esc_html__( 'Add comma separated tags', 'fw_emailoctopus_subscribe' ),
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
            $element['emailoctopus_list']
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


        
        // Data from the form in the frontend.
        $subscriber_data = $this->map_fields( $record );

        // Create or update a subscriber.
        $subscriber = $this->create_or_update_subscriber( $subscriber_data, $form_settings );

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

    public function handle_panel_request( array $data ) {

        $api_key = $this->get_global_api_key();
        
        $handler = new EmailOctopus_subscriptions( $api_key );

        switch ( $data['emailoctopus_action'] ) {
            case 'lists':
                return $handler->get_lists();

            default:
                return $handler->get_list_fields( $data['emailoctopus_list'] );

        }
        
    }

    public function __construct() {
        
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