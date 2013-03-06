<?php
# ***** BEGIN LICENSE BLOCK *****
#
# This program is free software. It comes without any warranty, to
# the extent permitted by applicable law. You can redistribute it
# and/or modify it under the terms of the Do What The Fuck You Want
# To Public License, Version 2, as published by Sam Hocevar. See
# http://sam.zoy.org/wtfpl/COPYING for more details.
#
#
# ***** END LICENSE BLOCK *****

$__autoload['Mailjet'] =	dirName(__FILE__) . '/inc/class.dc.mailjet.php';
$__autoload['MailjetAPI'] =	dirName(__FILE__) . '/inc/class.dc.mailjetapi.php';
$__autoload['PHPMailer'] =	dirName(__FILE__) . '/inc/class.dc.phpmailer.php';

require_once dirName (__FILE__) . '/_services.php';

$core->rest->addFunction('getPostListSubscribe', array('mailjetPluginRestMethods', 'getPostListSubscribe'));
