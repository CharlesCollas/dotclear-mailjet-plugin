<?php
if (!defined('DC_RC_PATH')) { return; }

$core->addBehavior('initWidgets', array('mailjetSubscribeWidgetBehaviors', 'initWidgets'));

class mailjetSubscribeWidgetBehaviors
{
	public static function initWidgets($w)
	{
		global $core;

		$blog =		$core->blog;
		$settings =	$blog->settings->mailjet;
		$api =		new MailjetAPI($settings->mj_username, $settings->mj_password);
		$r =		$api->listsAll();
		$lists =	array();

		if ($r->status == 'OK')
		{
			$lists = $r->lists;
			foreach ($lists as $list)
				$options[$list->label] = $list->id;
		}

		$w->create('mailjetSubscribeWidget', __('Mailjet subscription'),
					array('publicMailjetSubscribeWidget','mailjetSubscribeWidget'));

		$w->mailjetSubscribeWidget->setting('title', __('Title:'), 'Subscribe to our newsletter','text');
		$w->mailjetSubscribeWidget->setting('button_text', __('Button text:'), 'Subscribe','text');
		$w->mailjetSubscribeWidget->setting('list', __('List:'), null, 'combo', $options);
	}
}
