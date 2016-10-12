//<?php

class nexus_hook_clientAreaLink extends _HOOK_CLASS_
{

/* !Hook Data - DO NOT REMOVE */
public static function hookData() {
 return array_merge_recursive( array (
  'userBar' => 
  array (
    0 => 
    array (
      'selector' => '#elAccountSettingsLink',
      'type' => 'add_after',
      'content' => '{{if \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( \'nexus\', \'clients\' ) )}}
	<li class=\'ipsMenu_item\'><a href=\'{url="app=nexus&module=clients&controller=splash" seoTemplate="clients" protocol="\IPS\Settings::i()->nexus_https"}\' title=\'{lang="module__nexus_clients"}\'>{lang="module__nexus_clients"}</a></li>
{{endif}}',
    ),
    1 => 
    array (
      'selector' => '#cUserLink',
      'type' => 'add_before',
      'content' => '{{if \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( \'nexus\', \'store\' ) )}}
	{template="cartHeader" app="nexus" group="store" params=""}
{{endif}}',
    ),
    2 => 
    array (
      'selector' => '#elSignInLink',
      'type' => 'add_before',
      'content' => '{{if \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( \'nexus\', \'store\' ) )}}
	{template="cartHeader" app="nexus" group="store" params=""}
{{endif}}',
    ),
  ),
  'footer' => 
  array (
    0 => 
    array (
      'selector' => '#elFooterLinks',
      'type' => 'add_inside_end',
      'content' => '{{if \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( \'nexus\', \'clients\' ) ) and settings.network_status}}
	<li><a href=\'{url="app=nexus&module=clients&controller=networkStatus" seoTemplate="nexus_network_status"}\'>{lang=\'network_status\'}</a></li>
{{endif}}',
    ),
  ),
  'mobileNavigation' => 
  array (
    0 => 
    array (
      'selector' => '#elAccountSettingsLinkMobile',
      'type' => 'add_after',
      'content' => '{{if \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( \'nexus\', \'clients\' ) )}}
	<li><a href=\'{url="app=nexus&module=clients&controller=splash" seoTemplate="clients" protocol="\IPS\Settings::i()->nexus_https"}\' title=\'{lang="module__nexus_clients"}\'>{lang="module__nexus_clients"}</a></li>
{{endif}}',
    ),
  ),
  'mobileNavBar' => 
  array (
    0 => 
    array (
      'selector' => '#elMobileNav li:last-child',
      'type' => 'add_before',
      'content' => '{{if \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( \'nexus\', \'store\' ) )}}
	{template="cartHeaderMobile" app="nexus" group="store" params=""}
{{endif}}',
    ),
  ),
), parent::hookData() );
}
/* End Hook Data */


































}