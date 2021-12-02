<?php
if (fsockopen('3dcart.creditkey.com', 80)) {
    echo('Server is Online');
} else {
    echo('Server is Offline');
}
?>