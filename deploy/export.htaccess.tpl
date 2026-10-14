AuthType Basic
AuthName "VKU-Basel Export"
AuthUserFile {{HTPASSWD}}
Require valid-user

<IfModule mod_headers.c>
    Header always set X-Robots-Tag "noindex, nofollow"
</IfModule>
