<?php
/** Atsijungimas — sunaikina sesiją (public/logout.php). */
require_once __DIR__ . "/../lib/bootstrap.php";

logout_user();
redirect("index.php");
