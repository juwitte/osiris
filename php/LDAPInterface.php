<?php

require_once 'Groups.php';

class LDAPInterface
{
    public $attributes = [];

    private $connection;
    private $bind;
    private $openldap = false;
    private $userkey = 'samaccountname';
    private $uniqueid = 'objectguid';

    private $keys = [
        "username" => "samaccountname",
        "first" => "givenname",
        "last" => "sn",
        "displayname" => "cn",
        "unit" => "description",
        "telephone" => "telephonenumber",
        "mail" => "mail",
        "uniqueid" => "objectguid",
        // "academic_title" => "title",
        "room" => "physicaldeliveryofficename",
    ];

    public function __construct()
    {
        $this->openldap = defined('OPEN_LDAP') && OPEN_LDAP;
        if ($this->openldap) {
            $this->userkey = 'uid';
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
            $this->uniqueid = 'entryuuid';
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
                $this->attributes,
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
        $return = array("msg" => '', "success" => false, 'uniqueid' => null);
        if (empty($username) || empty($password)) {
            $return['msg'] = lang("Please enter your username and password.", "Bitte geben Sie Ihren Benutzernamen und Ihr Passwort ein.");
            return $return;
        }
        if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
            $return['msg'] = lang("You are already logged in.", "Sie sind bereits angemeldet.");
            return $return;
        }

        // Step 1: User-Bind (zum Prüfen der Credentials)
        if (!$this->bind($username, $password)) {
            $return['msg'] = lang("Login failed. Please check your username and password.", "Anmeldung fehlgeschlagen. Bitte überprüfen Sie Ihren Benutzernamen und Ihr Passwort.");
            return $return;
        }

        // Step 2: Re-bind mit Admin für Suche
        if (!$this->bind(LDAP_USER, LDAP_PASSWORD)) {
            $return['msg'] = "Internal error: cannot search LDAP.";
            return $return;
        }

        // Step 3: Suche nach dem Benutzer (um Daten zu holen)
        $filter = "(" . $this->userkey . "=" . ldap_escape($username, "", LDAP_ESCAPE_FILTER) . ")";
        $search = ldap_search($this->connection, LDAP_BASEDN, $filter, $this->attributes);
        if ($search === false) {
            $return['msg'] = lang("Error while searching for the user in LDAP.", "Fehler bei der Suche nach dem Benutzer in LDAP.");
            return $return;
        }

        $result = ldap_get_entries($this->connection, $search);
        if ($result === false || $result['count'] == 0) {
            $return['msg'] = lang("User not found in LDAP.", "Benutzer nicht in LDAP gefunden.");
            return $return;
        }
        $result = $result[0];
        if (empty($result[$this->userkey][0])) {
            $return['msg'] = lang("User not found in LDAP or LDAP misconfigured.", "Benutzer nicht in LDAP gefunden oder LDAP falsch konfiguriert.");
            return $return;
        }
        $ldap_username = $result[$this->userkey][0];
        $ldap_first_name = $result['givenName'][0] ?? '';
        $ldap_last_name = $result['sn'][0] ?? '';
        $ldap_uniqueid = $result[$this->uniqueid][0] ?? null;
        if ($this->uniqueid == 'objectguid') {
            $ldap_uniqueid = $this->convertObjectGUID($ldap_uniqueid);
        }
        $return['uniqueid'] = $ldap_uniqueid;

        $_SESSION['username'] = $ldap_username;
        $_SESSION['name'] = $ldap_first_name . " " . $ldap_last_name;
        $_SESSION['loggedin'] = true;

        $return["success"] = true;

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

        $person['uniqueid'] = $user[$this->uniqueid][0] ?? null;
        if ($this->uniqueid == 'objectguid') {
            $person['uniqueid'] = $this->convertObjectGUID($person['uniqueid']);
        }

        return $person;
    }

    public function synchronizeAttributes(array $ldapMappings, $osiris)
    {
        $Groups = new Groups();
        $users = $this->fetchUsers();

        if (!is_array($users)) {
            echo "Fehler beim Abrufen der Benutzer aus LDAP.";
            return false;
        }

        foreach ($users as $entry) {
            $username = $entry[$this->userkey][0] ?? null;
            if (!$username) continue;
            echo $username . "\n";

            $userData = [];

            foreach ($ldapMappings as $osirisKey => $ldapKey) {
                if (empty($ldapKey)) {
                    $userData[$osirisKey] = null;
                } else {
                    $userData[$osirisKey] = $entry[$ldapKey][0] ?? null;
                }
            }

            $userData['username'] = $username;
            $userData['uniqueid'] = $entry[$this->uniqueid][0] ?? null;
            $userData['updated'] = date('Y-m-d');

            // TODO: update units
            if (isset($ldapMappings['department']) && !empty($ldapMappings['department'] ?? null)) {
                // first get the current units
                    $currentUnits = [];
                $currentUser = $osiris->persons->findOne(['username' => $username], ['projection' => ['units' => 1]]);
                if (!empty($currentUser)) {
                    foreach ($currentUser['units'] as $unit) {
                        $group = $Groups->getGroup($unit['unit']);
                        if (!empty($group)) {
                            $currentUnits[] = [
                                'id' => $unit['id'],
                                'unit' => $group['id'],
                                'name' => $group['name'],
                                'name_de' => $group['name_de'] ?? null,
                                'start' => $unit['start'],
                            ];
                        }
                    }
                }
                var_dump($currentUnits, true);
                // then get the new units
                $newUnits = $entry[$ldapMappings['department']];
                if (empty($newUnits)) {
                    $newUnits = [];
                } else {
                    unset($newUnits['count']);
                    foreach ($newUnits as $unit) {
                        $unit = $Groups->findGroup($unit);
                        if (!empty($unit) && !empty($unit['id'])) {
                            // unit is a group

                            // next: check if unit is already in the current units
                            $found = false;
                            foreach ($currentUnits as $currentUnit) {
                                if ($currentUnit['unit'] == $unit['id']) {
                                    $found = true;
                                    break;
                                }
                            }
                            if (!$found) {
                                $newUnits[] = [
                                    'id' => uniqid(),
                                    'unit' => $unit['id'],
                                    'name' => $unit['name'],
                                    'name_de' => $unit['name_de'] ?? null,
                                    'start' => null,
                                ];
                            }
                        }
                    }
                }

                var_dump($newUnits, true);
                // then compare the new units with the current units
                // if they are different, update the units

            }
            return true;
        //     $person['units'] = [];
        // if (!empty($units)) {
        //     unset($units['count']);
        //     foreach ($units as $unit) {
        //         $unit = $Groups->findGroup($unit);
        //         if (!empty($unit) && !empty($unit['id'])) {
        //             $person['units'][] = [
        //                 'id' => uniqid(),
        //                 'unit' => $unit['id'],
        //                 'start' => null,
        //                 'end' => null,
        //                 'scientific' => true
        //             ];
        //         }
        //     }
        // }


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

    public function convertObjectGUID($bin)
    {
        if (empty($bin)) {
            return null;
        }
        if (strlen($bin) != 16) {
            return $bin;
        }
        // Convert binary GUID to hex string
        $hex = bin2hex($bin);
        return vsprintf('%s%s%s%s-%s%s-%s%s-%s%s-%s%s%s%s%s%s', str_split($hex, 2));
    }

    public function close()
    {
        ldap_unbind($this->connection);
    }
}
