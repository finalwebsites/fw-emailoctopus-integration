<?php

/**
 * FW EmailOctopus Integration
 *
 * @package  FWEO_EmailOctopus_Action_After_Submit
 * @category Integration
 * @author   Olaf Lederer
 */

if ( ! defined( 'ABSPATH' ) ) exit;


use ElementorPro\Plugin;
use Elementor\Controls_Manager;
use Elementor\Settings;
use ElementorPro\Core\Utils;
use ElementorPro\Modules\Forms\Classes\Form_Record;
use ElementorPro\Modules\Forms\Classes\Integration_Base;

class FWEO_EmailOctopus_Action_After_Submit extends Integration_Base {

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
        return 'EmailOctopus';
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
                'label' => 'EmailOctopus',
                'condition' => [
                    'submit_actions' => $this->get_name(),
                ],
            ]
        );

        $handler = new FWEO_EmailOctopus_integration();
        $list_items = $handler->get_lists();

        $widget->add_control(
            'emailoctopus_list',
            [
                'label' => __( 'Mailing list', 'fw_emailoctopus_integration' ),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $list_items,
                'render_type' => 'none',
            ]
        );

        $emailoctopus_repeater = new \Elementor\Repeater();

        $emailoctopus_repeater->add_control(
            'list_options',
            [
                'label'   => __( 'Merge Fields', 'fw_emailoctopus_integration' ),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'options' => $this->get_emailoctopus_fields(),
            ]
        );

        $emailoctopus_repeater->add_control(
            'list_form_id',
            [
                'label'       => __( 'Field ID', 'fw_emailoctopus_integration' ),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'placeholder' => __( 'field ID', 'integrate-elementor-mailster' ),
            ]
        );

        $widget->add_control(
            'list',
            [
                'label'       => __( 'List Field', 'fw_emailoctopus_integration' ),
                'type'        => \Elementor\Controls_Manager::REPEATER,
                'fields'      => $emailoctopus_repeater->get_controls(),
                'default'     => [
                    [
                        'list_options' => 'email_address',
                        'list_form_id' => 'email',
                    ],
                ],
                'title_field' => '{{{ list_options }}}',
                'condition' => [
                    'emailoctopus_list!' => '',
                ],
            ]
        );


        $widget->add_control(
            'emailoctopus_tags',
            [
                'label' => esc_html__( 'Tags', 'fw_emailoctopus_integration' ),
                'description' => esc_html__( 'Add comma separated tags', 'fw_emailoctopus_integration' ),
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

    public function get_emailoctopus_fields() {

        $options_array = [
            'email_address'     => __( 'Email address', 'fw_emailoctopus_integration' ),
            'FirstName' => __( 'First Name', 'fw_emailoctopus_integration' ),
            'LastName'  => __( 'Last Name', 'fw_emailoctopus_integration' ),
            'Source'  => __( 'Source', 'fw_emailoctopus_integration' ),
            'Newsletter'  => __( 'Newsletter', 'fw_emailoctopus_integration' ),
            'Website'  => __( 'Website', 'fw_emailoctopus_integration' ),
            'PhoneNumber'  => __( 'Phone number', 'fw_emailoctopus_integration' ),
        ];
        return $options_array;
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
        $subscriber = $this->map_fields( $record );


        if ( ! empty( $settings['emailoctopus_tags'] ) ) {
            $tags = explode( ',', trim( $settings['emailoctopus_tags'] ) );
            if (!empty($subscriber['tags'])) {
                array_unshift($tags, $subscriber['tags']);
            } 
            $subscriber['tags'] = implode(',', $tags);
        }

        $list = $settings['emailoctopus_list'];
        $subscriber = $this->set_subscriber_data( $list, $subscriber['email_address'], $subscriber );


    }


    private function set_subscriber_data( $list, $email, $data ) {
        $handler = new FWEO_EmailOctopus_integration();

        $response = $handler->add_subscriber($email, $list, $data, true);

        if (isset($response['error']['code'])) {
            $error = ! empty( $response['error']['message'] ) ? $response['error']['message'] : '';
            $code = $response['error']['code'];

            throw new \Exception( esc_html ( "HTTP {$code} - {$error}" ) );
        }

    }

    /**
     * @param Form_Record $record
     * 
     *
     * @return array
     */
    private function map_fields( $record ) {
        $subscriber = [];
        $fields = $record->get( 'fields' );
        $settings = $record->get( 'form_settings' );

        $eo_fields = $this->get_emailoctopus_fields();

        $handler = new FWEO_EmailOctopus_integration();

        $existing_fields = $handler->get_list_fields($settings['emailoctopus_list']);

        foreach ( $settings['list'] as $list_field ) {
            $eo_field = $list_field['list_options'];
            $field_id = $list_field['list_form_id'];
            $field_val = $fields[$field_id]['value'];
            if ($eo_field == 'Newsletter') {
                if ($field_val != '') $subscriber['tags'] = 'newsletter';
            } elseif ($eo_field == 'email_address') {
                $subscriber['email_address'] = $field_val;
            } else {
                if (!in_array($eo_field, $existing_fields)) {
                    $data = array(
                        'label' => $eo_field,
                        'tag' => $eo_field,
                        'type' => 'TEXT'
                    );
                    $handler->create_list_field($data, $settings['emailoctopus_list']);
                }
                $subscriber[$eo_field] = $field_val;
            }
        }

        return $subscriber;
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
    $emailoctopus_action = new FWEO_EmailOctopus_Action_After_Submit();

// Register the action with form widget
    \ElementorPro\Plugin::instance()->modules_manager->get_modules( 'forms' )->add_form_action( $emailoctopus_action->get_name(), $emailoctopus_action );
});