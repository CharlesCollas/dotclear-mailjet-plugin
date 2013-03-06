<?php
# ***** BEGIN LICENSE BLOCK *****
#
# This program is free software. It comes without any warranty, to
# the extent permitted by applicable law. You can redistribute it
# and/or modify it under the terms of the Do What The Fuck You Want
# To Public License, Version 2, as published by Sam Hocevar. See
# http://sam.zoy.org/wtfpl/COPYING for more details.
#
# ***** END LICENSE BLOCK *****

l10n::set(dirname (__FILE__) . '/locales/' . $_lang . '/admin');
$default_tab = 'settings';

if (isset($_REQUEST['tab']))
	$default_tab = $_REQUEST['tab'];

$page_title = __('Mailjet');

$core->blog->settings->addNameSpace('mailjet');

$settings = &$core->blog->settings->mailjet;

$fields = array('mj_enabled' => '',
				'mj_test' => '',
				'mj_test_address' => '',
				'mj_port' => '',
				'mj_ssl' => '',
				'mj_username' => '',
				'mj_password' => '');

$errors = array();

if (isset($_POST['saveconfig']))
{
	$fields['mj_enabled'] =			isset($_POST['mj_enabled']);
	$fields['mj_test'] =			isset($_POST['mj_test']);
	$fields['mj_test_address'] =	strip_tags($_POST['mj_test_address']);
	$fields['mj_sender_address'] =	strip_tags($_POST['mj_sender_address']);
	$fields['mj_username'] =		strip_tags($_POST['mj_username']);
	$fields['mj_password'] =		strip_tags($_POST['mj_password']);

	if ($fields['mj_test'] && empty ($fields['mj_test_address']))
		$errors[] = __('Enter a test address email');

	if (empty($fields['mj_username']))
		$errors[] = __('You must enter your Mailjet API Key');

	if (empty($fields['mj_sender_address']))
		$fields['mj_sender_address'] = DC_ADMIN_MAILFROM;

	if (empty($fields['mj_password']))
		$errors[] = __('You must enter your Mailjet Secret Key');

	if (count ($errors))
	{
		foreach ($errors as $value)
			$core->error->add(__($value));
	}
	else
	{
		$was_enabled = $settings->mj_enabled;
		$settings->put('mj_enabled',		$fields['mj_enabled'], 'boolean', 'Enable Mailjet');
		$settings->put('mj_test',			$fields['mj_test'], 'boolean', 'Enable test mail');
		$settings->put('mj_test_address',	$fields['mj_test_address'], 'string', 'Test address');
		$settings->put('mj_sender_address',	$fields['mj_sender_address'], 'string', 'From address');
		$settings->put('mj_username',		$fields['mj_username'], 'string', 'API Key');
		$settings->put('mj_password',		$fields['mj_password'], 'string', 'Secret API');

		$configs = array(	array('ssl://', 465),
							array('tls://', 587),
							array('', 587),
							array('', 588),
							array('tls://', 25),
							array('', 25));

		$host = Mailjet::MJ_HOST;
		$connected = false;

		for ($i = 0; $i < count($configs); ++$i)
		{
			$soc = @fsockopen($configs[$i][0] . $host, $configs[$i][1], $errno, $errstr, 5);

			if ($soc)
			{
				fclose($soc);
				$connected = true;

				break;
			}
		}

		if ($connected)
		{
			if ('ssl://' == $configs[$i][0])
				$settings->put('mj_ssl', 'ssl', 'string', 'Secure connection');
			elseif ('tls://' == $configs[$i][0])
				$settings->put('mj_ssl', 'tls', 'string', 'Secure connection');
			else
				$settings->put('mj_ssl', '', 'string', 'Secure connection');

			$settings->put('mj_port', $configs[$i][1], 'integer', 'Port');

			if ($fields['mj_test'])
			{
				$subject = __('Your test mail from Mailjet');
				$message = __('Your Mailjet configuration is ok!');

				_mail($fields['mj_test_address'], $subject, $message, NULL);
			}

			http::redirect ($p_url . '&saveconfig=1&wasEnabled=' . $was_enabled);
		}
		else
			$core->error->add(sprintf(__('Please contact Mailjet support to sort this out.<br /><br />%d - %s', $errno, $errstr)));
	}
}
else
{
	$fields['mj_enabled'] =			$settings->mj_enabled;
	$fields['mj_test'] =			$settings->mj_test;
	$fields['mj_test_address'] =	$settings->mj_test_address;
	$fields['mj_sender_address'] =	$settings->mj_sender_address;
	$fields['mj_username'] =		$settings->mj_username;
	$fields['mj_password'] =		$settings->mj_password;
}

$api = new MailjetAPI($settings->mj_username, $settings->mj_password);

if (isset($_GET['saveconfig']))
{
	$msg =		__('Configuration successfully updated.');
	$enabled =	__('To enable Mailjet, please copy this function _mail () in your dotclear/inc/config.php file :');
	$disabled = __('To disable Mailjet, please remove the function _mail () in your dotclear/inc/config.php file.');
}

# Traitement
$step = (!empty($_GET['add']) ? (integer) $_GET['add'] : 0);
if (($step > 2) || ($step < 0))
	$step = 0;

if ($step)
{
	switch ($step)
	{
		case 1:

		break;

		case 2:

			$list_label = isset($_POST['list_label']) ? $_POST['list_label'] : '';

			// Fourth step, menu item to be added
			try
			{
				if ($list_label != '')
				{
					$list_name = substr(md5($list_label . time() . microtime()), 0, 8);
					$params = array(
						'method' =>	'POST',
						'label' =>	$list_label,
						'name' =>	$list_name,
					);

					$response = $api->listsCreate($params);
					if (!$response)
						throw new Exception(__('Could not create list at this time.'));

					// All done successfully, return to mailjet lists
					http::redirect($p_url . '&added=1&lists=1');
				}
				else
					throw new Exception(__('The list label is mandatory.'));
			}
			catch (Exception $e) {
				$core->error->add($e->getMessage());
			}
		break;
	}
}
else
{
    # Remove selected menu items
    if (!empty($_POST['removeaction']))
    {
        try
        {
            if (!empty($_POST['remove']))
            {
                foreach ($_POST['remove'] as $v)
                {
                    $params = array(
                        'method' => 'POST',
                        'id' => $v
                    );
                    $response = $api->listsDelete($params);
                }

                // All done successfully, return to menu items list
                http::redirect($p_url . '&removed=1&lists=1');
            }
            else
                throw new Exception(__('No menu items selected.'));
        }
        catch (Exception $e) {
            $core->error->add($e->getMessage());
        }
    }
    elseif(!empty($_POST['appendcontactaction']))
    {
        try
        {
            $list_id = filter_var($_POST['list_id'], FILTER_SANITIZE_NUMBER_INT);

            if (!empty($_POST['email']))
            {
                # Parameters
                $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
                $params = array(
                    'method' => 'POST',
                    'contact' => $email,
                    'id' => $list_id
                );
                $response = $api->listsAddContact($params);

                if ($response->status !='OK')
                    throw new Exception(__('Could not subscribe this contact.'));

                $contact_id = $response->contact_id;

                // All done successfully, return to menu items list
                http::redirect($p_url . '&added=1&edit=1&id=' . $list_id);
            }
            else
                throw new Exception(__('No email defined.'));
        }
        catch (Exception $e) {
            $core->error->add($e->getMessage());
        }
    }
    elseif (!empty($_POST['removecontactsaction']))
    {
        try
        {
            if (!empty($_POST['remove']))
            {
                $contacts = join(',', $_POST['remove']);
                $list_id = filter_var($_POST['list_id'], FILTER_SANITIZE_NUMBER_INT);

                # Parameters
                $params = array(
                    'method' =>		'POST',
                    'contacts' =>	$contacts,
                    'id' =>			$list_id
                );
				# Call
                $response = $api->listsRemoveManyContacts($params);

				# Result
                $affected = $response->affected;
                $count = $response->total;

                // All done successfully, return to menu items list
                http::redirect($p_url . '&edit=1&removedContacts=1&id=' . $list_id);
            }
            else
                throw new Exception(__('No contacts selected.'));
        }
        catch (Exception $e) {
            $core->error->add($e->getMessage());
        }
    }
}

?><html>
<head>
	<title><?php echo __('Mailjet settings'); ?></title>
    <style type="text/css">
        ul.nav{
            margin: 0;
            padding: 0;
        }
        ul.nav li {
            list-style: none;
            font-weight: bold;
            font-size: 1.1em;
            float: left;
            margin: 0;
        }
        ul.nav li a{
            display: block;
            padding: 0.5em 1.5em 0.5em 0;
            text-decoration: none;
            border: none;

        }
        ul.nav li a.active{
            color: #D30E60;
        }
    </style>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('.add-contact').click(function(e) {
                e.preventDefault();
                $('.hidden-form').slideDown();
            });
        });
    </script>
</head>
<body>
<?php

if ($step)
{
    // Formulaire d'ajout d'un item
    echo '<h2>' . html::escapeHTML($core->blog->name) . ' &rsaquo; <a href="' . $p_url . '">' . $page_title . '</a> &rsaquo; <span class="page-title">' . __('Create list') . '</span></h2>';
    $active = 'lists';
}
elseif (isset($_GET['lists']))
{
    echo '<h2>' . html::escapeHTML($core->blog->name) . ' &rsaquo; <a href="' . $p_url . '">' . $page_title . '</a> &rsaquo; <span class="page-title">' . __('Contact lists') . '</span></h2>';
    $active = 'lists';
}
elseif (isset($_GET['edit']))
{
    $active = 'lists';
    $response = $api->listsStatistics(array('id' => $_GET['id']));

    $list = $response->statistics;

    echo '<h2>' . html::escapeHTML($core->blog->name) . ' &rsaquo; <a href="' . $p_url . '">' . $page_title . '</a> &rsaquo; <a href="' . $p_url . '&lists=1">' . __('Contact lists') . '</a> &rsaquo; <span class="page-title">' . $list->label . '</span></h2>';
}
else
{
    $active = 'settings';
    echo '<h2>' . html::escapeHTML($core->blog->name) . ' &rsaquo; <span class="page-title">' . $page_title . '</span></h2>';
}

echo '<ul class="nav">
    <li>
    <a href="' . $p_url . '&lists=1"' . ($active == 'lists' ? 'class="active"' : '') . '>' . __('Contact lists') . '</a>
    </li>
    <li>
    <a href="' . $p_url . '"' . ($active == 'settings' ? 'class="active"' : '') . '>' . __('Settings') . '</a>
    </li>
    </ul>
    <hr style="visibility: hidden; clear:both;" />';

if (isset($_GET['saveconfig']))
{
    echo '<p class="message">' . $msg . '</p>';
    $mail = 'function _mail ($to, $subject, $message, $headers)
{
    require_once (dirname(__FILE__)."/../plugins/mailjet/inc/class.dc.mailjet.php");

    Mailjet::sendMail ($to, $subject, $message, $headers);
}
?>';
    $cf = dirname(__FILE__) . '/../../inc/config.php';

    if ($settings->mj_enabled)
    {
        //try and edit config.php file
        $contents = str_replace('?>', $mail, file_get_contents($cf));

        if (!function_exists('_mail') && @file_put_contents($cf, $contents) === false)
        {
            echo '<p class="message">'.$enabled.'</p>';
?>
        <pre>
function _mail($to, $subject, $message, $headers)
{
    require_once (dirname(__FILE__)."/../plugins/mailjet/inc/class.dc.mailjet.php");

    Mailjet::sendMail ($to, $subject, $message, $headers);
}
    </pre>
<?php
        }
        else
        { //config file updated automatically
            if ((bool)$_GET['wasEnabled'] != $settings->mj_enabled)
                echo '<p class="message">' . __('Mailjet plugin enabled') . '</p>';
        }
    }
    else
    { //Disable plugin
        $contents = str_replace( $mail, '?>', file_get_contents($cf));

        if ( function_exists('_mail') && @file_put_contents($cf, $contents) === false){
            echo '<p class="message">' . $disabled . '</p>';
            ?>
        <pre>
function _mail($to, $subject, $message, $headers)
{
    require_once (dirname(__FILE__) . "/../plugins/mailjet/inc/class.dc.mailjet.php");

    Mailjet::sendMail ($to, $subject, $message, $headers);
}
    </pre>
<?php
        }
        else
        { //config file updated automatically
            if ((bool)$_GET['wasEnabled'] != $settings->mj_enabled)
                echo '<p class="message">' . __('Mailjet plugin disabled') . '</p>';
        }
    }
}

if (isset($_GET['lists']) && isset($_GET['added']))
    echo '<p class="message">' . __('List added sucessfully').'</p>';

if (isset($_GET['lists']) && isset($_GET['removed']))
    echo '<p class="message">' . __('List removed sucessfully').'</p>';

if (isset($_GET['edit']) && isset($_GET['id']) && isset($_GET['removedContacts']))
    echo '<p class="message">' . __('Contact(s) removed successfully').'</p>';

if (isset($_GET['edit']) && isset($_GET['id']) && isset($_GET['added']))
    echo '<p class="message">' . __('Contact(s) subscribed successfully').'</p>';

if (!$step)
{
	if (isset($_GET['lists']))
	{
?>

<div class="multi-part" id="mailjetlists" title="<?php echo __('Lists'); ?>">
<?php
if (!$step)
{
    echo '<p><a href="#" class="add-contact">'.__('New list').'</a></p>';
    echo '<form id="additem" class="hidden-form" action="' . $p_url . '&add=2" method="post" style="display:none;">';
    echo '<fieldset><legend>' . __('Create list') . '</legend>';
    echo '<p class="field"><label for"item_type" class="classic">' . __('List label:') . '</label>' . form::field ('list_label', 50, 50, '') . '</p>';
    echo '<p>' . $core->formNonce() . '<input type="submit" name="appendaction" value="' . __('Create list') . '" />'.'</p>';
    echo '</fieldset>';
    echo '</form>';
}
?>
    <form method="post" action="<?php echo $p_url; ?>">
        <table class="maximal">
            <thead>
            <tr>
                <th colspan="2"><?php echo __('Label'); ?></th>
                <th><?php echo __('Name'); ?></th>
                <th><?php echo __('Number of contacts'); ?></th>
                <th><?php echo __('ID'); ?></th>
            </tr>
            </thead>
            <tbody id="lists-list">
<?php
                $r = $api->listsAll();
                if (isset($r->status) && $r->status == 'OK')
                {
                    $lists = $r->lists;
                    foreach ($lists as $list)
                    {
					    echo
                            '<tr class="line" id="list_' . $list->id . '">'.
                            '<td class="minimal">' . form::checkbox(array('remove[]'), $list->id, '', '', '', false, 'title="' . __('select this list') . '"') . '</td>';

                        echo
                            '<td><a href="' . $p_url . '&amp;edit=1&amp;id=' . $list->id . '">'.
                            html::escapeHTML($list->label) . '</a></td>'.
                            '<td>' . html::escapeHTML($list->name) . '</td>'.
                            '<td>' . html::escapeHTML($list->subscribers) . '</td>'.
                            '<td>' . html::escapeHTML($list->id) . '</td>';

                        echo '</tr>';
                    }
                }
                else
                {
                    echo '<tr class="line">
                <td colspan="6">No lists available</td>
                </tr>';
                }
?>
            </tbody>
        </table>
<?php
        echo '<div class="two-cols">';
        echo '<p class="col">' . form::hidden('im_order', '') . $core->formNonce();
        echo '</p>';
        echo '<p class="col right">'.'<input type="submit" class="delete" name="removeaction" '.
            'value="' . __('Delete selected lists') . '" '.
            'onclick="return window.confirm(\'' . html::escapeJS(__('Are you sure you want to remove selected lists?')) . '\');" />'.
            '</p>';
        echo '</div>';
?>
    </form>
</div>
<?php
}
elseif(isset($_GET['edit']))
{
    //TODO get contacts from API and show here
    $r = $api->listsContacts(array('id' => $_GET['id']));

    if ($r->status == 'OK')
        $contact = $r->result;
?>

<div class="multi-part" id="mailjetlistcontacts" title="<?php echo sprintf(__('Contacts of %s'), $list->label); ?>">
<?php

if (!$step)
{
    echo '<p><a href="#" class="add-contact">' . __('New contact') . '</a></p>';

    echo '<form id="listcontactsappend" class="hidden-form" action="' . $p_url . '&addContact=1&lists=1" method="post" style="display:none">';
    echo '<fieldset><legend>' . __('Create list') . '</legend>';
    echo '<input type="hidden" name="list_id" value="' . $list->id . '" />';
    echo '<label>' . __('Contact email') . '<input type="email" name="email" value="" /></label>';
    echo '<p>' . $core->formNonce() . '<input class="add" type="submit" name="appendcontactaction" value="' . __('Subscribe new contact') . '" /></p>';
    echo '</fieldset>';
    echo '</form>';
}
?>
    <form method="post" action="<?php echo $p_url; ?>&contacts=1">
        <table class="maximal">
            <thead>
            <tr>
                <th colspan="2"><?php echo __('Email'); ?></th>
                <th><?php echo __('Created on'); ?></th>
                <th><?php echo __('Last activity'); ?></th>
                <th><?php echo __('Messages sent'); ?></th>
                <th><?php echo __('ID'); ?></th>
            </tr>
            </thead>
            <tbody id="contacts-list">
<?php
                $r = $api->listsContacts(array('id' => $_GET['id']));

                if ($r && $r->status=='OK')
                {
                    $contacts = $r->result;
                    foreach ($contacts as $contact)
                    {
                        echo
                            '<tr class="line" id="contact_' . $contact->id . '">'.
                            '<td class="minimal">' . form::checkbox(array('remove[]'), $contact->email, '', '', '', false, 'title="' . __('select this contact') . '"') . '</td>';

                        echo
                            '<td>' . html::escapeHTML($contact->email) . '</td>'.
                            '<td>' . html::escapeHTML(date('d/m/Y', $contact->created_at)) . '</td>'.
                            '<td>' . html::escapeHTML(date('d/m/Y', $contact->last_activity)) . '</td>'.
                            '<td>' . html::escapeHTML($contact->sent) . '</td>'.
                            '<td>' . html::escapeHTML($contact->id) . '</td>';

                        echo '</tr>';
                    }
                }
                else
                {
                    echo '<tr class="line">
                <td colspan="6">No Contacts for this list.</td>
                </tr>';
                }
?>
            </tbody>
        </table>
<?php
        echo '<div class="two-cols">';
        echo '<p class="col">' . form::hidden('im_order', '') . $core->formNonce();
        echo '</p>';
        echo '<input type="hidden" name="list_id" value="' . $list->id . '" />';
        echo '<p class="col right"><input type="submit" class="delete" name="removecontactsaction" '.
            'value="' . __('Delete selected contacts') . '" '.
            'onclick="return window.confirm(\'' . html::escapeJS(__('Are you sure you want to remove selected contacts from this list?')) . '\');" />'.
            '</p>';
        echo '</div>';
        ?>
    </form>
</div>
<?php
} else {
?>
<div class="multi-part" id="settings" title="<?php echo __('Settings'); ?>">
    <form method="post" action="<?php echo $p_url; ?>">
        <fieldset>
            <legend><?php echo __('General settings'); ?></legend>
            <p>
                <label class="classic"><?php echo __('Enabled :') . ' ' . form::checkbox ('mj_enabled', '1', $fields['mj_enabled']); ?></label>
            </p>
            <p>
                <label class="classic"><?php echo __('Send test mail now :') . ' ' . form::checkbox ('mj_test', '1', $fields['mj_test']); ?></label>
            </p>
            <p>
                <label class="classic"><?php echo __('Recipient of test mail :') . ' ' . form::field ('mj_test_address', 50, 50, $fields['mj_test_address']); ?></label>
            </p>
            <p>
                <label class="classic"><?php echo __('Sender email address :') . ' ' . form::field ('mj_sender_address', 50, 50, ($fields['mj_sender_address'] ? $fields['mj_sender_address'] : DC_ADMIN_MAILFROM)); ?></label>
            </p>
        </fieldset>
        <fieldset>
            <legend><?php echo __('Mailjet settings'); ?></legend>
            <p>
                <label class="classic"><?php echo __('API Key :') . ' ' . form::field ('mj_username', 32, 32, $fields['mj_username']); ?></label>
            </p>
            <p>
                <label class="classic"><?php echo __('Secret Key :') . ' ' . form::field ('mj_password', 32, 32, $fields['mj_password']); ?></label>
            </p>
        </fieldset>
        <p>
            <?php echo $core->formNonce(); ?>
        </p>
        <p>
            <input type="submit" name="saveconfig" value="<?php echo __('Save configuration'); ?>" />
        </p>
    </form>
</div>
<?php }
}
?>
</body>
</html>