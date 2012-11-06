<?php
$cmd = "ffmpeg/ffmpeg -i uploads/capturedvideo.MOV test.wav";
exec($cmd." 2>&1", $out, $ret);
if ($ret){
    echo "There was a problem!\n";
    print_r($out);
}else{
	print_r($out);
    echo "Everything went better than expected!\n";
}
?>