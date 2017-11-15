<?php

//
require '../includes/class/api.client.inc.php';


?><form method="POST" action="http://<?= $_SERVER['HTTP_HOST'] ?>/api/image/?api_key=<?= MY_AUTH_KEY ?>" enctype="multipart/form-data">
    <input type="file" name="image" /><br />
    <input type="text" name="directory" value="repertoire1" /><br />
    <input type="submit" />
        
</form>