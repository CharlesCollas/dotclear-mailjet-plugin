<?php


class Mailjet
{
	const MJ_HOST = 'in.mailjet.com';

	protected static $mailer = null;

	protected static function initMailer()
	{
		global $core;

		$blog = $core->blog;

		if (is_null($blog))
			return false;

		$settings = &$blog->settings->mailjet;

		$_mailer = new PHPMailer(true);

		$_mailer->Mailer = 'smtp';
		$_mailer->SMTPSecure = $settings->mj_ssl;

		$_mailer->Host = self::MJ_HOST;
		$_mailer->Port = $settings->mj_port;

		$_mailer->SMTPAuth = true;
		$_mailer->Username = $settings->mj_username;
		$_mailer->Password = $settings->mj_password;

		$_mailer->SetFrom ($settings->mj_sender_address);

		self::$mailer = $_mailer;

		return true;
	}

	public static function sendMail($to, $subject, $message, $headers)
	{
		if (is_null (self::$mailer) && !self::initMailer())
			return @mail($to, $subject, $message, $headers);

		self::$mailer->ClearAllRecipients();
		self::$mailer->ClearCustomHeaders();

		self::$mailer->Subject = $subject;
		self::$mailer->Body = $message;

		self::$mailer->AddAddress($to);

		if (!is_null($headers))
		{
			if (is_array($headers))
			{
				foreach ($headers as $value)
					self::$mailer->AddCustomHeader($value);
			}
			else
				self::$mailer->AddCustomHeader($headers);
		}

		self::$mailer->AddCustomHeader('X-Mailer:Mailjet-for-Dotclear/1.0');

		try
		{
			return self::$mailer->Send();
		}
		catch (phpmailerException $exc)
		{
			echo $exc->getMessage();
		}
	}
}

?>