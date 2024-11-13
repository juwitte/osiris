<?php
return [
    'clientId' => '<CLIENT_ID>',
    'clientSecret' => '<CLIENT_SECRET>',
    'redirectUri' => 'http://localhost/user/oauth',
    'urlAuthorize' => 'https://login.microsoftonline.com/<TENANT_ID>/oauth2/v2.0/authorize',
    'urlAccessToken' => 'https://login.microsoftonline.com/<TENANT_ID>/oauth2/v2.0/token',
    'urlResourceOwnerDetails' => '',
    'scopes' => 'openid profile email',
];
