<?php

/** * close all open xhtml tags at the end of the string

 * * @param string $html

 * @return string

 * @author Milian <mail@mili.de>
 
 * from http://www.kirupa.com/forum/showthread.php?343478-Close-all-open-HTML-tags

 */
 
class htmlchecker {



 public function closetags($html) {

  #put all opened tags into an array

  preg_match_all('#<([a-z]+)(?: .*)?(?<![/|/ ])>#iU', $html, $result);

  $openedtags = $result[1];   #put all closed tags into an array

  preg_match_all('#</([a-z]+)>#iU', $html, $result);

  $closedtags = $result[1];

  $len_opened = count($openedtags);

  # all tags are closed

  if (count($closedtags) == $len_opened) {

    return $html;

  }

  $openedtags = array_reverse($openedtags);

  # close tags

  for ($i=0; $i < $len_opened; $i++) {

    if (!in_array($openedtags[$i], $closedtags)){

      $html .= '</'.$openedtags[$i].'>';

    } else {

      unset($closedtags[array_search($openedtags[$i], $closedtags)]);    }

  }  return $html;} 
}  
?>