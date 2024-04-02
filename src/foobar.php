<?php
function foobar($x, $y) {
    foreach(range($x, $y) as $i) {
        if ($i > 1) echo ", ";   
        if ($i % 3 == 0) echo "foo";
        if ($i % 5 == 0) echo "bar";
        if ($i % 3 && $i % 5) echo $i;
    }
    echo "\n";
}

foobar(1, 100);
?>