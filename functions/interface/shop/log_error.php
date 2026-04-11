<?php

if (isset($_POST['error'])) {
    error_log($_POST['error']);
}