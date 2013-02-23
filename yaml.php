<?php
include('Spyc.php');

if (isset($_POST['path'])) {
    $data = Spyc::YAMLLoad($_POST['path']);

    foreach ($data as $kek => $row) {
        $final = str_replace("\\'", "'", $_POST['format']);
        $final = str_replace('__KEY__', $kek, $final);
        foreach ($row as $k => $v) {
            $final = str_replace('__' . $k . '__', trim($v), $final);
        }
        echo $final;
    }
}
?>

<form action="yaml.php" method="POST">
    <textarea name="format"></textarea>
    <input type="text" name="path"/>
    <input type="submit"/>
</form>