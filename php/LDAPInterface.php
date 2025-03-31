<?php

require_once 'Groups.php';

class LDAPInterface
{
    public $attributes = [];

    private $connection;
    private $bind;
    private $openldap = false;
    private $userkey = 'samaccountname';
    private $uniqueid = 'entryUUID';

    private $keys = [
        "username" => "samaccountname",
        "first" => "givenname",
        "last" => "sn",
        "displayname" => "cn",
        "unit" => "description",
        "telephone" => "telephonenumber",
        "mail" => "mail",
        "uniqueid" => "entryUUID",
        // "academic_title" => "title",
        "room" => "physicaldeliveryofficename",
    ];

    public function __construct()
    {
        $this->openldap = defined('OPEN_LDAP') && OPEN_LDAP;
        if ($this->openldap) {
            $this->userkey = 'uid';
            $this->attributes = ['cn', 'mail', 'uid', 'givenName', 'sn', 'ou', 'employeetype'];
            $this->keys = [
                "username" => "uid",
                "first" => "givenname",
                "last" => "sn",
                "displayname" => "cn",
                "unit" => "ou",
                "telephone" => "telephonenumber",
                "mail" => "mail",
                "uniqueid" => "entryuuid",
                "position" => "title",
                "room" => "roomnumber",
            ];
        }

        $this->attributes = array_values($this->keys);
        $this->connect();
    }

    private function connect()
    {
        $server = LDAP_IP;
        $port = LDAP_PORT;
        $useSSL = defined('LDAP_USE_SSL') ? LDAP_USE_SSL : false;
        $useTLS = defined('LDAP_USE_TLS') ? LDAP_USE_TLS : false;

        $protocol = $useSSL ? "ldaps://" : "ldap://";
        $this->connection = ldap_connect($protocol . $server . ':' . $port);

        if (!$this->connection) {
            throw new Exception("Could not connect to LDAP server.");
        }

        ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->connection, LDAP_OPT_REFERRALS, 0);

        if ($useTLS && !$useSSL) {
            if (!ldap_start_tls($this->connection)) {
                throw new Exception("Could not start TLS connection.");
            }
        }
    }

    public function bind($username, $password)
    {
        if (!defined('LDAP_DOMAIN')) {
            throw new Exception("LDAP_DOMAIN is not defined.");
        }
        if (str_starts_with($username, 'cn=')) {
            $dn = $username;
        } else if (str_contains(LDAP_DOMAIN, '%s')) {
            $dn = sprintf(LDAP_DOMAIN, $username);
        } else {
            $dn = $username . LDAP_DOMAIN;
        }
        $this->bind = @ldap_bind($this->connection, $dn, $password);

        if (!$this->bind) {
            $error = ldap_error($this->connection);
            echo lang("Error while connecting to the LDAP server:", "Fehler bei der Verbindung mit dem LDAP-Server: ") . $error;
            return false;
        }
        return true;
    }

    public function fetchUser($username)
    {
        if (!$this->bind(LDAP_USER, LDAP_PASSWORD)) {
            echo "Internal error: cannot search LDAP.";
            return null;
        }
        // dynamically build search filter based on primary user id (samaccountname or uid)
        $userSearchFilter = "(" . $this->userkey . "=%s)";
        $searchFilter = sprintf($userSearchFilter, $username);
        $searchBase = LDAP_BASEDN;
        $searchResult = ldap_search($this->connection, $searchBase, $searchFilter, $this->attributes);

        if (!$searchResult) {
            return null;
        }

        $entries = ldap_get_entries($this->connection, $searchResult);

        if ($entries["count"] > 0) {
            return $entries[0];
        }
        return null;
    }

    public function fetchUsers($filter = '(cn=*)', array $attributes = [])
    {
        if (!$this->bind(LDAP_USER, LDAP_PASSWORD)) {
            echo "Internal error: cannot search LDAP.";
            return null;
        }

        $res = array();
        $cookie = '';

        do {
            $filter = '(cn=*)';
            // overwrite filter if set in CONFIG
            if (defined('LDAP_FILTER') && !empty(LDAP_FILTER)) $filter = LDAP_FILTER;

            $result = @ldap_search(
                $this->connection,
                LDAP_BASEDN,
                $filter,
                [],
                0,
                0,
                0,
                LDAP_DEREF_NEVER,
                [['oid' => LDAP_CONTROL_PAGEDRESULTS, 'value' => ['size' => 1000, 'cookie' => $cookie]]]
            );

            if ($result === false) {
                $error = ldap_error($this->connection);
                return "Fehler bei der LDAP-Suche: " . $error;
            }

            $parseResult = ldap_parse_result($this->connection, $result, $errcode, $matcheddn, $errmsg, $referrals, $controls);
            if ($parseResult === false) {
                $error = ldap_error($this->connection);
                return "Fehler beim Parsen des LDAP-Ergebnisses: " . $error;
            }

            $entries = ldap_get_entries($this->connection, $result);
            if ($entries === false) {
                $error = ldap_error($this->connection);
                return "Fehler beim Abrufen der LDAP-Einträge: " . $error;
            }
            foreach ($entries as $entry) {
                if (!isset($entry[$this->userkey][0])) {
                    continue;
                }
                if (!empty($attributes)) {
                    if (!in_array($this->userkey, $attributes)) {
                        // Add the user key to the attributes array
                        $attributes[] = $this->userkey;
                    }
                    $entry = array_filter($entry, function ($key) use ($attributes) {
                        // Filter out the keys that are not in the attributes array
                        return in_array($key, $attributes);
                    }, ARRAY_FILTER_USE_KEY);
                }

                $res[] = $entry;
            }

            if (isset($controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'])) {
                $cookie = $controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'];
            } else {
                $cookie = '';
            }
        } while (!empty($cookie));

        // $res = array_map([$this, 'cleanLdapEntry'], array_slice($res, 0, $res['count']));
        return $res;
    }

    public function fetchUserActivity()
    {
        // dynamically build search filter based on primary user id (samaccountname or uid)

        if (!$this->bind(LDAP_USER, LDAP_PASSWORD)) {
            echo "Internal error: cannot search LDAP.";
            return null;
        }

        $entries = $this->fetchUsers();
        if (empty($entries)) {
            return null;
        }

        $key_active = 'useraccountcontrol';
        $key_expires = 'accountexpires';

        $res = array();
        foreach ($entries as $entry) {
            $accountControl = isset($entry[$key_active][0]) ? (int)$entry[$key_active][0] : 0;
            $accountExpires = isset($entry[$key_expires][0]) ? (int)$entry[$key_expires][0] : 0;

            $isDisabled = ($accountControl & 2); // 2 = ACCOUNTDISABLE
            $isExpired = ($accountExpires != 0 && $accountExpires <= time() * 10000000 + 116444736000000000);

            $active = !$isDisabled && !$isExpired;

            $res[$entry[$this->userkey][0]] = $active;
        }

        return $res;
    }


    function login($username, $password)
    {
        $return = array("msg" => '', "success" => false);

        // Step 1: User-Bind (zum Prüfen der Credentials)
        if (!$this->bind($username, $password)) {
            $return['msg'] = "Login failed or user not found.";
            return $return;
        }

        // Step 2: Re-bind mit Admin für Suche
        if (!$this->bind(LDAP_USER, LDAP_PASSWORD)) {
            $return['msg'] = "Internal error: cannot search LDAP.";
            return $return;
        }

        // Step 3: Suche nach dem Benutzer (um Daten zu holen)
        $filter = "(" . $this->userkey . "=" . ldap_escape($username, "", LDAP_ESCAPE_FILTER) . ")";
        $search = ldap_search($this->connection, LDAP_BASEDN, $filter);
        if ($search === false) {
            $return['msg'] = "Login failed or user not found.";
            return $return;
        }

        $result = ldap_get_entries($this->connection, $search);

        $ldap_username = $result[0][$this->userkey][0];
        $ldap_first_name = $result[0]['givenName'][0] ?? '';
        $ldap_last_name = $result[0]['sn'][0] ?? '';

        $_SESSION['username'] = $ldap_username;
        $_SESSION['name'] = $ldap_first_name . " " . $ldap_last_name;
        $_SESSION['loggedin'] = true;

        $return["status"] = true;

        return $return;
    }

    public function newUser($username)
    {
        $Groups = new Groups();

        $user = $this->fetchUser($username);
        if (empty($user) || $user['count'] == 0) {
            return null;
        }

        $units = [];
        $person = [];

        foreach ($this->keys as $key => $name) {
            // dump($value, true);
            if ($key == 'unit' && isset($user[$name])) {
                $units = $user[$name];
            } else {
                $person[$key] = $user[$name][0] ?? null;
            }
        }

        $person['units'] = [];
        if (!empty($units)) {
            unset($units['count']);
            foreach ($units as $unit) {
                $unit = $Groups->findGroup($unit);
                if (!empty($unit) && !empty($unit['id'])) {
                    $person['units'][] = [
                        'id' => uniqid(),
                        'unit' => $unit['id'],
                        'start' => null,
                        'end' => null,
                        'scientific' => true
                    ];
                }
            }
        }

        $person['orcid'] = null;
        $person['formalname'] = $person['last'] . ', ' . $person['first'];
        $person['academic_title'] = null;
        $accountControl = isset($user['useraccountcontrol'][0]) ? (int)$user['useraccountcontrol'][0] : 0;
        $person['is_active'] = !($accountControl & 2); // 2 entspricht ACCOUNTDISABLE

        $person['created'] = date('Y-m-d');
        $person['roles'] = [];

        return $person;
    }

    public function synchronizeAttributes(array $ldapMappings, $osiris)
    {
        $users = $this->fetchUsers();

        if (!is_array($users)) {
            echo "Fehler beim Abrufen der Benutzer aus LDAP.";
            return false;
        }

        foreach ($users as $entry) {
            $username = $entry[$this->userkey][0] ?? null;
            if (!$username) continue;

            $userData = [];

            foreach ($ldapMappings as $osirisKey => $ldapKey) {
                if (empty($ldapKey)) {
                    $userData[$osirisKey] = null;
                } else {
                    $userData[$osirisKey] = $entry[$ldapKey][0] ?? null;
                }
            }

            $userData['username'] = $username;

            // Update in der Datenbank speichern
            // Beispiel mit MongoDB:
            $osiris->persons->updateOne(
                ['username' => $username],
                ['$set' => $userData],
                ['upsert' => true]
            );
        }

        return true;
    }

    public function close()
    {
        ldap_unbind($this->connection);
    }
}
