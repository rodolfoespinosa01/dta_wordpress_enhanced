<?php

namespace Hostinger\EasyOnboarding\Admin;

use Hostinger\EasyOnboarding\Admin\Onboarding\Onboarding;

defined( 'ABSPATH' ) || exit;

class Actions {
	public const LOGO_UPLOAD         = 'add_logo';
	public const IMAGE_UPLOAD        = 'image_upload';
	public const EDIT_DESCRIPTION    = 'edit_description';
	public const EDIT_SITE_TITLE     = 'edit_site_title';
	public const ADD_POST            = 'add_post';
	public const ADD_PAGE            = 'add_page';
	public const SETUP_STORE         = 'setup_store';

	public const ADD_PRODUCT         = 'add_product';

	public const ADD_PAYMENT         = 'add_payment_method';

	public const ADD_SHIPPING         = 'add_shipping_method';

	public const ADD_MARKETING         = 'add_marketing';
	public const DOMAIN_IS_CONNECTED = 'connect_domain';

	public const GOOGLE_KIT = 'google_kit';
	public const ACTIONS_LIST        = array(
		self::LOGO_UPLOAD,
		self::IMAGE_UPLOAD,
		self::EDIT_DESCRIPTION,
		self::EDIT_SITE_TITLE,
		self::ADD_POST,
		self::ADD_PAGE,
		self::DOMAIN_IS_CONNECTED,
	);

    public const STORE_ACTIONS_LIST        = array(
        self::SETUP_STORE,
        self::ADD_PRODUCT,
        self::ADD_PAYMENT,
        self::ADD_SHIPPING,
        self::ADD_MARKETING,
    );

    public static function get_category_action_lists(): array {
        return array(
            Onboarding::HOSTINGER_EASY_ONBOARDING_WEBSITE_STEP_CATEGORY_ID => self::get_action_list(),
            Onboarding::HOSTINGER_EASY_ONBOARDING_STORE_STEP_CATEGORY_ID => self::get_store_action_list()
        );
    }

    public static function get_action_list(): array {
        $list = self::ACTIONS_LIST;

        if( ! is_plugin_active( 'woocommerce/woocommerce.php' ) && is_plugin_active( 'google-site-kit/google-site-kit.php' ) ) {
            $list[] = self::GOOGLE_KIT;
        }

        return $list;
    }

    public static function get_store_action_list(): array {
        $list = self::STORE_ACTIONS_LIST;

        if( is_plugin_active( 'woocommerce/woocommerce.php' ) && is_plugin_active( 'google-site-kit/google-site-kit.php' ) ) {
            $list[] = self::GOOGLE_KIT;
        }

        return $list;
    }
}
