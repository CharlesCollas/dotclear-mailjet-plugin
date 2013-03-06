<?php

class mailjetPluginRestMethods
{
	public static function getPostListSubscribe($core, $get)
	{
		global $core;

		$list_id = isset($_GET['list']) && $_GET['list'] ? $_GET['list'] : null;

		if ($list_id === null)
			throw new Exception(__('No list ID given'));

		$email = isset($_GET['email']) && $_GET['email'] ? $_GET['email'] : null;

		if ($email === null)
			throw new Exception(__('Please enter your email'));

		$blog = $core->blog;
		$settings = $blog->settings->mailjet;
		$api = new MailjetAPI($settings->mj_username, $settings->mj_password);
		
		$params = array(
			'method' => 'POST',
			'contact' => $email,
			'id' => $list_id,
		);

		$response = $api->listsAddContact($params);
		if (!$response || $response->status != 'OK')
			throw new Exception( __('Sorry, we could not subscribe you at this time' . print_r($response, true)));

		$rsp = new xmlTag();
		$rsp->message(__('Thanks for subscribing'));
		return $rsp;
	}
}
