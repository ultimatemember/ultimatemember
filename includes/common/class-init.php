<?php
namespace um\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\common\Init' ) ) {

	/**
	 * Class Init
	 *
	 * @package um\common
	 */
	class Init {

		/**
		 * Create classes' instances where __construct isn't empty for hooks init
		 *
		 * @used-by \UM::includes()
		 */
		public function includes() {
			$this->cpt()->hooks();
			$this->screen();
			$this->secure()->hooks();
			$this->site_health();
		}

		/**
		 * @since 2.6.8
		 *
		 * @return CPT
		 */
		public function cpt() {
			if ( empty( UM()->classes['um\common\cpt'] ) ) {
				UM()->classes['um\common\cpt'] = new CPT();
			}
			return UM()->classes['um\common\cpt'];
		}

		/**
		 * @since 2.6.8
		 *
		 * @return Screen
		 */
		public function screen() {
			if ( empty( UM()->classes['um\common\screen'] ) ) {
				UM()->classes['um\common\screen'] = new Screen();
			}
			return UM()->classes['um\common\screen'];
		}

		/**
		 * @since 2.6.8
		 *
		 * @return Secure
		 */
		public function secure() {
			if ( empty( UM()->classes['um\common\secure'] ) ) {
				UM()->classes['um\common\secure'] = new Secure();
			}
			return UM()->classes['um\common\secure'];
		}

		/**
		 * @since 2.6.8
		 *
		 * @return Site_Health
		 */
		public function site_health() {
			if ( empty( UM()->classes['um\common\site_health'] ) ) {
				UM()->classes['um\common\site_health'] = new Site_Health();
			}
			return UM()->classes['um\common\site_health'];
		}
	}
}
