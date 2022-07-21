<?php
namespace umm\forumwp\includes\cross_modules;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Real_Time_Notifications
 *
 * @package umm\forumwp\includes\cross_modules
 */
class Real_Time_Notifications {


	/**
	 * Real_Time_Notifications constructor.
	 */
	public function __construct() {
		// Real-time notifications
		add_filter( 'um_notifications_core_log_types', array( &$this, 'add_notifications_types' ), 9999, 1 );

		// don't create real-time notification when reply is restored from trash
		add_action( 'fmwp_before_restore_reply', array( &$this, 'remove_subscription_notice_on_restore' ), 10, 1 );
		add_action( 'fmwp_after_restore_reply', array( &$this, 'subscription_notice_restore' ), 10, 1 );

		add_action( 'save_post', array( &$this, 'mention_notification' ), 999998, 3 );
		add_action( 'save_post', array( &$this, 'subscription_notification' ), 999999, 3 );
	}


	/**
	 * @param array $logs
	 *
	 * @return array
	 */
	public function add_notifications_types( $logs ) {
		$logs['fmwp_mention'] = array(
			'title'        => __( 'User mention you in forum', 'ultimate-member' ),
			'account_desc' => __( 'When a member posts a topic or reply and mention you.', 'ultimate-member' ),
			'content'      => __( '<strong>{member}</strong> just mentioned you <a href="{post_url}" target="_blank">here</a>.', 'ultimate-member' ),
			'placeholders' => array( 'member', 'post_url' ),
			'icon'         => array(
				'class' => 'ion-ios-contact',
				'color' => '#00c9ae',
			),
		);

		if ( FMWP()->modules()->is_active( 'subscriptions' ) ) {
			$logs['fmwp_new_reply'] = array(
				'title'        => __( 'User leaves a reply to topic', 'ultimate-member' ),
				'account_desc' => __( 'When a member replies to one of forums or topics to which I\'m subscribed.', 'ultimate-member' ),
				'content'      => __( '<strong>{member}</strong> has <strong><a href="{post_url}" target="_blank">replied</a></strong> to a topic or forum on which you are subscribed.', 'ultimate-member' ),
				'placeholders' => array( 'member', 'post_url' ),
				'icon'         => array(
					'class' => 'fas fa-comments',
					'color' => '#67e264',
				),
			);

			$logs['fmwp_new_topic'] = array(
				'title'        => __( 'User creates a topic in forum', 'ultimate-member' ),
				'account_desc' => __( 'When a member creates a topic in one of forums to which I\'m subscribed.', 'ultimate-member' ),
				'content'      => __( '<strong>{member}</strong> has <strong>created a new <a href="{post_url}" target="_blank">topic</a></strong> in a forum on which you are subscribed.', 'ultimate-member' ),
				'placeholders' => array( 'member', 'post_url' ),
				'icon'         => array(
					'class' => 'fas fa-comments',
					'color' => '#67e264',
				),
			);
		}

		return $logs;
	}


	/**
	 *
	 */
	public function remove_subscription_notice_on_restore() {
		add_filter( 'fmwp_subscription_notice_disabled', array( &$this, 'disable_notice' ), 10 );
	}


	/**
	 *
	 */
	public function subscription_notice_restore() {
		remove_filter( 'fmwp_subscription_notice_disabled', array( &$this, 'disable_notice' ), 10 );
	}


	/**
	 * @return bool
	 */
	public function disable_notice() {
		return true;
	}


	/**
	 * @param int $post_ID
	 * @param \WP_Post $post
	 * @param bool $update
	 */
	public function mention_notification( $post_ID, $post, $update ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! in_array( $post->post_type, array( 'fmwp_forum', 'fmwp_topic', 'fmwp_reply' ) ) ) {
			return;
		}

		if ( $post->post_status == 'auto-draft' ) {
			return;
		}

		if ( $post->post_type == 'fmwp_topic' ) {
			$f_id = FMWP()->common()->topic()->get_forum_id( $post_ID );
			if ( empty( $f_id ) ) {
				return;
			}
		}

		$users = get_post_meta( $post_ID, 'fmwp_original_mentions', true );
		$users = ! empty( $users ) ? $users : array();
		$users = apply_filters( 'fmwp_notify_mentioned_users_list', $users, $post );

		$need_mention = array();

		if ( ! $update ) {
			if ( $post->post_status !== 'publish' ) {

				if ( empty( $users ) ) {
					return;
				}

				foreach ( $users as $user_id ) {
					if ( $user_id == $post->post_author ) {
						continue;
					}

					if ( $post->post_type == 'fmwp_reply' ) {
						if ( FMWP()->user()->can_view_reply( $user_id, $post_ID ) ) {
							continue;
						}
					} elseif ( $post->post_type == 'fmwp_topic' ) {
						if ( user_can( $user_id, 'manage_fmwp_topics_all' ) ) {
							if ( $post->post_status == 'pending' || $post->post_status == 'private' ) {
								continue;
							}
						}
					} elseif ( $post->post_type == 'fmwp_forum' ) {
						if ( user_can( $user_id, 'manage_fmwp_forums_all' ) ) {
							if ( $post->post_status == 'pending' || $post->post_status == 'private' ) {
								continue;
							}
						}
					}

					$need_mention[] = $user_id;
				}

				update_post_meta( $post_ID, 'fmwp_um_notifications_need_mention', $need_mention );
			}
		} else {
			if ( $post->post_status == 'publish' ) {
				$need_mention = get_post_meta( $post_ID, 'fmwp_um_notifications_need_mention', true );
				$need_mention = ! empty( $need_mention ) ? $need_mention : array();

				if ( ! empty( $need_mention ) ) {
					$users = array_unique( array_merge( $users, $need_mention ) );

					delete_post_meta( $post_ID, 'fmwp_um_notifications_need_mention' );

					$need_mention = array();
				}
			} else {

				if ( empty( $users ) ) {
					return;
				}

				foreach ( $users as $user_id ) {
					if ( $user_id == $post->post_author ) {
						continue;
					}

					if ( $post->post_type == 'fmwp_reply' ) {
						if ( FMWP()->user()->can_view_reply( $user_id, $post_ID ) ) {
							continue;
						}
					} elseif ( $post->post_type == 'fmwp_topic' ) {
						if ( user_can( $user_id, 'manage_fmwp_topics_all' ) ) {
							if ( $post->post_status == 'pending' || $post->post_status == 'private' ) {
								continue;
							}
						}
					} elseif ( $post->post_type == 'fmwp_forum' ) {
						if ( user_can( $user_id, 'manage_fmwp_forums_all' ) ) {
							if ( $post->post_status == 'pending' || $post->post_status == 'private' ) {
								continue;
							}
						}
					}

					$need_mention[] = $user_id;
				}

				update_post_meta( $post_ID, 'fmwp_um_notifications_need_mention', $need_mention );
			}
		}

		if ( empty( $users ) ) {
			return;
		}

		$mentioned_users = get_post_meta( $post_ID, 'fmwp_um_notifications_mentioned', true );
		$mentioned_users = ! empty( $mentioned_users ) ? $mentioned_users : array();

		foreach ( $users as $user_id ) {
			if ( $user_id == $post->post_author ) {
				continue;
			}

			if ( in_array( $user_id, $mentioned_users ) ) {
				continue;
			}

			$send = true;

			if ( $post->post_type == 'fmwp_reply' ) {

				$send = FMWP()->user()->can_view_reply( $user_id, $post_ID );

			} elseif ( $post->post_type == 'fmwp_topic' ) {
				$topic_id = $post_ID;

				$forum_id = FMWP()->common()->topic()->get_forum_id( $topic_id );
				$forum = get_post( $forum_id );

				if ( $forum->post_status !== 'publish' ) {
					$send = false;
					if ( user_can( $user_id, 'manage_fmwp_forums_all' ) ) {
						if ( $forum->post_status == 'pending' || $forum->post_status == 'private' ) {
							$send = true;
						}
					}
				}

				if ( $send ) {
					if ( $post->post_status !== 'publish' ) {
						$send = false;
						if ( user_can( $user_id, 'manage_fmwp_topics_all' ) ) {
							if ( $post->post_status == 'pending' || $post->post_status == 'private' ) {
								$send = true;
							}
						}
					}
				}
			} elseif ( $post->post_type == 'fmwp_forum' ) {
				if ( $post->post_status !== 'publish' ) {
					$send = false;
					if ( user_can( $user_id, 'manage_fmwp_forums_all' ) ) {
						if ( $post->post_status == 'pending' || $post->post_status == 'private' ) {
							$send = true;
						}
					}
				}
			}

			$send = apply_filters( 'fmwp_um_notification_send_mention', $send, $post_ID, $post, $update );

			if ( $send ) {
				um_fetch_user( $post->post_author );

				$vars['photo'] = um_get_avatar_url( get_avatar( $post->post_author, 40 ) );
				$vars['member'] = um_user('display_name');
				$vars['post_url'] = get_permalink( $post );
				$vars['notification_uri'] = get_permalink( $post );

				UM()->Notifications_API()->api()->store_notification( $user_id, 'fmwp_mention', $vars );
			}
		}

		// update mentioned users, don't remove old mentioned users. Avoid re-send mentioned email notification
		$mentioned_users = array_diff( array_unique( array_merge( $mentioned_users, $users ) ), $need_mention );
		update_post_meta( $post_ID, 'fmwp_um_notifications_mentioned', $mentioned_users );
	}


	/**
	 * @param int $post_ID
	 * @param \WP_Post $post
	 * @param bool $update
	 */
	public function subscription_notification( $post_ID, $post, $update ) {
		if ( ! FMWP()->modules()->is_active( 'subscriptions' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$disable_subscription_notice = apply_filters( 'fmwp_subscription_notice_disabled', false, $post, $update );
		if ( $disable_subscription_notice ) {
			return;
		}

		if ( ! in_array( $post->post_type, array( 'fmwp_topic', 'fmwp_reply' ) ) ) {
			return;
		}

		if ( $post->post_status == 'auto-draft' ) {
			return;
		}

		$need_notify = array();

		if ( $post->post_type == 'fmwp_topic' ) {
			$notification_key = 'fmwp_new_topic';

			$forum_id = FMWP()->common()->topic()->get_forum_id( $post_ID );
			if ( empty( $forum_id ) ) {
				return;
			}

			$users = FMWP()->module( 'subscriptions' )->get_forum_subscribers( $forum_id );
		} elseif ( $post->post_type == 'fmwp_reply' ) {
			$notification_key = 'fmwp_new_reply';

			$topic_id = FMWP()->common()->reply()->get_topic_id( $post_ID );
			$forum_id = FMWP()->common()->topic()->get_forum_id( $topic_id );

			$topic_subscribers = FMWP()->module( 'subscriptions' )->get_topic_subscribers( $topic_id );
			$forum_subscribers = FMWP()->module( 'subscriptions' )->get_forum_subscribers( $forum_id );

			$users = array_merge( $topic_subscribers, $forum_subscribers );
			$users = array_unique( $users );
		}

		$users = array_diff( $users, array( $post->post_author ) );

		$users = apply_filters( 'fmwp_notify_subscribed_users_list', $users, $post );

		if ( ! $update ) {
			if ( $post->post_status !== 'publish' ) {
				$need_notify = array();

				if ( empty( $users ) ) {
					return;
				}

				foreach ( $users as $user_id ) {
					if ( $user_id == $post->post_author ) {
						continue;
					}

					if ( $post->post_type == 'fmwp_reply' ) {
						if ( FMWP()->user()->can_view_reply( $user_id, $post_ID ) ) {
							continue;
						}
					} elseif ( $post->post_type == 'fmwp_topic' ) {
						if ( user_can( $user_id, 'manage_fmwp_topics_all' ) ) {
							if ( $post->post_status == 'pending' || $post->post_status == 'private' ) {
								continue;
							}
						}
					}

					$need_notify[] = $user_id;
				}

				update_post_meta( $post_ID, 'fmwp_um_notifications_subscribers_need_notified', $need_notify );
			}
		} else {
			if ( $post->post_status == 'publish' ) {
				$need_notify = get_post_meta( $post_ID, 'fmwp_um_notifications_subscribers_need_notified', true );
				$need_notify = ! empty( $need_notify ) ? $need_notify : array();

				if ( ! empty( $need_notify ) ) {
					$users = array_unique( array_intersect( $users, $need_notify ) );

					delete_post_meta( $post_ID, 'fmwp_um_notifications_subscribers_need_notified' );

					$need_notify = array();
				}
			} else {
				$need_notify = array();

				if ( empty( $users ) ) {
					return;
				}

				foreach ( $users as $user_id ) {
					if ( $user_id == $post->post_author ) {
						continue;
					}

					if ( $post->post_type == 'fmwp_reply' ) {
						if ( FMWP()->user()->can_view_reply( $user_id, $post_ID ) ) {
							continue;
						}
					} elseif ( $post->post_type == 'fmwp_topic' ) {
						if ( user_can( $user_id, 'manage_fmwp_topics_all' ) ) {
							if ( $post->post_status == 'pending' || $post->post_status == 'private' ) {
								continue;
							}
						}
					}

					$need_notify[] = $user_id;
				}

				update_post_meta( $post_ID, 'fmwp_um_notifications_subscribers_need_notified', $need_notify );
			}
		}

		if ( empty( $users ) ) {
			return;
		}

		$notified_users = get_post_meta( $post_ID, 'fmwp_um_notifications_subscribers_notified', true );
		$notified_users = ! empty( $notified_users ) ? $notified_users : array();

		foreach ( $users as $user_id ) {
			if ( $user_id == $post->post_author ) {
				continue;
			}

			if ( in_array( $user_id, $notified_users ) ) {
				continue;
			}

			$send = true;

			if ( $post->post_type == 'fmwp_reply' ) {
				$send = FMWP()->user()->can_view_reply( $user_id, $post_ID );

				$send = apply_filters( 'fmwp_um_notifications_send_new_reply', $send, $post_ID, $post, $update );
			} elseif ( $post->post_type == 'fmwp_topic' ) {
				$topic_id = $post_ID;

				$forum_id = FMWP()->common()->topic()->get_forum_id( $topic_id );
				$forum = get_post( $forum_id );

				if ( $forum->post_status !== 'publish' ) {
					$send = false;
					if ( user_can( $user_id, 'manage_fmwp_forums_all' ) ) {
						if ( $forum->post_status == 'pending' || $forum->post_status == 'private' ) {
							$send = true;
						}
					}
				}

				if ( $send ) {
					if ( $post->post_status !== 'publish' ) {
						$send = false;
						if ( user_can( $user_id, 'manage_fmwp_topics_all' ) ) {
							if ( $post->post_status == 'pending' || $post->post_status == 'private' ) {
								$send = true;
							}
						}
					}
				}

				$send = apply_filters( 'fmwp_um_notifications_send_new_topic', $send, $post_ID, $post, $update );
			}

			if ( $send && ! empty( $notification_key ) ) {
				um_fetch_user( $post->post_author );

				$vars['photo'] = um_get_avatar_url( get_avatar( $post->post_author, 40 ) );
				$vars['member'] = um_user('display_name');
				$vars['post_url'] = get_permalink( $post );
				$vars['notification_uri'] = get_permalink( $post );

				UM()->Notifications_API()->api()->store_notification( $user_id, $notification_key, $vars );
			}
		}

		// update notified users, don't remove old notified users. Avoid re-send notified email notification
		$notified_users = array_diff( array_unique( array_merge( $notified_users, $users ) ), $need_notify );
		update_post_meta( $post_ID, 'fmwp_um_notifications_subscribers_notified', $notified_users );
	}
}
