AuthType Basic
AuthName "VKU-Basel Export"
AuthUserFile {{HTPASSWD}}
Require valid-user

<IfModule mod_headers.c>
    Header always set X-Robots-Tag "noindex, nofollow"
    # LiteSpeed (cyon) schickt WWW-Authenticate auch bei 200 mit; Safari fragt dann erneut nach dem Passwort.
    Header unset WWW-Authenticate
</IfModule>
