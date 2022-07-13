<?php
namespace PHPSTORM_META {
	expectedArguments( \UM::module(), 0, 'forumwp','jobboardwp','member-directory','online','recaptcha','terms-conditions');

	override( \UM::module(0), map([
		'forumwp'          => \umm\forumwp\Init::class,
		'jobboardwp'       => \umm\jobboardwp\Init::class,
		'member-directory' => \umm\member_directory\Init::class,
		'online'           => \umm\online\Init::class,
		'recaptcha'        => \umm\recaptcha\Init::class,
		'terms-conditions' => \umm\terms_conditions\Init::class,
	]));
}
