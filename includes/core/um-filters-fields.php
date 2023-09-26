<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Field is required?
 *
 * @param $label
 * @param $data
 *
 * @return string
 */
function um_edit_label_all_fields( $label, $data ) {
	$asterisk = UM()->options()->get( 'form_asterisk' );
	if ( $asterisk && isset( $data['required'] ) && $data['required'] == 1 ) {
		$label = $label . '<span class="um-req" title="' . esc_attr__( 'Required', 'ultimate-member' ) . '">*</span>';
	}

	return $label;
}
add_filter( 'um_edit_label_all_fields', 'um_edit_label_all_fields', 10, 2 );


/**
 * Outputs a oEmbed field
 *
 * @param string $value
 * @param array $data
 *
 * @return string
 */
function um_profile_field_filter_hook__oembed( $value, $data ) {
	if ( empty( $value ) ) {
		return '';
	}
	$responce = wp_oembed_get( $value );
	if ( empty( $responce ) ) {
		$value = '<a href="' . esc_url( $value ) . '" target="_blank">' . esc_html( $value ) . '</a>';
	} else {
		$value = $responce;
	}

	return $value;
}
add_filter( 'um_profile_field_filter_hook__oembed', 'um_profile_field_filter_hook__oembed', 99, 2 );


/**
 * Outputs a SoundCloud track
 *
 * @param string $value
 * @param array $data
 *
 * @return string
 */
function um_profile_field_filter_hook__soundcloud_track( $value, $data ) {
	if ( empty( $value ) ) {
		return '';
	}
	if ( ! is_numeric( $value ) ) {
		# if we're passed a track url:
		if ( preg_match( '/https:\/\/soundcloud.com\/.*/', $value ) ) {
			$value = '<div class="um-soundcloud">
					<iframe width="100%" height="166" scrolling="no" frameborder="no" src="https://w.soundcloud.com/player/?url=' . esc_url( $value ) . '&amp;color=ff6600&amp;auto_play=false&amp;show_artwork=true"></iframe>
					</div>';
			return $value;
		} else {
			# neither a track id nor url:
			return __( 'Invalid SoundCloud track ID', 'ultimate-member' );
		}
	}

	# if we're passed a track id:
	$value = '<div class="um-soundcloud">
					<iframe width="100%" height="166" scrolling="no" frameborder="no" src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/' . esc_attr( $value ) . '&amp;color=ff6600&amp;auto_play=false&amp;show_artwork=true"></iframe>
					</div>';

	return $value;
}
add_filter( 'um_profile_field_filter_hook__soundcloud_track', 'um_profile_field_filter_hook__soundcloud_track', 99, 2 );


/**
 * Outputs a youtube video
 *
 * @param $value
 * @param $data
 *
 * @return bool|string
 */
function um_profile_field_filter_hook__youtube_video( $value, $data ) {
	if ( empty( $value ) ) {
		return '';
	}
	$value = ( strstr( $value, 'http' ) || strstr( $value, '://' ) ) ? um_youtube_id_from_url( $value ) : $value;
	$value = '<div class="um-youtube">
					<iframe width="600" height="450" src="https://www.youtube.com/embed/' . $value . '" frameborder="0" allowfullscreen></iframe>
					</div>';

	return $value;
}
add_filter( 'um_profile_field_filter_hook__youtube_video', 'um_profile_field_filter_hook__youtube_video', 99, 2 );


/**
 * Outputs a spotify iframe
 *
 * @param $value
 * @param $data
 *
 * @return bool|string
 */
function um_profile_field_filter_hook__spotify( $value, $data ) {
	if ( preg_match( '/https:\/\/open.spotify.com\/.*/', $value ) ) {
		if ( false !== strpos( $value, '/user/' ) ) {
			$value = '<a href="' . esc_attr( $value ) . '" target="_blank">' . esc_html( $value ) . '</a>';
		} else {
			$url = str_replace( 'open.spotify.com/', 'open.spotify.com/embed/', $value );

			$value = '<div class="um-spotify">
				<iframe width="100%" height="352" style="border-radius:12px" frameBorder="0" allowfullscreen="" loading="lazy"  src="' . esc_url( $url ) . '"></iframe>
				</div>';
		}
	} else {
		return __( 'Invalid Spotify URL', 'ultimate-member' );
	}

	return $value;
}
add_filter( 'um_profile_field_filter_hook__spotify', 'um_profile_field_filter_hook__spotify', 99, 2 );


/**
 * Outputs a vimeo video
 *
 * @param $value
 * @param $data
 *
 * @return int|string
 */
function um_profile_field_filter_hook__vimeo_video( $value, $data ) {
	if ( empty( $value ) ) {
		return '';
	}

	$value = ! is_numeric( $value ) ? (int) substr( parse_url( $value, PHP_URL_PATH ), 1 ) : $value;
	$value = '<div class="um-vimeo">
					<iframe src="https://player.vimeo.com/video/' . $value . '" width="600" height="450" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>
					</div>';
	return $value;
}
add_filter( 'um_profile_field_filter_hook__vimeo_video', 'um_profile_field_filter_hook__vimeo_video', 99, 2 );


/**
 * Outputs a phone link
 *
 * @param $value
 * @param $data
 *
 * @return int|string
 */
function um_profile_field_filter_hook__phone( $value, $data ) {
	$value = '<a href="tel:' . esc_attr( $value ) . '" rel="nofollow" title="' . esc_attr( $data['title'] ) . '">' . esc_html( $value ) . '</a>';
	return $value;
}
add_filter( 'um_profile_field_filter_hook__phone_number', 'um_profile_field_filter_hook__phone', 99, 2 );
add_filter( 'um_profile_field_filter_hook__mobile_number', 'um_profile_field_filter_hook__phone', 99, 2 );


/**
 * Outputs a viber link
 *
 * @param $value
 * @param $data
 *
 * @return int|string
 */
function um_profile_field_filter_hook__viber( $value, $data ) {
	$value = str_replace('+', '', $value);
	$value = '<a href="viber://chat?number=%2B' . esc_attr( $value ) . '" target="_blank"  rel="nofollow" title="' . esc_attr( $data['title'] ) . '">' . esc_html( $value ) . '</a>';
	return $value;
}
add_filter( 'um_profile_field_filter_hook__viber', 'um_profile_field_filter_hook__viber', 99, 2 );


/**
 * Outputs a whatsapp link
 *
 * @param $value
 * @param $data
 *
 * @return int|string
 */
function um_profile_field_filter_hook__whatsapp( $value, $data ) {
	$value = str_replace('+', '', $value);
	$value = '<a href="https://api.whatsapp.com/send?phone=' . esc_attr( $value ) . '" target="_blank"  rel="nofollow" title="' . esc_attr( $data['title'] ) . '">' . esc_html( $value ) . '</a>';
	return $value;
}
add_filter( 'um_profile_field_filter_hook__whatsapp', 'um_profile_field_filter_hook__whatsapp', 99, 2 );


/**
 * Outputs a google map
 *
 * @param $value
 * @param $data
 *
 * @return string
 */
function um_profile_field_filter_hook__googlemap( $value, $data ) {
	if ( ! $value ) {
		return '';
	}
	$value = '<div class="um-googlemap">
					<iframe width="600" height="450" frameborder="0" style="border:0" src="https://maps.google.it/maps?q=' . urlencode( $value ) . '&output=embed"></iframe>
				</div>';
	return $value;
}
add_filter( 'um_profile_field_filter_hook__googlemap', 'um_profile_field_filter_hook__googlemap', 99, 2 );


/**
 * User's registration date
 *
 * @param $value
 * @param $data
 *
 * @return false|int|string
 */

function um_profile_field_filter_hook__user_registered( $value, $data ) {
	if ( ! $value ) {
		return '';
	}
	$value = strtotime( $value );
	// translators: %s: date.
	$value = sprintf( __( 'Joined %s', 'ultimate-member' ), date_i18n( get_option( 'date_format' ), $value ) );
	return $value;
}
add_filter( 'um_profile_field_filter_hook__user_registered', 'um_profile_field_filter_hook__user_registered', 99, 2 );


/**
 * Last login date
 *
 * @param $value
 * @param $data
 *
 * @return string
 */
function um_profile_field_filter_hook__last_login( $value, $data ) {
	if ( ! $value ) {
		return '';
	}
	//$value = sprintf( __('Last login: %s','ultimate-member'), um_user_last_login( um_user('ID') ) );
	$value = um_user_last_login( um_user( 'ID' ) );
	return $value;
}
add_filter( 'um_profile_field_filter_hook__last_login', 'um_profile_field_filter_hook__last_login', 99, 2 );
add_filter( 'um_profile_field_filter_hook___um_last_login', 'um_profile_field_filter_hook__last_login', 99, 2 );


/**
 * URLs in textarea
 *
 * @param $value
 * @param $data
 *
 * @return mixed|string|void
 */
function um_profile_field_filter_hook__textarea( $value, $data ) {
	if ( ! $value ) {
		return '';
	}
	if ( ! empty( $data['html'] ) ) {
		return $value;
	}

	$description_key = UM()->profile()->get_show_bio_key( UM()->fields()->global_args );

	$value = wp_kses( $value, 'strip' );
	$value = html_entity_decode( $value );
	$value = preg_replace( '$(https?://[a-z0-9_./?=&#-]+)(?![^<>]*>)$i', ' <a href="$1" target="_blank">$1</a> ', $value . ' ' );
	$value = preg_replace( '$(www\.[a-z0-9_./?=&#-]+)(?![^<>]*>)$i', '<a target="_blank" href="http://$1">$1</a> ', $value . ' ' );

	if ( ! ( isset( $data['metakey'] ) && $description_key === $data['metakey'] ) ) {
		$value = wpautop( $value );
	}

	return $value;
}
add_filter( 'um_profile_field_filter_hook__textarea', 'um_profile_field_filter_hook__textarea', 99, 2 );

    /***
     ***	@urls in description
     ***/
/*    add_filter('um_profile_field_filter_hook__description', 'um_profile_field_filter_hook__description', 99, 2);
    function um_profile_field_filter_hook__description( $value, $data ) {

        if ( isset( $data ) && isset( $data['html'] ) && $data['html'] == 1 )
            return $value;

        $value = esc_textarea( $value );
        $value = preg_replace('$(https?://[a-z0-9_./?=&#-]+)(?![^<>]*>)$i', ' <a href="$1" target="_blank">$1</a> ', $value." ");
        $value = preg_replace('$(www\.[a-z0-9_./?=&#-]+)(?![^<>]*>)$i', '<a target="_blank" href="http://$1">$1</a> ', $value." ");

        return $value;
    }*/


/**
 * Time field
 *
 * @param $value
 * @param $data
 *
 * @return mixed|string
 */
function um_profile_field_filter_hook__time( $value, $data ) {
	if ( ! $value ) {
		return '';
	}
	$value = UM()->datetime()->format( $value, $data['format'] );

	$value = str_replace( 'am', 'a.m.', $value );
	$value = str_replace( 'pm', 'p.m.', $value );

	return $value;
}
add_filter( 'um_profile_field_filter_hook__time', 'um_profile_field_filter_hook__time', 99, 2 );


/**
 * Date field
 *
 * @param $value
 * @param $data
 *
 * @return string
 */
function um_profile_field_filter_hook__date( $value, $data ) {
	if ( ! $value ) {
		return '';
	}
	if ( isset( $data['pretty_format'] ) && $data['pretty_format'] == 1 ) {
		$value = UM()->datetime()->get_age( $value );
	} else {
		$format = empty( $data['format_custom'] ) ? $data['format'] : $data['format_custom'];
		$value = date_i18n( $format, strtotime( $value ) );
	}

	return $value;
}
add_filter( 'um_profile_field_filter_hook__date', 'um_profile_field_filter_hook__date', 99, 2 );


/**
 * File field
 * @param $value
 * @param $data
 *
 * @return string
 */
function um_profile_field_filter_hook__file( $value, $data ) {
	if ( ! $value ) {
		return '';
	}
	$file_type = wp_check_filetype( $value );
	$uri = UM()->files()->get_download_link( UM()->fields()->set_id, $data['metakey'], um_user( 'ID' ) );

	$removed = false;
	if ( ! file_exists( UM()->uploader()->get_upload_base_dir() . um_user( 'ID' ) . DIRECTORY_SEPARATOR . $value ) ) {
		if ( is_multisite() ) {
			//multisite fix for old customers
			$file_path = str_replace( DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . get_current_blog_id() . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, UM()->uploader()->get_upload_base_dir() . um_user( 'ID' ) . DIRECTORY_SEPARATOR . $value );
			if ( ! file_exists( $file_path ) ) {
				$removed = true;
			}
		} else {
			$removed = true;
		}
	}

	if ( $removed ) {
		$value = __( 'This file has been removed.', 'ultimate-member' );
	} else {
		$file_info = um_user( $data['metakey'] . "_metadata" );
		if ( ! empty( $file_info['original_name'] ) ) {
			$value = $file_info['original_name'];
		}
		$value = '<div class="um-single-file-preview show">
                        <div class="um-single-fileinfo">
                            <a href="' . esc_attr( $uri )  . '" target="_blank">
                                <span class="icon" style="background:'. UM()->files()->get_fonticon_bg_by_ext( $file_type['ext'] ) . '"><i class="'. UM()->files()->get_fonticon_by_ext( $file_type['ext'] ) .'"></i></span>
                                <span class="filename">' . esc_attr( $value ) . '</span>
                            </a>
                        </div>
                    </div>';
	}

	return $value;
}
add_filter( 'um_profile_field_filter_hook__file', 'um_profile_field_filter_hook__file', 99, 2 );


/**
 * Image field
 *
 * @param $value
 * @param $data
 *
 * @return string
 */
function um_profile_field_filter_hook__image( $value, $data ) {
	if ( ! $value ) {
		return '';
	}
	$uri = UM()->files()->get_download_link( UM()->fields()->set_id, $data['metakey'], um_user( 'ID' ) );
	$title = ( isset( $data['title'] ) ) ? $data['title'] : __( 'Untitled photo', 'ultimate-member' );

	$removed = false;
	if ( ! file_exists( UM()->uploader()->get_upload_base_dir() . um_user( 'ID' ) . DIRECTORY_SEPARATOR . $value ) ) {
		if ( is_multisite() ) {
			//multisite fix for old customers
			$file_path = str_replace( DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . get_current_blog_id() . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, UM()->uploader()->get_upload_base_dir() . um_user( 'ID' ) . DIRECTORY_SEPARATOR . $value );
			if ( ! file_exists( $file_path ) ) {
				$removed = true;
			}
		} else {
			$removed = true;
		}
	}

	// if value is an image tag
	if( preg_match( '/\<img.*src=\"([^"]+).*/', $value, $matches ) ) {
		$uri   = $matches[1];
		$value = '<div class="um-photo"><a href="#" class="um-photo-modal" data-src="' . esc_attr( $uri ) . '"><img src="' . esc_attr( $uri ) . '" alt="' . esc_attr( $title ) . '" title="' . esc_attr( $title ) . '" class="" /></a></div>';
	} else if ( ! $removed ) {
		$value = '<div class="um-photo"><a href="#" class="um-photo-modal" data-src="' . esc_attr( $uri ) . '"><img src="' . esc_attr( $uri ) . '" alt="' . esc_attr( $title ) . '" title="' . esc_attr( $title ) . '" class="" /></a></div>';
	} else {
		$value = '';
	}

	return $value;
}
add_filter( 'um_profile_field_filter_hook__image', 'um_profile_field_filter_hook__image', 99, 2 );

/**
 * Global sanitize
 *
 * @param $value
 * @param $data
 * @param string $type
 *
 * @return string
 */
function um_profile_field_filter_hook__( $value, $data, $type = '' ) {
	if ( ! $value ) {
		return '';
	}

	if ( isset( $data['type'] ) && 'text' === $data['type'] && isset( $data['validate'] ) && 'skype' === $data['validate'] ) {
		$alt                = ! empty( $data['url_text'] ) ? $data['url_text'] : $value;
		$url_rel            = ( isset( $data['url_rel'] ) && 'nofollow' === $data['url_rel'] ) ? 'rel="nofollow"' : '';
		$data['url_target'] = ( isset( $data['url_target'] ) ) ? $data['url_target'] : '_blank';

		if ( false === strstr( $value, 'join.skype.com' ) ) {
			$value = 'skype:' . $value . '?chat';
		}

		$value = '<a href="' . esc_attr( $value ) . '" title="' . esc_attr( $alt ) . '" target="' . esc_attr( $data['url_target'] ) . '" ' . $url_rel . '>' . esc_html( $alt ) . '</a>';
	} else {
		// check $value is oEmbed
		if ( 'oembed' === $data['type'] ) {
			return $value;
		}

		if ( ( isset( $data['validate'] ) && '' !== $data['validate'] && 'spotify' !== $data['type'] && strstr( $data['validate'], 'url' ) ) || ( isset( $data['type'] ) && 'url' === $data['type'] && 'oembed' !== $data['type'] ) ) {
			$alt     = ( isset( $data['url_text'] ) && ! empty( $data['url_text'] ) ) ? $data['url_text'] : $value;
			$url_rel = ( isset( $data['url_rel'] ) && 'nofollow' === $data['url_rel'] ) ? 'rel="nofollow"' : '';
			if ( ! strstr( $value, 'http' )
				&& ! strstr( $value, '://' )
				&& ! strstr( $value, 'www.' )
				&& ! strstr( $value, '.com' )
				&& ! strstr( $value, '.net' )
				&& ! strstr( $value, '.org' )
				&& ! strstr( $value, '.me' )
			) {
				if ( 'soundcloud_url' === $data['validate'] ) {
					$value = 'https://soundcloud.com/' . $value;
				}
				if ( 'youtube_url' === $data['validate'] ) {
					$value = 'https://youtube.com/user/' . $value;
				}
				if ( 'telegram_url' === $data['validate'] ) {
					$value = 'https://t.me/' . $value;
				}
				if ( 'facebook_url' === $data['validate'] ) {
					$value = 'https://facebook.com/' . $value;
				}
				if ( 'twitter_url' === $data['validate'] ) {
					$value = 'https://twitter.com/' . $value;
				}
				if ( 'linkedin_url' === $data['validate'] ) {
					$value = 'https://linkedin.com/' . $value;
				}
				if ( 'instagram_url' === $data['validate'] ) {
					$value = 'https://instagram.com/' . $value;
				}
				if ( 'tiktok_url' === $data['validate'] ) {
					$value = 'https://tiktok.com/' . $value;
				}
				if ( 'twitch_url' === $data['validate'] ) {
					$value = 'https://twitch.tv/' . $value;
				}
				if ( 'reddit_url' === $data['validate'] ) {
					$value = 'https://www.reddit.com/user/' . $value;
				}
				if ( 'spotify_url' === $data['validate'] ) {
					$value = 'https://open.spotify.com/' . $value;
				}
			}

			if ( strpos( $value, 'http://' ) !== 0 ) {
				$value = 'http://' . $value;
			}

			$value = str_replace( 'https://https://', 'https://', $value );
			$value = str_replace( 'http://https://', 'https://', $value );

			$onclick_alert = '';
			if ( UM()->options()->get( 'allow_url_redirect_confirm' ) && wp_validate_redirect( $value ) !== $value ) {
				$onclick_alert = sprintf(
					' onclick="' . esc_attr( 'return confirm( "%s" );' ) . '"',
					// translators: %s: link.
					esc_js( sprintf( __( 'This link leads to a 3rd-party website. Make sure the link is safe and you really want to go to this website: \'%s\'', 'ultimate-member' ), $value ) )
				);
			}

			$data['url_target'] = ( isset( $data['url_target'] ) ) ? $data['url_target'] : '_blank';
			$value              = '<a href="' . esc_url( $value ) . '" title="' . esc_attr( $alt ) . '" target="' . esc_attr( $data['url_target'] ) . '" ' . $url_rel . $onclick_alert . '>' . esc_html( $alt ) . '</a>';
		}
	}

	if ( ! is_array( $value ) ) {
		if ( is_email( $value ) ) {
			$value = '<a href="mailto:' . $value . '" title="' . $value . '">' . $value . '</a>';
		}
	} else {
		$value = implode( ', ', $value );
	}

	$value = str_replace( 'https://https://', 'https://', $value );
	$value = str_replace( 'http://https://', 'https://', $value );
	//$value = UM()->shortcodes()->emotize( $value );
	return $value;

}
add_filter( 'um_profile_field_filter_hook__', 'um_profile_field_filter_hook__', 99, 3 );


/**
 * Get form fields
 *
 * @param string|array $array
 * @param int          $form_id
 *
 * @return array|string
 */
function um_get_form_fields( $array, $form_id ) {
	if ( $form_id && UM()->fields()->set_mode ) {
		$array = UM()->query()->get_attr( 'custom_fields', $form_id );
	} else {
		$array = '';
	}

	return $array;
}
add_filter( 'um_get_form_fields', 'um_get_form_fields', 99, 2 );


/**
 * Validate conditional logic
 *
 * @param $array
 * @param $fields
 * @return mixed
 */
function um_get_custom_field_array( $array, $fields ) {

	if ( ! empty( $array['conditions'] ) ) {
		foreach ( $array['conditions'] as $key => $value ) {
			if ( ! isset( $value[1] ) ) {
				continue;
			}

			if ( empty( $fields[ $value[1] ] ) ) {
				continue;
			}

			if ( empty( $fields[ $value[1] ]['metakey'] ) ) {
				continue;
			}

			$condition_metakey = $fields[ $value[1] ]['metakey'];

			if ( isset( $_POST[ $condition_metakey ] ) ) {
				$cond_value = ( $fields[ $value[1] ]['type'] === 'radio' ) ? $_POST[ $condition_metakey ][0] : $_POST[ $condition_metakey ];
				list( $visibility, $parent_key, $op, $parent_value ) = $value;

				if ( $visibility == 'hide' ) {
					if ( $op == 'empty' ) {
						if ( empty( $cond_value ) ) {
							$array['required'] = 0;
						}
					} elseif ( $op == 'not empty' ) {
						if ( ! empty( $cond_value ) ) {
							$array['required'] = 0;
						}
					} elseif ( $op == 'equals to' ) {
						if ( $cond_value == $parent_value ) {
							$array['required'] = 0;
						}
					} elseif ( $op == 'not equals' ) {
						if ( $cond_value != $parent_value ) {
							$array['required'] = 0;
						}
					} elseif ( $op == 'greater than' ) {
						if ( $cond_value > $parent_value ) {
							$array['required'] = 0;
						}
					} elseif ( $op == 'less than' ) {
						if ( $cond_value < $parent_value ) {
							$array['required'] = 0;
						}
					} elseif ( $op == 'contains' ) {
						if ( is_string( $cond_value ) && strstr( $cond_value, $parent_value ) ) {
							$array['required'] = 0;
						}
						if( is_array( $cond_value ) && in_array( $parent_value, $cond_value ) ) {
							$array['required'] = 0;
						}
					}
				} elseif ( $visibility == 'show' ) {
					if ( $op == 'empty' ) {
						if ( ! empty( $cond_value ) ) {
							$array['required'] = 0;
						}
					} elseif ( $op == 'not empty' ) {
						if ( empty( $cond_value ) ) {
							$array['required'] = 0;
						}
					} elseif ( $op == 'equals to' ) {
						if ( $cond_value != $parent_value ) {
							$array['required'] = 0;
						}
					} elseif ( $op == 'not equals' ) {
						if ( $cond_value == $parent_value ) {
							$array['required'] = 0;
						}
					} elseif ( $op == 'greater than' ) {
						if ( $cond_value <= $parent_value ) {
							$array['required'] = 0;
						}
					} elseif ( $op == 'less than' ) {
						if ( $cond_value >= $parent_value ) {
							$array['required'] = 0;
						}
					} elseif ( $op == 'contains' ) {
						if( is_string( $cond_value ) && !strstr( $cond_value, $parent_value ) ) {
							$array['required'] = 0;
						}
						if( is_array( $cond_value ) && !in_array( $parent_value, $cond_value ) ) {
							$array['required'] = 0;
						}
					}
				}
			}
		}
	}

	return $array;
}
add_filter( 'um_get_custom_field_array', 'um_get_custom_field_array', 99, 2 );


/**
 * Force fields to use UTF-8 encoding
 *
 * @param mixed $value
 * @param array $data
 * @param string $type
 *
 * @return mixed
 */
function um_force_utf8_fields( $value, $data, $type = '' ) {

	if( ! UM()->options()->get('um_force_utf8_strings') )
		return $value;

		$value = um_force_utf8_string( $value );

		return $value;

	}
add_filter('um_profile_field_filter_hook__','um_force_utf8_fields', 9, 3 );


/**
 * Filter profile data value
 * @param  mixed $value
 * @return mixed
 * @uses   hook filter: um_is_selected_filter_value
 */
function um_is_selected_filter_value( $value ) {
	if ( ! UM()->options()->get( 'um_force_utf8_strings' ) ) {
		return $value;
	}

	$value = um_force_utf8_string( $value );

	return $value;
}
add_filter( 'um_is_selected_filter_value','um_is_selected_filter_value', 9, 1 );
add_filter( 'um_select_dropdown_dynamic_option_value','um_is_selected_filter_value', 10, 1 );

/**
 * Filter select dropdown to use UTF-8 encoding
 *
 * @param  array $options
 * @param  array $data
 * @return array
 * @uses   hook filter: um_select_dropdown_dynamic_options
 */
function um_select_dropdown_dynamic_options_to_utf8( $options, $data ) {
	if ( ! UM()->options()->get( 'um_force_utf8_strings' ) ) {
		return $options;
	}

	foreach ( $options as $key => $value ) {
		$options[ $key ] = um_force_utf8_string( $value );
	}

	return $options;
}
add_filter( 'um_select_dropdown_dynamic_options','um_select_dropdown_dynamic_options_to_utf8', 10, 2 );


/**
 * Filter non-UTF8 strings
 * @param  string $value
 * @return string
 * @uses hook filter: um_field_non_utf8_value
 */
function um_field_non_utf8_value( $value ) {

	if ( function_exists( 'mb_detect_encoding' ) ) {
		$encoding = mb_detect_encoding( $value, 'utf-8, iso-8859-1, ascii', true );
		if ( strcasecmp( $encoding, 'UTF-8' ) !== 0 ) {
			if ( function_exists( 'iconv' ) ) {
				$value = iconv( $encoding, 'utf-8', $value );
			}
		}
	}

	return $value;
}
add_filter( 'um_field_non_utf8_value', 'um_field_non_utf8_value' );


/**
 * Returns dropdown/multi-select options from a callback function
 * @param  $options array
 * @param  $data array
 * @return array
 * @uses   hook filter: um_select_dropdown_dynamic_options, um_multiselect_options
 */
function um_select_dropdown_dynamic_callback_options( $options, $data ) {
	if ( ! empty( $data['custom_dropdown_options_source'] ) && function_exists( $data['custom_dropdown_options_source'] ) ) {
		if ( UM()->fields()->is_source_blacklisted( $data['custom_dropdown_options_source'] ) ) {
			return $options;
		}
		$options = call_user_func( $data['custom_dropdown_options_source'] );
	}

	return $options;
}
add_filter( 'um_select_dropdown_dynamic_options','um_select_dropdown_dynamic_callback_options', 10, 2 );
add_filter( 'um_multiselect_options','um_select_dropdown_dynamic_callback_options', 10, 2 );


/**
 * Pair dropdown/multi-select options from a callback function
 *
 * @param  $value string
 * @param  $data  array
 * @return string
 * @uses   hook filter: um_profile_field_filter_hook__
 */

function um_option_match_callback_view_field( $value, $data ) {
	if ( ! empty( $data['custom_dropdown_options_source'] ) ) {
		return UM()->fields()->get_option_value_from_callback( $value, $data, $data['type'] );
	}

	return $value;
}
add_filter('um_profile_field_filter_hook__select','um_option_match_callback_view_field', 10, 2);
add_filter('um_profile_field_filter_hook__multiselect','um_option_match_callback_view_field', 10, 2);
add_filter('um_field_select_default_value','um_option_match_callback_view_field', 10, 2);
add_filter('um_field_multiselect_default_value','um_option_match_callback_view_field', 10, 2);


/**
 * Apply textdomain in select/multi-select options
 *
 * @param  $value string
 * @param  $data  array
 * @return string
 * @uses   hook filters: um_profile_field_filter_hook__select, um_profile_field_filter_hook__multiselect
 */

function um_profile_field__select_translate( $value, $data ) {

	if ( empty( $value  ) ) return $value;

	$options = explode(", ", $value );
	$arr_options = array();
	if( is_array( $options ) ){
		foreach ( $options as $item ) {
			$arr_options[] = __( $item, 'ultimate-member' );
		}
	}

	$value = implode(", ", $arr_options);

	return $value;
}
add_filter( 'um_profile_field_filter_hook__select','um_profile_field__select_translate', 10, 2 );
add_filter( 'um_profile_field_filter_hook__multiselect','um_profile_field__select_translate', 10, 2 );


/**
 * Cleaning on XSS injection
 * @param  $value string
 * @param  $data  array
 * @param  string $type
 * @return string $value
 * @uses   hook filters: um_profile_field_filter_hook__
 */
function um_profile_field_filter_xss_validation( $value, $data, $type = '' ) {
	if ( ! empty( $value ) && is_string( $value ) ) {
		$value = stripslashes( $value );
		$data['validate'] = isset( $data['validate'] ) ? $data['validate'] : '';

		if ( 'text' == $type && ! in_array( $data['validate'], array( 'unique_email' ) ) || 'password' == $type ) {
			$value = esc_attr( $value );
		} elseif ( $type == 'url' ) {
			$value = esc_url( $value );
		} elseif ( 'textarea' == $type ) {
			if ( empty( $data['html'] ) ) {
				$value = wp_kses_post( $value );
			}
		} elseif ( 'rating' == $type ) {
			if ( ! is_numeric( $value ) ) {
				$value = 0;
			} else {
				if ( $data['number'] == 5 ) {
					if ( ! in_array( $value, range( 1, 5 ) ) ) {
						$value = 0;
					}
				} elseif ( $data['number'] == 10 ) {
					if ( ! in_array( $value, range( 1, 10 ) ) ) {
						$value = 0;
					}
				}
			}
		} elseif ( 'select' == $type || 'radio' == $type ) {

			/** This filter is documented in includes/core/class-fields.php */
			$option_pairs = apply_filters( 'um_select_options_pair', null, $data );

			$array = empty( $data['options'] ) ? array() : $data['options'];

			if ( $data['metakey'] == 'country' && empty( $array ) ) {
				$array = UM()->builtin()->get( 'countries' );
			}

			if ( $option_pairs ) {
				$arr = array_keys( $array );
			} else {
				$arr = $array;
			}

			if ( ! empty( $arr ) && ! in_array( $value, array_map( 'trim', $arr ) ) && empty( $data['custom_dropdown_options_source'] ) ) {
				$value = '';
			} else {
				if ( $option_pairs && is_array( $array ) && isset( $array[ $value ] ) ) {
					$value = $array[ $value ];
				}
			}
		}
	} elseif ( ! empty( $value ) && is_array( $value ) ) {
		if ( 'multiselect' == $type || 'checkbox' == $type ) {

			/** This filter is documented in includes/core/class-fields.php */
			$option_pairs = apply_filters( 'um_select_options_pair', null, $data );

			$arr = $data['options'];
			if ( $option_pairs ) {
				$arr = array_keys( $data['options'] );
			}

			if ( ! empty( $arr ) && empty( $data['custom_dropdown_options_source'] ) ) {
				$arr = wp_unslash( $arr );
				$arr = wp_slash( array_map( 'trim', $arr ) );
				$value = array_intersect( $value, $arr );
			}

			if ( $option_pairs ) {
				foreach ( $value as &$val ) {
					$val = $data['options'][ $val ];
				}
			}
		}
	}

	return $value;
}
add_filter( 'um_profile_field_filter_hook__', 'um_profile_field_filter_xss_validation', 10, 3 );


/**
 * Trim All form POST submitted data
 *
 * @todo Maybe deprecate because data is sanitized in earlier code and trim included to `sanitize_text_field()`. Need testing and confirmation.
 *
 * @param $post_form
 *
 * @return mixed
 */
function um_submit_form_data_trim_fields( $post_form ) {
	foreach ( $post_form as $key => $field ) {
		if ( is_string( $field ) ) {
			$post_form[ $key ] = trim( $field );
		}
	}

	return $post_form;
}
add_filter( 'um_submit_form_data', 'um_submit_form_data_trim_fields', 9, 1 );


/**
 * Add `role_select` and `role_radio` to the $post_form
 * It is necessary for that if on these fields the conditional logic.
 *
 * @param array $post_form
 * @param string $mode
 * @param array $all_cf_metakeys
 *
 * @return array
 */
function um_submit_form_data_role_fields( $post_form, $mode, $all_cf_metakeys ) {
	if ( 'login' === $mode ) {
		return $post_form;
	}

	if ( ! array_key_exists( 'role', $post_form ) ) {
		return $post_form;
	}

	$role_fields = array( 'role_select', 'role_radio' );

	$form_has_role_field = count( array_intersect( $all_cf_metakeys, $role_fields ) ) > 0;
	if ( ! $form_has_role_field ) {
		return $post_form;
	}

	foreach ( $role_fields as $role_field ) {
		if ( in_array( $role_field, $all_cf_metakeys, true ) ) {
			$post_form[ $role_field ] = $post_form['role'];
		}
	}

	return $post_form;
}
add_filter( 'um_submit_form_data', 'um_submit_form_data_role_fields', 10, 3 );


/**
 * Cleaning on XSS injection for url editing field
 * @param  $value string
 * @param  $key   string
 *
 * @return string $value
 * @uses   hook filters: um_edit_url_field_value
 */
function um_edit_url_field_value( $value, $key ) {
	$value = esc_attr( $value );
	return $value;
}
add_filter( 'um_edit_url_field_value', 'um_edit_url_field_value', 10, 2 );
