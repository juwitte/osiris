
<?php
// apt-get install php-ldap

require_once 'LDAPInterface.php';

// $LDAP = new LDAPInterface();

// if (!defined('LDAP_IP')) {
//     if (file_exists('CONFIG.php')) {
//         require_once 'CONFIG.php';
//     } else {
//         require_once 'CONFIG.default.php';
//     }
// }
// if (!defined('LDAP_IP') || !defined('LDAP_PORT') || !defined('LDAP_USER') || !defined('LDAP_DOMAIN') || !defined('LDAP_BASEDN') || !defined('LDAP_PASSWORD')) {
//     die("LDAP Settings are missing. Please enter details in CONFIG.php or use AUTH as USER_MANAGEMENT.");
// }


require_once BASEPATH . '/php/Groups.php';

function login($username, $password)
{
    // $return = array("msg" => '', "success" => false);
    $LDAP = new LDAPInterface();

    return $LDAP->login($username, $password);
}

function getUser($name)
{
    $LDAP = new LDAPInterface();
    return $LDAP->fetchUser($name);
}

function getUsers()
{
    $LDAP = new LDAPInterface();
    return $LDAP->fetchUserActivity();
    // $return = [];
    // $username = LDAP_USER;
    // $password = LDAP_PASSWORD;
    // $base_dn = LDAP_BASEDN;

    // $connect = LDAPconnect($username, $password);
    // if (is_string($connect)) {
    //     $return['msg'] = $connect;
    //     return $return;
    // }

    // $res = array();
    // $cookie = '';

    // do {
    //     $filter = '(cn=*)';
    //     // overwrite filter if set in CONFIG
    //     if (defined('LDAP_FILTER') && !empty(LDAP_FILTER)) $filter = LDAP_FILTER;
    //     $attributes = ['samaccountname', 'useraccountcontrol', 'accountexpires'];

    //     $result = @ldap_search(
    //         $connect,
    //         $base_dn,
    //         $filter,
    //         $attributes,
    //         0,
    //         0,
    //         0,
    //         LDAP_DEREF_NEVER,
    //         [['oid' => LDAP_CONTROL_PAGEDRESULTS, 'value' => ['size' => 1000, 'cookie' => $cookie]]]
    //     );

    //     if ($result === false) {
    //         $error = ldap_error($connect);
    //         ldap_close($connect);
    //         return "Fehler bei der LDAP-Suche: " . $error;
    //     }

    //     $parseResult = ldap_parse_result($connect, $result, $errcode, $matcheddn, $errmsg, $referrals, $controls);
    //     if ($parseResult === false) {
    //         $error = ldap_error($connect);
    //         ldap_close($connect);
    //         return "Fehler beim Parsen des LDAP-Ergebnisses: " . $error;
    //     }

    //     $entries = ldap_get_entries($connect, $result);
    //     if ($entries === false) {
    //         $error = ldap_error($connect);
    //         ldap_close($connect);
    //         return "Fehler beim Abrufen der LDAP-Einträge: " . $error;
    //     }

    //     if (!defined('OPEN_LDAP') || !OPEN_LDAP) {
    //         $key_user = 'samaccountname';
    //     } else {
    //         $key_user = 'uid';
    //     }
        
    //     $key_active = 'useraccountcontrol';
    //     $key_expires = 'accountexpires';

    //     foreach ($entries as $entry) {
    //         if (!isset($entry[$key_user][0])) {
    //             continue;
    //         }

    //         $accountControl = isset($entry[$key_active][0]) ? (int)$entry[$key_active][0] : 0;
    //         $accountExpires = isset($entry[$key_expires][0]) ? (int)$entry[$key_expires][0] : 0;
            
    //         $isDisabled = ($accountControl & 2); // 2 = ACCOUNTDISABLE
    //         $isExpired = ($accountExpires != 0 && $accountExpires <= time() * 10000000 + 116444736000000000);

    //         $active = !$isDisabled && !$isExpired;

    //         $res[$entry[$key_user][0]] = $active;
    //     }

    //     if (isset($controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'])) {
    //         $cookie = $controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'];
    //     } else {
    //         $cookie = '';
    //     }
    // } while (!empty($cookie));

    // ldap_close($connect);
    // return $res;
}

function newUser($username)
{
    $LDAP = new LDAPInterface();
    return $LDAP->newUser($username);
}
