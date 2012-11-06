<?php
  
  ini_set("max_execution_time", "30000");
  
  // how much detail we want. Larger number means less detail
  // (basically, how many bytes/frames to skip processing)
  // the lower the number means longer processing time
  define("DETAIL", 5);
  
  define("DEFAULT_WIDTH", 500);
  define("DEFAULT_HEIGHT", 100);
  define("DEFAULT_FOREGROUND", "#FF0000");
  define("DEFAULT_BACKGROUND", "#000000");
  
  /**
   * GENERAL FUNCTIONS
   */
  function findValues($byte1, $byte2){
    $byte1 = hexdec(bin2hex($byte1));                        
    $byte2 = hexdec(bin2hex($byte2));                        
    return ($byte1 + ($byte2*256));
  }
  

    /**
     * PROCESS THE FILE
     */
  
    // temporary file name
    $tmpname = substr(md5(time()), 0, 10);
    
    
		// support for stereo waveform?
    $stereo = false;
   
		// array of wavs that need to be processed
    $wavs_to_process = array();
    
    
      $wavs_to_process[] = "test.wav";
    
    
    // Could just print to the output buffer, but saving to a variable
    // makes it easier to display the SVG and dump it to a file without
    // any messy ob_*() hassle
    $svg  = "<html><head>\n";
    $svg .= "<link rel=\"stylesheet\" href=\"waveform.css\" type=\"text/css\">\n";
   
     $svg .= "<script src=\"http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js\"></script></head><body>";
    $svg .= "<svg width=\"100%\" height=\"50%\" version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\">\n";
		// rect for background color
    $svg .= "<rect width=\"100%\" height=\"50%\" />\n";
    
    $y_offset = floor(1 / sizeof($wavs_to_process) * 100);
    
    // process each wav individually
    for($wav = 1; $wav <= sizeof($wavs_to_process); $wav++) {
    
      $svg .= "<svg y=\"" . ($y_offset * ($wav - 1)) . "%\" width=\"100%\" height=\"{$y_offset}%\">";
 
      $filename = $wavs_to_process[$wav - 1];
    
      /**
       * Below as posted by "zvoneM" on
       * http://forums.devshed.com/php-development-5/reading-16-bit-wav-file-318740.html
       * as findValues() defined above
       * Translated from Croation to English - July 11, 2011
       */
      $handle = fopen($filename, "r");
      // wav file header retrieval
      $heading[] = fread($handle, 4);
      $heading[] = bin2hex(fread($handle, 4));
      $heading[] = fread($handle, 4);
      $heading[] = fread($handle, 4);
      $heading[] = bin2hex(fread($handle, 4));
      $heading[] = bin2hex(fread($handle, 2));
      $heading[] = bin2hex(fread($handle, 2));
      $heading[] = bin2hex(fread($handle, 4));
      $heading[] = bin2hex(fread($handle, 4));
      $heading[] = bin2hex(fread($handle, 2));
      $heading[] = bin2hex(fread($handle, 2));
      $heading[] = fread($handle, 4);
      $heading[] = bin2hex(fread($handle, 4));
      
      // wav bitrate 
      $peek = hexdec(substr($heading[10], 0, 2));
      $byte = $peek / 8;
      
      // checking whether a mono or stereo wav
      $channel = hexdec(substr($heading[6], 0, 2));
      
      $ratio = ($channel == 2 ? 40 : 80);
      
      // start putting together the initial canvas
      // $data_size = (size_of_file - header_bytes_read) / skipped_bytes + 1
      $data_size = floor((filesize($filename) - 44) / ($ratio + $byte) + 1);
      $data_point = 0;

      while(!feof($handle) && $data_point < $data_size){
        if ($data_point++ % DETAIL == 0) {
          $bytes = array();
          
          // get number of bytes depending on bitrate
          for ($i = 0; $i < $byte; $i++)
            $bytes[$i] = fgetc($handle);
          
          switch($byte){
            // get value for 8-bit wav
            case 1:
              $data = findValues($bytes[0], $bytes[1]);
              break;
            // get value for 16-bit wav
            case 2:
              if(ord($bytes[1]) & 128)
                $temp = 0;
              else
                $temp = 128;
              $temp = chr((ord($bytes[1]) & 127) + $temp);
              $data = floor(findValues($bytes[0], $temp) / 256);
              break;
          }
          
          // skip bytes for memory optimization
          fseek($handle, $ratio, SEEK_CUR);
          
          // draw this data point
          // data values can range between 0 and 255        
          $x1 = $x2 = number_format($data_point / $data_size * 100, 2);
          $y1 = number_format($data / 255 * 100, 2);
          $y2 = 100 - $y1;
          // don't bother plotting if it is a zero point
          if ($y1 != $y2)
            $svg .= "<line x1=\"{$x1}%\" y1=\"{$y1}%\" x2=\"{$x2}%\" y2=\"{$y2}%\" />";   
          
        } else {
          // skip this one due to lack of detail
          fseek($handle, $ratio + $byte, SEEK_CUR);
        }
      }
      
      $svg .= "</svg>\n";
      
      // close and cleanup
      fclose($handle);

      // delete the processed wav file
     // unlink($filename);
      
    }
    
    $svg .= "\n</svg>";
    
    //header("Content-Type: image/svg+xml");
    
    print $svg;

    
?>
<script type="text/javascript">
function moveLeft(px) {
	console.log(px);
	$('#marker').css('left', px + 'px');
}
</script>
<div id="marker" style="position:absolute;top:0px;height:50%;width:1px;border-left:1px solid #aaa;"></div>
<audio id="track" ontimeupdate="$('#marker').css('left',this.currentTime/this.duration*window.innerWidth + 'px')"><source src="test.wav" /></audio>

<div id="container_button">
	<div id="hole" onclick="document.getElementById('track').play();">
		<div id="button">
			<div id="triangle"></div>
			<div id="lighter_triangle"></div>
			<div id="darker_triangle"></div>
		</div>
	</div>
</div>
  </body></html>