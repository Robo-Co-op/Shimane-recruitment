<?php
echo "pgsql: "      . (extension_loaded('pgsql') ? 'YES' : 'NO') . "\n";
echo "pdo_pgsql: "  . (extension_loaded('pdo_pgsql') ? 'YES' : 'NO') . "\n";
echo "pdo_mysql: "  . (extension_loaded('pdo_mysql') ? 'YES' : 'NO') . "\n";
echo "pdo_sqlite: " . (extension_loaded('pdo_sqlite') ? 'YES' : 'NO') . "\n";
echo "curl: "       . (extension_loaded('curl') ? 'YES' : 'NO') . "\n";
echo "php: "        . PHP_VERSION . "\n";
